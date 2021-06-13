<?php
    require_once('validacion.php');
    session_start();

    // Creamos las sesiones para guardar los datos necesarios para
    // realizar la acción indicada por el usuario (pulsando el botón en el formualrio)
    $_SESSION["accionPulsadaCalend"] = $_POST['accion'];

    if($_SESSION["accionPulsadaCalend"] == "borrar"){

        $id_calend = $_SESSION['calend_temp_borrar']['IDCalendar'];

        unset($_SESSION['calend_temp_borrar']);
        unset($_SESSION['accionPulsada']);

        eliminar_calendario($id_calend);

        header("Location: ../index.php");

    }else if($_SESSION["accionPulsadaCalend"] != "confirmar"){

        $_SESSION['acro_ref_calend_temp'] = $_POST["vacunaRef"];
        $_SESSION['sexo_calend_temp'] = $_POST["generoCalend"];
        $_SESSION['mes_ini_temp'] = $_POST["mesIni"];
        $_SESSION['mes_fin_temp'] = $_POST["mesFin"];
        $_SESSION['tipo_calend_temp'] = $_POST["tipoCalend"];
        $_SESSION['descp_calend_temp'] = $_POST["descC"];

    }

    switch($_SESSION["accionPulsadaCalend"]){
        case "registrar":
            $hay_error = comprobar_errores_calend();

            if($hay_error == False){
                //Se realizara una redireccion a confirmar
                $_SESSION['accionPulsadaCalend'] = "confirmar";
                // Para guardar que operación CRUD se hará en la BD
                $_SESSION["accionBD"] = "registrar";  
            }

            header("Location: ../index.php");

        break;

        case "editar":
            $hay_error = comprobar_errores_calend();

            if($hay_error == False){
                //Se realizara una redireccion a confirmar
                $_SESSION['accionPulsadaCalend'] = "confirmar";
                // Para guardar que operación CRUD se hará en la BD
                $_SESSION["accionBD"] = "editar";  
            }

            header("Location: ../index.php");

        break;

        case "confirmar":

            if(isset($_SESSION["accionBD"])){

                if($_SESSION["accionBD"] == "registrar"){
                    $_SESSION['exito'] = insertar_calendario($_SESSION['row_datos_temp']);

                    $datos_log = array(
                        'Tipo' => "tipo_modif_calend",
                        'Fecha' => date("Y-m-d H:i:s"),
                        'Descripcion' => "CALENDARIO MODIFICADO"
                    ); 
        
                    insertar_log($datos_log);
                }
            }

            unset($_SESSION['acro_ref_calend_temp']);
            unset($_SESSION['sexo_calend_temp']);
            unset($_SESSION['mes_ini_temp']);
            unset($_SESSION['mes_fin_temp']);
            unset($_SESSION['tipo_calend_temp']);
            unset($_SESSION['descp_calend_temp']);
            unset($_SESSION['row_errores_temp']);
            unset($_SESSION['row_datos_temp']);
            unset($_SESSION['accionPulsadaCalend']);
            unset($_SESSION['accionBD']);

            header("Location: ../index.php");

        break;

    }

?>