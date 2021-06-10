<?php

    require_once './vendor/autoload.php';
    require_once('./core/db_credenciales.php');
    require_once('./controller/login.php');
    require_once('./core/db.php');

    session_start();

    /*
        if(!$db){
            //die("No se ha podido establecer una conexión:" .mysqli_connect_error());
        }
    */

    $loader = new \Twig\Loader\FilesystemLoader('./view');
    $twig = new \Twig\Environment($loader);

    ///////////////////////////////// FUNCIONES DEL PROGRAMA

    // Funcion para acceder los assets (css, imagenes...)
    $twig->addFunction(new \Twig\TwigFunction('asset', function ($asset) {
        return sprintf('./view/%s', ltrim($asset, '/'));
    }));

    // Funcion para poder acceder a archivos php
    $twig->addFunction(new \Twig\TwigFunction('funciones', function ($funciones) {
        return sprintf('./controller/%s', ltrim($funciones, '/'));
    }));

    // Función que devuelve la lista de vacunas
    $twig->addFunction(new \Twig\TwigFunction('lista_vacunas', function () {
        $lista = devolver_lista_vacunas();

        foreach($lista as $vacuna){
            echo sprintf('<p>Acrónimo: %s Nombre: %s</p>', $vacuna['Acronimo'], $vacuna['Nombre']);
            echo sprintf('<button class="form-button" name="idEditarVac" value="%s">Editar</button>', $vacuna['Acronimo']);
            echo sprintf('<button class="form-button" name="idBorrarVac" value="%s">Borrar</button>', $vacuna['Acronimo']);
        }
    }));

    // Función que devuelve la lista de usuarios
    $twig->addFunction(new \Twig\TwigFunction('lista_usuarios', function () {
        $lista = devolver_lista_usuarios();

        foreach($lista as $usuario){
            if($usuario["Rol"] != "Admin"){
                echo sprintf('<img height="130px" width="150px" src="data:image;base64,%s" />', $usuario["Fotografia"]);
                echo sprintf('<p>Nombre: %s %s</p>', $usuario["Nombre"], $usuario['Apellidos']);
                echo sprintf('<button class="form-button" name="idEditarUser" value="%s">Editar</button>', $usuario["DNI"]);
                echo sprintf('<button class="form-button" name="idBorrarUser" value="%s">Borrar</button>', $usuario["DNI"]);
            }
        }
    }));


    //////////////////////////////////////////////////////////////////////////////
    // ESTO LO ESTOY USANDO PARA HACER PRUEBAS CON PHP                          //
    //////////////////////////////////////////////////////////////////////////////

    if(isset($_SESSION['accionPulsada'])){
        $accion = $_SESSION['accionPulsada'];
    }else if(isset($_SESSION['accionPulsadaVac'])){
        $accion = $_SESSION['accionPulsadaVac'];
    }else if(isset($_SESSION['accionPulsadaCalend'])){
        $accion = $_SESSION['accionPulsadaCalend'];
    }

    if(isset($_POST['boton-login-visitante'])){
        // ENTRAMOS EN ESTE IF CUANDO UN VISITANTE INTENTE LOGUEARSE
        // Obtenemos los valores introducidos por el usuario
        $dni_login = $_POST['usr'];
        $clv_login = $_POST['psw'];

        $datos_logueado = procesar_login($dni_login, $clv_login);

        
        // El login no ha sido correcto
        if($datos_logueado == False){
            echo $twig->render('error_inicio.twig');
        }else{
            // Se ha iniciado sesión correctamente
            // Esta será la variable con la que se guarde la sesión
            $_SESSION['login'] = True;

            // Datos importantes del usuario logueado
            $_SESSION['row_datos'] = $datos_logueado;
            $us_user = $datos_logueado['DNI'];
            $nombre_user = $datos_logueado['Nombre'];
            $rol_user = $datos_logueado['Rol'];
            $image_user = $datos_logueado['Foto'];
            
            echo $twig->render('inicio_logueado.twig', compact('us_user', 'nombre_user', 'rol_user', 'image_user'));
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

        $accion = 'registrar';
        $us_user = '';
        $rol_user = 'Visitante';
    
        echo $twig->render('formulario_usuario.twig', compact('row', 'us_user', 'rol_user', 'accion'));

    }else if(isset($_POST['log-out'])){
        // Llamamos a la función de cerrar sesión ubicada en login.php
        procesar_logout();

        echo $twig->render('inicio.twig');

    }else if(isset($_SESSION['login'])){

        // El usuario tiene la sesión iniciada, si vuelve para atrás, se mantiene.
        $us_user = $_SESSION['row_datos']['DNI'];
        $rol_user = $_SESSION['row_datos']['Rol'];
        $nombre_user = $_SESSION['row_datos']['Nombre'];
        $image_user = $_SESSION['row_datos']['Foto'];

        // El usuario es ADMIN y puede registrar otros usuarios
        // o eres un visitante que quiere registrarse
        if(isset($_POST['regist']) or (isset($_SESSION['accionPulsada']) and $_SESSION['accionPulsada'] == "registrar")){

            $accion = "registrar";

            // Si al registrar hay errores, se carga formulario sticky con los errores escritos
            if(isset($_SESSION['row_errores_temp'])){
                $row = $_SESSION['row_datos_temp'];
                $errores = $_SESSION['row_errores_temp'];
                echo $twig->render('formulario_usuario.twig', compact('row', 'errores', 'us_user', 'rol_user', 'accion'));
            }else{
                echo $twig->render('formulario_usuario.twig', compact('us_user', 'rol_user', 'accion'));
            }
            
        }else if((isset($_POST['editar-me']) or (isset($_POST['idEditarUser']) or (isset($_SESSION['accionPulsada']) and $_SESSION['accionPulsada'] == "editar")))){

    
            if(isset($_POST['idEditarUser'])){
                $_SESSION['dni_antiguo'] = $_POST['idEditarUser']; //DNI DE LA PERSONA A LA QUE VAMOS A EDITAR
                $_SESSION['user_a_editar'] = devolver_usuario($_SESSION['dni_antiguo']);
                $row = $_SESSION['user_a_editar'];
                $_SESSION['foto_antigua'] = $row['Foto'];
                $accion = "editar";
                echo $twig->render('formulario_usuario.twig', compact('row', 'us_user','rol_user','accion'));
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

                echo $twig->render('formulario_usuario.twig', compact('row', 'us_user','rol_user','accion'));

            }

            // Si al registrar hay errores, se carga formulario sticky con los errores escritos
            if(isset($_SESSION['row_errores_temp'])){
                $row = $_SESSION['row_datos_temp'];
                $errores = $_SESSION['row_errores_temp'];

                echo $twig->render('formulario_usuario.twig', compact('row', 'errores', 'us_user', 'rol_user', 'accion'));
            }

        }else if(isset($_SESSION['accionPulsada']) and $_SESSION['accionPulsada'] == "confirmar"){

            // Cargamos el formulario de confirmación
            $row = $_SESSION['row_datos_temp']; 
            echo $twig->render('formulario_usuario.twig', compact('row', 'us_user', 'rol_user', 'accion'));

        }else if(isset($_POST['regist-vac']) or (isset($_SESSION['accionPulsadaVac']) and $_SESSION['accionPulsadaVac'] == "registrar" )){

            $accion = "registrar"; // Creamos accion para la 1a vez
    
            // Si al registrar hay errores, se carga formulario sticky con los errores escritos
            if(isset($_SESSION['row_errores_temp'])){
                $vac = $_SESSION['row_datos_temp'];
                $erroresVac = $_SESSION['row_errores_temp'];
                echo $twig->render('formulario_vacuna.twig', compact('vac', 'erroresVac', 'us_user', 'accion'));
            }else{
                echo $twig->render('formulario_vacuna.twig', compact('us_user', 'accion'));
            }
    
        }else if(isset($_SESSION['accionPulsadaVac']) and $_SESSION['accionPulsadaVac'] == "confirmar"){

            // Cargamos el formulario de confirmación
            $vac = $_SESSION['row_datos_temp']; 
            echo $twig->render('formulario_vacuna.twig', compact('vac', 'us_user', 'accion'));
            
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
                echo $twig->render('formulario_calendario.twig', compact('calend', 'erroresCalend', 'us_user', 'accion', 'vacunaPorDefecto', 'vacunas'));
            }else{
                echo $twig->render('formulario_calendario.twig', compact('us_user', 'accion', 'vacunaPorDefecto', 'vacunas'));
            }
            
        }else if(isset($_SESSION['accionPulsadaCalend']) and $_SESSION['accionPulsadaCalend'] == "confirmar"){

            // Cargamos el formulario de confirmación
            $calend = $_SESSION['row_datos_temp']; 
            echo $twig->render('formulario_calendario.twig', compact('calend', 'us_user', 'accion'));

        }else if(isset($_POST['listado_vac'])){

            echo $twig->render('listado_vacunas.twig', compact('nombre_user','rol_user', 'image_user'));

        }else if(isset($_POST['listado_user'])){

            echo $twig->render('listado_usuarios.twig', compact('nombre_user','rol_user', 'image_user'));
            
        }else if(isset($_POST['idEditarVac']) or (isset($_SESSION['accionPulsadaVac']) and $_SESSION['accionPulsadaVac'] == "editar" )){
            
            // PARA LA EDICION DE VACUNAS

            if(isset($_POST['idEditarVac'])){
                $_SESSION['acro_antigua'] = $_POST['idEditarVac']; //Por si se modifica el acronimo
                $_SESSION['vacuna_a_editar'] = devolver_vacuna($_SESSION['acro_antigua']);
                $vac = $_SESSION['vacuna_a_editar'];
                $accion = "editar";
                echo $twig->render('formulario_vacuna.twig', compact('vac', 'us_user', 'accion'));
            }else{
                // Si el botón no existe, vienes de un fallo
                $vac = $_SESSION['row_datos_temp'];
                $erroresVac = $_SESSION['row_errores_temp'];
                echo $twig->render('formulario_vacuna.twig', compact('vac', 'erroresVac', 'us_user', 'accion'));
            }

        }else{
            
            // Lo introduzco en el else para que no cargue ambas vistas a la vez en el caso de que se quiera
            echo $twig->render('inicio_logueado.twig', compact('us_user', 'nombre_user', 'rol_user', 'image_user'));
        }

    }else{
        echo $twig->render('inicio.twig');
    }

    

   

?>