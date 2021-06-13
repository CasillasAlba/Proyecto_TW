<?php
    function procesar_login($usuario, $clave){

        // Hacemos una llamada al metodo de db.php
        // el cual nos devolverá los datos del usuario
        // o falso si no existe en la BBDD
        $resultado = devolver_usuario($usuario);


        if($resultado != False){
            // Solo podrá iniciar sesión los usuarios con Estado Activo
            if($resultado['Estado'] == "Activo"){ 
                // Si nos ha devuelto algo, comprobamos que la clave
                // haya sido introducida correctamente
                $clave_devuelta = $resultado['Clave'];

                // Comparamos los hash para ver si son iguales
                if(password_verify($clave, $clave_devuelta)){
                    return $resultado;
                }
            }else{
                return "Inactivo";
            }

        }

        return False;

    }

    function procesar_logout(){
        // La sesión debe estar iniciada
        if(session_status() == PHP_SESSION_NONE){
            session_start();
        }

        // Borrar variables de sesión
        session_unset();

        // Obtener parámetros de cookie de sesión
        $param = session_get_cookie_params();

        // Borrar cookie de sesión
        setcookie(session_name(), $_COOKIE[session_name()], time()-2592000,
            $param['path'], $param['domain'], $param['secure'], $param['httponly']);

        // Destruir la sesión
        session_destroy();
    }

?>