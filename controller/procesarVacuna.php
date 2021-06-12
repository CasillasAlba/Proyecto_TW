<?php
    require_once('validacion.php');
    session_start();

        // Creamos las sesiones para guardar los datos necesarios para
    // realizar la acción indicada por el usuario (pulsando el botón en el formualrio)

    $_SESSION["accionPulsadaVac"] = $_POST['accion'];

    if($_SESSION["accionPulsadaVac"] != "confirmar"){
        
        $_SESSION['acronimo_vac_temp'] = $_POST["acro"];
        $_SESSION['nombre_vac_temp'] = $_POST["nomV"];
        $_SESSION['descrip_vac_temp'] = $_POST["descV"];

    }
    
    if($_SESSION["accionPulsadaVac"] == "borrar"){
        $_SESSION['id_vac_temp'] = $_POST['id_vac'];
    }

    switch($_SESSION["accionPulsadaVac"]){
        case "registrar":
            $hay_error = comprobar_errores_vacunas();

            if($hay_error == False){
                //Se realizara una redireccion a confirmar
                $_SESSION['accionPulsadaVac'] = "confirmar";
                // Para guardar que operación CRUD se hará en la BD
                $_SESSION["accionBD"] = "registrar";  
            }

            header("Location: ../index.php");

        break;

        case "editar":
            $hay_error = comprobar_errores_vacunas();

            if($hay_error == False){
                //Se realizara una redireccion a confirmar
                $_SESSION['accionPulsadaVac'] = "confirmar";
                // Para guardar que operación CRUD se hará en la BD
                $_SESSION["accionBD"] = "editar";  
            }

            header("Location: ../index.php");

        break;

        case "confirmar":

            if(isset($_SESSION["accionBD"])){

                if($_SESSION["accionBD"] == "registrar"){
                    insertar_vacuna($_SESSION['row_datos_temp']);
                }else if($_SESSION["accionBD"] == "editar"){
                    modificar_vacuna($_SESSION['row_datos_temp']);
                }
            }

            unset($_SESSION['acronimo_vac_temp']);
            unset($_SESSION['nombre_vac_temp']);
            unset($_SESSION['descrip_vac_temp']);
            unset($_SESSION['row_errores_temp']);
            unset($_SESSION['row_datos_temp']);
            unset($_SESSION['accionPulsadaVac']);
            unset($_SESSION['accionBD']);

            if(isset($_SESSION['acro_antigua'])){
                unset($_SESSION['acro_antigua']);
                unset($_SESSION['vacuna_a_editar']);
            }


            header("Location: ../index.php");

        break;

        case "borrar":
            eliminar_vacuna($_SESSION['id_vac_temp']);
            unset($_SESSION['id_vac_temp']);
            unset($_SESSION['accionPulsadaVac']);

            header("Location: ../index.php");

        break;

    }



?>