<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DiaCare - Login</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .error-message {
      color: red;
      margin: 10px 0;
      text-align: center;
      display: <?= isset($_SESSION['login_error']) ? 'block' : 'none' ?>;
    }
    .divider {
      display: block; 
      text-align: center; 
      margin: 10px 0;
      color: #666;
    }
  </style>
</head>
<body>
  <header class="header">
    <div class="logo-container">
      <h1>DiaCare</h1>
      <div class="heart"></div>
    </div>
  </header>

  <main class="content">
    <h2>Login</h2>
    
    <div class="error-message">
      <?= $_SESSION['login_error'] ?? '' ?>
    </div>
    
    <form class="form" action="processa_login.php" method="POST">
      <!-- Campo CPF com máscara -->
      <label for="cpf">CPF:</label>
      <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}">
      
      <span class="divider">OU</span>
      
      <!-- Campo E-mail -->
      <label for="email">E-mail:</label>
      <input type="email" id="email" name="email" placeholder="Digite seu e-mail">

      <!-- Campo Senha -->
      <label for="password">Senha:</label>
      <input type="password" id="password" name="password" required placeholder="Digite sua senha">

      <button type="submit" class="form-button">Entrar</button>

      <p class="redirect">
        Não tem uma conta? <a href="cadastro.html">Cadastre-se aqui</a>.
      </p>
     
    </form>
  </main>

  <button class="botao-voltar" onclick="window.history.back()">Voltar</button>

  <footer class="footer">
    <p>&copy; 2024 DiaCare. Todos os direitos reservados.</p>
  </footer>

  <script>
    // Máscara automática para CPF
    document.getElementById('cpf').addEventListener('input', function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length > 3) value = value.replace(/^(\d{3})/, '$1.');
      if (value.length > 7) value = value.replace(/^(\d{3})\.(\d{3})/, '$1.$2.');
      if (value.length > 11) value = value.replace(/^(\d{3})\.(\d{3})\.(\d{3})/, '$1.$2.$3-');
      e.target.value = value.substring(0, 14);
    });
  </script>

  <?php
  unset($_SESSION['login_error']);
  ?>
</body>

</html>
