<footer class="rodape">
    © <?= date('Y'); ?> PetFinder Brasil
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Abre/fecha o menu lateral no celular (a sidebar fica escondida por padrão
    // abaixo de 900px de largura — ver dashboard.css).
    (function () {
        var toggle = document.getElementById('menuToggle');
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
<script src="../assets/js/click-sounds.js"></script>
<?php
require_once __DIR__ . '/../Helpers/Csrf.php';
$pushVapidKey = getenv('PUSH_VAPID_PUBLIC_KEY') ?: '';
if ($pushVapidKey && isset($_SESSION['usuario_id'])):
    ?>
    <script>
        window.PetfinderCsrfToken = <?= json_encode(Csrf::gerarToken()) ?>;
        document.body.dataset.pushVapidKey = <?= json_encode($pushVapidKey) ?>;
    </script>
    <script src="../assets/js/push-notifications.js"></script>
<?php endif; ?>
<script>
    if (window.PetfinderLocalizacao) {
        window.PetfinderLocalizacao.solicitarSeNecessario('../app/ajax/salvar_localizacao.php');
    }
</script>
<!-- Tiramos o </div> </body> </html> daqui, pois quem vai fechar é a própria página no final! -->