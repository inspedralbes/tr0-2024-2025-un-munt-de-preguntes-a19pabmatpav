<?php
function getConnection(){
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
}
$numTotalPreguntes = 10;
$numTotalRespostes = 4;
session_start();

function cargarPreguntes() {
    $pdo = getConnection();
    $sql = "SELECT id, pregunta, imagen FROM preguntas ORDER BY RAND() LIMIT $numTotalPreguntes"; 
    $stmt = $pdo->query($sql);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function prepararPreguntes() {
    $preguntas = cargarPreguntes();
    
    $preguntasConRespuestas = [];
    
    foreach ($preguntas as $pregunta) {
        $idPregunta = $pregunta['id'];
        $preguntaImagen = $pregunta['imagen'];

        $pdo = getConnection();
        $sqlRespuestas = "SELECT respuesta FROM respuestas WHERE id_pregunta = :id_pregunta ORDER BY RAND() LIMIT $numTotalRespostes";
        $stmt = $pdo->prepare($sqlRespuestas);
        $stmt->execute([':id_pregunta' => $idPregunta]);
        $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Respuestas en un formato simple
        $respuestasList = array_map(function($respuesta) {
            return $respuesta['respuesta'];
        }, $respuestas);

        $preguntasConRespuestas[] = [
            'id' => $idPregunta,
            'pregunta' => $pregunta['pregunta'],
            'imagen' => $preguntaImagen,
            'respuestas' => $respuestasList
        ];
    }

    shuffle($preguntasConRespuestas);
    foreach ($preguntasConRespuestas as &$pregunta) {
        shuffle($pregunta['respuestas']);
    }

    $_SESSION['preguntas'] = $preguntasConRespuestas;
    return $preguntasConRespuestas;
}


function EnviarPreguntes() {
    if (isset($_SESSION['preguntas'])) {
        echo json_encode($_SESSION['preguntas']);
    } else {
        echo json_encode(['error' => 'No se han preparado las preguntas']);
    }
}

function corregirPreguntes($respuestasCliente) {
    $preguntas = $_SESSION['preguntas'];

    if (!is_array($preguntas)) {
        return false;
    }

    foreach ($respuestasCliente as $respuesta) {
        list($preguntaId, $respostaUsuari) = explode("+", $respuesta);
        if (!isset($preguntas[$preguntaId])) {
            return false; 
        }
        if ($preguntas[$preguntaId]['respostaCorrecta'] != $respostaUsuari) {
            return false; 
        }
    }

    return true; 
}


function reinicializarSesion() {
    session_destroy();  
    session_start();
}

// Lógica para manejar las solicitudes del cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = json_decode(file_get_contents("php://input"), true);
    if (isset($postData['action'])) {
        $action = $postData['action'];
    switch ($action) {
        case 'prepararPreguntes':
            $preguntas = prepararPreguntes();
            echo json_encode($preguntas);
            break;

        case 'EnviarPreguntes':
            EnviarPreguntes();
            break;

        case 'corregirPreguntes':
            $respuestasCliente = json_decode(file_get_contents("php://input"), true)['respuestas'];
            $resultado = corregirPreguntes($respuestasCliente);
            echo json_encode(['success' => $resultado]);
            break;

        case 'reinicializarSesion':
            reinicializarSesion();
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
}}
?>
