<?php
require 'conexao.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Limpa e valida os inputs
        $cpf = !empty($_POST['cpf']) ? str_replace(['.', '-'], '', $_POST['cpf']) : null;
        $email = !empty($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : null;
        $senha = $_POST['password'];

        // Verifica se pelo menos CPF ou E-mail foi preenchido
        if (empty($cpf) && empty($email)) {
            $_SESSION['login_error'] = "Digite CPF ou E-mail";
            header('Location: login.php');
            exit();
        }

        // Busca usuário por CPF OU E-mail
        $sql = "SELECT id, nome, senha FROM usuarios WHERE cpf = :cpf OR email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verifica a senha
            if (password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                header('Location: main.html');
                exit();
            }
        }
        
        // Credenciais inválidas
        $_SESSION['login_error'] = "CPF/E-mail ou senha incorretos";
        header('Location: login.php');
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['login_error'] = "Erro no sistema: " . $e->getMessage();
        header('Location: login.php');
        exit();
    }
}
?>