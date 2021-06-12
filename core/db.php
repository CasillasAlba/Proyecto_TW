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
                Fotografia LONGBLOB NOT NULL,
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

        // En el sistema trabajamos con el acrónimo por facilidad
        // pero para insertar debemos recoger el ID de la vacuna (PRIMARY KEY)
        //  Por lo cual obtenemos el objeto de la vacuna correspondiente y obtenemos su ID.
        $v = devolver_vacuna($datos['VacunaRef']);

        $prep = $db->prepare("INSERT INTO calendario(IDVacuna, Sexo, Meses_ini, Meses_fin, Tipo, Comentarios)
                VALUES (?, ?, ?, ?, ?, ?)");

        $prep->bind_param('isiiss', $idv, $sex, $mi, $mf, $tipo, $desc);
        
        // Establecer parámetros y ejecutar
        $idv = $v['ID'];
        $sex = $datos['Sexo'];
        $mi = $datos['MesIni'];
        $mf = $datos['MesFin'];
        $tipo = $datos['Tipo'];
        $desc = $datos['DescripCalend'];

        $prep->execute();
        
        // Cerramos la consulta preparada
        $prep->close();
    }

    // Realizamos una CONSULTA PREPARADA para insertar datos de tipo Vacunacion
    function insertar_vacunacion($datos, $dni_user, $id_calend, $fecha_actual){
        global $db;

        $prep = $db->prepare("INSERT INTO vacunacion(IDUsuario, IDCalendario, Fecha, Fabricante, Comentarios)
                VALUES (?, ?, ?, ?, ?)");

        $prep->bind_param('sisss', $idu, $idc, $fec, $fabric, $desc);
        
        // Establecer parámetros y ejecutar
        $idu = $dni_user;
        $idc = $id_calend;
        $fec = $fecha_actual; // Guardamos la fecha actual del registro
        $fabric = $datos['Fabricante'];
        $desc = $datos['DescripVacunacion'];

        $prep->execute();
        
        // Cerramos la consulta preparada
        $prep->close();
    }

    // Realizamos una CONSULTA PREPARADA para insertar datos de tipo Log
    function insertar_log($datos){
        global $db;

        $prep = $db->prepare("INSERT INTO log (Tipo, Fecha, Descripcion)
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

    // Función que devuelve la lista de usuarios
    function devolver_lista_usuarios(){
        global $db;
        $datos = [];

        $prep = $db->prepare("SELECT * FROM usuarios");

        if($prep->execute()){

            //Vinculamos variables a consultas
            $result = $prep->get_result();

            // Obtenemos los valores
            while($elem = $result->fetch_assoc()){
                array_push($datos, $elem);
            }
        }else{
            $datos = false; // Error en la consulta
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $datos;

    }

    // Funcion que devuelve la lista de usuarios inactivos
    function devolver_lista_peticiones(){
        global $db;
        $datos = [];

        $prep = $db->prepare("SELECT * FROM usuarios WHERE Estado='Inactivo'");

        if($prep->execute()){

            //Vinculamos variables a consultas
            $result = $prep->get_result();

            // Obtenemos los valores
            while($elem = $result->fetch_assoc()){
                array_push($datos, $elem);
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
                $datos['NombreVac'] = $nom;
                $datos['DescripVac'] = $desc;
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

    function devolver_vacuna_por_id($id){
        global $db;

        $prep = $db->prepare("SELECT * FROM vacunas WHERE ID=?");

        // El primer parametro es el tipo de datos que vamos a insertar, un caracter por cada tipo de dato.
        $prep->bind_param('i', $id);

        if($prep->execute()){
            //Vinculamos variables a consultas
            $prep->bind_result($id, $acro, $nom, $desc);

            // Obtenemos los valores
            if($prep->fetch()){
                $datos['ID'] = $id;
                $datos['Acronimo'] = $acro;
                $datos['NombreVac'] = $nom;
                $datos['DescripVac'] = $desc;
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

    // Función que devuelve un array con los acrónimos de las vacunas
    function devolver_acronimos_vacunas(){
        global $db;
        $datos = [];

        $prep = $db->prepare("SELECT Acronimo FROM vacunas");

        if($prep->execute()){
            //Vinculamos variables a consultas
            $result = $prep->get_result();

            // Obtenemos los valores
            while($elem = $result->fetch_assoc()){
                array_push($datos, $elem['Acronimo']);
            }
        }else{
            $datos = false; // Error en la consulta
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $datos;

    }

    // Función que devuelve la lista de vacunas
    function devolver_lista_vacunas(){
        global $db;
        $datos = [];

        $prep = $db->prepare("SELECT * FROM vacunas");

        if($prep->execute()){
            //Vinculamos variables a consultas
            $result = $prep->get_result();

            // Obtenemos los valores
            while($elem = $result->fetch_assoc()){
                array_push($datos, $elem);
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

    function devolver_calendario($sex, $meses, $id_us){
        global $db;
        $datos = [];

        // Devolvemos el nombre y el acronimo de las vacunas que están en el calendario.
        // Para ello indicamos que ambas tablas están relacionadas (calendario.IDVacuna = vacunas.ID)
        // donde el sexo sea el del usuario que va a vacunarse (más las que son para ambos sexos)
        // que la edad para ponerse la vacuna sea menor a su edad
        // y que el usuario no se la haya puesto

        $prep = $db->prepare("SELECT vacunas.Acronimo, calendario.Meses_ini, calendario.ID FROM vacunas, calendario
                WHERE calendario.IDVacuna = vacunas.ID 
                AND (calendario.Sexo = ? OR calendario.Sexo = 'Ambos')
                AND (calendario.Meses_ini <= ?)
                AND (calendario.ID NOT IN (SELECT vacunacion.IDCalendario FROM usuarios, vacunacion
                             WHERE vacunacion.IDUsuario = ?))
                ");

        $prep->bind_param('sis', $sex, $meses, $id_us);

        if($prep->execute()){
            //Vinculamos variables a consultas
            $result = $prep->get_result();
            $datos_temp = [];

            // Obtenemos los valores
            while($elem = $result->fetch_assoc()){
                array_push($datos_temp, $elem);
            }

            // Este for se realiza porque debemos de tener el cuenta el caso en el que tengamos
            // dos vacunas con el mismo acrónimo, pero con distinta fecha
            // P.ej: tienes 13 años y no te has puesto la dosis de la vacuna de VPH de los 12
            // años ni la dosis de entre los 13-18 años. Sin embargo, a la cartilla de vacunación
            // solo debería añadirse la dosis que deba ponerse antes, por lo cual nos quedamos
            // con la que tiene el mes de inicio menor.

            foreach($datos_temp as $t){
                if(count($datos) > 0){
                    $actualizado = false;
                    foreach($datos as $d){
                        if($t['Acronimo'] == $d['Acronimo']){
                            if($t['Meses_ini'] > $d['Meses_ini']){
                                $d = $t;
                                $actualizado = true;
                            }
                        }
                    }
                    
                    if($actualizado == false){
                        array_push($datos, $t);
                    }else{
                        $actualizado = false;
                    }
                }else{
                    array_push($datos, $t);
                }
            }
        }else{
            $datos = false; // Error en la consulta
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $datos;
    }

    // Función de devuelve el calendario completo
    function devolver_calendario_full() {
        global $db;
        $datos = [];

        $prep = $db->prepare("SELECT vacunas.Acronimo, vacunas.Nombre, calendario.Sexo, calendario.Meses_ini, calendario.Meses_fin, calendario.Tipo, calendario.comentarios FROM vacunas, calendario WHERE calendario.IDVacuna = vacunas.ID");

        if($prep->execute()){
            //Vinculamos variables a consultas
            $result = $prep->get_result();

            // Obtenemos los valores
            while($elem = $result->fetch_assoc()){
                array_push($datos, $elem);
            }
        }else{
            $datos = false; // Error en la consulta
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $datos;
    }

    // Función que devuelve la lista de vacunación de un usuario

    function devolver_lista_vacunacion($dni){
        global $db;
        $datos = [];

        $prep = $db->prepare("SELECT vacunacion.IDCalendario, vacunacion.Fecha, vacunacion.Fabricante, vacunacion.Comentarios, vacunacion.IDUsuario,
                                vacunas.Acronimo, vacunas.Nombre, vacunas.Descripcion FROM vacunacion, calendario, vacunas 
                                WHERE vacunacion.IDUsuario = ?
                                AND (vacunacion.IDCalendario = calendario.ID)
                                AND(calendario.IDVacuna = vacunas.ID)");

        $prep->bind_param('s', $dni);

        if($prep->execute()){
            //Vinculamos variables a consultas
            $result = $prep->get_result();

            // Obtenemos los valores
            while($elem = $result->fetch_assoc()){
                array_push($datos, $elem);
            }
        }else{
            $datos = false; // Error en la consulta
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $datos;

    }

    // Función que devuelve la lista de logs

    function devolver_lista_logs(){
        global $db;
        $datos = [];

        $prep = $db->prepare("SELECT * FROM log ORDER BY Fecha DESC");
    
        if($prep->execute()){
            //Vinculamos variables a consultas
            $result = $prep->get_result();

            // Obtenemos los valores
            while($elem = $result->fetch_assoc()){
                array_push($datos, $elem);
            }
        }else{
            $datos = false; // Error en la consulta
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $datos;

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

    function modificar_vacuna($datos){
        global $db;

        $prep = $db->prepare("UPDATE vacunas SET Acronimo=?, Nombre=?, Descripcion=? WHERE Acronimo=?");
  
        // Establecer parámetros y ejecutar
        $acro = $datos['Acronimo'];
        $nom = $datos['NombreVac'];
        $desc = $datos['DescripVac'];
        $acro_cond = $datos['Acronimo'];

   
        // El primer parametro es el tipo de datos que vamos a insertar, un caracter por cada tipo de dato.
        $prep->bind_param('ssss', $acro, $nom, $desc, $acro_cond);
     
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


function eliminar_vacunacion($dni_user, $id_calend){

    global $db;

    $prep = $db->prepare("DELETE FROM vacunacion WHERE IDUsuario=? AND IDCalendario=?");
    $prep->bind_param('si', $dni_user, $id_calend);
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


function eliminar_usuario($dni){
    global $db;

    // Antes de borrar un usuario, debemos asegurarnos de que no existe
    // ningun objeto cuya creación dependa de ese usuario

    $prep_vacunacion = $db->prepare("DELETE FROM vacunacion WHERE IDUsuario = ?");

    $prep_vacunacion->bind_param('s', $dni);
    $prep_vacunacion->execute();

    
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


function eliminar_calendario($calend){
    global $db;

    $prep_vacunacion = $db->prepare("DELETE FROM vacunacion WHERE IDCalendario = ?");

    $prep_vacunacion->bind_param('i', $calend);
    $prep_vacunacion->execute();

    
    $prep = $db->prepare("DELETE FROM calendario WHERE ID=?");
    $prep->bind_param('i', $calend);
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

function eliminar_vacuna($vac){
    global $db;

    /*
        Para borrar una vacuna primero hay que borrar todos los elementos de calendario asociado, que requiere borrar todas la vacunaciones
        La Tabla que cuya existencia depende de más tablas es Vacunacion, ya que esta existe gracias a Calendario, pero Calendario existe gracias a Vacuna
        Se borran todas las vacunaciones que tengan un IDCalendario = Al ID asociado a cada IDVacuna
        Si por ejemplo la Vacuna VPH tiene 3 elementos en Calendario que dependen de ella, habrá 3 IDs de Calendario distinto con IDVacuna asociada
        El segundo select devuelve TODOS esos IDs, por eso la condición WHERE es IN, ya que está diciendo: Elimina todas la VACUNACIONES 
        cuyo ID sea ALGUNO de los que devuelve el anterior SELECT
    */

    $prep_vacunacion = $db->prepare("DELETE FROM vacunacion WHERE IDCalendario IN (SELECT ID FROM calendario WHERE IDVacuna = ?)");

    $prep_vacunacion->bind_param('i', $vac);
    $prep_vacunacion->execute();

    
    $prep_calendario = $db->prepare("DELETE FROM calendario WHERE IDVacuna=?");
    $prep_calendario->bind_param('i', $vac);
    $prep_calendario->execute();


    $prep = $db->prepare("DELETE FROM vacunas WHERE ID = ?");
    $prep->bind_param('i', $vac);
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


function eliminar_log($id){
    global $db;

    $prep = $db->prepare("DELETE FROM log WHERE ID=?");
    $prep->bind_param('s', $id);
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

    // Calcular la edad a partir de la fecha de nacimiento de un usuario
    function calcular_edad($fecha){
        $nacimiento = new DateTime($fecha);
        $ahora = new DateTime(date("Y-m-d"));

        $diferencia = $ahora->diff($nacimiento);

        $edad = $diferencia->format("%y");

        $edad_user = array(
            'mes' => false,
            'valor' => $edad
        );

        // Si la persona tiene menos de un año, devolvemos los meses de vida
        if($edad == 0){
            $edad = $diferencia->format("%m");

            $edad_user['mes'] = true;
            $edad_user['valor'] = $edad;
        }

        return $edad_user;
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                           Funciones de estadísticas                                               //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    function total_usuarios() {
        global $db;

        $prep = $db->prepare("SELECT COUNT(DNI) FROM usuarios");

        if($prep->execute()){
            //Vinculamos variables a consultas
            $total = $prep->get_result()->fetch_row()[0];

        }else{
            $total = 0; // Error en la consulta
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $total;
    }

    function total_vacunas_30dias() {
        global $db;
        $date = date_create(date('Y-m-d'));
        date_sub($date,date_interval_create_from_date_string("30 days"));
        $date = date_format($date,"Y/m/d");

        $prep = $db->prepare("SELECT COUNT(ID) FROM vacunacion WHERE fecha >= ?");
        $prep->bind_param('s', $date);

        if($prep->execute()){
            //Vinculamos variables a consultas
            $total = $prep->get_result()->fetch_row()[0];

        }else{
            $total = 0; // Error en la consulta
        }

        // Cerramos la consulta preparada
        $prep->close();

        return $total;
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

    //$result = devolver_acronimos_vacunas();
    //foreach ($result as $v){
        //echo $v;
    //}
?>