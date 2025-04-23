<?php
header('Content-Type: application/json');
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../../config/config.php'; // ajusta o caminho conforme seu projeto

$response = ['success' => false, 'message' => 'Erro interno no servidor.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao decodificar JSON: ' . json_last_error_msg()
        ]);
        exit;
    }

    if (
        !isset($_SESSION['usuario_id']) ||
        !isset($data['numeros']) ||
        !isset($data['mensagem_escolhida']) ||
        !isset($data['mensagem_gerada'])
    ) {
        $response['message'] = 'Dados incompletos ou usuário não autenticado.';
        echo json_encode($response);
        exit;
    }

    $usuario_id = $_SESSION['usuario_id'];
    $numerosRaw = is_array($data['numeros']) ? implode("\n", $data['numeros']) : trim($data['numeros']);
    $mensagemEscolhida = trim($data['mensagem_escolhida']);
    $linkOriginal = $data['link_original'] ?? null;
    $encurtador = $data['encurtador_utilizado'] ?? null;
    $mensagemGerada = trim($data['mensagem_gerada']);

    $listaNumeros = preg_split('/\r\n|\r|\n/', $numerosRaw);
    $totalNumeros = count(array_filter($listaNumeros));

    $valorPorNumero = 0.45;
    $valorTotal = $valorPorNumero * $totalNumeros;

    // Consulta o saldo do usuário
    $stmtSaldo = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = :id LIMIT 1");
    $stmtSaldo->bindParam(':id', $usuario_id);
    $stmtSaldo->execute();
    $user = $stmtSaldo->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['saldo'] < $valorTotal) {
        $response['message'] = 'Saldo insuficiente. Necessário: R$ ' . number_format($valorTotal, 2, ',', '.');
        echo json_encode($response);
        exit;
    }

    // Insere a campanha no banco
    $stmtInsert = $pdo->prepare("INSERT INTO envios_sms_campanha (
        usuario_id,
        numeros,
        mensagem_escolhida,
        link_original,
        encurtador_utilizado,
        mensagem_gerada,
        enviados,
        nao_enviados,
        criado_em
    ) VALUES (
        :usuario_id,
        :numeros,
        :mensagem_escolhida,
        :link_original,
        :encurtador,
        :mensagem_gerada,
        :enviados,
        :nao_enviados,
        NOW()
    )");

    $stmtInsert->execute([
        ':usuario_id' => $usuario_id,
        ':numeros' => $numerosRaw,
        ':mensagem_escolhida' => $mensagemEscolhida,
        ':link_original' => $linkOriginal,
        ':encurtador' => $encurtador,
        ':mensagem_gerada' => $mensagemGerada,
        ':enviados' => json_encode($listaNumeros),
        ':nao_enviados' => json_encode([])
    ]);


    // Desconta o saldo
    $stmtUpdate = $pdo->prepare("UPDATE usuarios SET saldo = saldo - :valor WHERE id = :id");
    $stmtUpdate->bindParam(':valor', $valorTotal);
    $stmtUpdate->bindParam(':id', $usuario_id);
    $stmtUpdate->execute();

    // Registra no histórico
    $stmtHistorico = $pdo->prepare("INSERT INTO historico_saldo (usuario_id, valor, tipo) VALUES (:usuario_id, :valor, 'retirada')");
    $stmtHistorico->bindParam(':usuario_id', $usuario_id);
    $stmtHistorico->bindParam(':valor', $valorTotal);
    $stmtHistorico->execute();

    $response = [
        'success' => true,
        'message' => 'Campanha registrada e saldo descontado.',
        'dados' => [
            'total_numeros' => $totalNumeros,
            'valor_total' => $valorTotal
        ]
    ];
}

echo json_encode($response);
