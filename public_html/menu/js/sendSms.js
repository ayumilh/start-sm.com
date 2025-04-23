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