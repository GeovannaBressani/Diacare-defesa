<?php
require 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

try {
    $usuario_id = $_SESSION['usuario_id'];
    
    $sql = "SELECT id, data, sistolica, diastolica, pulso 
            FROM pressao 
            WHERE usuario_id = :usuario_id 
            ORDER BY data DESC, criado_em DESC 
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($dados);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar dados: ' . $e->getMessage()]);
}
?>