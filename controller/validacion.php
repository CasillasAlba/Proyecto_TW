<?php
require_once('../core/db.php');

    function comprobar_errores_usuario(){
        // Inicializamos el array de errores
        $array_errores = array(
            "dniError" => "",
            "nombreError" => "",
            "apellidosError" => "",
            "emailError" => "",
            "telefonoError" => "",
            "fechaNacError" => "",
            "sexoError" => "",
            "claveError" => "",
            "rolError" => "",
            "estadoError" => ""
        );

        $error = false;
        $dni = $_SESSION['dni'];

        // Para controlar la validación al editar un usuario
        if(isset($_SESSION['dni_antiguo'])){
            $dni_antiguo = $_SESSION['dni_antiguo'];

            if($dni != $dni_antiguo){
                $existe = usuario_ya_registrado($dni);

                if($existe == 'true'){
                    $array_errores['dniError'] = 'Este DNI ya esta registrado en la base de datos';
                    $error = True;
                }else{
                    if(strlen($dni) == 9){
                        // Comprobamos que el formato del DNI es correcto (8 numeros y una letra al final)
                        $letra = substr($dni, -1);
                        $numeros = substr($dni, 0, -1);
                        if (!(is_numeric($numeros) && is_string($letra))){
                            $array_errores['dniError'] = 'DNI incorrecto';
                            $error = True;
                        }
                    }else{
                        $array_errores['dniError'] = 'Este DNI no tiene 9 dígitos';
                        $error = True;
                    }
                }
            }
        }else{
            $existe = usuario_ya_registrado($dni);

            if($existe == true){
                $array_errores['dniError'] = 'Este DNI ya esta registrado en la base de datos';
                $error = True;
            }else{
                if(strlen($dni) == 9){
                    // Comprobamos que el formato del DNI es correcto (8 numeros y una letra al final)
                    $letra = substr($dni, -1);
                    $numeros = substr($dni, 0, -1);
                    if (!(is_numeric($numeros) && is_string($letra))){
                        $array_errores['dniError'] = 'DNI incorrecto';
                        $error = True;
                    }
                }else{
                    $array_errores['dniError'] = 'Este DNI no tiene 9 dígitos';
                    $error = True;
                }
            }
        }

        ////////////////////////////  Validaciones del Nombre ////////////////////////////////////////////

        if (!preg_match('/^[a-zA-ZÀ-ÿ\u00f1\u00d1]+(\s*[a-zA-ZÀ-ÿ\u00f1\u00d1]*)*[a-zA-ZÀ-ÿ\u00f1\u00d1]+$/',$_SESSION['nombre'])){
            $array_errores['nombreError'] = 'El nombre no es correcto';
            $error = True;
        }

        ////////////////////////////  Validaciones de los Apellidos //////////////////////////////////////

        if (!preg_match('/^[a-zA-ZÀ-ÿ\u00f1\u00d1]+(\s*[a-zA-ZÀ-ÿ\u00f1\u00d1]*)*[a-zA-ZÀ-ÿ\u00f1\u00d1]+$/',$_SESSION['apellidos'])){
            $array_errores['apellidosError'] = 'Los apellidos son incorrectos';
            $error = True;
        }

        ////////////////////////////  Validaciones del Email /////////////////////////////////////////////
        if(!(filter_var($_SESSION['email'], FILTER_VALIDATE_EMAIL))){
            $array_errores['emailError'] = 'Email incorrecto. [Forma correcta: xxx@yyy.zzz]';
            $error = True;
        } 

        ////////////////////////////  Validaciones del Telefono //////////////////////////////////////////
        if (!preg_match('/^(\(\+[0-9]{2}\))?\s*[0-9]{3}\s*[0-9]{6}$/',$_SESSION['telefono'])){
            $array_errores['telefonoError'] = 'Número de teléfono incorrecto';
            $error = True;
        }

        ////////////////////////////  Validaciones de la Fecha de Nacimiento /////////////////////////////
        $fecha_actual = date("Y/m/d");
        $fecha_limite = date("1900/1/1");
        $fecha_user = $_SESSION['nacimiento'];

        if(($fecha_actual < $fecha_user) || ($fecha_limite > $fecha_user)){
            $array_errores['fechaNacError'] = 'Fecha de nacimiento incorrecta';
            $error = True;
        }

        ////////////////////////////  Validaciones del Sexo ////////////////////////////////////////////
        if($_SESSION['sexo'] != "Masculino" && $_SESSION['sexo'] != "Femenino"){
            $array_errores['sexoError'] = 'El sexo es incorecto';
            $error = True;
        }

        ////////////////////////////  Validaciones del Rol ////////////////////////////////////////////////
        if(isset($_POST["rol"])){
            if($_SESSION['rol'] != "Paciente" && $_SESSION['rol'] != "Sanitario"){
                $array_errores['rolError'] = 'El rol es incorrecto';
                $error = True;
            }
        }

        ////////////////////////////  Validaciones del Estado /////////////////////////////////////////////
        if(isset($_POST["estado"])){
            if($_SESSION['estado'] != "Activo" && $_SESSION['estado'] != "Inactivo"){
                $array_errores['estadoError'] = 'El estado no es correcto';
                $error = True;
            }
        }
        
        ////////////////////////////  Validaciones de la Clave ////////////////////////////////////////////
        if(strlen($_SESSION['clave']) < 6 || strlen($_SESSION['clave']) > 15){
            $array_errores['claveError'] = 'Introduzca una clave entre 6 y 15 digitos';
            $error = True;
        }else{
            // Una clave no deberia tener espacios en blanco
            if(strrpos($_SESSION['clave'], ' ') == True){
                $array_errores['claveError'] = 'La clave no es correcta';
                $error = True;
            }else{
                if(isset($_POST["clv_rep"])){ // en editar no existe
                    // Comrpobar si las dos claves son la misma
                    if($_SESSION['clave'] != $_SESSION['clave_rep']){
                        $array_errores['claveError'] = 'Ambas claves no coinciden';
                        $error = True;
                    }
                }
            }
        }


        if($_SESSION["accionPulsada"] == "editar"){
            if($_SESSION['file_name'] != ''){
                $array = array(
                    "Foto" => $_SESSION['foto'],
                    "Nombre" => $_SESSION['nombre'],
                    "Apellidos" => $_SESSION['apellidos'],
                    "DNI" => $_SESSION['dni'],
                    "Email" => $_SESSION['email'],
                    "Telefono" => $_SESSION['telefono'],
                    "FechaNac" => $_SESSION['nacimiento'],
                    "Sexo" => $_SESSION['sexo'] ,
                    "Clave" => $_SESSION['clave'],
                    "Rol" => $_SESSION['rol'],     
                    "Estado" => $_SESSION['estado']           
                );

                $_SESSION['foto_antigua'] = $_SESSION['foto'];
                
            }else{
                $array = array(
                    "Foto" => $_SESSION['foto_antigua'],
                    "Nombre" => $_SESSION['nombre'],
                    "Apellidos" => $_SESSION['apellidos'],
                    "DNI" => $_SESSION['dni'],
                    "Email" => $_SESSION['email'],
                    "Telefono" => $_SESSION['telefono'],
                    "FechaNac" => $_SESSION['nacimiento'],
                    "Sexo" => $_SESSION['sexo'] ,
                    "Clave" => $_SESSION['clave'],
                    "Rol" => $_SESSION['rol'],     
                    "Estado" => $_SESSION['estado']               
                );
                
            }

            
        }else{
            $array = array(
                "Foto" => $_SESSION['foto'],
                "Nombre" => $_SESSION['nombre'],
                "Apellidos" => $_SESSION['apellidos'],
                "DNI" => $_SESSION['dni'],
                "Email" => $_SESSION['email'],
                "Telefono" => $_SESSION['telefono'],
                "FechaNac" => $_SESSION['nacimiento'],
                "Sexo" => $_SESSION['sexo'] ,
                "Clave" => $_SESSION['clave'],
                "Rol" => $_SESSION['rol'],     
                "Estado" => $_SESSION['estado']                  
            );

        }

        $_SESSION['row_datos'] = $array;
        $_SESSION['row_errores'] = $array_errores;

        return $error;
    }

?>