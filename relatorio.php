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
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado']);
    exit;
}

// Função para normalizar números (trata 0,9 e 0.9 como iguais)
function normalizarNumero($valor) {
    if (is_numeric($valor)) {
        return $valor;
    }
    // Substitui vírgula por ponto e tenta converter para float
    $normalizado = str_replace(',', '.', $valor);
    return is_numeric($normalizado) ? (float)$normalizado : $valor;
}

try {
    // Conexão com o banco
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Pega todos os tipos de exames distintos do usuário
    $stmt = $pdo->prepare("
        SELECT DISTINCT tipo_exame 
        FROM exames 
        WHERE usuario_id = :usuario_id
        ORDER BY tipo_exame
    ");
    $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
    $tiposExames = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $examesPorTipo = [];
    
    // Para cada tipo de exame, pega os 5 últimos registros
    foreach ($tiposExames as $tipo) {
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
            WHERE usuario_id = :usuario_id AND tipo_exame = :tipo_exame
            ORDER BY STR_TO_DATE(data_exame, '%d/%m/%Y') DESC, id DESC
            LIMIT 5
        ");
        $stmt->execute([
            'usuario_id' => $_SESSION['usuario_id'],
            'tipo_exame' => $tipo
        ]);
        $exames = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Normaliza os resultados numéricos (0,9 → 0.9)
        foreach ($exames as &$exame) {
            $exame['resultado'] = normalizarNumero($exame['resultado']);
        }

        // Se for do tipo "Outros", usa o nome_exame como chave
        if ($tipo === 'Outros') {
            foreach ($exames as $exame) {
                $nomeExame = $exame['nome_exame'] ?? 'Outros';
                if (!isset($examesPorTipo[$nomeExame])) {
                    $examesPorTipo[$nomeExame] = [];
                }
                $examesPorTipo[$nomeExame][] = $exame;
            }
        } else {
            $examesPorTipo[$tipo] = $exames;
        }
    }

    // Busca os dados de IMC, RCQ, RCA e CC do banco de dados - APENAS O ÚLTIMO REGISTRO
    $medidasData = [];
    $stmt = $pdo->prepare("
        SELECT data, altura, peso, imc, cintura, quadril, rca, rcq, genero
        FROM imc_registros 
        WHERE usuario_id = :usuario_id 
        ORDER BY data DESC, id DESC 
        LIMIT 1
    ");
    $stmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
    $ultimoRegistro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ultimoRegistro) {
        $imc = (float)$ultimoRegistro['imc'];
        $cintura = $ultimoRegistro['cintura'] ? (float)$ultimoRegistro['cintura'] : null;
        $quadril = $ultimoRegistro['quadril'] ? (float)$ultimoRegistro['quadril'] : null;
        $rca = $ultimoRegistro['rca'] ? (float)$ultimoRegistro['rca'] : null;
        $rcq = $ultimoRegistro['rcq'] ? (float)$ultimoRegistro['rcq'] : null;
        $genero = $ultimoRegistro['genero'] ?? 'feminino';
        
        // Classifica o IMC
        if ($imc < 18.5) {
            $medidasData['imc'] = [
                'imc' => $imc,
                'classificacao' => 'Abaixo do peso',
                'cor' => '#3b82f6',
                'posicao' => ($imc / 18.5) * 15
            ];
        } elseif ($imc < 25) {
            $medidasData['imc'] = [
                'imc' => $imc,
                'classificacao' => 'Peso normal',
                'cor' => '#10b981',
                'posicao' => 15 + (($imc - 18.5) / 6.4) * 16
            ];
        } elseif ($imc < 30) {
            $medidasData['imc'] = [
                'imc' => $imc,
                'classificacao' => 'Sobrepeso',
                'cor' => '#f59e0b',
                'posicao' => 31 + (($imc - 25) / 4.9) * 18
            ];
        } elseif ($imc < 35) {
            $medidasData['imc'] = [
                'imc' => $imc,
                'classificacao' => 'Obesidade Grau I',
                'cor' => '#f97316',
                'posicao' => 49 + (($imc - 30) / 4.9) * 18
            ];
        } elseif ($imc < 40) {
            $medidasData['imc'] = [
                'imc' => $imc,
                'classificacao' => 'Obesidade Grau II',
                'cor' => '#ef4444',
                'posicao' => 67 + (($imc - 35) / 4.9) * 18
            ];
        } else {
            $medidasData['imc'] = [
                'imc' => $imc,
                'classificacao' => 'Obesidade Grau III',
                'cor' => '#b91c1c',
                'posicao' => 85 + min((($imc - 40) / 10) * 15, 15)
            ];
        }

        // Classifica a Circunferência da Cintura (CC)
        if ($cintura) {
            $limiteCintura = ($genero === 'masculino') ? 94 : 80;
            $medidasData['cintura'] = [
                'valor' => $cintura,
                'classificacao' => $cintura > $limiteCintura ? 'Risco aumentado' : 'Normal',
                'cor' => $cintura > $limiteCintura ? '#ef4444' : '#10b981',
                'limite' => $limiteCintura
            ];
        }

        // Classifica a Relação Cintura/Quadril (RCQ)
        if ($rcq) {
            $limiteRCQ = ($genero === 'masculino') ? 1.0 : 0.85;
            $medidasData['rcq'] = [
                'valor' => $rcq,
                'classificacao' => $rcq > $limiteRCQ ? 'Risco aumentado' : 'Normal',
                'cor' => $rcq > $limiteRCQ ? '#ef4444' : '#10b981',
                'limite' => $limiteRCQ
            ];
        }

        // Classifica a Relação Cintura/Altura (RCA)
        if ($rca) {
            $medidasData['rca'] = [
                'valor' => $rca,
                'classificacao' => $rca > 0.5 ? 'Risco aumentado' : 'Normal',
                'cor' => $rca > 0.5 ? '#ef4444' : '#10b981',
                'limite' => 0.5
            ];
        }
    }

    // Envia os dados
    echo json_encode([
        'status' => 'success',
        'exames_por_tipo' => $examesPorTipo,
        'medidas' => $medidasData,
        'ultima_atualizacao' => time()
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao carregar exames',
        'details' => $e->getMessage()
    ]);
}
?>