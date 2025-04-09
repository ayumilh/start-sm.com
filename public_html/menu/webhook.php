<?php
session_start();
header('Content-Type: application/json');

// Recebe os dados do SuitPay via POST
$data = json_decode(file_get_contents('php://input'), true);
require '../config/config.php';

// Verifica se os dados foram recebidos corretamente
if (empty($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Nenhum dado recebido']);
    error_log('Nenhum dado recebido');
    exit;
}

// Verifica se o status de pagamento é "PAID_OUT"
if (isset($data['statusTransaction']) && $data['statusTransaction'] == 'PAID_OUT') {
    // Dados do pagamento
    $requestNumber = $data['requestNumber'];
    $paymentCode = $data['paymentCode'];
    $totalAmount = $data['value'];

    // Verifica se o valor do pagamento é numérico
    if (!is_numeric($totalAmount)) {
        echo json_encode(['status' => 'error', 'message' => 'Valor inválido']);
        error_log('Valor do pagamento inválido: ' . $totalAmount);
        exit;
    }

    // Aqui você vai buscar o client_id com base no request_number
    $stmt = $pdo->prepare("SELECT client_id FROM pagamentos WHERE request_number = ?");
    $stmt->execute([$requestNumber]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Agora temos o client_id
        $clientId = $result['client_id'];
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Pagamento não encontrado ou request_number inválido']);
        exit;
    }

    $totalAmount -= 1; // Subtrai a taxa de R$1,00

    // Função para atualizar o status do pagamento no banco
    $updateStatusResponse = updatePaymentStatus($requestNumber, 'Aprovado', $paymentCode, $totalAmount, $clientId);

    if ($updateStatusResponse) {
        // Retorna uma resposta positiva
        echo json_encode(['status' => 'success', 'message' => 'Pagamento aprovado']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar o status do pagamento']);
    }
} else {
    // Se o pagamento não foi aprovado, retorna uma mensagem de erro
    echo json_encode(['status' => 'error', 'message' => 'Pagamento não aprovado']);
}

// Função para atualizar o status do pagamento no banco de dados
function updatePaymentStatus($requestNumber, $status, $paymentCode, $totalAmount, $clientId)
{
    global $pdo;
    try {
        // Atualiza o status do pagamento
        $stmt = $pdo->prepare("UPDATE pagamentos SET status = :status, payment_code = :payment_code, total_amount = :total_amount WHERE request_number = :request_number");
        $stmt->execute([
            ':status' => $status,
            ':payment_code' => $paymentCode,
            ':total_amount' => $totalAmount,
            ':request_number' => $requestNumber
        ]);

        // Verifica se o pagamento foi atualizado
        if ($stmt->rowCount() > 0) {
            error_log('Pagamento atualizado com sucesso para o RequestNumber: ' . $requestNumber);

            // Atualiza o saldo do usuário
            $updateStmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo + :total_amount WHERE id = :client_id");
            $updateStmt->execute([
                ':total_amount' => $totalAmount, // Atualiza com o valor do pagamento
                ':client_id' => $clientId
            ]);

            // Verifica se o saldo foi atualizado
            if ($updateStmt->rowCount() > 0) {
                error_log('Saldo do usuário atualizado com sucesso para o cliente ID: ' . $clientId);
            } else {
                error_log('Erro ao atualizar saldo do usuário para o cliente ID: ' . $clientId);
            }

            // Verifica se o saldo foi atualizado
            if ($updateStmt->rowCount() > 0) {
                error_log('Saldo do usuário atualizado com sucesso para o cliente ID: ' . $clientId);

                // Agora, insere no histórico de saldo
                $insertStmt = $pdo->prepare('INSERT INTO historico_saldo (usuario_id, valor, data_hora, tipo) VALUES (?, ?, NOW(), ?)');
                $tipo = $status === 'pago' ? 'adicao' : 'retirada'; // Definindo tipo como 'adicao' ou 'retirada' com base no status
                $insertStmt->execute([$clientId, $totalAmount, $tipo]);

                // Verifica se o histórico foi registrado corretamente
                if ($insertStmt->rowCount() > 0) {
                    error_log('Histórico de saldo atualizado com sucesso para o cliente ID: ' . $clientId);
                } else {
                    error_log('Erro ao registrar histórico de saldo para o cliente ID: ' . $clientId);
                }

            } else {
                error_log('Erro ao atualizar saldo do usuário para o cliente ID: ' . $clientId);
            }

            return true; // Retorna verdadeiro se o pagamento e o saldo foram atualizados
        } else {
            error_log('Nenhuma atualização encontrada para o RequestNumber: ' . $requestNumber);
            return false; // Retorna falso se não houve atualização no pagamento
        }
    } catch (Exception $e) {
        // Caso haja um erro, registra o erro no log
        error_log('Erro ao atualizar status do pagamento ou saldo do usuário: ' . $e->getMessage());
        return false; // Retorna falso em caso de erro
    }
}
