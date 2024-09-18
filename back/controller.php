<?php
session_start();

// Ruta del archivo JSON que contiene las preguntas
$jsonFile = 'preguntes.json';

// Función para cargar preguntas del archivo JSON
function cargarPreguntes() {
    global $jsonFile;
    $jsonData = file_get_contents($jsonFile);
    return json_decode($jsonData, true);
}

// 1. Preparar las preguntas: selecciona preguntas aleatorias y mezcla las respuestas
function prepararPreguntes() {
    $data = cargarPreguntes();
    $preguntas = $data['preguntes'];

    // Seleccionamos preguntas aleatorias
    shuffle($preguntas);

    // Mezclar las respuestas de cada pregunta
    foreach ($preguntas as &$pregunta) {
        shuffle($pregunta['respostes']);
    }

    // Guardar las preguntas en la sesión para usarlas más adelante
    $_SESSION['preguntas'] = $preguntas;

    return $preguntas;
}

// 2. Enviar las preguntas al cliente
function EnviarPreguntes() {
    if (isset($_SESSION['preguntas'])) {
        echo json_encode($_SESSION['preguntas']);
    } else {
        echo json_encode(['error' => 'No se han preparado las preguntas']);
    }
}

// 3. Corregir las respuestas enviadas por el cliente
function corregirPreguntes($respuestasCliente) {
    $preguntas = $_SESSION['preguntas'];

    foreach ($respuestasCliente as $respuesta) {
        list($preguntaId, $respostaUsuari) = explode("+", $respuesta);

        // Comprobar si la respuesta del cliente es correcta
        if ($preguntas[$preguntaId]['respostaCorrecta'] != $respostaUsuari) {
            return false; // La respuesta es incorrecta
        }
    }

    return true; // Todas las respuestas son correctas
}

// 4. Reinicializar la sesión del cliente
function reinicializarSesion() {
    session_destroy();  // Destruir la sesión actual
    session_start();    // Iniciar una nueva sesión
}

// Lógica para manejar las solicitudes del cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

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
}
?>
