<?php
    require_once('validacion.php');
    session_start();

    // Creamos las sesiones para guardar los datos necesarios para
    // realizar la acción indicada por el usuario (pulsando el botón en el formualrio)
    $_SESSION["accionPulsada"] = $_POST['accion'];

    if($_SESSION["accionPulsada"] != "confirmar"){

        // '_temp' hara referencia a los datos que introduce el usuario que 
        // esta haciendo una accion con el formulario.
        $_SESSION['nombre_temp'] = $_POST["nom"];
        $_SESSION['apellidos_temp'] = $_POST["apell"];
        $_SESSION['dni_temp'] = $_POST["dn"];
        $_SESSION['email_temp'] = $_POST["ema"];
        $_SESSION['telefono_temp'] = $_POST["tel"];
        $_SESSION['nacimiento_temp'] = $_POST["nac"];
        $_SESSION['sexo_temp'] = $_POST["genero"];
        $_SESSION['clave_temp'] = $_POST["clv"];

        if(isset($_POST["clv_rep"])){
            $_SESSION['clave_rep_temp'] = $_POST["clv_rep"];
            unset($_POST["clv_rep"]);
        }

        if(isset($_POST["rol"])){
            $_SESSION['rol_temp'] = $_POST["rol"];
            unset($_POST["rol"]);
        }

        if(isset($_POST["estado"])){
            $_SESSION['estado_temp'] = $_POST["estado"];
            unset($_POST["estado"]);
        }

        if(isset($_FILES['file']['name'])){
            $_SESSION['file_name_temp'] = $_FILES['file']['name'];

            if($_SESSION['file_name_temp'] != ''){
                $_SESSION['tmp_name_temp']  = $_FILES['file']['tmp_name'];
                $_SESSION['file_size_temp'] = getimagesize($_FILES['file']['tmp_name']);
                // Esto es lo que se guarda en la base de datos   
                //Coge el contenido del fichero temporal (bits) y los encodifica 
                $_SESSION['foto_temp'] = base64_encode(file_get_contents(addslashes($_SESSION['tmp_name_temp'])));
            }
        }
        
    }
 
    switch($_SESSION["accionPulsada"]){
        case "registrar":
            //Validamos los datos del usuario
            $hay_error = comprobar_errores_usuario();

            if($hay_error == False){
                //Se realizara una redireccion a confirmar
                $_SESSION['accionPulsada'] = "confirmar";
                // Para guardar que operación CRUD se hará en la BD
                $_SESSION["accionBD"] = "registrar";  
            }

            header("Location: ../index.php");

        break;

        case "editar":
            //Validamos los datos del usuario
            $hay_error = comprobar_errores_usuario();

            if($hay_error == False){
                //Se realizara una redireccion a confirmar
                $_SESSION['accionPulsada'] = "confirmar";
                // Para guardar que operación CRUD se hará en la BD
                $_SESSION["accionBD"] = "editar";
            }

            header("Location: ../index.php");

        break;

        case "confirmar":

            if(isset($_SESSION["accionBD"])){

                if($_SESSION["accionBD"] == "registrar"){

                    $_SESSION['row_datos_temp']['Clave'] = cifrar_claves($_SESSION['row_datos_temp']['Clave']);
                    insertar_usuario($_SESSION['row_datos_temp']);

                }else if($_SESSION["accionBD"] == "editar"){

                    $clave_formulario = $_SESSION['row_datos_temp']['Clave'];
                    $hash_clv = comparar_claves($clave_formulario, $_SESSION['clave_antigua']);
                    $_SESSION['row_datos_temp']['Clave'] = $hash_clv;

                    if(isset($_SESSION['rol_admin'])){
                        $_SESSION['row_datos_temp']['Rol'] = $_SESSION['rol_admin'];
                        unset($_SESSION['rol_admin']);
                    }
                    

                    modificar_usuario($_SESSION['row_datos_temp']);
                    unset($_SESSION['clave_antigua']);
                    
                }

            }

            // Cerramos las sesiones abiertas porque hemos acabado el proceso
            // de registro o edición
            unset($_SESSION['nombre_temp']);
            unset($_SESSION['apellidos_temp']);
            unset($_SESSION['dni_temp']);
            unset($_SESSION['email_temp']);
            unset($_SESSION['telefono_temp']);
            unset($_SESSION['nacimiento_temp']);
            unset($_SESSION['sexo_temp']);
            unset($_SESSION['clave_temp']);
            unset($_SESSION['clave_rep_temp']);
            unset($_SESSION['rol_temp']);
            unset($_SESSION['estado_temp']);
            unset($_SESSION['row_errores_temp']);
            unset($_SESSION['row_datos_temp']);
            unset($_SESSION['accionPulsada']);
            unset($_SESSION['accionBD']);
            if(isset($_SESSION['dni_antiguo'])){
                unset($_SESSION['dni_antiguo']);
            }

           header("Location: ../index.php");

        break;
    
        case "activar":
            if(isset($_POST['boton-activar-user'])){

                $_SESSION['datos_visitante']['Estado'] = "Activo";
                modificar_usuario($_SESSION['datos_visitante']);
                unset( $_SESSION['datos_visitante']);
                unset( $_SESSION['rol_user_visitante']);
                
            }else if(isset($_POST['boton-informar-error-user'])){
                echo "Ay mecachis";
            }else if(isset($_POST['boton-borrar-user'])){
                echo "Ay mecachis";
            }

            header("Location: ../index.php");

        break;

    }

?>