<?php
session_start();
header('Content-Type: application/json');
date_default_timezone_set('America/Sao_Paulo');

// Configurações para debug (remover em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CONEXÃO COM O BANCO
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'diacare';

// Verifica autenticação
if (!isset($_SESSION['usuario_id'])) {
    // Para testes, vamos usar um ID fixo se a sessão não existir
    $_SESSION['usuario_id'] = 1;
    // Em produção, a linha abaixo seria a correta:
    // http_response_code(401); // Unauthorized
    // echo json_encode(['status' => 'error', 'message' => 'Não autorizado']);
    // exit;
}
$usuario_id = $_SESSION['usuario_id'];

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro de conexão: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8');

// ROTEADOR DE AÇÕES

$method = $_SERVER['REQUEST_METHOD'];

// AÇÃO DE EXCLUIR (usando GET para simplicidade)
if ($method === 'GET' && isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    $stmt = $conn->prepare("DELETE FROM exames WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id, $usuario_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Exame excluído.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir exame: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// AÇÃO DE INSERIR OU EDITAR (usando POST)
if ($method === 'POST') {
    // Coleta e sanitiza os dados do formulário
    $data_exame = $_POST['data-exame'] ?? '';
    $data_resultado = !empty($_POST['data-resultado']) ? $_POST['data-resultado'] : null;
    $tipo_exame = $_POST['tipo-exame'] ?? '';
    $nome_outro = $_POST['nome-outro'] ?? '';
    $resultado = $_POST['resultado'] ?? '';
    $referencia = $_POST['referencia'] ?? '';

    // Define qual será o nome do exame e qual será a referência
    if ($tipo_exame === 'Outros') {
        $nome_exame_final = $nome_outro;
    } else {
        $nome_exame_final = $tipo_exame;
    }

    // Validação básica
    if (empty($data_exame) || empty($tipo_exame) || ($tipo_exame === 'Outros' && empty($nome_exame_final)) || empty($resultado)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Campos obrigatórios estão faltando.']);
        exit;
    }

    // Se GET 'editar' está setado, é uma atualização
    if (isset($_GET['editar'])) {
        $id = (int)$_GET['editar'];
        $stmt = $conn->prepare("UPDATE exames SET data_exame=?, data_resultado=?, tipo_exame=?, nome_exame=?, resultado=?, referencia=? WHERE id=? AND usuario_id=?");
        $stmt->bind_param("ssssssii", $data_exame, $data_resultado, $tipo_exame, $nome_exame_final, $resultado, $referencia, $id, $usuario_id);
    } 
    // Caso contrário, é uma inserção
    else {
        $stmt = $conn->prepare("INSERT INTO exames (usuario_id, data_exame, data_resultado, tipo_exame, nome_exame, resultado, referencia) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $usuario_id, $data_exame, $data_resultado, $tipo_exame, $nome_exame_final, $resultado, $referencia);
    }
    
    // Executa e retorna a resposta
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro no banco de dados: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

// Se nenhuma ação válida foi encontrada
http_response_code(405); // Method Not Allowed
echo json_encode(['status' => 'error', 'message' => 'Ação não permitida ou inválida.']);
$conn->close();
?>