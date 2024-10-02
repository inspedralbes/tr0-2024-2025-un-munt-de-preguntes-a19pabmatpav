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
        return $pdo;
    } catch (PDOException $e) {
        die("Error en la conexión: " . $e->getMessage());
    }
}

session_start();

function cargarPreguntes($numTotalPreguntes, $numTotalRespostes) {
    $pdo = getConnection();
    $sql = "SELECT id, pregunta, imatge FROM preguntes ORDER BY RAND() LIMIT :numTotalPreguntes"; 

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':numTotalPreguntes', $numTotalPreguntes, PDO::PARAM_INT); 

    $stmt->execute(); 

    return $stmt->fetchAll(PDO::FETCH_ASSOC); 
}


function prepararPreguntes($numeroPreguntas) {
    $numTotalPreguntes = $numeroPreguntas;
    $numTotalRespostes = 4;
    $preguntas = cargarPreguntes($numTotalPreguntes, $numTotalRespostes);
    
    $preguntasConRespuestas = [];
    $pdo = getConnection();
    foreach ($preguntas as $pregunta) {
        $idPregunta = $pregunta['id'];
        $preguntaImagen = $pregunta['imatge'];

        
        $sqlRespuestas = "SELECT resposta FROM respostes WHERE id_pregunta = :id_pregunta ORDER BY RAND() LIMIT :numTotalRespostes";
        $stmt = $pdo->prepare($sqlRespuestas);
        $stmt->bindParam(':id_pregunta', $idPregunta, PDO::PARAM_INT);
        $stmt->bindParam(':numTotalRespostes', $numTotalRespostes, PDO::PARAM_INT);
        $stmt->execute();
        $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Respuestas en un formato simple
        $respuestasList = array_map(function($respuesta) {
            return $respuesta['resposta'];
        }, $respuestas);

        $preguntasConRespuestas[] = [
            'id_pregunta' => $idPregunta,
            'pregunta' => $pregunta['pregunta'],
            'imatge' => $preguntaImagen,
            'respostes' => $respuestasList
        ];
    }

    shuffle($preguntasConRespuestas);
    foreach ($preguntasConRespuestas as &$pregunta) {
        shuffle($pregunta['respostes']);
    }
    
    $_SESSION['preguntas'] = $preguntasConRespuestas;
    return $preguntasConRespuestas;
    EnviarPreguntes();
}


function EnviarPreguntes() {
    if (isset($_SESSION['preguntas'])) {
        echo $_SESSION['preguntas'];
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
            $cantidad = isset($requestData['cantidad']) ? $requestData['cantidad'] : null;
            $preguntas = prepararPreguntes($cantidad);
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
