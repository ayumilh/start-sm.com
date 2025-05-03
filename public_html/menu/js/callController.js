const arrow = document.getElementById('arrowIcon');
const avancado = document.getElementById('vozAvancado');

function toggleAvancado() {
  avancado.classList.toggle('hidden');
  arrow.classList.toggle('rotate-180');
}

document.getElementById("vozAudioFile").addEventListener("change", function () {
  const file = this.files[0];
  const status = document.getElementById("vozAudioStatus");

  if (file && file.type === "audio/mpeg") {
    status.innerText = `Áudio carregado: ${file.name}`;
    window.audioFileToSend = file;
  } else {
    status.innerText = "Arquivo inválido. Apenas .mp3 permitido.";
    this.value = "";
  }
});