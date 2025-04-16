<?php
session_start();

require '../config/config.php';

if (!isset($_SESSION['usuario_id'])) {
  header("Location: /login/");
  exit;
}

$usuario_id = $_SESSION['usuario_id'];


// Define o caminho do arquivo de manutenção (usando maintenance.txt)
$maintenanceFile = '../config/maintenance.txt';
$manutencao_ativa = false;
if (file_exists($maintenanceFile)) {
  $manutencaoValue = trim(file_get_contents($maintenanceFile));
  $manutencao_ativa = ($manutencaoValue === '1');
}

$stmt = $pdo->prepare('SELECT id, mensagem FROM mensagens');
$stmt->execute();
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para obter os dados do usuário
$stmt = $pdo->prepare('SELECT id, nome, saldo, nivel_acesso, email FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Verifica se o usuário foi encontrado
if (!$usuario) {
  header("Location: /login/");
  exit;
}

// Acessa os dados do usuário
$nome = htmlspecialchars($usuario['nome']); // Atribui o nome do usuário
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
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu Principal</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css" rel="stylesheet">

  <style>
    button#btnPagar {
      background-color: #28a745;
      color: white;
      padding: 10px 20px;
      border: none;
      cursor: pointer;
      border-radius: 5px;
    }
  </style>
</head>

