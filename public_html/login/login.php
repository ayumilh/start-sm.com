<?php
session_start();
require '../config/keyX/get_token.php';
require '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email']) && isset($_POST['senha'])) {
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];

            // Chama a função para atualizar o token ou gerar um novo token se necessário
            $userId = $usuario['id'];

            // Verifica se o refresh token está presente e tenta atualizar o access token
            $tokenAtualizado = refreshTokenKeyX($userId);  // Tenta atualizar o token

            if (!$tokenAtualizado) {
                // Se não conseguir atualizar o token, gera um novo token (caso o refresh não funcione)
                gerarTokenKeyX($userId);  // Gera e armazena o token
            }

            header("Location: ../menu/");

            exit;
        } else {
            header("Location: index.html?message=error&action=login");
            exit;
        }
    } else {
        header("Location: index.html?message=error&action=login");
        exit;
    }
}
