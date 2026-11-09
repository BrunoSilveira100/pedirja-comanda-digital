<?php
// mesa.php?t=TOKEN — destino do QR Code colado na mesa.
// Valida o token no banco e guarda a mesa na sessão. O pedido usará
// sempre a mesa da sessão, nunca um número enviado pelo navegador.
require_once "includes/auth.php";
require_once "config/conexao.php";
require_once "includes/layout.php";

$token = trim($_GET["t"] ?? "");

$st = $pdo->prepare("SELECT id, numero FROM mesas WHERE qr_code_token = :t");
$st->execute([":t" => $token]);
$mesa = $st->fetch();

if (!$mesa) {
    http_response_code(404);
    cabecalho("Mesa não encontrada");
    ?>
    <div class="vazio entra" style="max-width:520px;margin:40px auto">
        <?= svg("mesa") ?>
        <strong>QR Code inválido</strong>
        <p>Não encontramos esta mesa. Chame um atendente.</p>
    </div>
    <?php
    rodape();
    exit;
}

$_SESSION["mesa_id"] = (int) $mesa["id"];
$_SESSION["mesa_numero"] = (int) $mesa["numero"];

header("Location: " . (usuarioLogado() ? "painel.php" : "login.php"));
exit;
