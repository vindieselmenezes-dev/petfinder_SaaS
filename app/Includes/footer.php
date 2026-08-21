<footer class="rodape"> 
    © <?= date('Y'); ?> PetFinder Brasil 
</footer> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Abre/fecha o menu lateral no celular (a sidebar fica escondida por padrão
// abaixo de 900px de largura — ver dashboard.css).
(function () {
    var toggle  = document.getElementById('menuToggle');
    var sidebar = document.getElementById('sidebarMenu');
    var overlay = document.getElementById('sidebarOverlay');

    if (!toggle || !sidebar || !overlay) {
        return;
    }

    function fecharMenu() {
        sidebar.classList.remove('aberto');
        overlay.classList.remove('ativo');
        toggle.setAttribute('aria-expanded', 'false');
    }

    function alternarMenu() {
        var abrindo = !sidebar.classList.contains('aberto');
        sidebar.classList.toggle('aberto');
        overlay.classList.toggle('ativo');
        toggle.setAttribute('aria-expanded', abrindo ? 'true' : 'false');
    }

    toggle.addEventListener('click', alternarMenu);
    overlay.addEventListener('click', fecharMenu);
})();
</script>

<script src="../assets/js/geolocalizacao.js"></script>
<script>
    if (window.PetfinderLocalizacao) {
        window.PetfinderLocalizacao.solicitarSeNecessario('../app/ajax/salvar_localizacao.php');
    }
</script>
<!-- Tiramos o </div> </body> </html> daqui, pois quem vai fechar é a própria página no final! -->
