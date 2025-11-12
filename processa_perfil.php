<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$response = ['success' => false, 'message' => ''];

try {
    $conn->beginTransaction();
    
    // Remove formatação do CPF
    $cpf = str_replace(['.', '-'], '', $_POST['cpf']);
    
    // Atualizar dados do usuário
    if (!empty($_POST['senha'])) {
        $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, cpf = ?, senha = ? WHERE id = ?");
        $stmt->execute([
            $_POST['nome'],
            $_POST['email'],
            $_POST['telefone'],
            $cpf,
            $senha_hash,
            $usuario_id
        ]);
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, cpf = ? WHERE id = ?");
        $stmt->execute([
            $_POST['nome'],
            $_POST['email'],
            $_POST['telefone'],
            $cpf,
            $usuario_id
        ]);
    }
    
    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Dados atualizados com sucesso!';
} catch (PDOException $e) {
    $conn->rollBack();
    $response['message'] = 'Erro ao atualizar dados: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);