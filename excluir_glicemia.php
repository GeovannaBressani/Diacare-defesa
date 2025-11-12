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
        $id = $_POST['id'];

        // Verificar se o registro pertence ao usuário
        $sqlVerifica = "SELECT id FROM glicemia WHERE id = :id AND usuario_id = :usuario_id";
        $stmtVerifica = $conn->prepare($sqlVerifica);
        $stmtVerifica->bindParam(':id', $id);
        $stmtVerifica->bindParam(':usuario_id', $usuario_id);
        $stmtVerifica->execute();
        
        if ($stmtVerifica->rowCount() == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Registro não encontrado']);
            exit();
        }

        $sql = "DELETE FROM glicemia WHERE id = :id AND usuario_id = :usuario_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':usuario_id', $usuario_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
            exit();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir']);
            exit();
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}
?>