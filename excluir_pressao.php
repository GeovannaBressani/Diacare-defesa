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
        $registro_id = (int)$_POST['id'];

        // Verificar se o registro pertence ao usuário
        $sqlVerifica = "SELECT id FROM pressao WHERE id = :id AND usuario_id = :usuario_id";
        $stmtVerifica = $conn->prepare($sqlVerifica);
        $stmtVerifica->bindParam(':id', $registro_id);
        $stmtVerifica->bindParam(':usuario_id', $usuario_id);
        $stmtVerifica->execute();
        
        if (!$stmtVerifica->fetch()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Registro não pertence ao usuário']);
            exit();
        }

        // Excluir registro
        $sql = "DELETE FROM pressao WHERE id = :id AND usuario_id = :usuario_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $registro_id);
        $stmt->bindParam(':usuario_id', $usuario_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Registro excluído com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir registro']);
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