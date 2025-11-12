<?php
require 'conexao.php';
session_start();

// Headers para evitar cache e garantir resposta JSON
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Usuário não logado');
    }

    $usuario_id = $_SESSION['usuario_id'];
    $response = [];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Consulta os dados existentes
        $stmt = $conn->prepare("SELECT * FROM anamnese WHERE usuario_id = :usuario_id ORDER BY data DESC LIMIT 1");
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $response = [
            'logado' => true,
            'dados' => $resultado ?: []
        ];
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verifica se já existe registro
        $stmt = $conn->prepare("SELECT id FROM anamnese WHERE usuario_id = :usuario_id ORDER BY data DESC LIMIT 1");
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        $registroExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($registroExistente) {
            // Atualização parcial
            $campos = [
                'data' => $_POST['data'] ?? null,
                'tipo_diabetes' => $_POST['tipo-diabetes'] ?? null,
                'usa_insulina' => $_POST['insulina'] ?? null,
                'outras_doencas' => $_POST['doenca'] ?? null,
                'alergias' => $_POST['alergias'] ?? null,
                'medicacoes' => $_POST['medicacoes'] ?? null,
                'historico_familiar' => $_POST['historico'] ?? null
            ];
            
            // Remove campos não alterados (null)
            $campos = array_filter($campos, function($value) {
                return $value !== null;
            });
            
            if (!empty($campos)) {
                $sql = "UPDATE anamnese SET ";
                $updates = [];
                foreach ($campos as $key => $value) {
                    $updates[] = "$key = :$key";
                }
                $sql .= implode(', ', $updates) . " WHERE id = :id";
                
                $stmt = $conn->prepare($sql);
                $campos['id'] = $registroExistente['id'];
                $stmt->execute($campos);
            }
            
            $response = ['success' => true];
            
        } else {
            // Primeiro cadastro - valida campos obrigatórios
            if (empty($_POST['data']) || empty($_POST['tipo-diabetes']) || empty($_POST['insulina'])) {
                throw new Exception('Para o primeiro cadastro, preencha todos os campos obrigatórios');
            }

            $dados = [
                'usuario_id' => $usuario_id,
                'data' => $_POST['data'],
                'tipo_diabetes' => $_POST['tipo-diabetes'],
                'usa_insulina' => $_POST['insulina'],
                'outras_doencas' => $_POST['doenca'] ?? null,
                'alergias' => $_POST['alergias'] ?? null,
                'medicacoes' => $_POST['medicacoes'] ?? null,
                'historico_familiar' => $_POST['historico'] ?? null
            ];

            $sql = "INSERT INTO anamnese (
                usuario_id, data, tipo_diabetes, usa_insulina, 
                outras_doencas, alergias, medicacoes, historico_familiar
            ) VALUES (
                :usuario_id, :data, :tipo_diabetes, :usa_insulina, 
                :outras_doencas, :alergias, :medicacoes, :historico_familiar
            )";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($dados);
            $response = ['success' => true];
        }
    }

    echo json_encode($response);

} catch (PDOException $e) {
    error_log('Erro no banco de dados: ' . $e->getMessage());
    echo json_encode(['error' => 'Erro no servidor']);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>