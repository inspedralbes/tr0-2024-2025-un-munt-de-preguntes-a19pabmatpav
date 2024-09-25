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

session_start();

function cargarPreguntes() {
    global $jsonFile;
    $jsonData = file_get_contents($jsonFile);
    return json_decode($jsonData, true);
}

function prepararPreguntes() {
    $data = cargarPreguntes();
    $preguntas = $data['preguntes'];

    shuffle($preguntas);

    foreach ($preguntas as &$pregunta) {
        shuffle($pregunta['respostes']);
    }

    $_SESSION['preguntas'] = $preguntas;
    return $preguntas;
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
