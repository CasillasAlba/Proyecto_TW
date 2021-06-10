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

    $twig->addFunction(new \Twig\TwigFunction('lista_vacunas', function () {
        $lista = devolver_lista_vacunas();

        foreach($lista as $vacuna){
            echo sprintf('<p>Acrónimo: %s Nombre: %s</p>', $vacuna['Acronimo'], $vacuna['Nombre']);
            echo sprintf('<form action="index.php" method="POST"><input type="hidden" name="idEditarVac" value="%s"/><button class="form-button">Editar Vacuna</button></form>', $vacuna['Acronimo']);
            echo sprintf('<form action="index.php" method="POST"><input type="hidden" name="idBorrarVac" value="%s"/><button class="form-button">Borrar Vacuna</button></form>', $vacuna['Acronimo']);
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
            
        }else if(isset($_POST['editar-me']) or (isset($_SESSION['accionPulsada']) and $_SESSION['accionPulsada'] == "editar")){

            $accion = "editar";
            // Cargamos los valores del propio usuario para cargar SUS datos
            $row = $_SESSION['row_datos'];
            // Editando debemos de controlar el DNI antinguo, para en validación
            // poder comparar si es un usuario ya existente en la BBDD o no.
            $_SESSION['dni_antiguo'] = $_SESSION['row_datos']['DNI'];
            $_SESSION['clave_antigua'] = $row['Clave'];

            // Necesitamos este if para que si el admin se edita a si mismo, que su rol no se vea afectado
            // ya que Admin no es un rol que se pueda elegir ni cambiar en la plataforma
            if(isset($_POST['editar-me']) and $_SESSION['row_datos']['Rol'] == "Admin"){
                $_SESSION['rol_admin'] = "Admin";
            }

            // Si al registrar hay errores, se carga formulario sticky con los errores escritos
            if(isset($_SESSION['row_errores_temp'])){
                $row = $_SESSION['row_datos_temp'];
                $errores = $_SESSION['row_errores_temp'];

                echo $twig->render('formulario_usuario.twig', compact('row', 'errores', 'us_user', 'rol_user', 'accion'));
            }else{
                echo $twig->render('formulario_usuario.twig', compact('row', 'us_user', 'rol_user', 'accion'));
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
        }else{
            
            // Lo introduzco en el else para que no cargue ambas vistas a la vez en el caso de que se quiera
            echo $twig->render('inicio_logueado.twig', compact('us_user', 'nombre_user', 'rol_user', 'image_user'));
        }

    }else{
        echo $twig->render('inicio.twig');
    }

    

   

?>