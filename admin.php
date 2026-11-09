<?php
// Painel do gerente / caixa: visão geral, caixa, pedidos, cardápio e usuários.
// As seções são trocadas pelo endereço (#visao, #caixa, ...) e carregam dados da API.
require_once "includes/auth.php";
exigirPerfil("admin");

require_once "includes/layout.php";

cabecalho("Gestão", '<button class="btn btn-sec" type="button" id="btn-atualizar">' . svg("lista") . '<span>Atualizar</span></button>');
?>
<div id="admin-conteudo" aria-live="polite"></div>
<?php rodape(["assets/js/admin.js"]); ?>
