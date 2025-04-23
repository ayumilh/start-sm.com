document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById("fileUpload");
    const contador = document.getElementById("numero-contador");
    const iniciarBtn = document.getElementById("iniciar-campanha");
    const btnConfirmarEnvio = document.getElementById("btnConfirmarEnvio");

    let numerosCarregados = [];

    // Carrega o arquivo de números
    fileInput.addEventListener("change", () => {
      const file = fileInput.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = (e) => {
        const conteudo = e.target.result;
        const numeros = conteudo.split(/\r?\n/).map(n => n.trim()).filter(Boolean);
        numerosCarregados = numeros;
        contador.textContent = `${numeros.length} números carregados`;
      };
      reader.readAsText(file);
    });

    // Ao clicar no botão "Iniciar Campanha"
    iniciarBtn.addEventListener("click", () => {
      const mensagemEscolhida = document.getElementById('mensagem').value;
      const linkOriginal = document.getElementById('link').value.trim();
      const encurtador = document.getElementById('encurtador').value;
      const mensagemGerada = document.getElementById('mensagem-gerada').value.trim();

      if (!numerosCarregados.length || !mensagemEscolhida || !mensagemGerada) {
        alert('Preencha todos os campos e carregue a lista de números.');
        return;
      }

      const valorTotal = (numerosCarregados.length * 0.45).toFixed(2);

      // Preenche o modal com os dados
      document.getElementById("modal-msg-escolhida").innerText = mensagemEscolhida;
      document.getElementById("modal-link").innerText = linkOriginal || "Nenhum";
      document.getElementById("modal-encurtador").innerText = encurtador;
      document.getElementById("modal-msg-gerada").innerText = mensagemGerada;
      document.getElementById("modal-qtd").innerText = numerosCarregados.length;
      document.getElementById("modal-total").innerText = valorTotal;

      // Exibe o modal
      document.getElementById("modalConfirmacao").classList.remove("hidden");
    });

    // Ao confirmar envio no modal
    btnConfirmarEnvio.addEventListener("click", async () => {
      const dados = {
        numeros: numerosCarregados,
        mensagem_escolhida: document.getElementById('mensagem').value,
        link_original: document.getElementById('link').value.trim(),
        encurtador_utilizado: document.getElementById('encurtador').value,
        mensagem_gerada: document.getElementById('mensagem-gerada').value.trim()
      };


      try {
        const resposta = await fetch('./php/enviar_campanha.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(dados)
        });

        const textoResposta = await resposta.text();

        try {
          const resultado = JSON.parse(textoResposta);
          if (resultado.success) {
            alert("Campanha enviada com sucesso!");

            document.getElementById("smsForm").reset();
            contador.textContent = "0 números carregados";
            numerosCarregados = [];

          } else {
            alert("Erro: " + resultado.message);
          }
        } catch {
          alert("Erro inesperado. Verifique o console.");
        }
      } catch (err) {
        console.error("Erro na requisição:", err);
        alert("Erro ao enviar. Tente novamente.");
      }

      // Esconde o modal
      document.getElementById("modalConfirmacao").classList.add("hidden");
    });
  });