<?php

    require_once './vendor/autoload.php';
    require_once('./core/db_credenciales.php');
    require_once('./controller/login.php');
    require_once('./core/db.php');
    require_once('./core/db_backup.php');
    require_once('./controller/procesarFiltrado.php');

    session_start();

    /*
        if(!$db){
            //die("No se ha podido establecer una conexión:" .mysqli_connect_error());
        }
    */

    $loader = new \Twig\Loader\FilesystemLoader('./view');
    $twig = new \Twig\Environment($loader);

    ///////////////////////////////// FUNCIONES DEL PROGRAMA


//////////////////////////////////////////////////////////////////////////////////
/*
  Funciones para mostrar mensajes de error en la aplicación PHP.
  Funciones obtenidas de las explicaciones de: Javier Martínez Baena
*/

    function _msgErrorR($msg) {
      if (is_array($msg))
        foreach ($msg as $v)
          _msgErrorR($v);
      else
        echo "<p>$msg</p>";
    }
    
    function msgError($msg, $tipo='msgerror') {
      echo "<div class='$tipo'>";
      _msgErrorR($msg);
      echo '</div>';
    }
    
    function msgCount($msg) {
      if (is_array($msg))
        if (count($msg)==0)
          return 0;
        else
          return msgCount($msg[0])+msgCount(array_slice($msg,1));
      else if (!is_bool($msg))
        return 1;
      else
        return 0;
    }

