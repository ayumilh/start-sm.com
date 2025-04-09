<?php
function gerarTokenKeyX($userId)
{
    // Carregar a configuração do banco de dados (conexão PDO)
    require '../config/config.php';

    // Configuração para autenticação do KeyX
    $username = 'elprofessor';
    $password = '12345Abcd#';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username ou password não encontrados no .env']);
        return false;
    }

    $url = 'https://portal.keyx.com.br/api/token/';
    $requestData = [
        'username' => $username,
        'password' => $password
    ];

    // Codifica os dados em formato JSON
    $jsonData = json_encode($requestData);

    // Cria o contexto HTTP para a requisição
    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $jsonData,
            'ignore_errors' => true // Garante que podemos acessar a resposta, mesmo em caso de erro HTTP
        ]
    ];

    $context = stream_context_create($options);

    // Faz a requisição usando file_get_contents
    $response = file_get_contents($url, false, $context);

    // Verifica se houve erro na requisição
    if ($response === FALSE) {
        // Grava o erro no log
        error_log("Erro na requisição para a URL: $url. Não foi possível acessar o serviço.");
        echo json_encode(['status' => 'error', 'message' => 'Erro na requisição']);
        return false;
    }

    // Registra a resposta no log
    error_log("Resposta da API: $response");

    // Processa a resposta
    $responseData = json_decode($response, true);

    if (isset($responseData['access']) && isset($responseData['refresh'])) {
        try {
            // Verifica se a conexão PDO está funcionando
            if (!$pdo) {
                error_log("Erro na conexão com o banco de dados");
                echo json_encode(['status' => 'error', 'message' => 'Erro na conexão com o banco de dados']);
                return false;
            }

            // Armazena o token na base de dados
            $stmt = $pdo->prepare("INSERT INTO user_tokens (client_id, access_token, refresh_token) 
                                    VALUES (:client_id, :access_token, :refresh_token)
                                    ");


            // Adiciona os parâmetros corretamente
            $stmt->bindValue(':client_id', $userId, PDO::PARAM_INT); // Use bindValue ao invés de bindParam
            $stmt->bindValue(':access_token', $responseData['access'], PDO::PARAM_STR);
            $stmt->bindValue(':refresh_token', $responseData['refresh'], PDO::PARAM_STR);

            // Executa a consulta
            $stmt->execute();

            // Verifica se a execução foi bem-sucedida
            if ($stmt->errorCode() != '00000') {
                $errorInfo = $stmt->errorInfo();
                error_log("Erro ao inserir os tokens no banco: " . print_r($errorInfo, true));
                echo json_encode(['status' => 'error', 'message' => 'Erro ao inserir os tokens no banco de dados']);
                return false;
            }

            return true;
        } catch (PDOException $e) {
            // Registra o erro no log caso falhe na execução
            error_log("Erro ao salvar os tokens no banco de dados: " . $e->getMessage());
            echo "<div style='color: red;'>Erro ao salvar os tokens no banco de dados: " . $e->getMessage() . "</div>";
            return false;
        }
    } else {
        // Grava no log se os tokens não foram encontrados
        error_log("Tokens não encontrados na resposta da API: " . print_r($responseData, true));
        echo "<div style='color: red;'>Tokens não encontrados na resposta da API</div>";
        return false;
    }
}

// Função para fazer o refresh do token
function refreshTokenKeyX($userId)
{
    // Consulta ao banco de dados para buscar o refresh token do usuário
    global $pdo;  // Use a conexão PDO global
    $stmt = $pdo->prepare("SELECT refresh_token FROM user_tokens WHERE client_id = ?");
    $stmt->execute([$userId]);
    $tokens = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokens || empty($tokens['refresh_token'])) {
        error_log("Refresh token não encontrado para o usuário ID $userId.");
        return false;
    }

    $refreshToken = $tokens['refresh_token'];

    // Configuração para autenticação do KeyX
    $url = 'https://portal.keyx.com.br/api/token/refresh/';
    $requestData = [
        'refresh' => $refreshToken
    ];

    // Codifica os dados em formato JSON
    $jsonData = json_encode($requestData);

    // Cria o contexto HTTP para a requisição
    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $jsonData,
            'ignore_errors' => true // Garante que podemos acessar a resposta, mesmo em caso de erro HTTP
        ]
    ];

    $context = stream_context_create($options);

    // Faz a requisição usando file_get_contents
    $response = file_get_contents($url, false, $context);

    // Verifica se houve erro na requisição
    if ($response === FALSE) {
        error_log("Erro na requisição para a URL: $url. Não foi possível acessar o serviço.");
        return false;
    }

    // Processa a resposta
    $responseData = json_decode($response, true);

    if (isset($responseData['access'])) {
        try {
            // Armazena o novo token de acesso na base de dados
            $stmt = $pdo->prepare("UPDATE user_tokens SET access_token = :access_token, updated_at = CURRENT_TIMESTAMP WHERE client_id = :client_id");

            // Adiciona os parâmetros corretamente
            $stmt->bindValue(':client_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':access_token', $responseData['access'], PDO::PARAM_STR);

            // Executa a consulta
            $stmt->execute();

            // Verifica se a execução foi bem-sucedida
            if ($stmt->errorCode() != '00000') {
                $errorInfo = $stmt->errorInfo();
                error_log("Erro ao atualizar o token no banco: " . print_r($errorInfo, true));
                return false;
            }

            return true;
        } catch (PDOException $e) {
            error_log("Erro ao salvar o novo token no banco de dados: " . $e->getMessage());
            return false;
        }
    } else {
        error_log("Erro ao fazer refresh do token: " . print_r($responseData, true));
        return false;
    }
}
?>
