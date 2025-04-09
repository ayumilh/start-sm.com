<?php
session_start();
require '../config/config.php'; // Ajuste o caminho conforme necessário

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

// Função para enviar a campanha e salvar no banco de dados
function enviarCampanha($id_usuario, $numeros, $mensagem) {
    global $pdo;

    $totalNumeros = count($numeros);
     // Busca o preço por número e o saldo do usuário
        $stmt = $pdo->prepare('SELECT saldo, preco_por_numero FROM usuarios WHERE id = ?');
        $stmt->execute([$id_usuario]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        $precoPorNumero = $usuario['preco_por_numero'] ?? 0.20; // Valor padrão 
    $saldoGasto = $totalNumeros * $precoPorNumero;

    // Verifica o saldo do usuário
    $stmt = $pdo->prepare('SELECT saldo FROM usuarios WHERE id = ?');
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && $usuario['saldo'] < $saldoGasto) {
        return ['success' => false, 'message' => 'Saldo insuficiente para disparar os números.'];
    }

    // Gerar número da campanha
    $numeroCampanha = rand(10000, 99999);

    try {
        // Salvar a campanha na tabela `campanhas`
        $stmt = $pdo->prepare('INSERT INTO campanhas (id_usuario, status, data_criacao, numero_campanha, total_enviados, total_sucesso, total_falhas, saldo_total_gasto) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)');
        $stmt->execute([$id_usuario, '1', $numeroCampanha, $totalNumeros, 0, 0, $saldoGasto]);

        // Pegar o ID da campanha que foi criada
        $idCampanha = $pdo->lastInsertId();

        // Inicializa contadores de sucesso e falhas
        $totalSucesso = 0;
        $totalFalhas = 0;

        // Iterar sobre os números e inserir cada envio na tabela `envios`
        foreach ($numeros as $numero) {
            // Atribuir status com 89% de chance de sucesso
            $statusEnvio = (rand(1, 100) <= 89) ? 'ENVIADO' : 'FALHA';

            // Atualiza os contadores
            if ($statusEnvio === 'ENVIADO') {
                $totalSucesso++;
            } else {
                $totalFalhas++;
            }

            $stmt = $pdo->prepare('INSERT INTO envios (numero_campanha, numero_destinatario, status_envio, mensagem, data_hora_envio) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([$numeroCampanha, $numero, $statusEnvio, $mensagem]); // Incluindo a mensagem
        }

        // Deduzir o saldo do usuário com base na quantidade de números disparados
        $stmt = $pdo->prepare('UPDATE usuarios SET saldo = saldo - ? WHERE id = ?');
        $stmt->execute([$saldoGasto, $id_usuario]);

        // Atualiza os totais da campanha
        $stmt = $pdo->prepare('UPDATE campanhas SET total_enviados = ?, total_sucesso = ?, total_falhas = ? WHERE id = ?');
        $stmt->execute([$totalNumeros, $totalSucesso, $totalFalhas, $idCampanha]);

        return $numeroCampanha;

    } catch (PDOException $e) {
        // Exibe o erro para depuração
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
        exit;
    }
}

// Receber os dados da campanha via POST
$data = json_decode(file_get_contents('php://input'), true);
$numeros = $data['numeros'];
$mensagem = $data['mensagem'];
$id_usuario = $_SESSION['usuario_id']; // Pega o ID do usuário logado

if (!$numeros || !$mensagem) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit;
}

// Chama a função para enviar a campanha
$result = enviarCampanha($id_usuario, $numeros, $mensagem);

// Retorna o sucesso da operação
if (isset($result['success']) && $result['success'] === false) {
    echo json_encode($result);
} else {
    echo json_encode(['success' => true, 'message' => 'Campanha iniciada com sucesso!', 'numero_campanha' => $result]);
}
?>
