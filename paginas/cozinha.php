<?php
// Tela da cozinha (PWA tablet): fila em tempo real (polling) e mudança de status.
require_once __DIR__ . "/../includes/auth.php";
exigirPerfil("cozinha");

require_once __DIR__ . "/../includes/layout.php";

cabecalho("Cozinha", '<span class="status-ao-vivo"><span class="ponto"></span><span id="atualizado">Conectando…</span></span>');
?>
<section class="hero" style="margin-bottom:24px">
    <?= tituloAnimado("Fila da cozinha", "cozinha", "h2") ?>
    <p class="sub cascata cascata-4">Os pedidos mais antigos aparecem primeiro. A tela se atualiza sozinha a cada poucos segundos.</p>
</section>

<div class="colunas cascata cascata-5">
    <section class="coluna" aria-labelledby="t-recebidos">
        <div class="coluna-topo">
            <span class="selo recebido">Novo</span>
            <h2 id="t-recebidos">Recebidos</h2>
            <span class="contagem" id="n-recebido">0</span>
        </div>
        <div class="lista" id="col-recebido" aria-live="polite"></div>
    </section>
    <section class="coluna" aria-labelledby="t-preparo">
        <div class="coluna-topo">
            <span class="selo em_preparo"><span class="pontos" aria-hidden="true"><i></i><i></i><i></i></span>Fogo</span>
            <h2 id="t-preparo">Em preparo</h2>
            <span class="contagem" id="n-em_preparo">0</span>
        </div>
        <div class="lista" id="col-em_preparo" aria-live="polite"></div>
    </section>
</div>
<?php rodape(["assets/js/fila.js", "assets/js/cozinha.js"]); ?>
