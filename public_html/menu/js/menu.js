// Variáveis para o dropdown do usuário
const userDropdownBtn = document.getElementById('userDropdownBtn');
const dropdownMenu = document.getElementById('dropdownMenu');
const dropdownIcon = document.getElementById('dropdownIcon');
const userDropdownBtnMobile = document.getElementById('userDropdownBtnMobile');
const dropdownMenuMobile = document.getElementById('dropdownMenuMobile');
const dropdownIconMobile = document.getElementById('dropdownIconMobile');


// Variáveis para o menu hambúrguer
const hamburgerBtn = document.getElementById('hamburgerBtn');
const closeMenuBtn = document.getElementById('closeMenuBtn');
const mobileMenu = document.getElementById('mobileMenu');

// função para abrir o dropdown do usuário
userDropdownBtnMobile.addEventListener('click', function (event) {
    event.stopPropagation();
    dropdownMenuMobile.classList.toggle('hidden');
    toggleIcon(dropdownMenuMobile);
});

// Para desktop
userDropdownBtn.addEventListener('click', function (event) {
    event.stopPropagation();
    dropdownMenu.classList.toggle('hidden');
    toggleIcon(dropdownMenu);
});

function toggleIcon(menu, type) {
    if (type === 'mobile') {
        if (menu.classList.contains('hidden')) {
            dropdownIconMobile.classList.remove('fa-chevron-up');
            dropdownIconMobile.classList.add('fa-chevron-down');
        } else {
            dropdownIconMobile.classList.remove('fa-chevron-down');
            dropdownIconMobile.classList.add('fa-chevron-up');
        }
    } else if (type === 'desktop') {
        if (menu.classList.contains('hidden')) {
            dropdownIcon.classList.remove('fa-chevron-up');
            dropdownIcon.classList.add('fa-chevron-down');
        } else {
            dropdownIcon.classList.remove('fa-chevron-down');
            dropdownIcon.classList.add('fa-chevron-up');
        }
    }
}



// Função para abrir o menu hambúrguer

// Fecha o menu se o usuário clicar fora dele
document.addEventListener('click', function (event) {
    if (!userDropdownBtn.contains(event.target) && !dropdownMenu.contains(event.target)) {
        dropdownMenu.classList.add('hidden');
        dropdownIcon.classList.add('fa-chevron-down');
        dropdownIcon.classList.remove('fa-chevron-up');
    }
});

document.addEventListener('click', function (event) {
    if (!userDropdownBtnMobile.contains(event.target) && !dropdownMenuMobile.contains(event.target)) {
        dropdownMenuMobile.classList.add('hidden'); // Fecha o menu
        dropdownIconMobile.classList.remove('fa-chevron-up');
        dropdownIconMobile.classList.add('fa-chevron-down');
    }
});


// Função para abrir o menu hambúrguer
if (hamburgerBtn && mobileMenu) {
    // Função para abrir o menu
    function openMenu() {
        mobileMenu.classList.remove('translate-x-full'); // Remove a classe de esconder o menu
        mobileMenu.classList.add('translate-x-0'); // Adiciona a classe para mostrar o menu
        mobileMenu.classList.remove('hidden'); // Torna o menu visível
    }

    // Função para fechar o menu
    function closeMenu() {
        mobileMenu.classList.add('translate-x-full'); // Fecha o menu
        mobileMenu.classList.add('hidden'); // Adiciona a classe de oculto
        mobileMenu.classList.remove('translate-x-0');
    }

    // Função para detectar cliques fora do menu e fechá-lo
    function handleOutsideClick(event) {
        if (!mobileMenu.contains(event.target) && !hamburgerBtn.contains(event.target)) {
            closeMenu();
        }
    }

    // Apenas no mobile, abrem o menu ao clicar no ícone do hambúrguer
    if (window.innerWidth <= 1024) {  // Verifica se é mobile
        hamburgerBtn.addEventListener('click', function (event) {
            event.stopPropagation(); // Impede que o clique no hambúrguer feche o menu
            openMenu();
        });
    }

    // Fecha o menu ao clicar no botão de fechar
    closeMenuBtn.addEventListener('click', function (event) {
        event.stopPropagation(); // Impede que o clique no botão de fechar seja capturado
        closeMenu();
    });

    // Adiciona o evento de clique fora do menu para fechar
    document.addEventListener('click', handleOutsideClick);

    // Previne o fechamento do menu se o clique ocorrer dentro do menu
    mobileMenu.addEventListener('click', function (event) {
        event.stopPropagation(); // Impede a propagação do evento de clique para o documento
    });
}
