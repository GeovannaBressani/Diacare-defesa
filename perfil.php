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

// Busca dados do usuário
$stmt_usuario = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt_usuario->execute([$usuario_id]);
$usuario = $stmt_usuario->fetch();

// Busca dados de anamnese
$stmt_anamnese = $conn->prepare("SELECT * FROM anamnese WHERE usuario_id = ? ORDER BY data DESC LIMIT 1");
$stmt_anamnese->execute([$usuario_id]);
$anamnese = $stmt_anamnese->fetch();

// Função para formatar CPF
function formatarCPF($cpf) {
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil | DiaCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --text-color: #333;
            --light-gray: #f8f9fa;
            --border-color: #ddd;
        }
        
       body {
  font-family: Arial, sans-serif;
  background-color: #b3d4fc; /* Azul claro */
  background-image: radial-gradient(#ffffff 1px, transparent 1px);
  background-size: 20px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-height: 100vh;
  margin: 0;
}
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .profile-section {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .profile-card {
            flex: 1;
            min-width: 300px;
            padding: 25px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .profile-card h2 {
            color: var(--primary-color);
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-gray);
        }
        
        .profile-field {
            margin: 15px 0;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .profile-field strong {
            color: #555;
        }
        
        .edit-form {
            display: none;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: 16px;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-edit {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-edit:hover {
            background: #3a5ccc;
        }
        
        .btn-save {
            background: var(--success-color);
            color: white;
        }
        
        .btn-save:hover {
            background: #17a673;
        }
        
        .btn-cancel {
            background: var(--danger-color);
            color: white;
            margin-left: 10px;
        }
        
        .btn-cancel:hover {
            background: #c23a2e;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input, 
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .button-group {
            margin-top: 30px;
            text-align: right;
        }
        
        .info-text {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
 

.botao-voltar {
  background-color: #007acc;   /* Fundo azul */
  color: white;                /* Texto branco */
  border: none;                /* Sem borda */
  border-radius: 5px;          /* Bordas arredondadas */
  padding: 10px 20px;          /* Espaçamento interno */
  font-size: 1rem;             /* Tamanho da fonte */
  cursor: pointer;             /* Cursor ao passar */
  position: fixed;             /* Fixa na tela */
  bottom: 60px;                /* Ajustado para cima do rodapé */
  right: 20px;                 /* Distância da direita */
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Sombra suave */
  transition: background-color 0.3s;  /* Efeito hover */
}

.botao-voltar:hover {
  background-color: #005fa3;  /* Cor mais escura ao passar o mouse */
}


        @media (max-width: 768px) {
            .profile-card {
                min-width: 100%;
            }
            
            .button-group {
                text-align: center;
            }
            
            .btn-cancel {
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        <button onclick="window.history.back()" class="botao-voltar">
            <i class="fas fa-arrow-left"></i> Voltar
        </button>
        
        <h1><i class="fas fa-user-circle"></i> Meu Perfil</h1>
        
        <div id="view-mode" class="profile-section">
            <div class="profile-card">
                <h2>Informações Pessoais</h2>
                <div class="profile-field">
                    <strong>Nome:</strong> <?= htmlspecialchars($usuario['nome'] ?? 'Não informado') ?>
                </div>
                <div class="profile-field">
                    <strong>CPF:</strong> <?= !empty($usuario['cpf']) ? formatarCPF($usuario['cpf']) : 'Não informado' ?>
                </div>
                <div class="profile-field">
                    <strong>Email:</strong> <?= htmlspecialchars($usuario['email'] ?? 'Não informado') ?>
                </div>
                <div class="profile-field">
                    <strong>Telefone:</strong> <?= htmlspecialchars($usuario['telefone'] ?? 'Não informado') ?>
                </div>
            </div>

            <div style="width: 100%; text-align: center;">
                <button id="edit-btn" class="btn btn-edit" onclick="enableEdit()">
                    <i class="fas fa-edit"></i> Editar Perfil
                </button>
            </div>
        </div>

        <div id="edit-mode" class="edit-form">
            <form id="profile-form" action="processa_perfil.php" method="post">
                <div class="profile-section">
                    <div class="profile-card">
                        <h2>Informações Pessoais</h2>
                        <div class="form-group">
                            <label for="nome">Nome:</label>
                            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="cpf">CPF:</label>
                            <input type="text" id="cpf" name="cpf" 
                                   value="<?= !empty($usuario['cpf']) ? formatarCPF($usuario['cpf']) : '' ?>" 
                                   pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" 
                                   placeholder="000.000.000-00"
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="telefone">Telefone:</label>
                            <input type="tel" id="telefone" name="telefone" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="senha">Nova Senha (opcional):</label>
                            <input type="password" id="senha" name="senha" placeholder="Deixe em branco para manter">
                            <p class="info-text">Preencha apenas se desejar alterar a senha</p>
                        </div>
                    </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-save">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <button type="button" class="btn btn-cancel" onclick="cancelEdit()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2024 DiaCare. Todos os direitos reservados.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dados do usuário:', <?= json_encode($usuario) ?>);
            console.log('Dados de anamnese:', <?= json_encode($anamnese) ?>);
            
            if (!<?= json_encode(isset($usuario)) ?> || !<?= json_encode(isset($anamnese)) ?>) {
                alert('Atenção: Alguns dados não foram carregados corretamente. Recarregue a página.');
            }
        });

        function enableEdit() {
            document.getElementById('view-mode').style.display = 'none';
            document.getElementById('edit-mode').style.display = 'block';
        }

        function cancelEdit() {
            document.getElementById('view-mode').style.display = 'block';
            document.getElementById('edit-mode').style.display = 'none';
        }

        // Formatação automática do CPF
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 3) value = value.replace(/^(\d{3})/, '$1.');
            if (value.length > 7) value = value.replace(/^(\d{3})\.(\d{3})/, '$1.$2.');
            if (value.length > 11) value = value.replace(/^(\d{3})\.(\d{3})\.(\d{3})/, '$1.$2.$3-');
            e.target.value = value.substring(0, 14);
        });

        document.getElementById('profile-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('processa_perfil.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na rede');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Perfil atualizado com sucesso!');
                    window.location.reload();
                } else {
                    alert('Erro: ' + (data.message || 'Não foi possível atualizar o perfil'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro na comunicação com o servidor. Por favor, tente novamente.');
            });
        });
    </script>
</body>
</html>