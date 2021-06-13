<?php
    // Realizamos conexión a base de datos
    require_once('db_credenciales.php');
    $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWD, DB_DATABASE);

    /* Volcado completo de la base de datos */
    function DB_backup() {
        global $db;

        $tablas = array(); // Obtener listado de tablas
        $result = mysqli_query($db, 'SHOW TABLES');
        while($row = mysqli_fetch_row($result))
            $tablas[] = $row[0];
        // Salvar cada tabla
        $salida = '';
        foreach ($tablas as $tab) {
            $result = mysqli_query($db, 'SELECT * FROM '.$tab);
            $num = mysqli_num_fields($result);
            $salida .= 'DROP TABLE IF EXISTS '.$tab.';';
            $row2 = mysqli_fetch_row(mysqli_query($db, 'SHOW CREATE TABLE '.$tab));
            $salida .= "\n\n".$row2[1].";\n\n";
            while ($row = mysqli_fetch_row($result)) {
                $salida .= 'INSERT INTO '.$tab.' VALUES(';
                for ($j=0; $j < $num; $j++) {
                    if (!is_null($row[$j])) {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = preg_replace("/\n/","\\n",$row[$j]);
                        if (isset($row[$j]))  $salida .= '"'.$row[$j].'"';
                        else  $salida .= '""';
                    } else  $salida .= 'NULL';
                    if ($j < ($num-1))    $salida .= ',';
                }
                $salida .= ");\n";
            }
            $salida .= "\n\n\n";
        }
        return $salida;
    }

    /* Restauración completa de la base de datos */
    function DB_restore($f) {
        global $db;

        mysqli_query($db,'SET FOREIGN_KEY_CHECKS=0');
        DB_delete($db);
        $error = [];
        $sql = file_get_contents($f);
        $queries = explode(';',$sql);
        foreach ($queries as $q) {
            $q = trim($q);
            if ($q!='' and !mysqli_query($db,$q))
                $error .= mysqli_error($db);
        }
        mysqli_commit($db);
        mysqli_query($db,'SET FOREIGN_KEY_CHECKS=1');
        return $error;
    }

    /* Borrar el contenido de las tablas de la base de datos */
    /*function DB_delete($db) {
        $result = mysqli_query($db,'SHOW TABLES');
        while ($row = mysqli_fetch_row($result))
            mysqli_query($db,'DELETE FROM '.$row[0]);
        mysqli_commit($db);
    }*/

    function DB_delete(){
        global $db;

        // Las tablas hay que borrarlas en el orden correcto
        // Ya que existen dependencias de FOREIGN KEY
        // ANTES de borrar Usuarios o Calendario, hay que borrar vacunacion
        // Y ANTES de borrar vacunas hay que borrar calendario
        // Para dejar el admin, se eliminan todos los usuarios menos aquel que en su rol tenga ADMIN
        mysqli_query($db,'DELETE FROM vacunacion');
        mysqli_query($db,'DELETE FROM calendario');
        mysqli_query($db,'DELETE FROM vacunas');
        mysqli_query($db,'DELETE FROM log');
        mysqli_query($db, "DELETE FROM usuarios WHERE Rol != 'Admin'");


        mysqli_commit($db);         
    }
?>
