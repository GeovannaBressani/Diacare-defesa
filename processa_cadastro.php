<?php
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $nome = htmlspecialchars($_POST['nome']);
        $data_nascimento = $_POST['data_nascimento'];
        $telefone = htmlspecialchars($_POST['telefone']);
        $cpf = str_replace(['.', '-'], '', $_POST['cpf']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

        // Verifica se email ou CPF já existem
        $verifica = $conn->prepare("SELECT id FROM usuarios WHERE email = :email OR cpf = :cpf");
        $verifica->bindParam(':email', $email);
        $verifica->bindParam(':cpf', $cpf);
        $verifica->execute();

        if ($verifica->rowCount() > 0) {
            echo "Email ou CPF já cadastrados.";
            exit();
        }

        $sql = "INSERT INTO usuarios (nome, data_nascimento, telefone, cpf, email, senha) 
                VALUES (:nome, :data_nascimento, :telefone, :cpf, :email, :senha)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':data_nascimento', $data_nascimento);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha);

        if ($stmt->execute()) {
            session_start();
            $_SESSION['usuario_id'] = $conn->lastInsertId();
            header('Location: login.php');
            exit();
        }
    } catch (PDOException $e) {
        echo "Erro no cadastro: " . $e->getMessage();
        exit();
    }
}
?>