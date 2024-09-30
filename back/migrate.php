<?php
//CONEXION A LA BBDD
$host = 'localhost';
$dbname = 'peliculas';
$username = 'root';  
$password = '';      

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

$jsonFile = '/opt/lampp/htdocs/tr0-2024-2025-un-munt-de-preguntes-a19pabmatpav/preguntes.json';

insertarPreguntasDesdeJson($jsonFile, $pdo);

function insertarPreguntasDesdeJson($jsonFile, $pdo) {
    //Comprobacion BBDD
    $sqlComprobar = "SELECT COUNT(*) FROM preguntes";
    $stmt = $pdo->prepare($sqlComprobar);
    $stmt->execute();
    $numPreguntas = $stmt->fetchColumn();

    if ($numPreguntas > 0) {
        echo "La base de datos ya contiene preguntas. No se insertaron nuevos datos.";
        return; 
    }

    $jsonData = file_get_contents($jsonFile);
    $data = json_decode($jsonData, true);
    
    if (isset($data['preguntes'])) {
        foreach ($data['preguntes'] as $pregunta) {
            $sqlPregunta = "INSERT INTO preguntes (pregunta, imatge) VALUES (:pregunta, :imatge)";
            $stmt = $pdo->prepare($sqlPregunta);
            $stmt->execute([
                ':pregunta' => $pregunta['pregunta'],
                ':imatge' => $pregunta['imatge']
            ]);

            $idPregunta = $pdo->lastInsertId();

            foreach ($pregunta['respostes'] as $respuesta) {
                $sqlRespuesta = "INSERT INTO respostes (id_pregunta, resposta, correcta) VALUES (:id_pregunta, :resposta, :correcta)";
                $stmt = $pdo->prepare($sqlRespuesta);
                $stmt->execute([
                    ':id_pregunta' => $idPregunta,
                    ':resposta' => $respuesta['resposta'],
                    ':correcta' => $respuesta['correcta'] ? 1 : 0 //Operacio ternaria per convertir boolean a tinyInt(mysql)
                ]);
            }
        }
        echo "Preguntas y respuestas insertadas correctamente.";
    } else {
        echo "No se encontraron preguntas en el archivo JSON.";
    }
}
?>