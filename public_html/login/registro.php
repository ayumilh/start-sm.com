<?php
session_start();
require '../config/config.php';
require '../config/keyX/get_token.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $documento = $_POST['documento'];
    $documento_tipo = $_POST['documento_tipo'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); // Criptografando a senha
    $nivel_acesso = 'usuario'; // Definir como 'usuario' por padrão

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, documento, documento_tipo, telefone, email, senha) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $documento, $documento_tipo, $telefone, $email, $senha]);

        // Após o registro, logar automaticamente o usuário
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        // Se o usuário foi encontrado, configurar a sessão
        if ($usuario) {
            $_SESSION['usuario_id'] = $usuario['id'];

            // Gerar o token KeyX para o usuário recém-criado
            $userId = $usuario['id'];
            $tokenGerado = gerarTokenKeyX($userId);  // Chama a função de gerar o token

            if (!$tokenGerado) {
                // Se não conseguir gerar o token, exibe uma mensagem de erro
                echo "Erro ao gerar o token para o usuário. Por favor, tente novamente.";
                exit;
            }

            // Redireciona para a dashboard
            header("Location: ../menu/");
            exit;
        }
    } catch (PDOException $e) {
        // Redirecionar para index.php com mensagem de erro
        header("Location: index.php?message=error&action=register");
        exit;
    }
}
