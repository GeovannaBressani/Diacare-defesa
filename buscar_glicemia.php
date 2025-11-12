<?php
require 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit();
}

try {
    $usuario_id = $_SESSION['usuario_id'];
    $sql = "SELECT id, data, valor_glicemia, periodo FROM glicemia 
            WHERE usuario_id = :usuario_id 
            ORDER BY data DESC, id DESC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // CORREÇÃO: Garantir que as datas estejam no formato correto
    foreach ($resultados as &$resultado) {
        // Se a data veio do banco como string, formatar corretamente
        $data = $resultado['data'];
        if (is_string($data)) {
            // Converter para objeto DateTime e depois para o formato desejado
            $dateTime = DateTime::createFromFormat('Y-m-d', $data);
            if ($dateTime) {
                $resultado['data'] = $dateTime->format('Y-m-d');
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($resultados);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>