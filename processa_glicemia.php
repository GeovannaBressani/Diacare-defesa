<?php
require 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $usuario_id = $_SESSION['usuario_id'];
        $data = $_POST['data'];
        $valor_glicemia = (float)$_POST['glicemia'];
        $periodo = $_POST['periodo'];

        // Validações adicionais
        if ($valor_glicemia < 20 || $valor_glicemia > 600) {
            echo json_encode(['status' => 'error', 'message' => 'Valor de glicemia inválido']);
            exit();
        }

        $sql = "INSERT INTO glicemia (usuario_id, data, valor_glicemia, periodo) 
                VALUES (:usuario_id, :data, :valor_glicemia, :periodo)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':valor_glicemia', $valor_glicemia);
        $stmt->bindParam(':periodo', $periodo);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
            exit();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao registrar']);
            exit();
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}
?>