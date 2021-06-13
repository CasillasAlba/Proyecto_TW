<?php
    require_once('./core/db.php');
    
    session_start();

    function filtrar_usuario_by_search($busqueda, $lista_user){
        $user_temp = [];

        if($busqueda != ""){
            
            $ok = false;
            $busqueda = $_POST['search'];

            for($i = 0; $i < count($lista_user) and !$ok; $i++){
                
                $u = $lista_user[$i]; // Se recoge un usuario
                
                // Si coincide con el DNI es que el usuario ha introducido en la busqueda un DNI
                // Y entonces la búsqueda finaliza (OK = TRUE)
                if(strcasecmp( $u['DNI'], $busqueda ) == 0){ // Si es 0 SON IGUALES 
                    
                    array_push($user_temp, $u);
                    $ok = true;

                }else{ // Si no es un DNI, se comprueba si ha introducido un nombre completo

                    $n = $u['Nombre']; // Se recoge el nombre del usuario analizado
                    $a = $u['Apellidos']; // Se recoge el apellido del usuario analizado
                    $nomCompleto = $n . ' ' . $a; // Se concatena formando algo del estilo Nombre Apellidos
                    
                    // Si el nombre completo coincide con lo que se ha introducido en el buscador
                    // Se añade el usuario al array y la búsqueda finaliza (OK = TRUE)
                    if(strcasecmp( $nomCompleto, $busqueda ) == 0){ 
                        array_push($user_temp, $u); // Ha introducido un nombre completo y ya no merece la pena seguir buscando
                        $ok = true;
                    
                    }else{ // Si no ha ocurrido ninguna de las dos cosas anteriores, se realizan búsquedas parciales
                        
                        // Si el nombre (Solo el nombre, sin apellidos) coincide con lo introducido
                        // Se añade el usuario como encontrado, pero la búsqueda continua recorriendo el resto de usuarios que queden
                        if(strcasecmp( $n, $busqueda ) == 0){
                            
                            array_push($user_temp, $u);

                        }else{ // Si el nombre no coincide
                            
                            // Se piensa que quizás haya introducido un nombre compuesto (Francisco Javier)
                            // Se divide el nombre por espacios
                            $nom = explode(' ', $n);
                            $nomCoincide = false;
                            
                            // Y por cada valor que consiga dividir se comprueba si coincide con lo introducido en el buscador
                            for($ni = 0; $ni < count($nom) and !$nomCoincide; $ni++){

                                if(strcasecmp( $nom[$ni], $busqueda ) == 0){ // Si coincide, se añade al array y se detiene la búsqueda para continuar con el siguiente usuario (nomCoincide = TRUE)

                                    array_push($user_temp, $u);
                                    $nomCoincide = true;
                                }
                            }

                            // Si no se ha encontrado que el nombre dividido por espacios sea lo del buscador, se comprueba el apellido completo
                            if(!$nomCoincide){

                                if(strcasecmp( $a, $busqueda ) == 0){
                                    array_push($user_temp, $u); // Se añade si coincide el apellido completo
                                
                                }else{

                                    $ape = explode(' ', $a); // De nuevo el apellido se divide
                                    $apellidoCoincide = false;
        
                                    for($ai = 0; $ai < count($ape) and !$apellidoCoincide; $ai++){
        
                                        if(strcasecmp( $ape[$ai], $busqueda ) == 0){ // Si alguna parte del apellido coincide se guarda el usuario y se continua con el siguiente usuario
        
                                            array_push($user_temp, $u);
                                            $apellidoCoincide = true;
                                        }
                                    }
                                }
                            }
                        }

                    }
                }
            }

            return $user_temp;
        }


    }

    function filtrar_usuario_by_activo($lista_user){
        
        $us_temp_estado = [];

        foreach($lista_user as $fn){

            if($fn['Estado'] == "Activo"){
                array_push($us_temp_estado, $fn);
            }

        }

        return $us_temp_estado;
    }

    function filtrar_usuario_by_inactivo($lista_user){
        
        $us_temp_estado = [];

        foreach($lista_user as $fn){

            if($fn['Estado'] == "Inactivo"){
                array_push($us_temp_estado, $fn);
            }

        }

        return $us_temp_estado;
    }

    function filtrar_usuario_by_fechas($lista_user, $fecha_ini, $fecha_fin){
        
        $us_temp_fechas = [];

        foreach($lista_user as $fn){

            if( $fn['FechaNac'] >= $fecha_ini and $fn['FechaNac'] <= $fecha_fin){
                array_push($us_temp_fechas, $fn);
            }

        }

        return $us_temp_fechas;
    }

    function mayor_menor_comparador($a, $b){
        // Porque si la fecha es MAYOR, entonces eres más pequeño
        return $a['FechaNac'] > $b['FechaNac'];
    }

    function menor_mayor_comparador($a, $b){
        // Porque si la fecha es MAYOR, entonces eres más pequeño
        return $a['FechaNac'] < $b['FechaNac'];
    }

    function ascendente_comparador($a, $b){
        // Alfabéticamente de menor a mayor (De Z a A)
        return strcasecmp($a['Apellidos'], $b['Apellidos']);
    }

    function descendente_comparador($a, $b){
        // Alfabéticamente de mayor a menor (De A a Z)
        return strcasecmp($b['Apellidos'], $a['Apellidos']);
    }


    // Se le pasa la lista por referencia, para que usort modifique la original
    function ordernar_by_edad(&$lista, $orden){
        
        if($orden == 1){ // MAYOR A MENOR
            usort($lista, "mayor_menor_comparador");
        
        }else{ // MENOR A MAYOR
            usort($lista, "menor_mayor_comparador");
        }
    }

    // Se le pasa la lista por referencia, para que usort modifique la original
    function ordernar_by_name(&$lista, $orden){

        if($orden == "ASC"){ // POR NOMBRE DE FORMA ASCENDENTE
            usort($lista, "ascendente_comparador");
        
        }else{ // POR NOMBRE DE FORMA DESCENDENTE
            usort($lista, "descendente_comparador");
        }

    }
    

    function usuarios_vacunas_pendientes($lista_users){

        $pacientes_pendientes = [];

        foreach($lista_users as $u){

            $edad_m = calcular_edad($u['FechaNac']);

            $pendientes = devolver_vacunacion_pendiente($u['Sexo'], $edad_m, $u['DNI']);

            if(count($pendientes) > 0){
                array_push($pacientes_pendientes, $u);
            }

        }

        return $pacientes_pendientes;

    }


    function vacunas_pendientes_usuario_n_meses($dni, $nac, $sex, $n_meses){

        $vacunas_n_meses = [];

        $edad_m = calcular_edad($nac);
       
        $vacunas_n_pendientes = devolver_vacunacion_pendiente_n_meses($sex, $edad_m, $dni);

        foreach($vacunas_n_pendientes as $vac){

            $diferencia = $vac['Meses_ini'] - $edad_m;

            if($n_meses >= $diferencia){
                array_push($vacunas_n_meses, $vac);
            }

        }

        return $vacunas_n_meses;

    }


    $users = [];

    if( isset($_SESSION['lista_usa']) ){

        if( count($_SESSION['lista_us'] > 0) ){
            
            $users = $_SESSION['lista_us'];
      
            if(isset($_POST['search'])) { // Si existe búsqueda, las demás opciones de filtrado se hacen sobre la lista de usuarios ya obtenida
                
                $filtrado_name = filtrar_usuario_by_search($_POST['search'], $users);

                // Si existe ACTIVO checked pero no INACTIVO
                if( isset($_POST['activo']) and !(isset($_POST['inactivo'])) ) {
                    
                    $us_temp_estado = filtrar_usuario_by_activo($filtrado_name);

                }else if( !(isset($_POST['activo'])) and isset($_POST['inactivo']) ) {
                    
                    $us_temp_estado = filtrar_usuario_by_inactivo($filtrado_name);

                
                }// No se hace una búsqueda de ACTIVO e INACTIVO porque no se modificaria la lista, ya que los usuarios solo pueden estar ACTIVOS o INACTIVOS
                

                // QUE HAYA O NO VACUNAS PENDIENTES
                if(isset($_POST['pendiente'])){
                    
                    
                }else{

                }

                // VACUNAS EN LAS PROXIMAS SEMANAS
                if(isset($_POST['vacuna_x_semanas'])){
                    
                    
                }

            // FIN DEL SEARCHBOX
            }else if( isset($_POST['activo']) and !(isset($_POST['inactivo'])) ) {

                $us_temp_estado = filtrar_usuario_by_activo($users);

                // QUE HAYA O NO VACUNAS PENDIENTES
                if(isset($_POST['pendiente'])){
                    
                    
                }else{
                    
                }

                // VACUNAS EN LAS PROXIMAS SEMANAS
                if(isset($_POST['vacuna_x_semanas'])){
                    
                    
                }

            }else if( !(isset($_POST['activo'])) and isset($_POST['inactivo']) ) {

                $us_temp_estado = filtrar_usuario_by_inactivo($users);

                // QUE HAYA O NO VACUNAS PENDIENTES
                if(isset($_POST['pendiente'])){
                    
                    
                }
                

                // VACUNAS EN LAS PROXIMAS SEMANAS
                if(isset($_POST['vacuna_x_semanas'])){
                    
                    
                }

            
            // FIN DE LOS CHECKBOX
            }else if(isset($_POST['pendiente'])){
                    
                    
                    
            }else if( isset($_POST['vacuna_x_semanas']) ){ // YA SOLO QUEDARÍA VACUNAS POR SEMANAS


            }
        
        
        } // FIN DEL COUNT()

    }else{
        $users = $_SESSION['lista_us'];


        echo "Hay " . count(usuarios_vacunas_pendientes($users)) . " con vacunas pendientes";
        

    } // FIN DEL ISSET
    


    

    if(isset($_POST['vacuna_x_semanas'])){
        
        if($_POST['vacuna_x_semanas'] != ""){
            echo "VACUNA X SEMANA EXISTE | ";
        }
    }


?>