
<?php
session_start();
header('Content-Type: application/json');
require_once 'conexao.php';

$action = $_GET['action'] ?? '';

// Bloqueia ações se não estiver logado
if (in_array($action, ['salvar', 'editar', 'atualizar', 'excluir']) && !isset($_SESSION['usuario_id'])) {
    die(json_encode(['erro' => 'Faça login para realizar esta ação.']));
}

// SALVAR
if ($action === 'salvar') {
    // Verifica se o usuário está logado
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['erro' => 'Faça login para salvar registros.']);
        exit;
    }

    $altura = floatval($_POST['altura'] ?? 0);
    $peso = floatval($_POST['peso'] ?? 0);
    $cintura = floatval($_POST['cintura'] ?? 0);
    $quadril = floatval($_POST['quadril'] ?? 0);
    $genero = $_POST['genero'] ?? '';
    $data = $_POST['data'] ?? date('Y-m-d');
    $usuario_id = $_SESSION['usuario_id'];

    if ($altura > 0 && $peso > 0) {
        $imc = round($peso / ($altura * $altura), 1);
        
        // Novos cálculos
        $rca = ($cintura > 0 && $altura > 0) ? round($cintura / ($altura * 100), 2) : null;
        $rcq = ($cintura > 0 && $quadril > 0) ? round($cintura / $quadril, 2) : null;

        try {
            // Insere no banco de dados SEM o campo ID (auto increment)
            $stmt = $conn->prepare("INSERT INTO imc_registros (data, altura, peso, imc, cintura, quadril, rca, rcq, genero, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$data, $altura, $peso, $imc, $cintura, $quadril, $rca, $rcq, $genero, $usuario_id]);

            echo json_encode(['sucesso' => true, 'mensagem' => 'Registro salvo com sucesso!']);
        } catch (PDOException $e) {
            echo json_encode(['erro' => 'Erro no banco de dados: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['erro' => 'Altura ou peso inválido']);
    }
    exit;
}

// EDITAR
if ($action === 'editar') {
    $id = $_GET['id'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM imc_registros WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $_SESSION['usuario_id']]);
    $dado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dado) {
        echo json_encode($dado);
    } else {
        echo json_encode(['erro' => 'Registro não encontrado']);
    }
    exit;
}

// ATUALIZAR
if ($action === 'atualizar') {
    $id = $_POST['id'] ?? '';
    $data = $_POST['data'] ?? '';
    $altura = floatval($_POST['altura'] ?? 0);
    $peso = floatval($_POST['peso'] ?? 0);
    $cintura = floatval($_POST['cintura'] ?? 0);
    $quadril = floatval($_POST['quadril'] ?? 0);
    $genero = $_POST['genero'] ?? '';

    if ($altura > 0 && $peso > 0) {
        $imc = round($peso / ($altura * $altura), 1);
        
        // Novos cálculos
        $rca = ($cintura > 0 && $altura > 0) ? round($cintura / ($altura * 100), 2) : null;
        $rcq = ($cintura > 0 && $quadril > 0) ? round($cintura / $quadril, 2) : null;

        $stmt = $conn->prepare("UPDATE imc_registros SET data = ?, altura = ?, peso = ?, imc = ?, cintura = ?, quadril = ?, rca = ?, rcq = ?, genero = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$data, $altura, $peso, $imc, $cintura, $quadril, $rca, $rcq, $genero, $id, $_SESSION['usuario_id']]);

        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['erro' => 'Altura ou peso inválido']);
    }
    exit;
}

// EXCLUIR
if ($action === 'excluir') {
    $id = $_GET['id'] ?? '';
    $stmt = $conn->prepare("DELETE FROM imc_registros WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $_SESSION['usuario_id']]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Registro excluído com sucesso!']);
    exit;
}
?>
