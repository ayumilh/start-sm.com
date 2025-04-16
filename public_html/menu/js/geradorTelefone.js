let pacoteSelecionado = null;

const dddsPorEstado = {
  "SP": [11, 12, 13, 14, 15, 16, 17, 18, 19],
  "RJ": [21, 22, 24],
  "MG": [31, 32, 33, 34, 35, 37, 38],
  "ES": [27, 28],
  "PR": [41, 42, 43, 44, 45, 46],
  "SC": [47, 48, 49],
  "RS": [51, 53, 54, 55],
  "DF": [61],
  "GO": [62, 64],
  "MT": [65, 66],
  "MS": [67],
  "TO": [63],
  "BA": [71, 73, 74, 75, 77],
  "SE": [79],
  "PE": [81, 87],
  "AL": [82],
  "PB": [83],
  "RN": [84],
  "CE": [85, 88],
  "PI": [86, 89],
  "PA": [91, 93, 94],
  "AP": [96],
  "RR": [95],
  "AM": [92, 97],
  "AC": [68],
  "RO": [69],
  "MA": [98, 99]
};

let quantidadeSelecionada = 0;

document.addEventListener('DOMContentLoaded', () => {
  const pacotes = document.querySelectorAll('.pacote');

  // Selecionar pacote
  pacotes.forEach(pacote => {
    pacote.addEventListener('click', () => {
      // Remove a seleção dos outros pacotes
      pacotes.forEach(p => p.classList.remove('bg-blue-100', 'border-blue-600'));

      // Adiciona classe de seleção ao clicado
      pacote.classList.add('bg-blue-100', 'border-blue-600');
      pacoteSelecionado = pacote;

      // Atribui a quantidade ao clicar no pacote
      quantidadeSelecionada = parseInt(pacote.dataset.quantidade);
    });
  });

  // Confirmar pacote
  const btnConfirmar = document.getElementById('btnConfirmarPacote');
  const btnGerar = document.getElementById('btnGerarNumeros');

  btnConfirmar.addEventListener('click', () => {
    if (pacoteSelecionado) {
      const preco = parseFloat(pacoteSelecionado.dataset.preco);
      const pacoteId = parseInt(pacoteSelecionado.dataset.pacote);

      fetch('confirmar_pacote.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `preco=${preco}&pacote_id=${pacoteId}`
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            btnGerar.classList.remove('hidden');
            alert(
              data.mensagem +
              "\nValor descontado: R$ " + preco.toFixed(2).replace('.', ',') +
              "\nNovo saldo: R$ " + data.novoSaldo
            );

            // Atualiza o saldo na interface chamando o getSaldo.php novamente
            fetch('getSaldo.php')
              .then(response => response.json())
              .then(data => {
                if (data.error) {
                  console.error("Erro ao buscar saldo:", data.error);
                } else {
                  const saldoElements = document.querySelectorAll(".saldoText");

                  // Verifica se o valor de saldo é um número
                  const saldo = parseFloat(data.saldo);

                  if (!isNaN(saldo)) {
                    // Formata o saldo com separadores de milhar e vírgula para os decimais
                    const formattedSaldo = saldo.toLocaleString('pt-BR', {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2
                    });

                    // Atualiza o saldo para cada elemento com a classe 'saldoText'
                    saldoElements.forEach(element => {
                      // Atualiza o conteúdo do elemento com o saldo formatado
                      element.innerText = `R$ ${formattedSaldo}`;
                    });
                  } else {
                    console.error("Erro: saldo retornado não é um número válido.");
                  }
                }
              })
              .catch(error => {
                console.error("Erro ao obter o saldo:", error);
              });

          } else {
            alert(data.mensagem);
          }
        });

    } else {
      alert('Por favor, selecione um pacote primeiro.');
    }
  });
});

