document.addEventListener("DOMContentLoaded", () => {
    const btnCampanhas = document.getElementById("btnCampanhas");
    const btnEnvios = document.getElementById("btnEnvios");
    const tabelaCampanhas = document.getElementById("tabelaCampanhas");
    const tabelaEnvios = document.getElementById("tabelaEnvios");

    btnCampanhas.addEventListener("click", (e) => {
        e.preventDefault();

        // Alterna a tabela de campanhas
        if (tabelaCampanhas.style.display === "none" || tabelaCampanhas.style.display === "") {
            tabelaCampanhas.style.display = "block";
            tabelaEnvios.style.display = "none";
            window.scrollTo({
                top: tabelaCampanhas.offsetTop - 40,
                behavior: "smooth"
            });
        } else {
            tabelaCampanhas.style.display = "none";
        }
    });

    btnEnvios.addEventListener("click", (e) => {
        e.preventDefault();

        // Alterna a tabela de envios
        if (tabelaEnvios.style.display === "none" || tabelaEnvios.style.display === "") {
            tabelaEnvios.style.display = "block";
            tabelaCampanhas.style.display = "none";
            window.scrollTo({
                top: tabelaEnvios.offsetTop - 40,
                behavior: "smooth"
            });
        } else {
            tabelaEnvios.style.display = "none";
        }
    });
});


function mostrarTexto(texto) {
    document.getElementById('textoCompleto').innerText = texto;
    $('#modalTexto').modal('show');
}

function mostrarDetalhes(envio) {
    let info = `
  ID: ${envio.id}
  Mensagem Gerada: ${envio.mensagem_gerada}
  Link Original: ${envio.link_original}
  Encurtador: ${envio.encurtador_utilizado}
  Enviados: ${envio.enviados}
  Não Enviados: ${envio.nao_enviados}
    `;
    mostrarTexto(info);
}