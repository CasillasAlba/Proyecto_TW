<?php

    // Realizamos conexión a base de datos
    require_once('db_credenciales.php');
    $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWD, DB_DATABASE);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                  FUNCIÓN QUE CREA LAS TABLAS DEL SISTEMA                                   //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    function crear_tablas(){
        global $db;
        // Tabla de usuarios
        mysqli_query($db, "CREATE TABLE usuarios(
                DNI VARCHAR(10) PRIMARY KEY,
                Nombre VARCHAR(20) NOT NULL,
                Apellidos VARCHAR(50) NOT NULL,
                Telefono INT(9) NOT NULL,
                Email VARCHAR(30) NOT NULL,
                FechaNac DATE NOT NULL,
                Sexo VARCHAR(10) NOT NULL,
                Fotografia BLOB NOT NULL,
                Clave VARCHAR(65) NOT NULL,
                Estado VARCHAR(10) NOT NULL, 
                Rol VARCHAR(20) NOT NULL
            )
        ");

        // Tabla de vacunas
        mysqli_query($db, "CREATE TABLE vacunas(
                ID INT PRIMARY KEY AUTO_INCREMENT,
                Acronimo VARCHAR(15) NOT NULL,
                Nombre VARCHAR(100) NOT NULL,
                Descripcion VARCHAR(255) NOT NULL
            )
        ");

        // Tabla de calendario de vacunacion
        mysqli_query($db, "CREATE TABLE calendario(
                ID INT PRIMARY KEY AUTO_INCREMENT,
                IDVacuna INT NOT NULL,
                Sexo VARCHAR(10) NOT NULL,
                Meses_ini SMALLINT(5) NOT NULL,
                Meses_fin SMALLINT(5) NOT NULL,
                Tipo CHAR(1) NOT NULL,
                Comentarios VARCHAR(255) NOT NULL,
                CONSTRAINT fk_vacuna FOREIGN KEY (IDVacuna) REFERENCES vacunas(ID)
            )
        ");

        // Tabla de vacunas puestas (vacunacion)
        mysqli_query($db, "CREATE TABLE vacunacion(
                ID INT PRIMARY KEY AUTO_INCREMENT,
                IDUsuario VARCHAR(10) NOT NULL,
                IDCalendario INT NOT NULL,
                Fecha DATE NOT NULL, 
                Fabricante VARCHAR(100) NOT NULL,
                Comentarios VARCHAR(255) NOT NULL,
                CONSTRAINT fk_usuario FOREIGN KEY (IDUsuario) REFERENCES usuarios(DNI),
                CONSTRAINT fk_calendario FOREIGN KEY (IDCalendario) REFERENCES calendario(ID)
            )
        ");

        // Tabla de cuaderno de bitácrona (log)
        mysqli_query($db, "CREATE TABLE log(
                ID INT PRIMARY KEY AUTO_INCREMENT,
                Tipo VARCHAR(50),
                Fecha DATETIME NOT NULL, 
                Descripcion VARCHAR(255) NOT NULL
            )
        ");
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                 FUNCIONES DE INSERTAR DATOS EN LAS TABLAS                                  //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Realizamos una CONSULTA PREPARADA para insertar usuarios
    function insertar_usuario($datos){
        global $db;

        $prep = $db->prepare("INSERT INTO usuarios(DNI, Nombre, Apellidos, Telefono, Email, FechaNac, Sexo, Fotografia, Clave, Estado, Rol)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // El primer parametro es el tipo de datos que vamos a insertar, un caracter por cada tipo de dato.
        $prep->bind_param('sssisssbsss', $dni, $nom, $apell, $tel, $ema, $nac, $sex, $foto, $clv, $estado, $rol);
        
        // Establecer parámetros y ejecutar
        $dni = $datos['DNI'];
        $nom = $datos['Nombre'];
        $apell = $datos['Apellidos'];
        $tel = $datos['Telefono'];
        $ema = $datos['Email'];
        $nac = $datos['FechaNac'];
        $sex = $datos['Sexo'];
        $clv = $datos['Clave'];
        $estado = $datos['Estado'];
        $rol = $datos['Rol'];

        $prep->send_long_data(7, $datos['Foto']);
        $prep->execute();

        // Cerramos la consulta preparada
        $prep->close();
    }

    // Realizamos una CONSULTA PREPARADA para insertar vacunas
    function insertar_vacuna($datos){
        global $db;
        echo "HE ENTRADO?????";
        $prep = $db->prepare("INSERT INTO vacunas(Acronimo, Nombre, Descripcion) VALUES (?, ?, ?)");

        $prep->bind_param('sss', $acro, $nom, $desc);
        
        // Establecer parámetros y ejecutar
        $acro = $datos['Acronimo'];
        $nom = $datos['NombreVac'];
        $desc = $datos['DescripVac'];

        $prep->execute();

        // Cerramos la consulta preparada
        $prep->close();

    }

    
    // Realizamos una CONSULTA PREPARADA para insertar un calendario de vacunacion
    function insertar_calendario($datos){
        global $db;

        $prep = $db->prepare("INSERT INTO calendario(IDVacuna, Sexo, Meses_ini, Meses_fin, Tipo, Comentarios)
                VALUES (?, ?, ?, ?, ?, ?)");

        $prep->bind_param('isiiss', $idv, $sex, $mi, $mf, $tipo, $desc);
        
        // Establecer parámetros y ejecutar
        $idv = $datos['idVacuna'];
        $sex = $datos['Sexo'];
        $mi = $datos['MesIni'];
        $mf = $datos['MesFin'];
        $tipo = $datos['Tipo'];
        $desc = $datos['Descripcion'];

        $prep->execute();
        
        // Cerramos la consulta preparada
        $prep->close();
    }

    // Realizamos una CONSULTA PREPARADA para insertar datos de tipo Vacunacion
    function insertar_vacunacion($datos){
        global $db;

        $prep = $db->prepare("INSERT INTO calendario(IDUsuario, IDCalendario, Fecha, Fabricante, Comentarios)
                VALUES (?, ?, ?, ?, ?)");

        $prep->bind_param('sisss', $idu, $idc, $fec, $fabric, $desc);
        
        // Establecer parámetros y ejecutar
        $idu = $datos['idUsuario'];
        $idc = $datos['idCalendario'];
        $fec = $datos['Fecha'];
        $fabric = $datos['Fabricante'];
        $desc = $datos['Descripcion'];

        $prep->execute();
        
        // Cerramos la consulta preparada
        $prep->close();
    }

    // Realizamos una CONSULTA PREPARADA para insertar datos de tipo Log
    function insertar_log($datos){
        global $db;

        $prep = $db->prepare("INSERT INTO calendario(Tipo, Fecha, Descripcion)
                VALUES (?, ?, ?)");

        $prep->bind_param('sss', $tipo, $fec, $desc);
        
        // Establecer parámetros y ejecutar
        $tipo = $datos['Tipo'];
        $fec = $datos['Fecha'];
        $desc = $datos['Descripcion'];

        $prep->execute();
        
        // Cerramos la consulta preparada
        $prep->close();
    }


////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                               FUNCIONES QUE DEVUELVEN UN ELEMENTO DE LA TABLA                              //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    function devolver_usuario($dni){
        global $db;

        $prep = $db->prepare("SELECT * FROM usuarios WHERE DNI=?");

        // El primer parametro es el tipo de datos que vamos a insertar, un caracter por cada tipo de dato. 
        $prep->bind_param('s', $dni);

        if($prep->execute()){
            $prep->store_result(); // Recuperar la fotografía

            //Vinculamos variables a consultas
            $prep->bind_result($dni, $nom, $apell, $tel, $ema, $nac, $sex, $foto, $clv, $estado, $rol);

            // Obtenemos los valores
            if($prep->fetch()){
                $datos['DNI'] = $dni;
                $datos['Nombre'] = $nom;
                $datos['Apellidos'] = $apell;
                $datos['Telefono'] = $tel;
                $datos['Email'] = $ema;
                $datos['FechaNac'] = $nac;
                $datos['Sexo'] = $sex;
                $datos['Foto'] = $foto;
                $datos['Clave'] = $clv;
                $datos['Estado'] = $estado;
                $datos['Rol'] = $rol;
            }else{
                $datos = false; // No hay resultados
            }
        }else{
            $datos = false; // Error en la consulta
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $datos;
    }

    function usuario_ya_registrado($dni){
        global $db;

        $prep = $db->prepare("SELECT * FROM usuarios WHERE DNI=?");

        // El primer parametro es el tipo de datos que vamos a insertar, un caracter por cada tipo de dato. 
        $prep->bind_param('s', $dni);

        if($prep->execute()){
            //Vinculamos variables a consultas
            $prep->bind_result($dni, $nom, $apell, $tel, $ema, $nac, $sex, $foto, $clv, $estado, $rol);

            // Obtenemos los valores
            if($prep->fetch()){
                $existe = true;
            }else{
                $existe = false; // No hay resultados
            }
        }else{
            $existe = false; // Error en la consulta
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $existe;
    }


    function devolver_vacuna($id){
        global $db;

        $prep = $db->prepare("SELECT * FROM vacunas WHERE Acronimo=?");

        // El primer parametro es el tipo de datos que vamos a insertar, un caracter por cada tipo de dato.
        $prep->bind_param('s', $id);

        if($prep->execute()){
            //Vinculamos variables a consultas
            $prep->bind_result($id, $acro, $nom, $desc);

            // Obtenemos los valores
            if($prep->fetch()){
                $datos['ID'] = $id;
                $datos['Acronimo'] = $acro;
                $datos['Nombre'] = $nom;
                $datos['Descripcion'] = $desc;
            }else{
                $datos = false; // No hay resultados
            }
        }else{
            $datos = false; // Error en la consulta
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $datos;
    }

    function vacuna_ya_registrada($acro){
        global $db;

        $prep = $db->prepare("SELECT * FROM vacunas WHERE Acronimo=?");

        // El primer parametro es el tipo de datos que vamos a insertar, un caracter por cada tipo de dato. 
        $prep->bind_param('s', $acro);

        if($prep->execute()){
            //Vinculamos variables a consultas
            $prep->bind_result($id, $acro, $nom, $desc);

            // Obtenemos los valores
            if($prep->fetch()){
                $existe = true;
            }else{
                $existe = false; // No hay resultados
            }
        }else{
            $existe = false; // Error en la consulta
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $existe;
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                               FUNCIONES QUE MODIFICAN UN ELEMENTO DE LA TABLA                              //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    function modificar_usuario($datos){
        global $db;

        $prep = $db->prepare("UPDATE usuarios SET Nombre=?, Apellidos=?, Telefono=?, Email=?, FechaNac=?, Sexo=?, Fotografia=?, Clave=?, Estado=?, Rol=? WHERE DNI=?");
  
        // Establecer parámetros y ejecutar
        $nom = $datos['Nombre'];
        $apell = $datos['Apellidos'];
        $tel = $datos['Telefono'];
        $ema = $datos['Email'];
        $nac = $datos['FechaNac'];
        $sex = $datos['Sexo'];
        $clv = $datos['Clave'];
        $estado = $datos['Estado'];
        $rol = $datos['Rol'];
        $dni = $datos['DNI'];
   
        // El primer parametro es el tipo de datos que vamos a insertar, un caracter por cada tipo de dato.
        $prep->bind_param('ssisssbssss', $nom, $apell, $tel, $ema, $nac, $sex, $foto, $clv, $estado, $rol,$dni);

        $prep->send_long_data(6, $datos['Foto']);
        
        if($prep->execute()){
            $resultado_ejecucion = true; // El Update se ha reaLizado correctamente
        }else{
            $resultado_ejecucion = false;
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $resultado_ejecucion;
    }


////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                               FUNCIONES QUE ELIMNAN UN ELEMENTO DE LA TABLA                                //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function eliminar_usuario($dni){
    global $db;

    $prep = $db->prepare("DELETE FROM usuarios WHERE DNI=?");
    $prep->bind_param('s', $dni);
    $prep->execute();

    if($prep->affected_rows == 1){
        $resultado_ejecucion = true; // El Delete se ha reaLizado correctamente
    }else{
        $resultado_ejecucion = false;
    }
    
    // Cerramos la consulta preparada
    $prep->close();

    return $resultado_ejecucion;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                           FUNCIONES AUXILIARES                                             //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Funcion que cifra una clave
    function cifrar_claves($clave){
        $opciones = [
            'cost'=> 12,
        ];

        $hash_clv = password_hash($clave, PASSWORD_BCRYPT, $opciones);

        return $hash_clv;
    }

    // Funcion que comprueba si una clave ha sido cambiada por otra
    function comparar_claves($clave_formulario, $clave_antigua){
        if($clave_formulario != substr($clave_antigua,0,6)){
            $hash_clv = cifrar_claves($clave_formulario);
        }else{
            $hash_clv = $clave_antigua;
        }

        return $hash_clv;     
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                           'MAIN' DEL CODIGO                                                //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////



    //crear_tablas($db);
    //$tabla_usuarios = mysqli_query($db, "DROP TABLE usuarios");

    //Insertamos usuarios de prueba:
    // Primero creamos arrays con los datos de los usuarios de prueva que vamos a crear
    /*
    $datos_admin = [
        'DNI' => '76738108B',
        'Nombre' => 'Alba',
        'Apellidos' => 'Casillas Rodríguez',
        'Telefono' => '633776632',
        'Email' => 'alba@correo.es',
        'FechaNac' => '1999-12-30',
        'Sexo' => 'Femenino',
        'Foto' => 00000,
        'Clave' => 'D3UX3XMACH1NA',
        'Estado' => 'Activo',
        'Rol' => 'Admin',
    ];
    
    $c = $datos_admin['Clave'];
    // Las claves deben almacenarse cifradas en la base de datos
    $datos_admin['Clave'] = cifrar_claves($c);

    insertar_usuario($datos_admin);
    */
    //$datos_user = devolver_usuario($db, $datos_admin['DNI'] );
    //foreach($datos_user as &$valor){
        //echo $valor;
    //}

    //modificar_usuario($db, $datos_admin);
    //eliminar_usuario($db, $datos_admin['DNI']);
?>