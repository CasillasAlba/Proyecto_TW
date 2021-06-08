<?php

    require_once './vendor/autoload.php';
    session_start();

    $loader = new \Twig\Loader\FilesystemLoader('./view');
    $twig = new \Twig\Environment($loader);

    require_once('./core/db_credenciales.php');
    $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWD, DB_DATABASE);

    ///////////////////////////////// FUNCIONES DEL PROGRAMA

    // Funcion para acceder los assets (css, imagenes...)
    $twig->addFunction(new \Twig\TwigFunction('asset', function ($asset) {
        return sprintf('./view/%s', ltrim($asset, '/'));
    }));

    // Funcion para poder acceder a archivos php
    $twig->addFunction(new \Twig\TwigFunction('funciones', function ($funciones) {
        return sprintf('./controller/%s', ltrim($funciones, '/'));
    }));

    //if(!$db){
        //die("No se ha podido establecer una conexión:" .mysqli_connect_error());
    //}

    //////////////////////////////////////////////////////////////////////////////
    // ESTO LO ESTOY USANDO PARA HACER PRUEBAS CON PHP                          //
    //////////////////////////////////////////////////////////////////////////////

    $us_user = '76738108B';
    $rol_user = 'Admin';
    if(isset($_SESSION['accionPulsada'])){
        $accion = $_SESSION['accionPulsada'];
    }
    
    if(isset($_SESSION['login'])){
        if(isset($_SESSION['row_datos'])){
            $row = $_SESSION['row_datos'];
            echo $twig->render('formulario_sticky.twig', compact('row', 'us_user', 'rol_user', 'accion'));
        }else{
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
    
            echo $twig->render('formulario_sticky.twig', compact('row', 'us_user', 'rol_user', 'accion'));
        }

    }else if(isset($_POST['registar-visitante'])){
        //Para cuando un visitante le de al boton de registar
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
    
        echo $twig->render('formulario_sticky.twig', compact('row', 'us_user', 'rol_user', 'accion'));

    }else{
        echo $twig->render('inicio.twig');
    }

   

?>