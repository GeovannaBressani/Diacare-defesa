<?php
session_start();
header('Content-Type: application/json');

// Configurações do banco
$host = 'localhost';
$dbname = 'diacare';
$username = 'root';
$password = '';

// Verifica autenticação
if (!isset($_SESSION['usuario_id'])) {
    // Para testes, vamos usar um ID fixo se a sessão não existir
    $_SESSION['usuario_id'] = 1; 
    // Em produção, a linha abaixo seria a correta:
    // echo json_encode(['status' => 'error', 'message' => 'Não autorizado']);
    // exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Seleciona as colunas 'tipo_exame' e 'nome_exame'
    $stmt = $pdo->prepare("
        SELECT 
            id,
            DATE_FORMAT(data_exame, '%d/%m/%Y') as data_exame,
            DATE_FORMAT(data_resultado, '%d/%m/%Y') as data_resultado,
            tipo_exame,
            nome_exame,
            resultado,
            referencia
        FROM exames 
        WHERE usuario_id = :usuario_id
        ORDER BY data_exame DESC, id DESC
    ");
    
    $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
    $exames = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'exames' => $exames
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro de banco de dados ao buscar exames.',
        'details' => $e->getMessage()
    ]);
}
?>