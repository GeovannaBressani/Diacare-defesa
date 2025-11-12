
<?php
session_start();

// Carrega dados do MySQL se o usuário estiver logado
$dados = [];
if (isset($_SESSION['usuario_id'])) {
    require_once 'conexao.php';
    $stmt = $conn->prepare("SELECT * FROM imc_registros WHERE usuario_id = ? ORDER BY data DESC");
    $stmt->execute([$_SESSION['usuario_id']]);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function classificaIMC($imc) {
    if ($imc < 18.5) return 'Abaixo do peso';
    if ($imc < 25) return 'Peso normal';
    if ($imc < 30) return 'Sobrepeso';
    if ($imc < 35) return 'Obesidade Grau I';
    if ($imc < 40) return 'Obesidade Grau II';
    return 'Obesidade Grau III';
}

function classificaRCQ($rcq, $genero = 'feminino') {
    if (!$rcq) return '-';
    if ($genero === 'masculino') {
        return $rcq > 1.0 ? '<span style="color: #e74c3c;">Risco ↑</span>' : '<span style="color: #27ae60;">Normal</span>';
    } else {
        return $rcq > 0.85 ? '<span style="color: #e74c3c;">Risco ↑</span>' : '<span style="color: #27ae60;">Normal</span>';
    }
}

function classificaRCA($rca) {
    if (!$rca) return '-';
    return $rca > 0.5 ? '<span style="color: #e74c3c;">Risco ↑</span>' : '<span style="color: #27ae60;">Normal</span>';
}

function getCorIMC($imc) {
    if ($imc < 18.5) return '#3498db';       // Azul - Abaixo
    if ($imc < 25) return '#27ae60';         // Verde - Normal
    if ($imc < 30) return '#f39c12';         // Amarelo - Sobrepeso
    if ($imc < 35) return '#e67e22';         // Laranja - Obesidade I
    if ($imc < 40) return '#e74c3c';         // Vermelho - Obesidade II
    return '#c0392b';                        // Vermelho escuro - Obesidade III
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IMC - DiaCare</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="styles.css">
  <style>
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); justify-content: center; align-items: center; z-index: 9999; }
    .modal-content { background: white; padding: 25px; border-radius: 15px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .modal-content h3 { margin-top: 0; color: #007acc; text-align: center; font-size: 1.4em; }
    .modal-content input, .modal-content select { width: 100%; padding: 12px; margin-bottom: 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; }
    .modal-content input:focus, .modal-content select:focus { border-color: #007acc; outline: none; }
    .modal-content button { background: linear-gradient(135deg, #007acc, #005a9e); color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; margin-right: 10px; font-size: 16px; transition: transform 0.2s; }
    .modal-content button:hover { transform: translateY(-2px); }
    .modal-content button[type="button"] { background: linear-gradient(135deg, #95a5a6, #7f8c8d); }
    .close-btn { float: right; color: #e74c3c; font-size: 24px; cursor: pointer; font-weight: bold; }
    
    .fa-edit { color: #007acc; }
    .fa-trash { color: #e74c3c; }
    
    .genero-group { 
        display: flex; 
        gap: 20px; 
        margin-bottom: 20px; 
        background: #f8f9fa; 
        padding: 15px; 
        border-radius: 10px;
        border: 2px solid #e9ecef;
    }
    .genero-group label { 
        display: flex; 
        align-items: center; 
        gap: 8px; 
        font-weight: 600;
        color: #2c3e50;
    }
    .genero-group input[type="radio"] {
        width: 18px;
        height: 18px;
    }
    
    .resultados-adicionais { 
        margin-top: 25px; 
        padding: 20px; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        color: white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .resultados-adicionais h3 {
        margin-top: 0;
        color: white;
        text-align: center;
        font-size: 1.3em;
        margin-bottom: 15px;
    }
    .campo-opcional { 
        color: #7f8c8d; 
        font-style: italic; 
        font-size: 0.9em;
    }
    
    .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    
    .result-card {
        background: white;
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    .result-card .valor {
        font-size: 1.5em;
        font-weight: bold;
        color: #2c3e50;
    }
    
    .result-card .classificacao {
        font-size: 0.9em;
        margin-top: 5px;
    }
    
    .medidas-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
        border-left: 4px solid #007acc;
    }
    
    .medidas-section h4 {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 1.1em;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    .table th {
        background: linear-gradient(135deg, #007acc, #005a9e);
        color: white;
        padding: 15px;
        text-align: left;
        font-weight: 600;
    }
    
    .table td {
        padding: 12px 15px;
        border-bottom: 1px solid #ecf0f1;
    }
    
    .table tr:hover {
        background: #f8f9fa;
    }
    
    .btn-acoes {
        display: flex;
        gap: 8px;
    }
    
    .btn-acoes button, .btn-acoes a {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        border-radius: 5px;
        transition: background 0.3s;
    }
    
    .btn-acoes button:hover, .btn-acoes a:hover {
        background: #ecf0f1;
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

<main>
  <div class="form">
    <h2>📊 Calculadora de IMC e Medidas Corporais</h2>
    
    <div class="medidas-section">
      <h4>📝 Informações Básicas</h4>
      <form action="imc_controller.php?action=salvar" method="POST" id="form-principal">
        <!-- REMOVIDO: campo usuario_id hidden -->
        
        <div class="input-group">
          <label for="data">📅 Data</label>
          <input type="date" name="data" id="data" required value="<?= date('Y-m-d') ?>">
        </div>
        
        <div class="genero-group">
          <label>👤 Gênero:</label>
          <label><input type="radio" name="genero" value="masculino" checked> Masculino</label>
          <label><input type="radio" name="genero" value="feminino"> Feminino</label>
        </div>
        
        <div class="card-grid">
          <div class="input-group">
            <label for="altura">📏 Altura (m)</label>
            <input type="number" step="0.01" name="altura" id="altura" required placeholder="Ex: 1.70">
          </div>
          
          <div class="input-group">
            <label for="peso">⚖️ Peso (kg)</label>
            <input type="number" step="0.1" name="peso" id="peso" required placeholder="Ex: 65">
          </div>
        </div>
    </div>

    <div class="medidas-section">
      <h4>📐 Medidas Adicionais <small class="campo-opcional">(Opcionais)</small></h4>
      <div class="card-grid">
        <div class="input-group">
          <label for="cintura">⭕ Circunferência da Cintura (cm)</label>
          <input type="number" step="0.1" name="cintura" id="cintura" placeholder="Ex: 80">
        </div>

        <div class="input-group">
          <label for="quadril">🔵 Circunferência do Quadril (cm)</label>
          <input type="number" step="0.1" name="quadril" id="quadril" placeholder="Ex: 95">
        </div>
      </div>
    </div>

    <button type="submit" class="form-button" <?= !isset($_SESSION['usuario_id']) ? 'disabled title="Faça login para salvar"' : '' ?>>💾 Calcular e Salvar</button>
    </form>

    <!-- Resultados em tempo real -->
    <div class="resultados-adicionais" id="resultados" style="display: none;">
      <h3>📈 Resultados</h3>
      <div class="card-grid" id="resultados-content"></div>
    </div>

    <h3 style="margin-top: 30px">📋 Histórico de Medidas</h3>
    <table class="table">
      <thead>
        <tr>
          <th>Data</th>
          <th>Altura</th>
          <th>Peso</th>
          <th>IMC</th>
          <th>RCQ</th>
          <th>RCA</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($dados as $item): ?>
          <tr>
            <td><strong><?= htmlspecialchars($item['data']) ?></strong></td>
            <td><?= htmlspecialchars($item['altura']) ?>m</td>
            <td><?= htmlspecialchars($item['peso']) ?>kg</td>
            <td>
              <span style="color: <?= getCorIMC($item['imc']) ?>; font-weight: bold;">
                <?= number_format($item['imc'], 1) ?>
              </span>
              <br><small><?= classificaIMC($item['imc']) ?></small>
            </td>
            <td>
              <?php if ($item['rcq']): ?>
                <strong><?= $item['rcq'] ?></strong>
                <br><small><?= classificaRCQ($item['rcq'], $item['genero'] ?? 'feminino') ?></small>
              <?php else: ?>
                <span class="campo-opcional">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($item['rca']): ?>
                <strong><?= $item['rca'] ?></strong>
                <br><small><?= classificaRCA($item['rca']) ?></small>
              <?php else: ?>
                <span class="campo-opcional">-</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="btn-acoes">
                <button onclick="abrirModal('<?= $item['id'] ?>')" title="Editar"><i class="fas fa-edit"></i></button>
                <button onclick="excluirRegistro('<?= $item['id'] ?>')" title="Excluir"><i class="fas fa-trash"></i></button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- Modal -->
<div id="modal-editar" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="fecharModal()">&times;</span>
    <h3>✏️ Editar Registro</h3>
    <form id="form-editar">
      <input type="hidden" name="id" id="editar-id">
      <label>📅 Data: <input type="date" name="data" id="editar-data" required></label>
      <label>👤 Gênero: 
        <select name="genero" id="editar-genero" required>
          <option value="masculino">Masculino</option>
          <option value="feminino">Feminino</option>
        </select>
      </label>
      <label>📏 Altura (m): <input type="number" step="0.01" name="altura" id="editar-altura" required></label>
      <label>⚖️ Peso (kg): <input type="number" step="0.1" name="peso" id="editar-peso" required></label>
      <label>⭕ Cintura (cm): <input type="number" step="0.1" name="cintura" id="editar-cintura" placeholder="Opcional"></label>
      <label>🔵 Quadril (cm): <input type="number" step="0.1" name="quadril" id="editar-quadril" placeholder="Opcional"></label>
      <div style="text-align: center; margin-top: 20px;">
        <button type="submit">💾 Atualizar</button>
        <button type="button" onclick="fecharModal()">❌ Cancelar</button>
      </div>
    </form>
  </div>
</div>

<a href="main.html" class="botao-voltar">← Voltar</a>
  
<footer class="footer">
  <p>&copy; 2024 DiaCare. Todos os direitos reservados.</p>
</footer>

<script>
// Calcular resultados em tempo real
document.getElementById('form-principal').addEventListener('input', function() {
    calcularResultados();
});

// Submit do formulário principal
document.getElementById('form-principal').addEventListener('submit', function(e) {
    e.preventDefault();
    
    <?php if (!isset($_SESSION['usuario_id'])): ?>
        alert('⚠️ Faça login para salvar registros!');
        return;
    <?php endif; ?>
    
    const formData = new FormData(this);
    
    fetch('imc_controller.php?action=salvar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            alert('✅ ' + data.mensagem);
            location.reload();
        } else {
            alert('❌ ' + (data.erro || 'Erro ao salvar registro'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('❌ Erro de conexão');
    });
});

// Função para excluir registro
function excluirRegistro(id) {
    if (confirm('Tem certeza que deseja excluir este registro?')) {
        fetch('imc_controller.php?action=excluir&id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    alert('✅ ' + data.mensagem);
                    location.reload();
                } else {
                    alert('❌ Erro ao excluir registro');
                }
            });
    }
}

function calcularResultados() {
    const altura = parseFloat(document.getElementById('altura').value);
    const peso = parseFloat(document.getElementById('peso').value);
    const cintura = parseFloat(document.getElementById('cintura').value);
    const quadril = parseFloat(document.getElementById('quadril').value);
    const genero = document.querySelector('input[name="genero"]:checked').value;
    
    let resultadosHTML = '';
    
    if (altura && peso) {
        const imc = peso / (altura * altura);
        const cor = getCorIMC(imc);
        resultadosHTML += `
            <div class="result-card" style="border-top: 4px solid ${cor}">
                <div class="valor" style="color: ${cor}">${imc.toFixed(1)}</div>
                <div class="classificacao">IMC</div>
                <small>${classificarIMC(imc)}</small>
            </div>
        `;
    }
    
    if (cintura) {
        const riscoCintura = (genero === 'masculino') ? (cintura > 94 ? 'Risco ↑' : 'Normal') : (cintura > 80 ? 'Risco ↑' : 'Normal');
        const cor = riscoCintura === 'Risco ↑' ? '#e74c3c' : '#27ae60';
        resultadosHTML += `
            <div class="result-card" style="border-top: 4px solid ${cor}">
                <div class="valor">${cintura} cm</div>
                <div class="classificacao">Cintura</div>
                <small style="color: ${cor}">${riscoCintura}</small>
            </div>
        `;
    }
    
    if (cintura && quadril) {
        const rcq = cintura / quadril;
        const riscoRCQ = (genero === 'masculino') ? (rcq > 1.0 ? 'Risco ↑' : 'Normal') : (rcq > 0.85 ? 'Risco ↑' : 'Normal');
        const cor = riscoRCQ === 'Risco ↑' ? '#e74c3c' : '#27ae60';
        resultadosHTML += `
            <div class="result-card" style="border-top: 4px solid ${cor}">
                <div class="valor">${rcq.toFixed(2)}</div>
                <div class="classificacao">RCQ</div>
                <small style="color: ${cor}">${riscoRCQ}</small>
            </div>
        `;
    }
    
    if (cintura && altura) {
        const alturaCm = altura * 100;
        const rca = cintura / alturaCm;
        const riscoRCA = rca > 0.5 ? 'Risco ↑' : 'Normal';
        const cor = riscoRCA === 'Risco ↑' ? '#e74c3c' : '#27ae60';
        resultadosHTML += `
            <div class="result-card" style="border-top: 4px solid ${cor}">
                <div class="valor">${rca.toFixed(2)}</div>
                <div class="classificacao">RCA</div>
                <small style="color: ${cor}">${riscoRCA}</small>
            </div>
        `;
    }
    
    const resultadosDiv = document.getElementById('resultados');
    const resultadosContent = document.getElementById('resultados-content');
    
    if (resultadosHTML) {
        resultadosContent.innerHTML = resultadosHTML;
        resultadosDiv.style.display = 'block';
    } else {
        resultadosDiv.style.display = 'none';
    }
}

function getCorIMC(imc) {
    if (imc < 18.5) return '#3498db';
    if (imc < 25) return '#27ae60';
    if (imc < 30) return '#f39c12';
    if (imc < 35) return '#e67e22';
    if (imc < 40) return '#e74c3c';
    return '#c0392b';
}

function classificarIMC(imc) {
    if (imc < 18.5) return 'Abaixo do peso';
    if (imc < 25) return 'Peso normal';
    if (imc < 30) return 'Sobrepeso';
    if (imc < 35) return 'Obesidade Grau I';
    if (imc < 40) return 'Obesidade Grau II';
    return 'Obesidade Grau III';
}

// Modal functions
function abrirModal(id) {
  fetch('imc_controller.php?action=editar&id=' + id)
    .then(res => res.json())
    .then(dado => {
      if (dado.erro) {
        alert(dado.erro);
      } else {
        document.getElementById('editar-id').value = dado.id;
        document.getElementById('editar-data').value = dado.data;
        document.getElementById('editar-genero').value = dado.genero || 'masculino';
        document.getElementById('editar-altura').value = dado.altura;
        document.getElementById('editar-peso').value = dado.peso;
        document.getElementById('editar-cintura').value = dado.cintura || '';
        document.getElementById('editar-quadril').value = dado.quadril || '';
        document.getElementById('modal-editar').style.display = 'flex';
      }
    });
}

function fecharModal() {
  document.getElementById('modal-editar').style.display = 'none';
}

document.getElementById('form-editar').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('imc_controller.php?action=atualizar', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.sucesso) {
      location.reload();
    } else {
      alert(data.erro || 'Erro ao atualizar');
    }
  });
});
</script>
</body>
</html>
