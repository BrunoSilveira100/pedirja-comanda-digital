<?php
// Tela do garçom (PWA celular): pedidos prontos e confirmação de entrega.
require_once "includes/auth.php";
exigirPerfil("garcom");

require_once "includes/layout.php";

cabecalho("Salão", '<span class="status-ao-vivo"><span class="ponto"></span><span id="atualizado">Conectando…</span></span>');
?>
<section class="hero" style="margin-bottom:24px">
    <?= tituloAnimado("Prontos para servir", "servir", "h2") ?>
    <p class="sub cascata cascata-4">Quando a cozinha marca um pedido como pronto, ele aparece aqui com um aviso. Leve até a mesa e confirme a entrega.</p>
</section>

<div class="secao-topo cascata cascata-5" style="margin-bottom:12px">
    <h2>Pedidos prontos <span class="selo pronto" id="n-prontos">0</span></h2>
</div>
<div class="grade" id="lista-prontos" aria-live="polite"></div>
<?php rodape(["assets/js/fila.js", "assets/js/garcom.js"]); ?>
