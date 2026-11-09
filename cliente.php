<?php
// Tela do cliente (PWA): cardápio, carrinho, acompanhamento dos pedidos e conta da mesa.
require_once "includes/auth.php";
exigirPerfil("cliente");

require_once "includes/layout.php";

$mesa = $_SESSION["mesa_numero"] ?? null;
$primeiroNome = explode(" ", $_SESSION["nome"] ?? "")[0];

$acoes = $mesa
    ? '<span class="selo livre">' . svg("mesa") . ' Mesa ' . (int) $mesa . '</span>'
    : '<span class="selo ocupada">Sem mesa</span>';

cabecalho("Olá, $primeiroNome", $acoes);
?>
<div id="tela-cliente" data-mesa="<?= $mesa ? (int) $mesa : "" ?>">

    <section class="hero">
        <?= tituloAnimado("Peça direto da sua mesa.", "mesa") ?>
        <p class="sub cascata cascata-4">Escolha os itens, envie para a cozinha e acompanhe cada etapa por aqui.</p>
        <?php if (!$mesa): ?>
            <div class="alerta aviso cascata cascata-5" role="status">
                <?= svg("mesa") ?>
                <span>Para fazer pedidos, escaneie o QR Code que está na sua mesa. Você já pode olhar o cardápio.</span>
            </div>
        <?php endif; ?>
    </section>

    <?= faixaRolante(["Lanches", "Padaria", "Bebidas", "Porções", "Sobremesas", "Salgados"]) ?>

    <section class="secao" id="cardapio" aria-labelledby="t-cardapio" style="margin-top:8px">
        <div class="secao-topo">
            <h2 id="t-cardapio">Cardápio</h2>
        </div>
        <div class="chips" id="chips" role="group" aria-label="Filtrar por categoria"></div>
        <div class="grade" id="lista-produtos" aria-live="polite"></div>
    </section>

    <section class="secao" id="pedidos" aria-labelledby="t-pedidos">
        <div class="secao-topo">
            <h2 id="t-pedidos">Meus pedidos</h2>
            <span class="status-ao-vivo"><span class="ponto"></span>Atualiza sozinho</span>
        </div>
        <div class="grade" id="lista-pedidos" aria-live="polite"></div>
    </section>

    <section class="secao" id="conta" aria-labelledby="t-conta">
        <div class="secao-topo">
            <h2 id="t-conta">Conta da mesa</h2>
        </div>
        <div id="conta-resumo"></div>
    </section>
</div>

<div class="barra-carrinho" id="barra-carrinho" aria-live="polite">
    <span><strong id="barra-qtd">0 itens</strong><br><span id="barra-total" class="suave" style="color:#d6ccc1">R$ 0,00</span></span>
    <button class="btn" type="button" id="btn-ver-carrinho"><?= svg("sacola") ?>Ver pedido</button>
</div>
<?php rodape(["assets/js/cliente.js"]); ?>
