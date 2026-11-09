<?php
// QR Codes das mesas (somente admin).
// O IP do computador é descoberto na hora (includes/rede.php): trocou de rede, é só abrir
// esta página de novo que os códigos já saem com o endereço certo.
require_once "includes/auth.php";
exigirPerfil("admin");

require_once "config/conexao.php";
require_once "includes/rede.php";
require_once "includes/layout.php";

$mesas = $pdo->query("SELECT numero, qr_code_token, status FROM mesas ORDER BY numero")->fetchAll();
$endereco = enderecoDaRede();   // ex.: http://192.168.0.86 (placa Wi-Fi/cabo, nunca a virtual do WSL)
$caminho = urlBase();           // ex.: /perdirja/

$rotuloStatus = ["livre" => "Livre", "ocupada" => "Ocupada", "aguardando_pagamento" => "Aguardando pagamento"];

cabecalho("QR Codes das mesas",
    '<button class="btn btn-sec" type="button" id="btn-imprimir">' . svg("recibo") . '<span>Imprimir</span></button>');
?>
<div id="qr-pagina" data-caminho="<?= e($caminho) ?>" data-endereco="<?= e($endereco ?? "") ?>">

    <section class="hero sem-impressao">
        <?= tituloAnimado("Escaneie a mesa e peça já.", "já") ?>
        <p class="sub cascata cascata-4">O endereço do computador é descoberto sozinho. Trocou de rede (Wi-Fi ou hotspot do celular)? Abra esta página de novo e os códigos já saem certos.</p>
    </section>

    <?php if (!$endereco): ?>
        <div class="alerta erro mt-g" role="alert">
            <?= svg("alerta") ?>
            <span>Não encontrei este computador em nenhuma rede. Conecte-o ao Wi-Fi ou ao hotspot do celular e recarregue a página.</span>
        </div>
    <?php else: ?>
        <section class="secao" aria-labelledby="t-mesas">
            <div class="secao-topo">
                <h2 id="t-mesas">Mesas</h2>
                <span class="suave">Aponte a câmera para o código na tela (clique no código para ampliar)</span>
            </div>
            <div class="qr-grade escalonar">
                <?php foreach ($mesas as $i => $m): ?>
                    <article class="card qr-mesa" style="--i:<?= $i ?>" data-token="<?= e($m["qr_code_token"]) ?>">
                        <div class="qr-topo">
                            <h3>Mesa</h3>
                            <span class="selo" title="<?= e($rotuloStatus[$m["status"]] ?? $m["status"]) ?>"><?= svg("mesa") ?>QR Code</span>
                        </div>
                        <div class="qr-numero"><?= sprintf("%02d", (int) $m["numero"]) ?></div>
                        <button class="qr" type="button" data-ampliar="<?= (int) $m["numero"] ?>"
                                aria-label="Ampliar o QR Code da mesa <?= (int) $m["numero"] ?>" title="Clique para ampliar"></button>
                        <div class="qr-dica">Aponte a câmera do celular</div>
                        <code class="qr-link"></code>
                        <button class="btn btn-sec btn-copiar sem-impressao" type="button" data-copiar><?= svg("copiar") ?>Copiar link</button>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="alerta info mt-g sem-impressao">
            <?= svg("alerta") ?>
            <span>Se o celular não abrir o link: confira se ele está na mesma rede do computador e se o Firewall do Windows permite o "Apache HTTP Server" no tipo dessa rede (o hotspot do celular costuma ser rede Pública).</span>
        </div>
    <?php endif; ?>
</div>
<?php rodape(["assets/vendor/qrcode.min.js", "assets/js/qrcodes.js"]); ?>
