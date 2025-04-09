<?php
session_start();
require '../config/config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login/");
    exit;
}

// Pega o ID do usuário da sessão
$usuario_id = $_SESSION['usuario_id'];

$stmt = $pdo->prepare('SELECT id, mensagem FROM mensagens');
$stmt->execute();
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Consulta para obter os dados do usuário
$stmt = $pdo->prepare('SELECT nome, saldo, nivel_acesso FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não encontrar o usuário, redireciona
if (!$usuario) {
    header("Location: /login/");
    exit;
}

// Acessa os dados do usuário
$nome = htmlspecialchars($usuario['nome']);
$saldo = number_format($usuario['saldo'], 2, ',', '.');
$nivel_acesso = htmlspecialchars($usuario['nivel_acesso']);

// Supondo que a conexão PDO já esteja configurada
$stmt = $pdo->prepare('SELECT nome FROM encurtadores');
$stmt->execute();
$encurtadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para obter o histórico de saldo do usuário
$stmt = $pdo->prepare('SELECT valor, data_hora, tipo FROM historico_saldo WHERE usuario_id = ? ORDER BY data_hora DESC');
$stmt->execute([$usuario_id]);
$historico_saldo = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Função de logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /login/");
    exit;
}



?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS em MASSA </title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <!-- Barra Superior -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a href="/menu" title="Ir para a página inicial">
            <img src="https://i.ibb.co/p0cKr26/Captura-de-Tela-2024-12-13-a-s-08-54-39-removebg-preview.png"
                alt="Start SMS"
                style="max-width: 120px; height: auto;">
        </a>
        <div class="ml-auto">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" id="userDropdown" data-toggle="dropdown">
                    <img src="https://i.pinimg.com/736x/61/f7/5e/61f75ea9a680def2ed1c6929fe75aeee.jpg" alt="user" width="30" height="30" class="rounded-circle">
                    <span class="ml-2"><?php echo $nome; ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="/menu">Menu Principal</a>
                    <a class="dropdown-item" href="campanhas.php">Histórico de campanhas</a>
                    <a class="dropdown-item" href="?logout=true">Sair</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Conteúdo da Página -->
    <div class="container mt-4">
        <div class="row">
            <!-- Saldo e nível de acesso -->
            <div class="col-md-3">
                <h4>Saldo</h4>
                <div class="saldo-box bg-success text-white text-center py-3 rounded">
                    <h3>R$ <?php echo $saldo; ?></h3>
                </div>
                <div class="nivel-box text-center mt-3">
                    <p class="font-weight-bold">Nível de Acesso: <?php echo ucfirst($nivel_acesso); ?></p> <!-- Nível de acesso separado -->
                </div>
                <!-- Botão de histórico de saldo -->
                <button type="button" class="btn btn-info btn-block mt-3" data-toggle="modal" data-target="#saldoModal">
                    <i class="fas fa-history"></i> Histórico de Saldo
                </button>

                <a href="./campanhas.php" class="btn btn-info btn-block mt-3" target="_blank">
                    <i class="fas fa-clipboard-list"></i> Histórico de Campanhas
                </a>


            </div>

            <!-- Formulário de Campanha de SMS -->
            <div class="col-md-9">
                <h4>Configurar Campanha de SMS</h4>
                <form id="smsForm" method="POST">
                    <div class="form-group">
                        <label for="numeros">Números de Telefone (um por linha)</label>
                        <textarea class="form-control" id="numeros" rows="5" placeholder="Exemplo de Formatação Correta: 5511988888888"></textarea>
                        <small id="numero-contador" class="form-text text-muted">0 / 30.000 números</small>
                    </div>

                    <div class="form-group">
                        <label for="mensagem">Escolher Mensagem</label>
                        <select class="form-control" id="mensagem">
                            <?php foreach ($mensagens as $mensagem): ?>
                                <option value="<?= htmlspecialchars($mensagem['mensagem']) ?>"><?= htmlspecialchars($mensagem['mensagem']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="link">Link a ser encurtado</label>
                        <input type="url" class="form-control" id="link" placeholder="Digite o SEU Link" oninput="atualizarMensagem()">
                    </div>

                    <div class="form-group">
                        <label for="encurtador">Escolher Encurtador</label>
                        <select class="form-control" id="encurtador" onchange="atualizarMensagem()">
                            <?php foreach ($encurtadores as $encurtador): ?>
                                <option value="<?= htmlspecialchars($encurtador['nome']) ?>"><?= htmlspecialchars($encurtador['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mensagem-gerada">Mensagem Gerada</label>
                        <textarea class="form-control" id="mensagem-gerada" rows="3" readonly></textarea>
                    </div>

                    <button type="button" id="iniciar-campanha" class="btn btn-primary btn-block">Iniciar Campanha e Enviar SMS</button>
                </form>

                <!-- Loading de progresso -->
                <div id="progresso-campanha" class="mt-4" style="display: none;">
                    <h5>Progresso do Envio</h5>
                    <div class="progress mb-3">
                        <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Enviados <i class="fas fa-eye" id="toggle-enviados" style="cursor: pointer;"></i></h6>
                            <ul id="sms-enviados" class="list-group" style="display: none;">
                                <!-- Lista de enviados -->
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Não Enviados <i class="fas fa-eye" id="toggle-nao-enviados" style="cursor: pointer;"></i></h6>
                            <ul id="sms-nao-enviados" class="list-group" style="display: none;">
                                <!-- Lista de não enviados -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                const MAX_NUMEROS = 30000;

                document.getElementById('numeros').addEventListener('input', function() {
                    let numeros = this.value.trim().split(/\n+/); // Separa os números por linha
                    let totalNumeros = numeros.length;

                    if (totalNumeros > MAX_NUMEROS) {
                        alert(`⚠️ Você inseriu ${totalNumeros} números. O limite máximo é de ${MAX_NUMEROS}. O excesso será removido.`);
                        this.value = numeros.slice(0, MAX_NUMEROS).join("\n"); // Remove o excesso automaticamente
                    }

                    document.getElementById('numero-contador').textContent = `${Math.min(totalNumeros, MAX_NUMEROS)} / 30000 números`;
                });

                function atualizarMensagem() {
                    const mensagemSelecionada = document.getElementById('mensagem').value;
                    const link = document.getElementById('link').value;
                    const encurtador = document.getElementById('encurtador').value;

                    // Substitui o ##LINK## pela URL inserida e adiciona o encurtador na mensagem
                    const mensagemGerada = `${mensagemSelecionada.replace('##LINK##', link)} (Encurtador: ${encurtador})`;

                    document.getElementById('mensagem-gerada').value = mensagemGerada;
                }

                // Para garantir que a mensagem gerada seja atualizada quando a página carregar
                document.addEventListener('DOMContentLoaded', atualizarMensagem);
            </script>

            <!-- Modal de Histórico de Saldo -->
            <?php
            // A sessão e a consulta ao banco de dados já estão configuradas conforme o seu código
            // Adicione a consulta para obter o histórico de saldo

            // Consulta para obter o histórico de saldo do usuário
            $stmt = $pdo->prepare('SELECT valor, data_hora, tipo FROM historico_saldo WHERE usuario_id = ? ORDER BY data_hora DESC');
            $stmt->execute([$usuario_id]);
            $historico_saldo = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <!-- Modal de Histórico de Saldo -->
            <div class="modal fade" id="saldoModal" tabindex="-1" aria-labelledby="saldoModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="saldoModalLabel">Histórico de Saldo</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <ul class="list-group">
                                <?php if (empty($historico_saldo)): ?>
                                    <li class="list-group-item">Nenhum histórico encontrado.</li>
                                <?php else: ?>
                                    <?php foreach ($historico_saldo as $registro): ?>
                                        <?php
                                        // Determina o ícone e texto com base no tipo
                                        if ($registro['tipo'] === 'adicao') {
                                            $icon = '<i class="fas fa-plus-circle text-success"></i>';
                                            $tipo_texto = 'Adição';
                                        } else {
                                            $icon = '<i class="fas fa-minus-circle text-danger"></i>';
                                            $tipo_texto = 'Retirada';
                                        }
                                        ?>
                                        <li class="list-group-item">
                                            <?php echo $icon; ?> <?php echo $tipo_texto; ?> de R$ <?php echo number_format($registro['valor'], 2, ',', '.'); ?> - <?php echo date('d/m/Y H:i:s', strtotime($registro['data_hora'])); ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                            <button type="button" class="btn btn-primary btn-block mt-3">Adicionar Saldo</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const btn = document.getElementById('iniciar-campanha');
                    const progresso = document.getElementById('progresso-campanha');
                    const progressBar = document.querySelector('.progress-bar');

                    btn.addEventListener('click', function() {
                        const numeros = document.getElementById('numeros').value;
                        const mensagem = document.getElementById('mensagem-gerada').value;
                        const link = document.getElementById('link').value;
                        const encurtador = document.getElementById('encurtador').value;

                        if (!numeros || !mensagem || !link || !encurtador) {
                            console.log("Valores dos campos:");
                            console.log("Numeros:", numeros);
                            console.log("Mensagem:", mensagem);
                            console.log("Link:", link);
                            console.log("Encurtador:", encurtador);

                            window.location.reload();
                            return false; // Impede a execução da campanha
                        }

                        // Inicia o progresso imediatamente
                        let progressValue = 0;
                        progresso.style.display = 'block'; // Mostra a barra de progresso
                        progressBar.style.width = '0%';
                        progressBar.textContent = '0%';
                        progressBar.classList.remove('bg-success', 'bg-danger');

                        const interval = setInterval(() => {
                            if (progressValue >= 100) {
                                clearInterval(interval);
                                progressBar.style.width = '100%';
                                progressBar.textContent = 'Processo concluído!';
                                progressBar.classList.add('bg-success');

                                // Atualiza a página ao concluir
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500); // Adiciona um pequeno delay antes de recarregar
                            } else {
                                progressValue += 10;
                                progressBar.style.width = `${progressValue}%`;
                                progressBar.setAttribute('aria-valuenow', progressValue);
                                progressBar.textContent = `${progressValue}%`;
                            }
                        }, 500);

                        // Alterar o texto do botão e adicionar uma classe
                        btn.textContent = 'Visualizar Campanha';
                        btn.classList.add('btn-primary'); // Ou qualquer outra classe para personalizar o estilo

                        // Redirecionar para uma pasta ou URL
                        btn.addEventListener('click', function() {
                            window.location.href = './campanhas.php'; // Substitua pelo caminho desejado
                        });

                        // Enviar campanha para o servidor
                        fetch('funcoes.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    numeros: numeros.split('\n'),
                                    mensagem: mensagem,
                                    link: link,
                                    encurtador: encurtador
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (!data.success) {
                                    // Exibe erro sem parar o progresso
                                    alert("Erro ao iniciar a campanha. Tente novamente.");
                                }
                            })
                            .catch(error => {
                                console.error('Erro:', error);
                                alert("Erro ao iniciar a campanha. Verifique o console.");
                            });
                    });
                });
            </script>

            <!-- Bootstrap JS, Popper.js, jQuery -->
            <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
            <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
            <!-- Custom JS -->
            <script src="js/scripts.js"></script>
</body>

</html>