function gerarNumeroAleatorio(estado = null) {
  const estados = Object.keys(dddsPorEstado);
  const estadoEscolhido = estado && dddsPorEstado[estado] ? estado : estados[Math.floor(Math.random() * estados.length)];
  const ddd = dddsPorEstado[estadoEscolhido][Math.floor(Math.random() * dddsPorEstado[estadoEscolhido].length)];
  const numero = `${ddd}9${Math.floor(10000000 + Math.random() * 90000000)},`;
  return numero;
}

let listaNumerosGerados = [];

function gerarListaNumeros() {
  const estado = document.getElementById("estadoSelect").value;
  const quantidade = quantidadeSelecionada;
  const listaContainer = document.getElementById("listaNumeros");
  const btnDownload = document.getElementById("btnDownloadTxt");

  const btn = document.getElementById("btnGerarNumeros");
  const spinner = document.getElementById("loadingSpinner");
  const btnText = document.getElementById("btnText");

  // Exibe o spinner (ícone de loading) e desativa o botão
  spinner.classList.remove("hidden");
  btn.disabled = true;
  btnText.innerText = "Gerando...";

  listaContainer.innerHTML = "";
  listaNumerosGerados = [];

  const numerosUnicos = new Set();

  // Gera números até atingir a quantidade desejada
  while (numerosUnicos.size < quantidade) {
    const numero = gerarNumeroAleatorio(estado);
    if (!numerosUnicos.has(numero)) {
      numerosUnicos.add(numero);

      const item = document.createElement("div");
      item.textContent = numero;
      listaContainer.appendChild(item);
    }
  }

  listaNumerosGerados = Array.from(numerosUnicos);

  spinner.classList.add("hidden");
  btn.disabled = false;

  // Mostra o botão de download se tiver pelo menos 1 número
  btnText.innerText = "Gerar Número";
  btnDownload.classList.remove("hidden");

  btn.classList.add("hidden");
}

function baixarListaTxt() {
  const conteudo = listaNumerosGerados.join('\n');
  const blob = new Blob([conteudo], {
    type: 'text/plain'
  });
  const url = URL.createObjectURL(blob);

  const link = document.createElement("a");
  link.href = url;
  link.download = "lista_numeros.txt";
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

function openModalNumero() {
  document.getElementById("modalNumero").classList.remove("hidden");
  document.getElementById("modalNumero").classList.add("flex");
}

function closeModalNumero() {
  document.getElementById("modalNumero").classList.remove("flex");
  document.getElementById("modalNumero").classList.add("hidden");
  document.getElementById("numeroGerado").innerText = '';
}

// Função para atualizar a mensagem gerada
function atualizarMensagem() {
  // Obtém os valores dos campos do formulário
  const link = document.getElementById('link').value;
  const encurtador = document.getElementById('encurtador').value;
  const mensagemElement = document.getElementById('mensagem');
  let mensagemSelecionada = mensagemElement.options[mensagemElement.selectedIndex].text; // Pega o texto da opção selecionada

  // Remove qualquer ocorrência de ##LINK## na mensagem selecionada
  mensagemSelecionada = mensagemSelecionada.replace("##LINK##", "");

  // Se o link for inserido, adiciona à mensagem
  if (link) {
    mensagemSelecionada += `, para maiores detalhes acesse ${link}`;
  }

  // Adiciona o encurtador à mensagem
  mensagemSelecionada += ` (Encurtador: ${encurtador})`;

  // Atribui a mensagem gerada ao campo de texto
  const mensagemGeradaField = document.getElementById('mensagem-gerada');
  mensagemGeradaField.value = mensagemSelecionada; // Exibe a mensagem gerada no campo
}

// Chama a função quando a página for carregada para garantir que a mensagem seja gerada automaticamente com as opções selecionadas
document.addEventListener('DOMContentLoaded', () => {
  atualizarMensagem();
});

// Chama a função sempre que um dos campos relevantes for alterado
document.getElementById('link').addEventListener('input', atualizarMensagem);
document.getElementById('mensagem').addEventListener('change', atualizarMensagem);
document.getElementById('encurtador').addEventListener('change', atualizarMensagem);

