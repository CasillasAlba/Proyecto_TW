<?php
    require_once('validacion.php');
    session_start();

    // Creamos las sesiones para guardar los datos necesarios para
    // realizar la acción indicada por el usuario (pulsando el botón en el formualrio)
    $_SESSION["accionPulsadaVacunacion"] = $_POST['accion'];

    if($_SESSION["accionPulsadaVacunacion"] != "confirmar"){

        $_SESSION['acro_ref_vacunacion_temp'] = $_POST["vacunaRef"];
        $_SESSION['fabric_vacunacion_temp'] = $_POST["fabric"];
        $_SESSION['desc_vacunacion_temp'] = $_POST["descVacun"];
    }

    switch($_SESSION["accionPulsadaVacunacion"]){
        case "registrar":

            //Se realizara una redireccion a confirmar
            $_SESSION['accionPulsadaVacunacion'] = "confirmar";
            // Para guardar que operación CRUD se hará en la BD
            $_SESSION["accionBD"] = "registrar";  
            $array = array(
                "VacunaRef" => $_SESSION['acro_ref_vacunacion_temp'],
                "Fabricante" => $_SESSION['fabric_vacunacion_temp'],
                "DescripVacunacion" => $_SESSION['desc_vacunacion_temp']
            );
    
            $_SESSION['row_datos_temp'] = $array;

            header("Location: ../index.php");

        break;

        case "editar":
            //Se realizara una redireccion a confirmar
            $_SESSION['accionPulsadaVacunacion'] = "confirmar";
            // Para guardar que operación CRUD se hará en la BD
            $_SESSION["accionBD"] = "editar";  

            header("Location: ../index.php");

        break;

        case "confirmar":

            if(isset($_SESSION["accionBD"])){
                $id_calendario = '';

                foreach($_SESSION['vacunacion_disponible'] as $dato){
                    if($dato['Acronimo'] == $_SESSION['row_datos_temp']['VacunaRef']){
                        $id_calendario = $dato['ID'];
                    }
                }

                $fecha_actual = (new DateTime(date("Y-m-d")))->format("Y-m-d");

                if($_SESSION["accionBD"] == "registrar"){
                    insertar_vacunacion($_SESSION['row_datos_temp'], $_SESSION['datos_paciente']['DNI'], $id_calendario, $fecha_actual);
                }

                $datos_log = array(
                    'Tipo' => "tipo_vacunacion",
                    'Fecha' => date("Y-m-d H:i:s"),
                    'Descripcion' => "VACUNACION A USUARIO"
                ); 
    
                insertar_log($datos_log);
            }

            unset($_SESSION['acro_ref_vacunacion_temp']);
            unset($_SESSION['fabric_vacunacion_temp']);
            unset($_SESSION['desc_vacunacion_temp']);
            unset($_SESSION['row_datos_temp']);
            unset($_SESSION['accionPulsadaVacunacion']);
            unset($_SESSION['accionBD']);
            unset($_SESSION['vacunacion_disponible']); // Los datos de la vacuna que hemos añadido a la cartilla de vacunacion
            unset($_SESSION['datos_paciente']); //Los datos del paciente al que le hemos modificado la cartilla de vacunacion

            header("Location: ../index.php");

        break;

    }

?>