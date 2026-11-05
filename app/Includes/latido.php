<?php

/**
 * Latido do Alerta Pet.
 * Carrega o script só para quem está logado. O rodapé comum (footer.php) já inclui
 * este arquivo; as páginas com layout próprio o incluem antes do </body>.
 */
if (class_exists('Auth') && class_exists('Url') && Auth::check()): ?>
    <script src="<?= Url::asset('js/latido-alerta.js') ?>?v=20261105" data-audio="<?= Url::asset('audio/latido.mp3') ?>" data-contagem="<?= Url::ajax('contar_notificacoes.php') ?>"></script>
<?php endif;
