<?php
function getConnection() {
    // CONEXION A LA BBDD
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

$pdo = getConnection();

// Manejo de acciones CRUD
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add'])) {
        // Agregar nueva pregunta
        $pregunta = $_POST['pregunta'];
        $imagen = './img/' . $_POST['imagen'];  // Añadir la parte './img/' a la imagen
        $sql = "INSERT INTO preguntes (pregunta, imatge) VALUES (:pregunta, :imagen)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pregunta' => $pregunta, ':imagen' => $imagen]);
        $id_pregunta = $pdo->lastInsertId();

        // Agregar respuestas
        for ($i = 1; $i <= 4; $i++) {
            if (isset($_POST["resposta$i"])) {
                $resposta = $_POST["resposta$i"];
                $correcta = ($_POST['resposta_correcta'] == $i) ? 1 : 0;
                $sql = "INSERT INTO respostes (id_pregunta, resposta, correcta) VALUES (:id_pregunta, :resposta, :correcta)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id_pregunta' => $id_pregunta, ':resposta' => $resposta, ':correcta' => $correcta]);
            }
        }
    } elseif (isset($_POST['edit'])) {
        // Editar pregunta existente
        $id = $_POST['id'];
        $pregunta = $_POST['pregunta'];
        $imagen = './img/' . $_POST['imagen'];  // Añadir la parte './img/' a la imagen

        $sql = "UPDATE preguntes SET pregunta=:pregunta, imatge=:imagen WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pregunta' => $pregunta, ':imagen' => $imagen, ':id' => $id]);

        // Actualizar respuestas
        for ($i = 1; $i <= 4; $i++) {
            if (isset($_POST["resposta$i"])) {
                $resposta = $_POST["resposta$i"];
                $correcta = ($_POST['resposta_correcta'] == $i) ? 1 : 0;
                $sql = "INSERT INTO respostes (id_pregunta, resposta, correcta) VALUES (:id_pregunta, :resposta, :correcta)
                        ON DUPLICATE KEY UPDATE resposta=:resposta, correcta=:correcta";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id_pregunta' => $id, ':resposta' => $resposta, ':correcta' => $correcta]);
            }
        }
    } elseif (isset($_POST['delete'])) {
        // Eliminar pregunta
        $id = $_POST['id'];
        $sql = "DELETE FROM respostes WHERE id_pregunta=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $sql = "DELETE FROM preguntes WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
}

// Obtener todas las preguntas con respuestas
$sql = "SELECT p.id, p.pregunta, p.imatge, r.id AS respuesta_id, r.resposta, r.correcta 
        FROM preguntes p LEFT JOIN respostes r ON p.id = r.id_pregunta";
$result = $pdo->query($sql);

$preguntas = [];
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $preguntas[$row['id']]['pregunta'] = $row['pregunta'];
    $preguntas[$row['id']]['imatge'] = $row['imatge'];
    $preguntas[$row['id']]['respuestas'][] = [
        'id' => $row['respuesta_id'],
        'respuesta' => $row['resposta'],
        'correcta' => $row['correcta']
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/estils.css">
    <title>Gestión de Preguntas</title>
</head>
<body id="gestor">
    <h1>Gestión de Preguntas</h1>

    <h2 class="subrayado">Añadir Nueva Pregunta</h2>
    <form method="POST" action="" class="form-container">
        <input type="text" name="pregunta" placeholder="Pregunta" required>
        <input type="text" name="imagen" placeholder="Nombre de Imagen (ej. nombreimg.jpeg)" required>
        <input type="text" name="resposta1" placeholder="Respuesta 1" required>
        <input type="text" name="resposta2" placeholder="Respuesta 2" required>
        <input type="text" name="resposta3" placeholder="Respuesta 3" required>
        <input type="text" name="resposta4" placeholder="Respuesta 4" required>
        <select name="resposta_correcta" required>
            <option value="">Seleccionar Respuesta Correcta</option>
            <option value="1">Respuesta 1</option>
            <option value="2">Respuesta 2</option>
            <option value="3">Respuesta 3</option>
            <option value="4">Respuesta 4</option>
        </select>
        <button type="submit" name="add">Agregar Pregunta</button>
    </form>

    <h2 class="subrayado">Lista de Preguntas</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Pregunta</th>
            <th>Imagen</th>
            <th>Respuestas</th>
            <th>Acciones</th>
        </tr>
        <?php foreach ($preguntas as $id => $pregunta) { ?>
        <tr>
            <td><?php echo $id; ?></td>
            <td><?php echo htmlspecialchars($pregunta['pregunta']); ?></td>
            <td><?php echo htmlspecialchars($pregunta['imatge']); ?></td>
            <td>
                <?php foreach ($pregunta['respuestas'] as $respuesta) {
                    echo htmlspecialchars($respuesta['respuesta']) . " (Correcta: " . ($respuesta['correcta'] ? "Sí" : "No") . ")<br>";
                } ?>
            </td>
            <td>
                <form method="POST" action="" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <button type="submit" name="delete">Eliminar</button>
                </form>
                <button onclick="editQuestion(<?php echo $id; ?>, '<?php echo addslashes($pregunta['pregunta']); ?>', '<?php echo addslashes(substr($pregunta['imatge'], 6)); ?>', '<?php echo addslashes($pregunta['respuestas'][0]['respuesta']); ?>', '<?php echo addslashes($pregunta['respuestas'][1]['respuesta']); ?>', '<?php echo addslashes($pregunta['respuestas'][2]['respuesta']); ?>', '<?php echo addslashes($pregunta['respuestas'][3]['respuesta']); ?>', <?php echo $pregunta['respuestas'][0]['correcta']; ?>)">Editar</button>
            </td>
        </tr>
        <?php } ?>
    </table>

    <script>
        function editQuestion(id, pregunta, imagen, resposta1, resposta2, resposta3, resposta4, resposta_correcta) {
            document.querySelector('input[name="id"]').value = id;
            document.querySelector('input[name="pregunta"]').value = pregunta;
            document.querySelector('input[name="imagen"]').value = imagen;
            document.querySelector('input[name="resposta1"]').value = resposta1;
            document.querySelector('input[name="resposta2"]').value = resposta2;
            document.querySelector('input[name="resposta3"]').value = resposta3;
            document.querySelector('input[name="resposta4"]').value = resposta4;
            document.querySelector('select[name="resposta_correcta"]').value = resposta_correcta;
        }
    </script>
</body>
</html>

<?php
$pdo = null;
?>
