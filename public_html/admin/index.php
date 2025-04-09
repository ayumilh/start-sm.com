<?php
session_start();
require '../config/config.php'; // Ajuste o caminho conforme necessário

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login/");
    exit;
}

// Pega o ID do usuário da sessão
$usuario_id = $_SESSION['usuario_id'];


$stmt = $pdo->prepare('SELECT nivel_acesso, FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);


// Se não encontrar o usuário ou se não for admin, redireciona
if (!$usuario || $usuario['nivel_acesso'] !== 'admin') {
    header("Location: /login/"); // Redireciona para a página de login ou uma página de acesso negado
    exit;
}


// Define o caminho do arquivo de manutenção (neste exemplo, maintenance.txt)
$maintenanceFile = '../config/maintenance.txt';

// Se o formulário de manutenção foi enviado, salva o status no arquivo
if (isset($_POST['manutencao'])) {
    $manutencao = $_POST['manutencao'];
    file_put_contents($maintenanceFile, $manutencao);
}

// Verifica o estado atual da manutenção lendo do arquivo
$manutencao_ativa = false;
if (file_exists($maintenanceFile)) {
    $manutencaoValue = trim(file_get_contents($maintenanceFile));
    $manutencao_ativa = ($manutencaoValue === '1');
}

// Função para editar campanhas
if (isset($_POST['editar_campanha'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $numero_campanha = $_POST['numero_campanha'];
    // Adicione outros campos conforme necessário

    $stmt = $pdo->prepare('UPDATE campanhas SET status = ?, numero_campanha = ? WHERE id = ?');
    $stmt->execute([$status, $numero_campanha, $id]);
}

// Função para baixar números da campanha
if (isset($_GET['baixar_numeros'])) {
    $numero_campanha = $_GET['baixar_numeros'];

    // Consulta para obter os números do destinatário
    $stmt = $pdo->prepare('SELECT numero_destinatario FROM envios WHERE numero_campanha = ?');
    $stmt->execute([$numero_campanha]);
    $numeros = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Gera o conteúdo do arquivo
    $content = implode(PHP_EOL, $numeros);

    // Define o cabeçalho para o download do arquivo
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="numeros_da_campanha_' . $numero_campanha . '.txt"');
    echo $content;
    exit;
}

// Função para editar usuários
if (isset($_POST['editar_usuario'])) {
    $id = $_POST['id'];
    $saldo = $_POST['saldo'];
    $preco_por_numero = $_POST['preco_por_numero'];

    // Obtendo o saldo atual do usuário antes da atualização
    $stmt = $pdo->prepare('SELECT saldo FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $usuarioInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $saldo_atual = $usuarioInfo['saldo'];

    // Atualizando o saldo e o preço por número no banco de dados
    $stmt = $pdo->prepare('UPDATE usuarios SET saldo = ?, preco_por_numero = ? WHERE id = ?');
    $stmt->execute([$saldo, $preco_por_numero, $id]);

    // Verificando se a atualização foi bem-sucedida
    if ($stmt->rowCount() > 0) {
        // Determinando o tipo de alteração no saldo (adicionando ou retirando saldo)
        $tipo = ($saldo > $saldo_atual) ? 'adicao' : 'retirada';
        
        // Inserindo o histórico de saldo
        $stmt = $pdo->prepare('INSERT INTO historico_saldo (usuario_id, valor, data_hora, tipo) VALUES (?, ?, NOW(), ?)');
        $stmt->execute([$id, abs($saldo - $saldo_atual), $tipo]); // Usando abs para garantir que o valor seja positivo
        
        echo "Saldo atualizado e histórico registrado!";
    } else {
        echo "Erro na atualização ou nenhum dado alterado.";
    }
}

// Paginação para campanhas
$limit = 10; // Número de campanhas por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filtro para Campanhas
$filtro_campanha = '';
if (isset($_POST['filtro_campanha'])) {
    $filtro_campanha = $_POST['filtro_campanha'];
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM campanhas WHERE numero_campanha LIKE ?');
$stmt->execute(['%' . $filtro_campanha . '%']);
$total_campanhas = $stmt->fetchColumn();
$total_pages_campanhas = ceil($total_campanhas / $limit);

// Listar campanhas com filtro
$stmt = $pdo->prepare('SELECT * FROM campanhas WHERE numero_campanha LIKE ? LIMIT ? OFFSET ?');
$stmt->execute(['%' . $filtro_campanha . '%', $limit, $offset]);
$campanhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Paginação para envios
$limit_envios = 10; // Número de envios por página
$page_envios = isset($_GET['page_envios']) ? (int)$_GET['page_envios'] : 1;
$offset_envios = ($page_envios - 1) * $limit_envios;

// Filtro para Envios
$filtro_envio = '';
if (isset($_POST['filtro_envio'])) {
    $filtro_envio = $_POST['filtro_envio'];
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM envios WHERE numero_campanha LIKE ?');
$stmt->execute(['%' . $filtro_envio . '%']);
$total_envios = $stmt->fetchColumn();
$total_pages_envios = ceil($total_envios / $limit_envios);

// Listar envios com filtro
$stmt = $pdo->prepare('SELECT * FROM envios WHERE numero_campanha LIKE ? LIMIT ? OFFSET ?');
$stmt->execute(['%' . $filtro_envio . '%', $limit_envios, $offset_envios]);
$envios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listar usuários
$stmt = $pdo->prepare('SELECT * FROM usuarios');
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consultar campanhas com envio e ordenadas pela data de criação (mais recentes primeiro)
$stmt = $pdo->prepare('
    SELECT campanhas.*, 
           (SELECT mensagem FROM envios WHERE numero_campanha = campanhas.numero_campanha LIMIT 1) AS mensagem_envio
    FROM campanhas
    WHERE numero_campanha LIKE ?
    ORDER BY campanhas.data_criacao DESC
    LIMIT ? OFFSET ?
');
$stmt->execute(['%' . $filtro_campanha . '%', $limit, $offset]);
$campanhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Função para editar mensagens
if (isset($_POST['editar_mensagem'])) {
    $id = $_POST['id'];
    $mensagem = $_POST['mensagem'];

    $stmt = $pdo->prepare('UPDATE mensagens SET mensagem = ? WHERE id = ?');
    $stmt->execute([$mensagem, $id]);
}

// Função para editar encurtadores
if (isset($_POST['editar_encurtador'])) {
    $id = $_POST['id'];
    $encurtador = $_POST['encurtador'];

    $stmt = $pdo->prepare('UPDATE encurtadores SET encurtador = ? WHERE id = ?');
    $stmt->execute([$encurtador, $id]);
}

// Função para criar novas mensagens
if (isset($_POST['criar_mensagem'])) {
    $mensagem = $_POST['mensagem'];

    $stmt = $pdo->prepare('INSERT INTO mensagens (mensagem) VALUES (?)');
    $stmt->execute([$mensagem]);
}

// Função para criar novos encurtadores
if (isset($_POST['criar_encurtador'])) {
    $encurtador = $_POST['encurtador'];

    $stmt = $pdo->prepare('INSERT INTO encurtadores (nome) VALUES (?)');
    $stmt->execute([$encurtador]);
}

// Função para excluir mensagens
if (isset($_POST['excluir_mensagem'])) {
    $id = $_POST['id'];

    $stmt = $pdo->prepare('DELETE FROM mensagens WHERE id = ?');
    $stmt->execute([$id]);
}

// Função para excluir encurtadores
if (isset($_POST['excluir_encurtador'])) {
    $id = $_POST['id'];

    $stmt = $pdo->prepare('DELETE FROM encurtadores WHERE id = ?');
    $stmt->execute([$id]);
}

// Listar mensagens
$stmt = $pdo->prepare('SELECT * FROM mensagens');
$stmt->execute();
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listar encurtadores
$stmt = $pdo->prepare('SELECT * FROM encurtadores');
$stmt->execute();
$encurtadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Admin</title>
    <!-- Seção de Manutenção -->
    <div class="container mt-3">
        <div class="card">
            <div class="card-header">
                Manutenção
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="manutencao">Status da Manutenção:</label>
                        <select class="form-control" name="manutencao" id="manutencao">
                            <option value="1" <?= $manutencao_ativa ? 'selected' : '' ?>>Ativada</option>
                            <option value="0" <?= !$manutencao_ativa ? 'selected' : '' ?>>Desativada</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </form>
            </div>
        </div>
    </div>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Painel de Administração</h1>

        <!-- Abas para Campanhas, Envios e Usuários -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="campanhas-tab" data-toggle="tab" href="#campanhas" role="tab" aria-controls="campanhas" aria-selected="true">Campanhas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="envios-tab" data-toggle="tab" href="#envios" role="tab" aria-controls="envios" aria-selected="false">Envios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="usuarios-tab" data-toggle="tab" href="#usuarios" role="tab" aria-controls="usuarios" aria-selected="false">Usuários</a>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <!-- Campanhas -->
            <div class="tab-pane fade show active" id="campanhas" role="tabpanel" aria-labelledby="campanhas-tab">
                <h2>Campanhas</h2>

                <!-- Filtro para Campanhas -->
                <form method="POST">
                    <div class="form-group">
                        <input type="text" class="form-control" name="filtro_campanha" placeholder="Filtrar por número da campanha" value="<?= htmlspecialchars($filtro_campanha) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </form>

                <table class="table mt-3">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Número da Campanha</th>
                            <th>Status</th>
                            <th>Mensagem de Envio</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campanhas as $campanha): ?>
                        <tr>
                            <td><?= htmlspecialchars($campanha['id']) ?></td>
                            <td><?= htmlspecialchars($campanha['numero_campanha']) ?></td>
                            <td><?= htmlspecialchars($campanha['status']) ?></td>
                            <td><?= htmlspecialchars($campanha['mensagem_envio']) ? htmlspecialchars($campanha['mensagem_envio']) : 'Sem envios' ?></td>
                            <td>
                                <button class="btn btn-primary" data-toggle="modal" data-target="#editarCampanhaModal<?= $campanha['id'] ?>">Editar</button>
                                <a class="btn btn-success" href="?baixar_numeros=<?= htmlspecialchars($campanha['numero_campanha']) ?>">Baixar Números da Campanha</a>
                            </td>
                        </tr>

                        <!-- Modal de Edição -->
                        <div class="modal fade" id="editarCampanhaModal<?= $campanha['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="editarCampanhaModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editarCampanhaModalLabel">Editar Campanha</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $campanha['id'] ?>">
                                            <div class="form-group">
                                                <label for="status">Status</label><br>
                                                <?= htmlspecialchars($campanha['status']) ?>
                                                <div class="form-group">
                                                    <label for="status">Status</label>
                                                    <select class="form-control" name="status" required>
                                                        <option value="1" <?= $campanha['status'] == '1' ? 'selected' : '' ?>>Aguardando Operadora</option>
                                                        <option value="2" <?= $campanha['status'] == '2' ? 'selected' : '' ?>>Pausada</option>
                                                        <option value="3" <?= $campanha['status'] == '3' ? 'selected' : '' ?>>Finalizado</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="numero_campanha">Número da Campanha</label>
                                                <input type="text" class="form-control" name="numero_campanha" value="<?= htmlspecialchars($campanha['numero_campanha']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary" name="editar_campanha">Salvar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginação para Campanhas -->
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_pages_campanhas; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>

            <!-- Envios -->
            <div class="tab-pane fade" id="envios" role="tabpanel" aria-labelledby="envios-tab">
                <h2>Envios</h2>

                <!-- Filtro para Envios -->
                <form method="POST">
                    <div class="form-group">
                        <input type="text" class="form-control" name="filtro_envio" placeholder="Filtrar por número da campanha" value="<?= htmlspecialchars($filtro_envio) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </form>

                <table class="table mt-3">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Número da Campanha</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Mensagem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($envios as $envio): ?>
                        <tr>
                            <td><?= htmlspecialchars($envio['id']) ?></td>
                            <td><?= htmlspecialchars($envio['numero_campanha']) ?></td>
                            <td><?= htmlspecialchars($envio['status_envio']) ?></td>
                            <td><?= htmlspecialchars($envio['data_hora_envio']) ?></td>
                            <td><?= htmlspecialchars($envio['mensagem']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Paginação para Envios -->
                <nav>
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_pages_envios; $i++): ?>
                        <li class="page-item <?= $i === $page_envios ? 'active' : '' ?>">
                            <a class="page-link" href="?page_envios=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>

            <!-- Usuários -->
            <div class="tab-pane fade" id="usuarios" role="tabpanel" aria-labelledby="usuarios-tab">
                <h2>Usuários</h2>

                <!-- Filtro para Usuários -->
                <form method="POST">
                    <div class="form-group">
                        <input type="text" class="form-control" name="filtro_usuario" placeholder="Filtrar por nome ou email" value="<?= htmlspecialchars($filtro_usuario ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </form>

                <table class="table mt-3">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Saldo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= htmlspecialchars($usuario['id']) ?></td>
                            <td><?= htmlspecialchars($usuario['nome']) ?></td>
                            <td><?= htmlspecialchars($usuario['email']) ?></td>
                            <td><?= htmlspecialchars($usuario['saldo']) ?></td>
                            <td>
                                <button class="btn btn-primary" data-toggle="modal" data-target="#editarUsuarioModal<?= $usuario['id'] ?>">Editar Saldo</button>
                            </td>
                        </tr>

                        <!-- Modal de Edição do Saldo -->
                        <div class="modal fade" id="editarUsuarioModal<?= $usuario['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="editarUsuarioModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editarUsuarioModalLabel">Editar Saldo do Usuário</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                                            <div class="form-group">
                                                <label for="saldo">Saldo</label>
                                                <input type="number" class="form-control" name="saldo" value="<?= htmlspecialchars($usuario['saldo']) ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="preco_por_numero">Preço por Número</label>
                                                <input class="form-control" name="preco_por_numero" value="<?= htmlspecialchars($usuario['preco_por_numero']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary" name="editar_usuario">Salvar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="container">
        <h1>Painel de Administração</h1>

        <!-- Abas para Mensagens e Encurtadores -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="mensagens-tab" data-toggle="tab" href="#mensagens" role="tab" aria-controls="mensagens" aria-selected="true">Mensagens</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="encurtadores-tab" data-toggle="tab" href="#encurtadores" role="tab" aria-controls="encurtadores" aria-selected="false">Encurtadores</a>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <!-- Mensagens -->
            <div class="tab-pane fade show active" id="mensagens" role="tabpanel" aria-labelledby="mensagens-tab">
                <h2>Mensagens</h2>

                <form method="POST" class="mb-3">
                    <div class="form-group">
                        <label for="mensagem">Nova Mensagem</label>
                        <textarea class="form-control" name="mensagem" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" name="criar_mensagem">Criar Mensagem</button>
                </form>

                <table class="table mt-3">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mensagem</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mensagens as $mensagem): ?>
                        <tr>
                            <td><?= htmlspecialchars($mensagem['id']) ?></td>
                            <td><?= htmlspecialchars($mensagem['mensagem']) ?></td>
                            <td>
                                <button class="btn btn-primary" data-toggle="modal" data-target="#editarMensagemModal<?= $mensagem['id'] ?>">Editar</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $mensagem['id'] ?>">
                                    <button type="submit" class="btn btn-danger" name="excluir_mensagem">Excluir</button>
                                </form>
                            </td>
                        </tr>

                        <!-- Modal de Edição -->
                        <div class="modal fade" id="editarMensagemModal<?= $mensagem['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="editarMensagemModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editarMensagemModalLabel">Editar Mensagem</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $mensagem['id'] ?>">
                                            <div class="form-group">
                                                <label for="mensagem">Mensagem</label>
                                                <textarea class="form-control" name="mensagem" required><?= htmlspecialchars($mensagem['mensagem']) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary" name="editar_mensagem">Salvar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Encurtadores -->
            <div class="tab-pane fade" id="encurtadores" role="tabpanel" aria-labelledby="encurtadores-tab">
                <h2>Encurtadores</h2>

                <form method="POST" class="mb-3">
                    <div class="form-group">
                        <label for="encurtador">Novo Encurtador</label>
                        <input type="text" class="form-control" name="encurtador" required>
                    </div>
                    <button type="submit" class="btn btn-success" name="criar_encurtador">Criar Encurtador</button>
                </form>

                <table class="table mt-3">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Encurtador</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($encurtadores as $encurtador): ?>
                        <tr>
                            <td><?= htmlspecialchars($encurtador['id']) ?></td>
                            <td><?= htmlspecialchars($encurtador['nome']) ?></td>
                            <td>
                                <button class="btn btn-primary" data-toggle="modal" data-target="#editarEncurtadorModal<?= $encurtador['id'] ?>">Editar</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $encurtador['id'] ?>">
                                    <button type="submit" class="btn btn-danger" name="excluir_encurtador">Excluir</button>
                                </form>
                            </td>
                        </tr>

                        <!-- Modal de Edição -->
                        <div class="modal fade" id="editarEncurtadorModal<?= $encurtador['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="editarEncurtadorModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editarEncurtadorModalLabel">Editar Encurtador</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $encurtador['id'] ?>">
                                            <div class="form-group">
                                                <label for="encurtador">Encurtador</label>
                                                <input type="text" class="form-control" name="encurtador" value="<?= htmlspecialchars($encurtador['nome']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary" name="editar_encurtador">Salvar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