//////////////////////////////////////////////////////////////////////////////////


    // Funcion para acceder los assets (css, imagenes...)
    $twig->addFunction(new \Twig\TwigFunction('asset', function ($asset) {
        return sprintf('./view/%s', ltrim($asset, '/'));
    }));

    // Funcion para poder acceder a archivos php
    $twig->addFunction(new \Twig\TwigFunction('funciones', function ($funciones) {
        return sprintf('./controller/%s', ltrim($funciones, '/'));
    }));


   
    // IF ppara cambiar la acción de los procesar.php
    if(isset($_SESSION['accionPulsada'])){

        $accion = $_SESSION['accionPulsada'];

    }else if(isset($_SESSION['accionPulsadaVac'])){

        $accion = $_SESSION['accionPulsadaVac'];

    }else if(isset($_SESSION['accionPulsadaCalend'])){

        $accion = $_SESSION['accionPulsadaCalend'];
    }

    //Global variables
    $n_usuarios = total_usuarios();
    $n_vacunas = total_vacunas_30dias();

    if(isset($_POST['boton-login-visitante'])){
        // ENTRAMOS EN ESTE IF CUANDO UN VISITANTE INTENTE LOGUEARSE
        // Obtenemos los valores introducidos por el usuario
        $dni_login = $_POST['usr'];
        $clv_login = $_POST['psw'];

        $datos_logueado = procesar_login($dni_login, $clv_login);

        // El login no ha sido correcto
        if($datos_logueado == False){
            // Se ha iniciado sesión correctamente
            $datos_log = array(
                'Tipo' => "tipo_log_error",
                'Fecha' => date("Y-m-d H:i:s"),
                'Descripcion' => "IDENTIFICACIÓN INCORRECTA"
            ); 

            insertar_log($datos_log);

            $motivo = "DNI O CONTRASEÑA INCORRECTA :(";
            $_SESSION['exito'] = False;
            $exito = $_SESSION['exito'];
            echo $twig->render('errores.twig', compact('exito' ,'motivo','n_usuarios', 'n_vacunas'));


        }else if($datos_logueado == "Inactivo"){

            $motivo = "NO PUEDE INICIAR SESIÓN HASTA QUE LE DEN DE ALTA EN EL SISTEMA";
            echo $twig->render('errores.twig', compact('motivo', 'n_usuarios', 'n_vacunas'));

        }else{
            $_SESSION['exito'] = true;

            // Se ha iniciado sesión correctamente
            $datos_log = array(
                'Tipo' => "tipo_login",
                'Fecha' => date("Y-m-d H:i:s"),
                'Descripcion' => "USUARIO LOGUEADO"
            ); 

            insertar_log($datos_log);
            // Esta será la variable con la que se guarde la sesión
            $_SESSION['login'] = True;

            // Datos importantes del usuario logueado
            $_SESSION['row_datos'] = $datos_logueado;
            $us_user = $datos_logueado['DNI'];
            $nombre_user = $datos_logueado['Nombre'];
            $rol_user = $datos_logueado['Rol'];
            $image_user = $datos_logueado['Foto'];
            $sexo_user = $datos_logueado['Sexo'];
            
            $calendario = devolver_calendario_full();
            echo $twig->render('calendario_logueado.twig', compact('us_user', 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'calendario', 'n_usuarios', 'n_vacunas'));
        }
        

    }else if(isset($_POST['registrar-visitante'])){

        // Para cuando un visitante le de al boton de registar
        // Sus datos están inicialmente vacíos, no tiene un valor dni en la BBDD y su rol es Visitante
        $row = [
            'DNI' => '',
            'Nombre' => '',
            'Apellidos' => '',
            'Telefono' => '',
            'Email' => '',
            'FechaNac' => '',
            'Sexo' => '',
            'Foto' => '',
            'Clave' => '',
            'Estado' => '',
            'Rol' => '',
        ];

        $us_user = '';
        $accion = 'registrar';
        $rol_user = 'Visitante';
        $_SESSION['rol_user_visitante'] = $rol_user;
    
        echo $twig->render('formulario_usuario.twig', compact('row', 'us_user', 'rol_user', 'accion', 'n_usuarios', 'n_vacunas'));

    }else if(isset($_POST['log-out'])){
        $datos_log = array(
            'Tipo' => "tipo_logout",
            'Fecha' => date("Y-m-d H:i:s"),
            'Descripcion' => "USUARIO DESLOGUEADO"
        ); 

        insertar_log($datos_log);
        // Llamamos a la función de cerrar sesión ubicada en login.php
        procesar_logout();

        $calendario = devolver_calendario_full();
        echo $twig->render('inicio_visitante.twig', compact('calendario', 'n_usuarios', 'n_vacunas'));

    }else if(isset($_SESSION['login'])){

        // El usuario tiene la sesión iniciada, si vuelve para atrás, se mantiene.
        $us_user = $_SESSION['row_datos']['DNI'];
        $rol_user = $_SESSION['row_datos']['Rol'];
        $nombre_user = $_SESSION['row_datos']['Nombre'];
        $image_user = $_SESSION['row_datos']['Foto'];
        $sexo_user = $_SESSION['row_datos']['Sexo'];

        // El usuario es ADMIN y puede registrar otros usuarios
        // o eres un visitante que quiere registrarse
        if(isset($_POST['regist']) or (isset($_SESSION['accionPulsada']) and $_SESSION['accionPulsada'] == "registrar")){

            $accion = "registrar";

            // Si al registrar hay errores, se carga formulario sticky con los errores escritos
            if(isset($_SESSION['row_errores_temp'])){
                $row = $_SESSION['row_datos_temp'];
                $errores = $_SESSION['row_errores_temp'];
                echo $twig->render('formulario_usuario.twig', compact('row', 'errores', 'us_user', 'rol_user', 'accion', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            }else{
                echo $twig->render('formulario_usuario.twig', compact('us_user', 'rol_user', 'accion' , 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            }
            
        }else if((isset($_POST['editar-me']) or (isset($_POST['idEditarUser']) or (isset($_SESSION['accionPulsada']) and $_SESSION['accionPulsada'] == "editar")))){

    
            if(isset($_POST['idEditarUser'])){
                $_SESSION['dni_antiguo'] = $_POST['idEditarUser']; //DNI DE LA PERSONA A LA QUE VAMOS A EDITAR
                $_SESSION['user_a_editar'] = devolver_usuario($_SESSION['dni_antiguo']);
                $row = $_SESSION['user_a_editar'];
                $_SESSION['foto_antigua'] = $row['Foto'];
                $accion = "editar";
                
                echo $twig->render('formulario_usuario.twig', compact('row', 'us_user', 'rol_user' , 'accion' , 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            
            }else if(isset($_POST['editar-me'])){ // TE EDITAS A TI MISMO
                $accion = "editar";
                // Cargamos los valores del propio usuario para cargar SUS datos
                $row = $_SESSION['row_datos'];
                // Editando debemos de controlar el DNI antinguo, para en validación
                // poder comparar si es un usuario ya existente en la BBDD o no.
                $_SESSION['dni_antiguo'] = $_SESSION['row_datos']['DNI'];
                $_SESSION['foto_antigua'] = $_SESSION['row_datos']['Foto'];
                $_SESSION['clave_antigua'] = $row['Clave'];

                // Necesitamos este if para que si el admin se edita a si mismo, que su rol no se vea afectado
                // ya que Admin no es un rol que se pueda elegir ni cambiar en la plataforma
                if(isset($_POST['editar-me']) and $_SESSION['row_datos']['Rol'] == "Admin"){
                    $_SESSION['rol_admin'] = "Admin";
                }

                echo $twig->render('formulario_usuario.twig', compact('row', 'us_user', 'rol_user', 'accion' , 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

            }

            // Si al registrar hay errores, se carga formulario sticky con los errores escritos
            if(isset($_SESSION['row_errores_temp'])){

                $row = $_SESSION['row_datos_temp'];
                $errores = $_SESSION['row_errores_temp'];

                echo $twig->render('formulario_usuario.twig', compact('row', 'errores', 'us_user', 'rol_user', 'accion' , 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            }

        }else if(isset($_POST['idBorrarUser'])){

            $accion = "borrar";
            $row = devolver_usuario($_POST['idBorrarUser']);

            echo $twig->render('formulario_usuario.twig', compact('row', 'us_user', 'accion' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

        }else if(isset($_SESSION['accionPulsada']) and $_SESSION['accionPulsada'] == "confirmar"){

            // Cargamos el formulario de confirmación
            $row = $_SESSION['row_datos_temp']; 
            echo $twig->render('formulario_usuario.twig', compact('row', 'us_user', 'rol_user', 'accion' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

        }else if(isset($_POST['regist-vac']) or (isset($_SESSION['accionPulsadaVac']) and $_SESSION['accionPulsadaVac'] == "registrar" )){

            $accion = "registrar"; // Creamos accion para la 1a vez
    
            // Si al registrar hay errores, se carga formulario sticky con los errores escritos
            if(isset($_SESSION['row_errores_temp'])){
                $vac = $_SESSION['row_datos_temp'];
                $erroresVac = $_SESSION['row_errores_temp'];
                echo $twig->render('formulario_vacuna.twig', compact('vac', 'erroresVac', 'us_user', 'accion' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            }else{
                echo $twig->render('formulario_vacuna.twig', compact('us_user', 'accion', 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            }
    
        }else if(isset($_SESSION['accionPulsadaVac']) and $_SESSION['accionPulsadaVac'] == "confirmar"){

            // Cargamos el formulario de confirmación
            $vac = $_SESSION['row_datos_temp']; 
            echo $twig->render('formulario_vacuna.twig', compact('vac', 'us_user', 'accion' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            
        }else if(isset($_POST['aniadir-vac-recomendada']) or (isset($_SESSION['accionPulsadaCalend']) and $_SESSION['accionPulsadaCalend'] == "registrar" )){

            $accion = "registrar"; // Creamos accion para la 1a vez

            // Obtenemos la lista de acronimos de vacunas disponibles a las que podemos hacerle referencia
            $vacunas = devolver_acronimos_vacunas();
            $vacunaPorDefecto = $vacunas[0];
            unset($vacunas[0]);
            // Si al registrar hay errores, se carga formulario sticky con los errores escritos
            if(isset($_SESSION['row_errores_temp'])){
                $calend = $_SESSION['row_datos_temp'];
                $erroresCalend = $_SESSION['row_errores_temp'];
                echo $twig->render('formulario_calendario.twig', compact('calend', 'erroresCalend', 'us_user', 'accion', 'vacunaPorDefecto', 'vacunas' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            }else{
                echo $twig->render('formulario_calendario.twig', compact('us_user', 'accion', 'vacunaPorDefecto', 'vacunas' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            }
            
        }else if(isset($_POST['eliminar-vacuna-recomendada'])){

            $accion = "borrar";

            $calend = devolver_calendario_by_id($_POST['eliminar-vacuna-recomendada-id'], $_POST['eliminar-vacuna-recomendada-acronimo']);
            
            $_SESSION['calend_temp_borrar'] = $calend;

            echo $twig->render('formulario_calendario.twig', compact('calend', 'us_user', 'accion' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            
        }else if(isset($_SESSION['accionPulsadaCalend']) and $_SESSION['accionPulsadaCalend'] == "confirmar"){

            // Cargamos el formulario de confirmación
            $calend = $_SESSION['row_datos_temp']; 
            echo $twig->render('formulario_calendario.twig', compact('calend', 'us_user', 'accion' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

        }else if(isset($_POST['listado_vac'])){

            $vacunas = devolver_lista_vacunas();
            echo $twig->render('listado_vacunas.twig', compact('rol_user', 'image_user', 'vacunas', 'nombre_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

        }else if(isset($_POST['listado_user'])){

            $usuarios = devolver_lista_usuarios();
            echo $twig->render('listado_usuarios.twig', compact('rol_user', 'image_user', 'usuarios' , 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            
        }else if(isset($_POST['listado_log'])){

            $datos_log = devolver_lista_logs();
            $n_peticiones = count($datos_log);
            echo $twig->render('logs_sistema.twig', compact('rol_user', 'image_user', 'n_peticiones', 'datos_log' , 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

        }else if(isset($_POST['idEditarVac']) or (isset($_SESSION['accionPulsadaVac']) and $_SESSION['accionPulsadaVac'] == "editar" )){
            
            // PARA LA EDICION DE VACUNAS

            if(isset($_POST['idEditarVac'])){

                $_SESSION['acro_antigua'] = $_POST['idEditarVac']; //Por si se modifica el acronimo
                $_SESSION['vacuna_a_editar'] = devolver_vacuna($_SESSION['acro_antigua']);
                $vac = $_SESSION['vacuna_a_editar'];
                $accion = "editar";
                echo $twig->render('formulario_vacuna.twig', compact('vac', 'us_user', 'accion' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

            }else{
                // Si el botón no existe, vienes de un fallo
                $vac = $_SESSION['row_datos_temp'];
                $erroresVac = $_SESSION['row_errores_temp'];
                echo $twig->render('formulario_vacuna.twig', compact('vac', 'erroresVac', 'us_user', 'accion' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            }

        }else if(isset($_POST['idBorrarVac'])){
            $accion = "borrar";
            $vac = devolver_vacuna_por_id($_POST['idBorrarVac']);

            echo $twig->render('formulario_vacuna.twig', compact('vac', 'us_user', 'accion' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
            
        }else if(isset($_POST['peticiones'])){
            $peticiones = devolver_lista_peticiones();
            $n_peticiones = count($peticiones);
            echo $twig->render('listado_peticiones.twig', compact( 'peticiones', 'n_peticiones' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

        }else if(isset($_POST['idProcesarPeticion'])){

            $accion = "activar";
            $dni_visitante = $_POST['idProcesarPeticion'];
            $row = devolver_usuario($dni_visitante);
            $_SESSION['datos_visitante'] = $row;

            echo $twig->render('formulario_usuario.twig', compact('row', 'us_user', 'rol_user', 'accion' , 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

        }else if(isset($_SESSION['informe']) and $_SESSION['informe'] == "activar-informar"){

            unset($_SESSION['informe']);
            
            $motivo = "Se ha dado de alta correctamente al usuario.";
            echo $twig->render('errores.twig', compact('motivo', 'n_usuarios', 'n_vacunas', 'us_user', 'rol_user', 'nombre_user', 'image_user', 'sexo_user'));


        }else if(isset($_SESSION['informe']) and $_SESSION['informe'] == "error-informar"){
        
        
            $informar = $_SESSION['informe'];
            unset($_SESSION['informe']);

            echo $twig->render('errores.twig', compact('informar', 'n_usuarios', 'n_vacunas', 'us_user', 'rol_user', 'nombre_user', 'image_user', 'sexo_user'));
        
        
        }else if(isset($_SESSION['informe']) and $_SESSION['informe'] == "borrar-informar"){

            $row = $_SESSION['user_informe'];
            unset($_SESSION['informe']);

            $accion = "borrar";

            echo $twig->render('formulario_usuario.twig', compact('row', 'us_user', 'rol_user', 'accion', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
        
        
        }else if(isset($_POST['cartilla'])){

            // Para que un usuario pueda ver su cartilla de vacunacion
            $lista_vacunacion = devolver_lista_vacunacion($us_user);
            $_SESSION['lista_vacunacion'] = $lista_vacunacion;
            $n_peticiones = count($lista_vacunacion);
            $accion = "ver";

            echo $twig->render('cartilla_vacunacion.twig', compact('us_user', 'lista_vacunacion', 'n_peticiones' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas', 'accion'));

        }else if(isset($_POST['vacunas_pendientes'])){

            $edad_mes = calcular_edad($_SESSION['row_datos']['FechaNac']);

            $lista_pendientes = devolver_vacunacion_pendiente($sexo_user, $edad_mes, $us_user);

            $n_pendientes = count($lista_pendientes);

            echo $twig->render('listado_pendientes.twig', compact('us_user', 'lista_pendientes', 'n_pendientes', 'rol_user', 'nombre_user', 'image_user', 'sexo_user' ));
            

        }else if(isset($_POST['idProcesarInfoCompleta']) or isset($_POST['idEditarInfoCompleta'])){

            if(isset($_POST['idProcesarInfoCompleta'])){
                $claves = explode("-", $_POST['idProcesarInfoCompleta']);   
                $accion = "ver";
            }else{
                $claves = explode("-", $_POST['idEditarInfoCompleta']);   
                $accion = "editar";
            }

            $clave_usuario = $claves[0];
            $clave_calendario = $claves[1];
            $vacuna = [];
            

            foreach($_SESSION['lista_vacunacion'] as $datos_vacuna){
                if($datos_vacuna['IDUsuario'] == $clave_usuario and $datos_vacuna['IDCalendario'] == $clave_calendario){
                    $vacuna = $datos_vacuna;
                }
            }

            unset($_SESSION['lista_vacunacion']);

            echo $twig->render('informacion_vacunacion.twig', compact('vacuna', 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas', 'accion', 'clave_usuario', 'clave_calendario'));
            
        }else if(isset($_POST['boton-edit-vacunacion-user'])){

            modificar_vacunacion($_POST['Cuser'], $_POST['Ccalend'], $_POST['fabricante_edit'], $_POST['coment_edit']);
            $calendario = devolver_calendario_full();

            echo $twig->render('calendario_logueado.twig', compact('us_user', 'nombre_user', 'rol_user', 'image_user', 'sexo_user' ,'calendario', 'n_usuarios', 'n_vacunas'));


        }else if(isset($_SESSION["accionPulsada"]) and $_SESSION["accionPulsada"] == "activar"){
            $peticiones = devolver_lista_peticiones();
            $n_peticiones = count($peticiones);
            echo $twig->render('listado_peticiones.twig', compact('nombre_user','rol_user', 'image_user', 'sexo_user', 'peticiones', 'n_peticiones', 'n_usuarios', 'n_vacunas'));
              
        }else if(isset($_POST['idPonerVacuna'])){

            // En este punto, un sanitario puede añadir una vacuna a la cartilla de vacunación de un paciente.

            // Devolvemos los datos del usuario al que vamos a modificar la cartilla
            $_SESSION['datos_paciente'] = devolver_usuario($_POST['idPonerVacuna']);

            // Obtenemos el sexo y la fecha de nacimiento del paciente, que son los datos necesarios
            // para saber las vacunas que se tiene o ha tenido que poner.
            $sexo_paciente = $_SESSION['datos_paciente']['Sexo'];

            // Calculamos su edad
            $edad_paciente = calcular_edad($_SESSION['datos_paciente']['FechaNac']);          

            // Devolvemos la el acronimo de las vacunas que disponibles para ponerse para cada usuario en su cartilla 
            // y el ID del calendario
            $resultado = devolver_vacunacion_disponible($sexo_paciente, $edad_paciente, $_POST['idPonerVacuna']);
            
            if($resultado != false){
                $_SESSION['vacunacion_disponible'] = $resultado;
                $vacunas = $resultado;
                $vacunaPorDefecto = $vacunas[0];
                unset($vacunas[0]);

                $accion = "registrar";
                
                echo $twig->render('formulario_vacunacion.twig', compact('us_user', 'accion', 'vacunaPorDefecto', 'vacunas' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

            }else{
                $motivo = "ESTE USUARIO NO TIENE NINGUNA VACUNA PENDIENTE.";
                echo $twig->render('errores.twig', compact('motivo', 'n_usuarios', 'n_vacunas'));
            }

        }else if(isset($_POST['idVerVacunacion'])){
            // Para poder ver la cartilla de vacunación de un usuario

            $lista_vacunacion = devolver_lista_vacunacion($_POST['idVerVacunacion']);
            $_SESSION['lista_vacunacion'] = $lista_vacunacion;
            $n_peticiones = count($lista_vacunacion);
            $accion = "ver";

            echo $twig->render('cartilla_vacunacion.twig', compact('us_user', 'lista_vacunacion', 'n_peticiones' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas', 'accion'));

        }else if(isset($_POST['idEditarVacunacion'])){
            $lista_vacunacion = devolver_lista_vacunacion($_POST['idEditarVacunacion']);
            $_SESSION['lista_vacunacion'] = $lista_vacunacion;
            $n_peticiones = count($lista_vacunacion);

            $accion = "editar";

            echo $twig->render('cartilla_vacunacion.twig', compact('us_user', 'lista_vacunacion', 'n_peticiones' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas', 'accion'));
           
            
        }else if(isset($_SESSION['accionPulsadaVacunacion']) and $_SESSION['accionPulsadaVacunacion'] == "confirmar"){

            $vacunacion = $_SESSION['row_datos_temp'];
            $accion = "confirmar";
            echo $twig->render('formulario_vacunacion.twig', compact('vacunacion', 'us_user', 'accion' , 'rol_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

        }else if(isset($_POST['nMeses'])){

            $fecha_nac = $_SESSION['row_datos']['FechaNac'];
    
            $meses = $_POST['n_meses_vacunas'];
    
            $lista_pendientes = vacunas_pendientes_usuario_n_meses($us_user, $fecha_nac, $sexo_user, $meses);
    
            $n_pendientes = count($lista_pendientes);
            
            echo $twig->render('listado_pendientes.twig', compact('us_user', 'lista_pendientes', 'n_pendientes', 'rol_user', 'nombre_user', 'image_user', 'sexo_user' ));
            
        }else if(isset($_POST['nVacunas'])){
            
            $fecha_nac = $_SESSION['row_datos']['FechaNac'];
    
            $n_sig = $_POST['n_vacunas_siguientes'];
    
            $lista_pendientes = vacunas_pendientes_usuario_n($us_user, $fecha_nac, $sexo_user, $n_sig);
    
            $n_pendientes = count($lista_pendientes);
            
            echo $twig->render('listado_pendientes.twig', compact('us_user', 'lista_pendientes', 'n_pendientes', 'rol_user', 'nombre_user', 'image_user', 'sexo_user' ));
            
        }else if(isset($_POST['vacunasFuturas'])){
         
            $fecha_nac = $_SESSION['row_datos']['FechaNac'];

            $edad_m = calcular_edad($fecha_nac);

            $lista_pendientes = devolver_vacunacion_futura($sexo_user, $edad_m, $us_user);

            $n_pendientes = count($lista_pendientes);
            
            echo $twig->render('listado_pendientes.twig', compact('us_user', 'lista_pendientes', 'n_pendientes', 'rol_user', 'nombre_user', 'image_user', 'sexo_user' ));
            
        }else if(isset($_POST['vacunasPendientes'])){

            $fecha_nac = $_SESSION['row_datos']['FechaNac'];

            $edad_m = calcular_edad($fecha_nac);

            $lista_pendientes = devolver_vacunacion_pendiente($sexo_user, $edad_m, $us_user);

            $n_pendientes = count($lista_pendientes);
            
            echo $twig->render('listado_pendientes.twig', compact('us_user', 'lista_pendientes', 'n_pendientes', 'rol_user', 'nombre_user', 'image_user', 'sexo_user' ));

        }else if(isset($_POST['idFiltrado'])){

            $usuarios = devolver_lista_usuarios();

            if(isset($_POST['search']) and ($_POST['search'] != "")){ // SEARCH
            
                $usuarios = filtrar_usuario_by_search($_POST['search'], $usuarios);
    
                // Si existe ACTIVO checked pero no INACTIVO
                if( isset($_POST['activo']) and !(isset($_POST['inactivo'])) ) {
                        
                    $usuarios = filtrar_usuario_by_activo($usuarios);
    
                }else if( !(isset($_POST['activo'])) and isset($_POST['inactivo']) ) {
                    
                    $usuarios = filtrar_usuario_by_inactivo($usuarios);
                
                }
    
                // QUE HAYA O NO VACUNAS PENDIENTES
                if(isset($_POST['pendiente'])){
    
                    for($i = 0; $i < count($usuarios); $i++){

                        $l = $usuarios[$i];
    
                        $mes = $l['FechaNac'];
    
                        $edad_m = calcular_edad($mes);
    
                        $lista_vacunacion_pendientes = devolver_vacunacion_pendiente($l['Sexo'], $edad_m, $l['DNI']);
    
                        if(count($lista_vacunacion_pendientes) == 0){
                            
                            unset($usuarios[$i]);
                        }
    
                    }
                       
                } 
                
                if(isset($_POST['from']) and isset($_POST['to'])){
                    if($_POST['from'] != "" and $_POST['to'] != ""){
                        $fecha_ini = $_POST['from'];
                       
                        $fecha_dividida = explode("/", $fecha_ini);
    
                        if($fecha_dividida[0] > 12){
                            $fecha_ini = $fecha_dividida[1] . "/" . $fecha_dividida[0] . "/" . $fecha_dividida[2];
                        }
    
    
                        $fecha_ini = (new DateTime($fecha_ini))->format("Y-m-d");
    
                        $fecha_fin = $_POST['to'];
    
                        $fecha_dividida = explode("/", $fecha_fin);
    
                        if($fecha_dividida[0] > 12){
                            $fecha_fin = $fecha_dividida[1] . "/" . $fecha_dividida[0] . "/" . $fecha_dividida[2];
                        }
    
                        $fecha_fin = (new DateTime($fecha_fin))->format("Y-m-d");
    
                        $usuarios = filtrar_usuario_by_fechas($usuarios, $fecha_ini, $fecha_fin);
                    } 
                }

                
                
            
            }else if( isset($_POST['activo']) and !(isset($_POST['inactivo'])) ) { // ACTIVO pero NO INACTIVO

                $usuarios = filtrar_usuario_by_activo($usuarios);

                // QUE HAYA O NO VACUNAS PENDIENTES
                if(isset($_POST['pendiente'])){
                    
                    for($i = 0; $i < count($usuarios); $i++){

                        $l = $usuarios[$i];
    
                        $mes = $l['FechaNac'];
    
                        $edad_m = calcular_edad($mes);
    
                        $lista_vacunacion_pendientes = devolver_vacunacion_pendiente($l['Sexo'], $edad_m, $l['DNI']);
    
                        if(count($lista_vacunacion_pendientes) == 0){
                            
                            unset($usuarios[$i]);
                        }
    
                    }
                    
                    if(isset($_POST['from']) and isset($_POST['to'])){
                        if($_POST['from'] != "" and $_POST['to'] != ""){
                            $fecha_ini = $_POST['from'];
                       
                        $fecha_dividida = explode("/", $fecha_ini);
    
                        if($fecha_dividida[0] > 12){
                            $fecha_ini = $fecha_dividida[1] . "/" . $fecha_dividida[0] . "/" . $fecha_dividida[2];
                        }
    
    
                        $fecha_ini = (new DateTime($fecha_ini))->format("Y-m-d");
    
                        $fecha_fin = $_POST['to'];
    
                        $fecha_dividida = explode("/", $fecha_fin);
    
                        if($fecha_dividida[0] > 12){
                            $fecha_fin = $fecha_dividida[1] . "/" . $fecha_dividida[0] . "/" . $fecha_dividida[2];
                        }
    
                        $fecha_fin = (new DateTime($fecha_fin))->format("Y-m-d");
    
                        $usuarios = filtrar_usuario_by_fechas($usuarios, $fecha_ini, $fecha_fin);

                        }
                        
                    }
                    
                }

            }else if( !(isset($_POST['activo'])) and isset($_POST['inactivo']) ) { // NO ACTIVO pero ACTIVO

                $usuarios = filtrar_usuario_by_inactivo($usuarios);

                // QUE HAYA O NO VACUNAS PENDIENTES
                if(isset($_POST['pendiente'])){
                    
                    for($i = 0; $i < count($usuarios); $i++){

                        $l = $usuarios[$i];
    
                        $mes = $l['FechaNac'];
    
                        $edad_m = calcular_edad($mes);
    
                        $lista_vacunacion_pendientes = devolver_vacunacion_pendiente($l['Sexo'], $edad_m, $l['DNI']);
    
                        if(count($lista_vacunacion_pendientes) == 0){
                            
                            unset($usuarios[$i]);
                        }
    
                    }

                    if(isset($_POST['from']) and isset($_POST['to'])){
                        if($_POST['from'] != "" and $_POST['to'] != ""){
                            $fecha_ini = $_POST['from'];
                           
                            $fecha_dividida = explode("/", $fecha_ini);
        
                            if($fecha_dividida[0] > 12){
                                $fecha_ini = $fecha_dividida[1] . "/" . $fecha_dividida[0] . "/" . $fecha_dividida[2];
                            }
        
        
                            $fecha_ini = (new DateTime($fecha_ini))->format("Y-m-d");
        
                            $fecha_fin = $_POST['to'];
        
                            $fecha_dividida = explode("/", $fecha_fin);
        
                            if($fecha_dividida[0] > 12){
                                $fecha_fin = $fecha_dividida[1] . "/" . $fecha_dividida[0] . "/" . $fecha_dividida[2];
                            }
        
                            $fecha_fin = (new DateTime($fecha_fin))->format("Y-m-d");
        
                            $usuarios = filtrar_usuario_by_fechas($usuarios, $fecha_ini, $fecha_fin);
                        }
                        
                    }
                }
            
            // FIN DE LOS CHECKBOX
            }else if(isset($_POST['pendiente'])){ // QUE HAYA O NO VACUNAS PENDIENTES
    
                for($i = 0; $i < count($usuarios); $i++){

                    $l = $usuarios[$i];

                    $mes = $l['FechaNac'];

                    $edad_m = calcular_edad($mes);

                    $lista_vacunacion_pendientes = devolver_vacunacion_pendiente($l['Sexo'], $edad_m, $l['DNI']);

                    if(count($lista_vacunacion_pendientes) == 0){
                        
                        unset($usuarios[$i]);
                    }

                }  

                if(isset($_POST['from']) and isset($_POST['to'])){
                    if($_POST['from'] != "" and $_POST['to'] != ""){
                        $fecha_ini = $_POST['from'];
                       
                        $fecha_dividida = explode("/", $fecha_ini);
    
                        if($fecha_dividida[0] > 12){
                            $fecha_ini = $fecha_dividida[1] . "/" . $fecha_dividida[0] . "/" . $fecha_dividida[2];
                        }
    
    
                        $fecha_ini = (new DateTime($fecha_ini))->format("Y-m-d");
    
                        $fecha_fin = $_POST['to'];
    
                        $fecha_dividida = explode("/", $fecha_fin);
    
                        if($fecha_dividida[0] > 12){
                            $fecha_fin = $fecha_dividida[1] . "/" . $fecha_dividida[0] . "/" . $fecha_dividida[2];
                        }
    
                        $fecha_fin = (new DateTime($fecha_fin))->format("Y-m-d");
    
                        $usuarios = filtrar_usuario_by_fechas($usuarios, $fecha_ini, $fecha_fin);
                    }
                    
                }
            }else if(isset($_POST['from']) and isset($_POST['to'])){
                if($_POST['from'] != "" and $_POST['to'] != ""){
                    $fecha_ini = $_POST['from'];
                   
                    $fecha_dividida = explode("/", $fecha_ini);

                    if($fecha_dividida[0] > 12){
                        $fecha_ini = $fecha_dividida[1] . "/" . $fecha_dividida[0] . "/" . $fecha_dividida[2];
                    }


                    $fecha_ini = (new DateTime($fecha_ini))->format("Y-m-d");

                    $fecha_fin = $_POST['to'];

                    $fecha_dividida = explode("/", $fecha_fin);

                    if($fecha_dividida[0] > 12){
                        $fecha_fin = $fecha_dividida[1] . "/" . $fecha_dividida[0] . "/" . $fecha_dividida[2];
                    }

                    $fecha_fin = (new DateTime($fecha_fin))->format("Y-m-d");

                    $usuarios = filtrar_usuario_by_fechas($usuarios, $fecha_ini, $fecha_fin);
                }
                
            }

            echo $twig->render('listado_usuarios.twig', compact('rol_user', 'image_user', 'usuarios' , 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));
        
        } else if (isset($_POST['backup'])) {
            echo $twig->render('backup.twig', compact('rol_user', 'image_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

        } else if (isset($_POST['export_db'])) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="db_backup.sql"');
            echo DB_backup();

        } else if (isset($_POST['delete_db'])) {
            DB_delete();
            
            session_unset();
            session_destroy();

            echo $twig->render('inicio_visitante.twig', compact('n_usuarios', 'n_vacunas'));

        }else if (isset($_POST['restore_db'])){
            echo $twig->render('backup_restore.twig', compact('rol_user', 'image_user', 'nombre_user', 'image_user', 'sexo_user', 'n_usuarios', 'n_vacunas'));

        } else if (isset($_POST['restore_from_file'])){
            if ((sizeof($_FILES)==0) || !array_key_exists("restore_file",$_FILES))
                $error = "No se ha podido subir el fichero";
            else if (!is_uploaded_file($_FILES['restore_file']['tmp_name']))
                $error = "Fichero no subido. Código de error: ".$_FILES['restore_file']['error'];
            else {
                $error = DB_restore($_FILES['restore_file']['tmp_name']);
            }
            if (isset($error) && msgCount($error)>0)
                msgError($error);
            else
                msgError("Base de datos restaurada correctamente","msginfo");

        } else{
            $calendario = devolver_calendario_full();
            // Lo introduzco en el else para que no cargue ambas vistas a la vez en el caso de que se quiera
            if(isset($_SESSION['exito'])){
                $exito = $_SESSION['exito']; // Comrpobamos si ha tenido o no exito
                unset($_SESSION['exito']);
                
                echo $twig->render('calendario_logueado.twig', compact('us_user', 'nombre_user', 'rol_user', 'image_user', 'sexo_user', 'calendario', 'n_usuarios', 'n_vacunas', 'exito'));
            }else{
                echo $twig->render('calendario_logueado.twig', compact('us_user', 'nombre_user', 'rol_user', 'image_user', 'sexo_user' ,'calendario', 'n_usuarios', 'n_vacunas'));
            }
                   
        }

    }else if(isset($_SESSION['accionPulsada']) and ($_SESSION['accionPulsada'] == "registrar" or $_SESSION['accionPulsada'] == "confirmar")){
        // ERES UN VISITANTE INTENTANDO REGISTRARTE 
        $accion = $_SESSION['accionPulsada'];
        $rol_user = $_SESSION['rol_user_visitante'];

        // Si al registrar hay errores, se carga formulario sticky con los errores escritos
        if(isset($_SESSION['row_errores_temp'])){
            $row = $_SESSION['row_datos_temp'];
            $errores = $_SESSION['row_errores_temp'];
            echo $twig->render('formulario_usuario.twig', compact('row', 'errores' , 'rol_user', 'accion', 'n_usuarios', 'n_vacunas'));
        }else{
            echo $twig->render('formulario_usuario.twig', compact('rol_user', 'accion', 'n_usuarios', 'n_vacunas'));
        }

        
    }else{
        
        $calendario = devolver_calendario_full();

        if(isset($_SESSION['exito'])){
            $exito = $_SESSION['exito']; // Comrpobamos si ha tenido o no exito
            unset($_SESSION['exito']);
            echo $twig->render('inicio_visitante.twig', compact('calendario', 'n_usuarios', 'n_vacunas', 'exito'));
        }else{
            echo $twig->render('inicio_visitante.twig', compact('calendario', 'n_usuarios', 'n_vacunas'));
        }

        
        
        
    } 

?>