<body class="bg-gray-200 text-gray-800">
  <div class="flex h-screen">
    <!-- Menu Lateral Fixo e Expandido -->
    <aside class="w-64 text-blue-100 text-white flex flex-col fixed inset-0 z-10 lg:block hidden border-r-2 border-gray-400">
      <div class="w-full flex items-center justify-center pt-6 pb-4">
        <img src="https://i.ibb.co/p0cKr26/Captura-de-Tela-2024-12-13-a-s-08-54-39-removebg-preview.png" alt="Logo"
          class="w-32 mt-1">
      </div>

      <hr class="w-full border border-gray-400 mb-4">

      <nav class="flex-1 px-2">
        <ul class="space-y-2 p-4">
          <!-- Envio de SMS em Massa -->
          <li class="relative group" id="smsMenuItem">
            <button onclick="toggleSubmenu('submenuSmsDesktop')"
              class="flex items-center w-full p-2 text-left hover:text-blue-700 rounded text-gray-800">
              <i class="fas fa-sms mr-3"></i>
              <span class="font-bold">Envio de SMS em Massa</span>
              <i class="fas fa-chevron-down ml-auto transition-transform duration-300 transform"
                id="iconSmsDesktop"></i>
            </button>

            <ul id="submenuSmsDesktop" class="hidden flex-col space-y-1 pl-10 mt-2 text-sm">
              <li>
                <button id="smsLeveLink" onclick="showContent('smsLeveContent', 'smsLeveLink')"
                  class="block text-gray-800 font-semibold hover:text-blue-600 text-base">Disparo Leve</button>
              </li>
              <li>
                <button id="smsTurboLink" onclick="showContent('smsTurboContent', 'smsTurboLink')"
                  class="block text-gray-800 font-semibold hover:text-blue-600 text-base">Disparo Turbo</button>
              </li>
              <li>
                <button id="smsFlexLink" onclick="showContent('smsFlexContent', 'smsFlexLink')"
                  class="block text-gray-800 font-semibold hover:text-blue-600 text-base">Disparo Flex</button>
              </li>
            </ul>

          </li>

          <!-- Disparo WhatsApp -->
          <li id="menuItemId">
            <button onclick="showSmsForm('smsFormContainer', 'menuItemId')"
              class="flex items-center w-full p-2 text-left text-gray-800 hover:text-blue-700 rounded">
              <i class="fab fa-whatsapp mr-3"></i>
              <span class="font-bold">Disparo WhatsApp</span>
            </button>
          </li>

          <!-- Adicionar Saldo -->
          <li id="saldoMenuItem">
            <button onclick="showContent('saldoContent', 'saldoMenuItem')"
              class="flex items-center w-full p-2 text-left text-gray-800 hover:text-blue-700 rounded">
              <i class="fas fa-wallet mr-3"></i>
              <span class="font-bold">Adicionar Saldo</span>
            </button>
          </li>

          <li id="geradorTelefoneMenuItem">
            <button onclick="showContent('geradorContent', 'geradorTelefoneMenuItem')"
              class="flex items-center w-full p-2 text-left text-gray-800 hover:text-blue-700 rounded">
              <i class="fas fa-phone mr-3"></i>
              <span class="font-bold">Gerador de Telefone</span>
            </button>
          </li>

          <!-- Outros Itens Bloqueados -->
          <li class="opacity-50 cursor-not-allowed">
            <div class="flex items-center w-full p-2 text-gray-800 hover:text-blue-700">
              <i class="fas fa-mobile-alt mr-3"></i>
              <span class="font-bold">Consultar Operadora</span>
            </div>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- MOBILE e TABLET: menu hamburger -->
    <div class="flex flex-col lg:hidden items-center justify-between p-4 md:p-6 bg-gray-200 text-white fixed z-20 w-full">
      <header
        class="w-full flex gap-2 justify-end items-end bg-gray-200 text-gray-800 lg:hidden border-b-2 border-gray-200">
        <button id="hamburgerBtn" class="text-white focus:outline-none p-2">
          <i class="fas fa-bars text-2xl text-blue-700"></i> <!-- Ícone de menu hambúrguer -->
        </button>

        <div class="w-full flex flex-col gap-2 justify-end items-end text-gray-800">
          <!-- Dropdown de usuário -->
          <div class="relative">
            <div class="dropdown flex flex-col items-center">
              <!-- O link de ativação do dropdown -->
              <button id="userDropdownBtnMobile" class="flex items-center text-dark text-decoration-none">
                <img src="https://i.pinimg.com/736x/61/f7/5e/61f75ea9a680def2ed1c6929fe75aeee.jpg" alt="user" width="30"
                  height="30" class="rounded-full">
                <span class="ml-2">
                  <?php echo $nome; ?>
                </span>
                <i id="dropdownIconMobile"
                  class="ml-2 fas fa-chevron-down transition-transform duration-300 ease-in-out"></i>
              </button>

              <!-- O menu do dropdown -->
              <div id="dropdownMenuMobile"
                class="dropdown-menu absolute right-0 top-10 hidden bg-white border border-gray-300 rounded-lg shadow-lg w-52 z-50">
                <a class="dropdown-item flex items-start px-4 py-3 text-gray-700 hover:bg-gray-200" href="#">Menu
                  Principal</a>
                <a class="dropdown-item flex items-start px-4 py-2 text-gray-700 hover:bg-gray-200"
                  href="../dashboard/campanhas.php">Histórico de campanhas</a>
                <a class="dropdown-item flex items-start px-4 py-2 text-gray-700 hover:bg-gray-200"
                  href="?logout=true">Sair</a>
              </div>
            </div>
          </div>

          <!-- Exibe o saldo disponível com o ícone -->
          <div class="saldo flex items-center">
            <i class="fas fa-wallet mr-2 text-lg text-blue-700"></i> <!-- Ícone de Carteira -->
            <p class="text-sm text-gray-800">
              Saldo disponível: <strong class="ml-1 saldoText">
                R$ <?php echo number_format($usuario['saldo'], 2, ',', '.'); ?>
              </strong>
            </p>
          </div>
        </div>
      </header>

      <div class="flex justify-between items-center mt-5 gap-5 w-full">
        <!-- Botão de histórico de saldo -->
        <button type="button" onclick="showSaldoModal()"
          class="w-full flex items-center gap-2 h-12 px-3 py-2 text-gray-800 rounded-md border-none bg-blue-500 bg-opacity-90 hover:bg-opacity-100 text-center shadow-md hover:shadow-lg whitespace-nowrap">
          <i class="fas fa-history text-sm text-white"></i>
          <span class="text-gray-200 whitespace-nowrap text-sm lg:text-base">Histórico de Saldo</span>
        </button>

        <a href="../dashboard/campanhas.php"
          class="flex items-center gap-2 w-full h-12 px-3 py-2 text-gray-800 rounded-md border-none bg-blue-500 bg-opacity-90 hover:bg-opacity-100 text-center shadow-md hover:shadow-lg whitespace-nowrap"
          target="_blank">
          <i class="fas fa-clipboard-list text-sm text-gray-200"></i>
          <p class="text-gray-200 whitespace-nowrap text-sm lg:text-base">Histórico de Campanhas</p>
        </a>
      </div>
    </div>

    <!-- MOBILE e TABLET: NAV menu hamburger -->
    <aside id="mobileMenu"
      class="w-64 bg-gray-200 text-white flex flex-col fixed inset-0 z-30 transform translate-x-full transition-transform duration-500 ease-in-out border-r-2 border-gray-400 hidden lg:hidden">
      <div class="p-2 lg:p-6 flex flex-col justify-between items-center border-b-2 border-gray-400">
        <div class="w-full flex justify-end items-center mb-4 pr-6 mt-4">
          <!-- Botão de Fechar Menu -->
          <button id="closeMenuBtn" class="text-white focus:outline-none">
            <i class="fas fa-times text-3xl text-blue-700"></i> <!-- Ícone de fechar -->
          </button>
        </div>

        <!-- Logo -->
        <img src="https://i.ibb.co/p0cKr26/Captura-de-Tela-2024-12-13-a-s-08-54-39-removebg-preview.png" alt="Logo"
          class="w-32 mb-4">
      </div>

      <nav class="flex-1 px-2">
        <ul class="space-y-2 p-4">
          <!-- Envio de SMS em Massa -->
          <li class="relative group" id="smsMenuItemMobile">
            <button onclick="toggleSubmenu('submenuSmsMobile')"
              class="flex items-center w-full p-2 text-left hover:text-blue-700 rounded text-gray-800">
              <i class="fas fa-sms mr-3"></i>
              <span class="font-bold">Envio de SMS em Massa</span>
              <i class="fas fa-chevron-down ml-auto transition-transform duration-300 transform" id="iconSmsMobile"></i>
            </button>

            <ul id="submenuSmsMobile" class="hidden flex-col space-y-1 pl-10 mt-2 text-sm">
              <li>
                <button id="smsLeveLinkMobile" onclick="showContent('smsLeveContent', 'smsLeveLinkMobile')"
                  class="block text-gray-800 font-semibold hover:text-blue-600 text-base">Disparo Leve</button>
              </li>
              <li>
                <button id="smsTurboLinkMobile" onclick="showContent('smsTurboContent', 'smsTurboLinkMobile')"
                  class="block text-gray-800 font-semibold hover:text-blue-600 text-base">Disparo Turbo</button>
              </li>
              <li>
                <button id="smsFlexLinkMobile" onclick="showContent('smsFlexContent', 'smsFlexLinkMobile')"
                  class="block text-gray-800 font-semibold hover:text-blue-600 text-base">Disparo Flex</button>
              </li>
            </ul>
          </li>

          <!-- Disparo WhatsApp -->
          <li id="menuItemId">
            <button onclick="showSmsForm('smsFormContainer', 'menuItemId')"
              class="flex items-center w-full p-2 text-left text-gray-800 hover:text-blue-700 rounded">
              <i class="fab fa-whatsapp mr-3"></i>
              <span class="font-bold">Disparo WhatsApp</span>
            </button>
          </li>

          <!-- Adicionar Saldo -->
          <li id="saldoMenuItem">
            <button onclick="showContent('saldoContent', 'saldoMenuItem')"
              class="flex items-center w-full p-2 text-left text-gray-800 hover:text-blue-700 rounded">
              <i class="fas fa-wallet mr-3"></i>
              <span class="font-bold">Adicionar Saldo</span>
            </button>
          </li>

          <li id="geradorTelefoneMenuItem">
            <button onclick="showContent('geradorContent', 'geradorTelefoneMenuItem')"
              class="flex items-center w-full p-2 text-left text-gray-800 hover:text-blue-700 rounded">
              <i class="fas fa-phone mr-3"></i>
              <span class="font-bold">Gerador de Telefone</span>
            </button>
          </li>

          <!-- Outros Itens Bloqueados -->
          <li class="opacity-50 cursor-not-allowed">
            <div class="flex items-center w-full p-2 text-gray-800 hover:text-blue-700">
              <i class="fas fa-mobile-alt mr-3"></i>
              <span class="font-bold">Consultar Operadora</span>
            </div>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="flex-1 lg:ml-64 p-4 lg:p-0">
      <header
        class="w-full mb-6 flex justify-between p-4 lg:px-4 items-center border-b-2 border-gray-200 bg-gray-200 hidden lg:flex">
        <div class="flex justify-between items-center gap-3 xl:gap-5 w-full max-w-xs">
          <!-- Botão de histórico de saldo -->
          <button type="button" onclick="showSaldoModal()"
            class="w-full px-3 py-2 text-gray-800 rounded-md border-none bg-blue-500 bg-opacity-90 hover:bg-opacity-100 text-center shadow-md hover:shadow-lg whitespace-nowrap">
            <i class="fas fa-history text-sm text-white"></i>
            <span class="text-gray-200 whitespace-nowrap">Histórico de Saldo</span>
          </button>

          <a href="../dashboard/campanhas.php"
            class="flex items-center gap-2 w-full px-3 py-2 text-gray-800 rounded-md border-none bg-blue-500 bg-opacity-90 hover:bg-opacity-100 text-center shadow-md hover:shadow-lg whitespace-nowrap"
            target="_blank">
            <i class="fas fa-clipboard-list text-sm text-gray-200"></i>
            <p class="text-gray-200 whitespace-nowrap">Histórico de Campanhas</p>
          </a>
        </div>

        <div class="flex lg:flex-col-reverse xl:flex-row lg:items-end xl:items-center text-end gap-4 w-full max-w-md">
          <!-- Exibe o saldo disponível com o ícone -->
          <div class="saldo flex items-end mt-2 xl:w-full">
            <i class="fas fa-wallet mr-2 text-xl text-blue-700"></i> <!-- Ícone de Carteira -->
            <p>
              Saldo disponível: <strong class="ml-1 saldoText">
                R$ <?php echo number_format($usuario['saldo'], 2, ',', '.'); ?>
              </strong>
            </p>
          </div>

          <!-- Dropdown de usuário -->
          <div class="relative">
            <div class="dropdown flex flex-col lg:items-start xl:items-center">
              <!-- O link de ativação do dropdown -->
              <button id="userDropdownBtn" class="flex items-center text-dark text-decoration-none">
                <i class="ml-2 fas fa-user-circle text-2xl"></i>
                <span class="ml-2 whitespace-nowrap">
                  <?php echo $nome; ?>
                </span>
                <i id="dropdownIcon" class="ml-2 fas fa-chevron-down transition-transform duration-300 ease-in-out"></i>
              </button>

              <!-- O menu do dropdown -->
              <div id="dropdownMenu"
                class="dropdown-menu absolute right-0 top-10 hidden bg-white border border-gray-300 rounded-lg shadow-lg w-52 z-50">
                <a class="dropdown-item flex items-start px-4 py-3 text-gray-700 hover:bg-gray-200" href="#">Menu
                  Principal</a>
                <a class="dropdown-item flex items-start px-4 py-2 text-gray-700 hover:bg-gray-200 whitespace-nowrap"
                  href="../dashboard/campanhas.php">Histórico de campanhas</a>
                <a class="dropdown-item flex items-start px-4 py-2 text-gray-700 hover:bg-gray-200"
                  href="?logout=true">Sair</a>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Conteúdo de Envio de SMS -->
      <div id="smsContent"
        class="modal mt-40 md:mt-46 lg:mt-0 content-section bg-white p-6 rounded-lg shadow-lg w-full lg:max-w-2xl xl:max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl text-gray-800 font-semibold">Envio de SMS</h2>
          <button onclick="hideContent('smsContent')" class="text-xl text-gray-500"></button>
        </div>
        <div class="flex flex-col gap-4 items-start">
          <p class="text-gray-800 font-semibold">Digite a mensagem a ser enviada:</p>
          <textarea id="smsMessage" rows="4" maxlength="200" placeholder="Digite sua mensagem"
            class="w-full p-3 border border-gray-300 rounded-md text-base text-gray-800 font-normal"></textarea>

          <p class="text-gray-800 font-semibold">Carregar lista de números (.txt):</p>
          <input type="file" id="fileUpload" accept=".txt" onchange="handleFileChange()"
            class="w-full p-3 border border-gray-300 rounded-md text-gray-800 font-semibold">

          <p id="fileStatus" class="text-gray-800 font-semibold">Nenhum arquivo carregado</p>
          <p id="smsQuantityText" class="text-gray-800"></p>

          <p class="total text-yellow-600 font-semibold">
            Valor do envio: <span class="">-</span> R$
            <span id="smsTotal">0,00</span>
          </p>

          <p class="text-gray-800 font-semibold text-xl">Saldo restante: <span id="discountedBalance">R$ 0,00</span></p>

          <hr class="border border-gray-200 w-full">

          <div class="w-full flex flex-col md:flex-row gap-4 px-1 md:-px-4 justify-between items-start lg:items-center">
            <div class="w-full flex items-center gap-2">
              <p class="w-full text-blue-500 font-semibold text-2xl whitespace-nowrap">Saldo atual: <span
                  id="userBalance" class="text-lg text-gray-900">R$
                  <?php echo number_format($usuario['saldo'], 2, ',', '.'); ?>
                </span></p>
            </div>

            <div class="flex w-full justify-end gap-3">
              <button id="sendSmsButton"
                class="w-full confirmaCompra px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 hover:shadow-sm"
                onclick="sendSms()" disabled>Enviar</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Formulário de Campanha de SMS -->
      <div id="smsFormContainer" class="content-section">
        <div class="bg-white mt-40 md:mt-46 lg:mt-0 p-6 rounded-lg shadow-lg lg:max-w-2xl xl:max-w-3xl w-full mx-auto">
          <h4 class="w-full flex text-xl lg:text-2xl font-semibold text-gray-800 mb-6 items-start">Configurar Campanha
            de
            SMS</h4>
          <form id="smsForm" method="POST" class="w-full flex flex-col gap-5">

            <!-- Números de Telefone -->
            <div class="form-group w-full flex flex-col items-start gap-2">
              <label for="numeros" class="text-lg font-medium text-gray-700">Números de Telefone (um por linha)</label>
              <textarea
                class="p-4 w-full border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                id="numeros" rows="3" placeholder="Exemplo de Formatação Correta: 5511988888888"></textarea>
              <small id="numero-contador" class="text-sm text-gray-500">0 / 30.000 números</small>
            </div>

            <!-- Escolher Mensagem -->
            <div class="form-group w-full flex flex-col items-start gap-2">
              <label for="mensagem" class="text-lg font-medium text-gray-700">Escolher Mensagem</label>
              <select
                class="p-3 w-full border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                id="mensagem" onchange="atualizarMensagem()">
                <?php foreach ($mensagens as $mensagem): ?>
                  <option value="<?= htmlspecialchars($mensagem['mensagem']) ?>">
                    <?= htmlspecialchars($mensagem['mensagem']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Link a ser Encurtado -->
            <div class="form-group w-full flex flex-col items-start gap-2">
              <label for="link" class="text-lg font-medium text-gray-700">Link a ser encurtado</label>
              <input type="url"
                class="p-3 w-full border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                id="link" placeholder="Digite o SEU Link" oninput="atualizarMensagem()">
            </div>

            <!-- Escolher Encurtador -->
            <div class="form-group w-full flex flex-col items-start gap-2">
              <label for="encurtador" class="text-lg font-medium text-gray-700">Escolher Encurtador</label>
              <select
                class="p-3 w-full border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                id="encurtador" onchange="atualizarMensagem()">
                <?php foreach ($encurtadores as $encurtador): ?>
                  <option value="<?= htmlspecialchars($encurtador['nome']) ?>">
                    <?= htmlspecialchars($encurtador['nome']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Mensagem Gerada -->
            <div class="form-group w-full flex flex-col items-start gap-2">
              <label for="mensagem-gerada" class="text-lg font-medium text-gray-700">Mensagem Gerada</label>
              <textarea
                class="bg-gray-200 p-3 w-full border border-gray-300 rounded-md focus:ring focus:ring-none focus:outline-none"
                id="mensagem-gerada" rows="3" readonly></textarea>
            </div>

            <!-- Botão Iniciar Campanha -->
            <button type="button" id="iniciar-campanha"
              class="w-full py-3 mt-5 bg-blue-600 text-white rounded-md text-lg hover:bg-blue-700 transition duration-300">
              Iniciar Campanha e Enviar SMS
            </button>
          </form>
        </div>

        <!-- Loading de Progresso -->
        <div id="progresso-campanha" class="mt-8 hidden">
          <h5 class="text-xl font-semibold text-gray-700">Progresso do Envio</h5>
          <div class="w-full bg-gray-200 rounded-full h-2.5 mt-3">
            <div class="progress-bar bg-blue-600 h-2.5 rounded-full" style="width: 0%;" aria-valuenow="0"
              aria-valuemin="0" aria-valuemax="100">0%</div>
          </div>
          <div class="flex gap-8 mt-4">
            <div class="w-1/2">
              <h6 class="text-lg font-medium text-gray-700">Enviados</h6>
              <ul id="sms-enviados" class="list-group mt-2 hidden"></ul>
            </div>
            <div class="w-1/2">
              <h6 class="text-lg font-medium text-gray-700">Não Enviados</h6>
              <ul id="sms-nao-enviados" class="list-group mt-2 hidden"></ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Conteúdo de Adicionar Saldo -->
      <div id="saldoContent"
        class="modal mt-40 md:mt-46 lg:mt-0  content-section p-6 lg:max-w-2xl xl:max-w-3xl bg-white rounded-lg shadow-lg w-full mx-auto">
        <div class="modal-content">
          <div class="modal-header flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">Adicionar Saldo de SMS</h2>
          </div>

          <p>Insira a quantidade de SMS desejada:</p>
          <input type="number" id="smsQuantityAdd" min="10" placeholder="Digite a quantidade de SMS"
            oninput="calculateTotal()" class="w-full p-3 border border-gray-300 rounded-md text-lg">

          <p id="transaction-taxa" class="font-italic">Será cobrada uma taxa de R$ 1,00 pela transação.</p>

          <!-- Observação CPF -->
          <p class="text-gray-700 mt-2 font-semibold"><span class="text-blue-500">OBS:</span> O CPF informado deve ser o mesmo do titular do banco que irá acrescentar o saldo.</p>

          <hr class="border border-gray-200 w-full my-3">

          <div class="w-full flex flex-col md:flex-row gap-4 px-1 md:-px-4 justify-between items-start lg:items-center">
            <div class="w-full flex items-center gap-2">
              <p class="total text-blue-500 font-semibold text-2xl whitespace-nowrap">
                Valor: <span class="text-lg text-gray-900">R$</span> <span id="totalAmount"
                  class="text-lg text-gray-900">0,00</span>
              </p>
            </div>
            <div class="w-full flex justify-end gap-3">
              <button class="w-full px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md"
                onclick="confirmPurchase()">Confirmar</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Disparo Flex -->
      <div id="smsFlexContent" class="modal hidden mt-40 md:mt-46 lg:mt-0 content-section bg-white p-6 rounded-lg shadow-lg w-full lg:max-w-2xl xl:max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl text-gray-800 font-semibold">FLEX</h2>
        </div>

        <div class="flex flex-col gap-4 items-start">
          <p class="text-gray-800 font-semibold">Digite a mensagem a ser enviada:</p>
          <textarea id="smsFlexMessage" rows="4" maxlength="200" placeholder="Digite sua mensagem"
            class="w-full p-3 border border-gray-300 rounded-md text-base text-gray-800 font-normal"></textarea>

          <p class="text-gray-800 font-semibold">Carregar lista de números (.txt):</p>
          <input type="file" id="flexFile" accept=".txt"
            class="w-full p-3 border border-gray-300 rounded-md text-gray-800 font-semibold"
            onchange="handleFlexFile()">

          <p id="flexFileStatus" class="text-gray-800 font-semibold">Nenhum arquivo carregado</p>
          <p id="flexQuantityText" class="text-gray-800"></p>

          <p class="total text-yellow-600 font-semibold">
            Valor do envio: <span class="">-</span> R$
            <span id="flexTotal">0,00</span>
          </p>

          <p class="text-gray-800 font-semibold text-xl">Saldo restante: <span id="flexSaldoRestante">R$ 0,00</span></p>

          <hr class="border border-gray-200 w-full">

          <div class="w-full flex flex-col md:flex-row gap-4 justify-between items-start lg:items-center">
            <p class="w-full text-blue-500 font-semibold text-2xl whitespace-nowrap">
              Saldo atual: <span id="userBalance" class="text-lg text-gray-900">R$ <?php echo number_format($usuario['saldo'], 2, ',', '.'); ?></span>
            </p>
            <button id="btnEnviarFlex" class="w-full confirmaCompra px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 hover:shadow-sm" disabled>
              Enviar
            </button>
          </div>
        </div>
      </div>

      <!-- Disparo Turbo -->
      <div id="smsTurboContent" class="modal hidden mt-40 md:mt-46 lg:mt-0 content-section bg-white p-6 rounded-lg shadow-lg w-full lg:max-w-2xl xl:max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl text-gray-800 font-semibold">TURBO</h2>
        </div>

        <div class="flex flex-col gap-4 items-start">
          <p class="text-gray-800 font-semibold">Digite a mensagem a ser enviada:</p>
          <textarea id="smsTurboMessage" rows="4" maxlength="200" placeholder="Digite sua mensagem"
            class="w-full p-3 border border-gray-300 rounded-md text-base text-gray-800 font-normal"></textarea>

          <p class="text-gray-800 font-semibold">Carregar lista de números (.txt):</p>
          <input type="file" id="turboFile" accept=".txt"
            class="w-full p-3 border border-gray-300 rounded-md text-gray-800 font-semibold"
            onchange="handleTurboFile()">

          <p id="turboFileStatus" class="text-gray-800 font-semibold">Nenhum arquivo carregado</p>
          <p id="turboQuantityText" class="text-gray-800"></p>

          <p class="total text-yellow-600 font-semibold">
            Valor do envio: <span class="">-</span> R$
            <span id="turboTotal">0,00</span>
          </p>

          <p class="text-gray-800 font-semibold text-xl">Saldo restante: <span id="turboSaldoRestante">R$ 0,00</span></p>

          <hr class="border border-gray-200 w-full">

          <div class="w-full flex flex-col md:flex-row gap-4 justify-between items-start lg:items-center">
            <p class="w-full text-blue-500 font-semibold text-2xl whitespace-nowrap">
              Saldo atual: <span id="userBalance" class="text-lg text-gray-900">R$ <?php echo number_format($usuario['saldo'], 2, ',', '.'); ?></span>
            </p>
            <button id="btnEnviarTurbo" class="w-full confirmaCompra px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 hover:shadow-sm" disabled>
              Enviar
            </button>
          </div>
        </div>
      </div>

      <!-- Disparo Leve -->
      <div id="smsLeveContent" class="modal hidden mt-40 md:mt-46 lg:mt-0 content-section bg-white p-6 rounded-lg shadow-lg w-full lg:max-w-2xl xl:max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl text-gray-800 font-semibold">LEVE</h2>
        </div>

        <div class="flex flex-col gap-4 items-start">
          <p class="text-gray-800 font-semibold">Digite a mensagem a ser enviada:</p>
          <textarea id="smsLeveMessage" rows="4" maxlength="200" placeholder="Digite sua mensagem"
            class="w-full p-3 border border-gray-300 rounded-md text-base text-gray-800 font-normal"></textarea>

          <p class="text-gray-800 font-semibold">Carregar lista de números (.txt):</p>
          <input type="file" id="leveFile" accept=".txt"
            class="w-full p-3 border border-gray-300 rounded-md text-gray-800 font-semibold"
            onchange="handleLeveFile()">

          <p id="leveFileStatus" class="text-gray-800 font-semibold">Nenhum arquivo carregado</p>
          <p id="leveQuantityText" class="text-gray-800"></p>

          <p class="total text-yellow-600 font-semibold">
            Valor do envio: <span class="">-</span> R$
            <span id="leveTotal">0,00</span>
          </p>

          <p class="text-gray-800 font-semibold text-xl">Saldo restante: <span id="leveSaldoRestante">R$ 0,00</span></p>

          <hr class="border border-gray-200 w-full">

          <div class="w-full flex flex-col md:flex-row gap-4 justify-between items-start lg:items-center">
            <p class="w-full text-blue-500 font-semibold text-2xl whitespace-nowrap">
              Saldo atual: <span id="userBalance" class="text-lg text-gray-900">R$ <?php echo number_format($usuario['saldo'], 2, ',', '.'); ?></span>
            </p>
            <button id="btnEnviarLeve" class="w-full confirmaCompra px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 hover:shadow-sm" disabled>
              Enviar
            </button>
          </div>
        </div>
      </div>

      <!-- Modal de Dados da Transação -->
      <div id="transactionModal"
        class="modal flex flex-col content-section bg-white p-6 rounded-lg shadow-lg w-full lg:max-w-2xl xl:max-w-3xl mx-auto mt-40 md:mt-46 lg:mt-0"
        style="display: none;">
        <div class="flex justify-between items-center mb-6">
          <h4 class="w-full flex text-xl lg:text-2xl font-semibold text-gray-800 items-start">Detalhes da Transação
          </h4>
          <button onclick="closeTransactionModal()" class="text-4xl font-bold text-gray-800">&times;</button>
        </div>

        <div class="flex flex-col gap-4 items-start">
          <div class="flex items-center gap-2 w-full">
            <p class="text-gray-800 text-lg font-bold">Número da Solicitação:</p>
            <p id="requestNumber" class="text-gray-800 mt-1"></p>
          </div>

          <div class="flex items-center gap-2 w-full">
            <p class="text-gray-800 text-lg font-bold">Data de Vencimento:</p>
            <p id="dueDate" class="text-gray-800 mt-1"></p>
          </div>

          <div class="flex items-center gap-2 w-full">
            <p class="total text-blue-500 font-semibold text-2xl whitespace-nowrap">Valor Total:</p>
            <p id="totalAmountTransaction" class="text-lg text-gray-900 font-bold mt-1">R$ 0,00</p>
          </div>

          <ul id="productList" class="list-disc pl-5 text-gray-800"></ul>

          <!-- Código Pix só aparecerá quando o QR Code for gerado -->
          <h3 id="pixTitle" class="text-gray-800 font-semibold hidden">Código Pix:</h3>

          <div id="qrcodeContainer" class="hidden flex justify-between items-center gap-5">
            <!-- QR Code -->
            <div id="qrcode" class="w-30 h-30 flex-shrink-0"></div>
          </div>

          <!-- Código Pix com ícone para copiar, só visível após gerar o QR Code -->
          <div id="paymentCodeContainer" style="display: none;" class="w-full max-w-full flex flex-col items-start">
            <p id="paymentCode"
              class="bg-green-100 p-2 rounded-md w-full max-w-xs md:max-w-xl lg:max-w-3xl break-words text-gray-800">
            </p>
          </div>

          <div id="copyContainer" style="display: none;" class="flex flex-col items-center justify-center">
            <button id="copyButton"
              class="flex items-center gap-2 px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
              onclick="copyPaymentCode()">
              <i id="copyIcon" class="fa fa-copy text-lg" title="Copiar código Pix"></i> Copiar Código
            </button>
          </div>

          <div class="w-full flex flex-col justify-between gap-2 items-center mt-6">
            <button id="btnPagar"
              class="w-full px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 hover:shadow-sm">Gerar
              QrCode</button>
            <button onclick="closeTransactionModal()"
              class="w-full rounded-md bg-blue-600 hover:bg-blue-700 px-4 py-2 text-gray-100">Fechar</button>
          </div>
        </div>
      </div>

      <!-- Modal do Gerador de Número -->
      <div id="geradorContent" class="modal mt-40 md:mt-46 lg:mt-0  w-full content-section p-6 lg:max-w-2xl xl:max-w-3xl w-full mx-auto">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">Gerador de Número por Estado</h2>
          </div>

          <div class="flex flex-col gap-4">
            <label for="estadoSelect" class="text-gray-700 font-semibold">Escolha o Estado:</label>
            <select id="estadoSelect" class="p-3 border border-gray-300 rounded">
              <option value="">Aleatório</option>
              <option value="AC">Acre</option>
              <option value="AL">Alagoas</option>
              <option value="AM">Amazonas</option>
              <option value="AP">Amapá</option>
              <option value="BA">Bahia</option>
              <option value="CE">Ceará</option>
              <option value="DF">Distrito Federal</option>
              <option value="ES">Espírito Santo</option>
              <option value="GO">Goiás</option>
              <option value="MA">Maranhão</option>
              <option value="MT">Mato Grosso</option>
              <option value="MS">Mato Grosso do Sul</option>
              <option value="MG">Minas Gerais</option>
              <option value="PA">Pará</option>
              <option value="PB">Paraíba</option>
              <option value="PR">Paraná</option>
              <option value="PE">Pernambuco</option>
              <option value="PI">Piauí</option>
              <option value="RJ">Rio de Janeiro</option>
              <option value="RN">Rio Grande do Norte</option>
              <option value="RS">Rio Grande do Sul</option>
              <option value="RO">Rondônia</option>
              <option value="RR">Roraima</option>
              <option value="SC">Santa Catarina</option>
              <option value="SP">São Paulo</option>
              <option value="SE">Sergipe</option>
              <option value="TO">Tocantins</option>
            </select>

            <!-- Nova Seção: Seleção de Pacotes -->
            <div class="mb-6">
              <h3 class="text-lg font-bold text-gray-800 mb-2">Escolha um pacote:</h3>
              <div id="listaPacotes" class="flex justify-center flex-wrap gap-4">
                <!-- Pacote 1: 100 números por R$ 5 -->
                <div class="pacote border border-gray-300 rounded-lg p-4 flex-1 md:flex-2 cursor-pointer hover:shadow-lg transition"
                  data-quantidade="100" data-preco="5" data-pacote="1">
                  <h4 class="font-bold">Pacote 1</h4>
                  <p class="text-gray-700">100 números</p>
                  <p class="text-gray-700">R$ 5,00</p>
                </div>

                <!-- Pacote 2: 1.000 números por R$ 50 -->
                <div class="pacote border border-gray-300 rounded-lg p-4 flex-1 md:flex-2 cursor-pointer hover:shadow-lg transition"
                  data-quantidade="1000" data-preco="50" data-pacote="2">
                  <h4 class="font-bold">Pacote 2</h4>
                  <p class="text-gray-700">1.000 números</p>
                  <p class="text-gray-700">R$ 50,00</p>
                </div>

                <!-- Pacote 3: 10.000 números por R$ 500 -->
                <div class="pacote border border-gray-300 rounded-lg p-4 flex-1 md:flex-2 cursor-pointer hover:shadow-lg transition"
                  data-quantidade="10000" data-preco="500" data-pacote="3">
                  <h4 class="font-bold">Pacote 3</h4>
                  <p class="text-gray-700">10.000 números</p>
                  <p class="text-gray-700">R$ 500,00</p>
                </div>

                <!-- Pacote 4: 100.000 números por R$ 5.000 -->
                <div class="pacote border border-gray-300 rounded-lg p-4 flex-1 md:flex-2 cursor-pointer hover:shadow-lg transition"
                  data-quantidade="100000" data-preco="5000" data-pacote="4">
                  <h4 class="font-bold">Pacote 4</h4>
                  <p class="text-gray-700">100.000 números</p>
                  <p class="text-gray-700">R$ 5.000,00</p>
                </div>

                <!-- Pacote 5: 1.000.000 números por R$ 50.000 -->
                <div class="pacote border border-gray-300 rounded-lg p-4 flex-1 md:flex-2 cursor-pointer hover:shadow-lg transition"
                  data-quantidade="1000000" data-preco="50000" data-pacote="5">
                  <h4 class="font-bold">Pacote 5</h4>
                  <p class="text-gray-700">1.000.000 números</p>
                  <p class="text-gray-700">R$ 50.000,00</p>
                </div>
              </div>

              <!-- Botão para confirmar seleção do pacote -->
              <div class="mt-6 text-center">
                <button id="btnConfirmarPacote" class="text-white px-4 py-2 rounded bg-blue-600 hover:bg-blue-700">
                  Confirmar Pacote
                </button>
              </div>
            </div>
            <button id="btnGerarNumeros" onclick="gerarListaNumeros()"
              class="hidden text-white px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 relative">
              <span id="loadingSpinner" class="hidden absolute left-1/2 transform -translate-x-1/2 text-white animate-spin">
                <i class="fas fa-spinner fa-spin"></i> <!-- Ícone de spinner -->
              </span>
              <span id="btnText">Gerar Número</span>
            </button>

            <!-- Botão de download -->
            <button id="btnDownloadTxt"
              onclick="baixarListaTxt()"
              class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 hidden mt-4">
              Baixar Lista .txt
            </button>


            <div id="listaNumeros" class="hidden text-center mt-4 space-y-1 font-mono text-blue-700 text-lg"></div>
          </div>
        </div>
      </div>
    </main>

    <div id="saldoModal" class="hidden bg-black bg-opacity-70 fixed z-50 inset-0 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-dark-bg rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full md:max-w-2xl lg:max-w-3xl xl:max-w-4xl">
          <div class="bg-white dark:bg-dark-bg px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <!-- header -->
            <div class="modal-header flex justify-between items-center mb-4">
              <h1 class='text-base text-neutral-700 dark:text-gray-200 font-semibold'>Histórico de Saldo</h1>
              <button type="button" onclick="closeSaldoModal()" class="bg-transparent border-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-6 w-6 text-gray-600 hover:text-gray-800">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- body -->
            <div class='mt-[14px] flex flex-col md:justify-around'>
              <div class="p-6">
                <ul class="space-y-4">
                  <?php if (empty($historico_saldo)): ?>
                    <li class="text-center text-gray-500">Nenhum histórico encontrado.</li>
                  <?php else: ?>
                    <?php foreach ($historico_saldo as $registro): ?>
                      <?php
                      // Determina o ícone e texto com base no tipo
                      if ($registro['tipo'] === 'adicao') {
                        $icon = '<i class="fas fa-plus-circle text-green-500"></i>';
                        $tipo_texto = 'Adição';
                      } else {
                        $icon = '<i class="fas fa-minus-circle text-red-500"></i>';
                        $tipo_texto = 'Retirada';
                      }
                      ?>
                      <li class="flex justify-between items-center">
                        <div class="flex items-center space-x-2">
                          <?php echo $icon; ?>
                          <span><?php echo $tipo_texto; ?> de R$ <?php echo number_format($registro['valor'], 2, ',', '.'); ?></span>
                        </div>
                        <span class="text-sm text-gray-500"><?php echo date('d/m/Y H:i:s', strtotime($registro['data_hora'])); ?></span>
                      </li>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </ul>
              </div>
            </div>
          </div>

          <!-- footer -->
          <div class="bg-gray-50 dark:bg-dark-bg px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="button" onclick="showContent('saldoContent', 'saldoMenuItem'); closeSaldoModal();" class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
              Adicionar Saldo
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <link rel="stylesheet"
    href="https://cdn.positus.global/production/resources/robbu/whatsapp-button/whatsapp-button.css">

  <script>
    function toggleSubmenu(id) {
      const submenu = document.getElementById(id);
      const icon = id === 'submenuSmsDesktop' ? document.getElementById('iconSmsDesktop') : document.getElementById('iconSmsMobile');

      if (submenu.classList.contains('hidden')) {
        submenu.classList.remove('hidden');
        submenu.classList.add('flex');
        icon.classList.add('rotate-180');
      } else {
        submenu.classList.add('hidden');
        submenu.classList.remove('flex');
        icon.classList.remove('rotate-180');
      }
    }
    // TESTE DO JS DO SMS -mover dps
    document.addEventListener("DOMContentLoaded", function() {
      const btnFlex = document.getElementById("btnEnviarFlex");
      const btnTurbo = document.getElementById("btnEnviarTurbo");
      const btnLeve = document.getElementById("btnEnviarLeve");

      if (btnFlex) {
        btnFlex.addEventListener("click", () => enviarSmsPorTipo('flex'));
      }

      if (btnTurbo) {
        btnTurbo.addEventListener("click", () => enviarSmsPorTipo('turbo'));
      }

      if (btnLeve) {
        btnLeve.addEventListener("click", () => enviarSmsPorTipo('leve'));
      }
    });

    function validarEnvioModal(tipo) {
      const mensagem = document.getElementById(`sms${capitalize(tipo)}Message`);
      const botao = document.getElementById(`btnEnviar${capitalize(tipo)}`);

      if (!mensagem || !botao) return;

      const mensagemPreenchida = mensagem.value.trim().length > 0;
      const numerosValidos = window.numbersWithNames && window.numbersWithNames.length >= 10;

      botao.disabled = !(mensagemPreenchida && numerosValidos);
    }

    document.getElementById("smsFlexMessage").addEventListener("input", () => validarEnvioModal("flex"));
    document.getElementById("smsTurboMessage").addEventListener("input", () => validarEnvioModal("turbo"));
    document.getElementById("smsLeveMessage").addEventListener("input", () => validarEnvioModal("leve"));


    function capitalize(str) {
      return str.charAt(0).toUpperCase() + str.slice(1);
    }


    function enviarSmsPorTipo(tipoDisparo) {
      console.log("Tipo de disparo:", tipoDisparo); // Debug: Verifica o tipo de disparo atual
      let textareaId = '';
      if (tipoDisparo === 'flex') textareaId = 'smsFlexMessage';
      else if (tipoDisparo === 'turbo') textareaId = 'smsTurboMessage';
      else if (tipoDisparo === 'leve') textareaId = 'smsLeveMessage';

      const smsMessage = document.getElementById(textareaId)?.value?.trim() || '';

      if (!smsMessage) {
        alert("Digite a mensagem antes de enviar.");
        return;
      }

      if (!window.numbersWithNames || window.numbersWithNames.length === 0) {
        alert("Você precisa carregar um arquivo válido antes de enviar.");
        return;
      }

      const numbers = window.numbersWithNames;
      const phoneMessageSending = numbers.map((recipient, index) => {
        const phone = recipient.t;
        const name = recipient.n || '';
        const message = smsMessage.replace(/{name}/g, name);
        const external_id = generateExternalId(index);
        return {
          phone,
          message,
          external_id
        };
      });

      // Definir preço por tipo
      let pricePerSms = 0.12;
      if (tipoDisparo === 'turbo') pricePerSms = 0.14;
      else if (tipoDisparo === 'flex') pricePerSms = 0.09;
      else if (tipoDisparo === 'leve') pricePerSms = 0.08;

      const total = phoneMessageSending.length * pricePerSms;

      const userBalanceText = document.getElementById("userBalance").innerText;
      const userBalance = parseFloat(userBalanceText.replace("R$", "").replace(".", "").replace(",", ".").trim());

      if (userBalance < total) {
        alert("Saldo insuficiente para enviar os SMS.");
        return;
      }

      const dataToSend = {
        phone_message_sending: phoneMessageSending,
        total: total,
        tipo: tipoDisparo
      };

      console.log("Enviando SMS com tipo:", tipoDisparo, dataToSend);
      sendSmsToServer(dataToSend);
    }

    // Função para calcular o custo do SMS e verificar os limites
    function calculateSmsCost() {
      let quantity = parseInt(document.getElementById("smsQuantity").value);

      // Garantir que a quantidade não ultrapasse 1000
      if (quantity > 1000) {
        alert("A quantidade máxima de SMS é 1000.");
        document.getElementById("smsQuantity").value = 1000; // Limita a quantidade a 1000
        quantity = 1000; // Atualiza a quantidade para 1000
      }

      // Pega o saldo diretamente do HTML
      let userBalanceText = document.getElementById("userBalance").innerText;

      // Remove "R$" e formatação de milhar com ponto e vírgula, converte para número
      let userBalance = parseFloat(userBalanceText.replace("R$", "").replace(".", "").replace(",", ".").trim());

      if (isNaN(quantity) || quantity < 10) {
        document.getElementById("smsTotal").innerText = "0,00";
        document.getElementById("discountedBalance").innerText = formatCurrency(userBalance);
        return;
      }

      // Lógica para preço escalonado
      let pricePerSms = 0.12; // Preço padrão
      if (quantity >= 500000) {
        pricePerSms = 0.11; // Preço reduzido para acima de 500.000 SMS
      }

      // Calculando o valor total
      let total = quantity * pricePerSms;

      // Exibe o valor total a ser pago pelos SMS
      document.getElementById("smsTotal").innerText = formatCurrency(total);

      // Verifica se o saldo é suficiente
      if (userBalance >= total) {
        // Calcular o saldo restante após o desconto
        let discountedBalance = userBalance - total;

        // Exibe o saldo restante após o desconto
        document.getElementById("discountedBalance").innerText = formatCurrency(discountedBalance);
      } else {
        // Caso o saldo não seja suficiente
        document.getElementById("discountedBalance").innerText = "Saldo insuficiente";
      }
    }


    // Função para validar se há mensagem antes de permitir o envio
    function validateMessage() {
      let smsMessage = document.getElementById("smsMessage").value.trim();
      let sendButton = document.getElementById("sendSmsButton");
      if (smsMessage.length > 0) {
        sendButton.disabled = false; // Ativa o botão de envio
      } else {
        sendButton.disabled = true; // Desativa o botão de envio
      }
    }


    // Adicionando o evento para verificar a mensagem ao digitar
    document.getElementById("smsMessage").addEventListener("input", validateMessage);


    // Função para atualizar o saldo do usuário
    function updateUserBalance() {
      // Pega o saldo diretamente do HTML
      let userBalanceText = document.getElementById("userBalance").innerText;

      // Remove "R$" e formatação de milhar com ponto e vírgula, converte para número
      let userBalance = parseFloat(userBalanceText.replace("R$", "").replace(".", "").replace(",", ".").trim());

      document.getElementById("userBalance").innerText = formatCurrency(userBalance);
    }

    // Função para formatar o valor como moeda
    function formatCurrency(value) {
      return value.toFixed(2).replace(".", ",").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Função para gerar um external_id único
    function generateExternalId(index) {
      // Gera um ID único usando um prefixo fixo e um valor aleatório
      let uniqueId = 'f3a5ac11-5d5f-47b0-b3e0-7e982709ca4e'; // Prefixo fixo (pode ser alterado conforme necessário)
      let randomId = Math.floor(Math.random() * 1000000000); // Gera um número aleatório
      return uniqueId + randomId + index; // Combina o prefixo fixo, o número aleatório e o índice
    }

    function getCurrentSmsType() {
      if (!document.getElementById('smsFlexContent').classList.contains('hidden')) return 'flex';
      if (!document.getElementById('smsTurboContent').classList.contains('hidden')) return 'turbo';
      if (!document.getElementById('smsLeveContent').classList.contains('hidden')) return 'leve';
      return 'padrao';
    }

    function sendSms() {
      let smsMessage = document.getElementById('smsMessage').value || '';

      if (!smsMessage) {
        alert("Por favor, digite uma mensagem.");
        return;
      }

      // Verifica se os números foram processados corretamente
      if (!window.numbersToSend || window.numbersToSend.length === 0) {
        alert("Nenhum número encontrado para envio.");
        return;
      }

      // Usando window.numbersWithNames para mapear os números e nomes
      let numbers = window.numbersWithNames;

      // Verifica se a mensagem contém {name}
      let hasNamePlaceholder = smsMessage.includes("{name}");

      // A lógica de mapeamento foi revisada para garantir o acesso correto aos dados
      let phoneMessageSending = numbers.map((recipient, index) => {
        let phoneNumber = recipient.t || ''; // Garantir que o telefone seja acessado corretamente
        let recipientName = recipient.n || ''; // Garantir que o nome seja acessado corretamente

        // Substitui {name} ou deixa vazio caso não tenha nome
        let personalizedMessage = smsMessage.replace(/{name}/g, recipientName);

        // Gerar um external_id único para cada mensagem
        let externalId = generateExternalId(index);

        // Criar o objeto de mensagem
        return {
          phone: phoneNumber, // Número de telefone
          message: personalizedMessage, // Mensagem personalizada
          external_id: externalId // External ID único
        };
      });

      // Verifica se o número de leads (números) excede o limite
      if (numbers.length > 1000) {
        alert("O número máximo de leads por automação é 1000.");
        return;
      }

      const tipoDisparo = getCurrentSmsType();
      let pricePerSms;

      if (tipoDisparo === 'turbo') pricePerSms = 0.14;
      else if (tipoDisparo === 'flex') pricePerSms = 0.09;
      else if (tipoDisparo === 'leve') pricePerSms = 0.08;
      else pricePerSms = 0.12; // fallback para "padrao"

      let quantity = numbers.length;
      let total = quantity * pricePerSms;

      // Verifica o saldo do usuário
      let userBalanceText = document.getElementById("userBalance").innerText;
      let userBalance = parseFloat(userBalanceText.replace("R$", "").replace(".", "").replace(",", ".").trim());

      if (userBalance < total) {
        document.getElementById("discountedBalance").innerText = "Saldo insuficiente";
        alert("Saldo insuficiente para realizar o envio.");
        return;
      } else {
        let discountedBalance = userBalance - total;

        // Verifica se o saldo após a subtração será negativo
        if (discountedBalance < 0) {
          document.getElementById("discountedBalance").innerText = "Saldo insuficiente";
          alert("Saldo insuficiente para realizar o envio.");
          return;
        } else {
          document.getElementById("discountedBalance").innerText = formatCurrency(discountedBalance); // Atualiza o saldo restante
        }
      }

      // Criação do objeto JSON desejado
      let automationJson = {
        "phone_message_sending": phoneMessageSending // Lista de mensagens personalizadas com número de telefone, mensagem e external_id
      };

      // Agora, cria o objeto final que inclui o total, mas fora do JSON de automações
      let dataToSend = {
        phone_message_sending: automationJson.phone_message_sending,
        total: total // Total separado, fora da chave "phone_message_sending"
      };

      console.log("Dados a serem enviados para o servidor:", JSON.stringify(dataToSend, null, 2));

      console.log("payload enviado:", JSON.stringify(dataToSend, null, 2));


      // Enviar os dados para o servidor
      sendSmsToServer(dataToSend);
    }

    // Função para processar o arquivo e extrair os números
    function processFile(fileUpload) {
      return new Promise((resolve, reject) => {
        let reader = new FileReader();
        reader.onload = function(event) {
          let lines = event.target.result.split("\n");
          let numbers = [];

          // Filtrando linhas vazias e processando números válidos
          lines.forEach(line => {
            let parts = line.trim().split(","); // Supondo que o arquivo tenha "numero, nome"

            // Verifica se a linha contém pelo menos um número válido
            if (parts.length >= 1) {
              let number = parts[0].trim();
              let name = parts[1] ? parts[1].trim() : null; // Nome é opcional, se não existir será null

              // Ignora números vazios
              if (number) {
                // Criação do objeto recipient
                let recipient = {
                  t: number
                }; // Armazenando número como t

                // Se o nome existir, adiciona ao objeto
                if (name) {
                  recipient.n = name; // Armazenando nome como n
                }

                // Adiciona o recipient no array numbers
                numbers.push(recipient);
              }
            }
          });

          // Verifica se o número de contatos é inferior a 10
          if (numbers.length < 10) {
            reject("O arquivo deve conter pelo menos 10 números.");
          } else {
            resolve(numbers);
          }
        };
        reader.onerror = reject;
        reader.readAsText(fileUpload);
      });
    }

    // Função para contabilizar a quantidade de números no arquivo e substituir o campo de input
    function handleFileChange() {
      let fileUpload = document.getElementById("fileUpload").files[0];

      if (fileUpload) {
        processFile(fileUpload)
          .then((numbers) => {
            // Filtra números duplicados (usando Set)
            let uniqueNumbers = [...new Set(numbers.map(recipient => recipient.t))];

            // Verifica se o número de contatos é inferior a 10
            if (uniqueNumbers.length < 10) {
              alert("O arquivo deve conter pelo menos 10 números.");
              document.getElementById("fileStatus").innerText = "Arquivo inválido";
              document.getElementById("fileStatus").style.color = "red";
              document.getElementById("fileUpload").value = '';
              return; // Impede o processamento e envio dos dados
            }

            // Atualiza a quantidade de SMS com base no número de contatos únicos
            let quantity = uniqueNumbers.length;
            document.getElementById("smsQuantityText").innerText = "Quantidade de SMS a serem enviadas: " + quantity;

            // Preço por SMS
            let pricePerSms = 0.12;
            let total = quantity * pricePerSms;
            document.getElementById("smsTotal").innerText = formatCurrency(total);

            // Calcula o saldo restante após o desconto
            let userBalanceText = document.getElementById("userBalance").innerText;
            let userBalance = parseFloat(userBalanceText.replace("R$", "").replace(".", "").replace(",", ".").trim());

            if (userBalance < total) {
              document.getElementById("discountedBalance").innerText = "Saldo insuficiente";
              alert("Saldo insuficiente para realizar o envio.");
              document.getElementById("fileUpload").value = '';
              return;
            } else {
              let discountedBalance = userBalance - total;
              document.getElementById("discountedBalance").innerText = formatCurrency(discountedBalance); // Atualiza o saldo restante

              // Alterando o texto e cor para "Arquivo carregado"
              document.getElementById("fileStatus").innerText = "Arquivo carregado";
              document.getElementById("fileStatus").style.color = "green";
            }

            // Salva os números processados globalmente (para usá-los depois)
            window.numbersToSend = uniqueNumbers;
            window.numbersWithNames = numbers; // Armazenando números e nomes
          })
          .catch((error) => {
            alert(error); // Exibe erro caso o arquivo não seja válido
          });
      } else {
        // Caso nenhum arquivo tenha sido selecionado
        document.getElementById("fileStatus").innerText = "Nenhum arquivo carregado";
        document.getElementById("fileStatus").style.color = "red";
      }
    }


    function capitalize(str) {
      return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Função para enviar os dados do SMS para o PHP
    function sendSmsToServer(automationData) {
      const data = automationData;

      console.log('Enviando dados para o servidor:', data);

      fetch('send_sms.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(data)
        })
        .then(response => response.text()) // Receber como texto para debugar
        .then(responseText => {
          console.log('Resposta bruta do servidor:', responseText);
          try {
            const responseData = JSON.parse(responseText); // Agora tenta converter em JSON
            console.log('Resposta JSON do servidor:', responseData);

            // Verifica a resposta do servidor
            if (responseData.success) {
              alert('SMS enviados com sucesso!');
              document.getElementById('smsContent').style.display = "none";
            } else {
              alert('Erro ao enviar SMS: ' + responseData.message);
            }

          } catch (error) {
            console.error('Erro ao processar JSON:', error);
            alert('Erro ao processar a resposta do servidor. Resposta recebida não é JSON.');
          }

          hideSaldoContent(); // Esconde o conteúdo de saldo após o envio
        })
        .catch(error => {
          console.error('Erro ao enviar a requisição:', error);
          alert('Ocorreu um erro ao tentar enviar os SMS.');
        });
    }

    function hideSaldoContent() {
      const saldoContent = document.getElementById('saldoContent');
      if (saldoContent) {
        saldoContent.classList.add('hidden');
      }
    }

    window.enviarSmsPorTipo = enviarSmsPorTipo;
  </script>

  <!-- <script src="js/sendSms.js"></script> -->

  <script src="js/geradorTelefone.js"></script>


  <script src="js/displayController.js"></script>

  <script src="js/handleFileController.js"></script>

  <script src="js/qrcode.js"></script>

  <script src="js/menu.js"></script>


  <!-- Inclusão do jQuery -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>

  <!-- Inclusão do Popper.js -->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>

  <!-- Incluir Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">


  <!-- Inclusão do Bootstrap JS -->
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


  <a id="robbu-whatsapp-button" target="_blank"
    href="https://api.whatsapp.com/send?phone=5521969195085&text=Adicionar%Saldo!">
    <div class="rwb-tooltip">Fale Conosco</div>
    <img src="https://cdn.positus.global/production/resources/robbu/whatsapp-button/whatsapp-icon.svg"
      alt="WhatsApp Icon" />
  </a>

</body>

</html>