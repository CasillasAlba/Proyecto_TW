<?php
    require_once('validacion.php');
    session_start();

    // Creamos las sesiones para guardar los datos necesarios para
    // realizar la acción indicada por el usuario (pulsando el botón en el formualrio)
    $_SESSION["accionPulsada"] = $_POST['accion'];

    if($_SESSION["accionPulsada"] != "confirmar"){

        $_SESSION['nombre'] = $_POST["nom"];
        $_SESSION['apellidos'] = $_POST["apell"];
        $_SESSION['dni'] = $_POST["dn"];
        $_SESSION['email'] = $_POST["ema"];
        $_SESSION['telefono'] = $_POST["tel"];
        $_SESSION['nacimiento'] = $_POST["nac"];
        $_SESSION['sexo'] = $_POST["genero"];
        $_SESSION['clave'] = $_POST["clv"];

        if(isset($_POST["clv_rep"])){
            $_SESSION['clave_rep'] = $_POST["clv_rep"];
            unset($_POST["clv_rep"]);
        }

        if(isset($_POST["rol"])){
            $_SESSION['rol'] = $_POST["rol"];
            unset($_POST["rol"]);
        }

        if(isset($_POST["estado"])){
            $_SESSION['estado'] = $_POST["estado"];
            unset($_POST["estado"]);
        }

        $_SESSION['file_name'] = $_FILES['file']['name'];

        if($_SESSION['file_name'] != ''){
            $_SESSION['tmp_name']  = $_FILES['file']['tmp_name'];
            $_SESSION['file_size'] = getimagesize($_FILES['file']['tmp_name']);
            // Esto es lo que se guarda en la base de datos   
            //Coge el contenido del fichero temporal (bits) y los encodifica 
            $_SESSION['foto'] = base64_encode(file_get_contents(addslashes($_SESSION['tmp_name'])));
        }
    }
 
    switch($_SESSION["accionPulsada"]){
        case "registrar":
            //Validamos los datos del usuario
            $hay_error = comprobar_errores_usuario();

            if($hay_error == False){
                //Se realizara una redireccion a confirmar
                echo "he llegado hasta aqui :)";
                $_SESSION['accionPulsada'] = "confirmar";
                // Para guardar que operación CRUD se hará en la BD
                $_SESSION["accionBD"] = "registrar";
                header("Location: ../index.php");
            }
        break;

        case "editar":
            //Validamos los datos del usuario
            $hay_error = comprobar_errores_usuario();

            if($hay_error == False){
                //Se realizara una redireccion a confirmar
                echo "he llegado hasta aqui :)";
                $_SESSION['accionPulsada'] = "confirmar";
                // Para guardar que operación CRUD se hará en la BD
                $_SESSION["accionBD"] = "editar";
                header("Location: ../index.php");
            }

        break;

        case "confirmar":

            if(isset($_SESSION["accionBD"])){

                if($_SESSION["accionBD"] == "registrar"){
                    $_SESSION['row_datos']['Clave'] = cifrar_claves($_SESSION['row_datos']['Clave']);
                    insertar_usuario($_SESSION['row_datos']);
                }else if($_SESSION["accionBD"] == "registrar"){
                    echo "funcion no programada";
                }
            }

            // Cerramos las sesiones abiertas porque hemos acabado el proceso
            // de registro o edición
            unset($_SESSION['nombre']);
            unset($_SESSION['apellidos']);
            unset($_SESSION['dni']);
            unset($_SESSION['email']);
            unset($_SESSION['telefono']);
            unset($_SESSION['nacimiento']);
            unset($_SESSION['sexo']);
            unset($_SESSION['clave']);
            unset($_SESSION['clave_rep']);
            unset($_SESSION['rol']);
            unset($_SESSION['estado']);
            unset($_SESSION['row_errores']);
            unset($_SESSION['row_datos']);
            unset($_SESSION['accionPulsada']);
            unset($_SESSION['accionBD']);

            echo "Insertado correctamente :)";

        break;
    
        case "activar":
            if(isset($_POST['boton-activar-user'])){
                echo "Oleeee que ole";
            }else if(isset($_POST['boton-informar-error-user'])){
                echo "Ay mecachis";
            }else if(isset($_POST['boton-borrar-user'])){
                echo "Ay mecachis";
            }

        break;

    }

?>