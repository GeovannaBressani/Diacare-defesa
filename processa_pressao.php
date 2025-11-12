<?php
require 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $usuario_id = $_SESSION['usuario_id'];
        $data = $_POST['data'];
        $sistolica = (int)$_POST['sistolica'];
        $diastolica = (int)$_POST['diastolica'];
        $pulso = !empty($_POST['pulso']) ? (int)$_POST['pulso'] : null;

        // Validações
        if (empty($data) || empty($sistolica) || empty($diastolica)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Dados incompletos']);
            exit();
        }

        if ($sistolica < 50 || $sistolica > 250 || $diastolica < 30 || $diastolica > 150) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Valores de pressão inválidos']);
            exit();
        }

        // Inserir dados na tabela pressao
        $sql = "INSERT INTO pressao (usuario_id, data, sistolica, diastolica, pulso) 
                VALUES (:usuario_id, :data, :sistolica, :diastolica, :pulso)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':sistolica', $sistolica);
        $stmt->bindParam(':diastolica', $diastolica);
        $stmt->bindParam(':pulso', $pulso);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Pressão registrada com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Erro ao executar query']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido']);
}
?>