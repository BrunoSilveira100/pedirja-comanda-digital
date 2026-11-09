<?php
// Página exibida por exigirPerfil() quando o perfil não tem permissão.
// Não deve ser acessada diretamente.
if (!function_exists("exigirPerfil")) {
    http_response_code(404);
    exit;
}
require_once __DIR__ . "/includes/layout.php";

cabecalho("Acesso negado");
?>
<div class="vazio entra" style="max-width:520px;margin:40px auto">
    <?= svg("sair") ?>
    <strong>Acesso negado (403)</strong>
    <p>Seu perfil não tem permissão para esta ação.</p>
    <p class="mt"><a class="btn" href="painel.php">Voltar para a minha tela</a></p>
</div>
<?php rodape(); ?>
