<?php
require 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID não fornecido']);
    exit();
}

try {
    $usuario_id = $_SESSION['usuario_id'];
    $registro_id = (int)$_GET['id'];
    
    $sql = "SELECT id, data, sistolica, diastolica, pulso 
            FROM pressao 
            WHERE id = :id AND usuario_id = :usuario_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $registro_id);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($registro) {
        echo json_encode($registro);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Registro não encontrado']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar registro: ' . $e->getMessage()]);
}
?>