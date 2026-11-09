<?php
// GET /api/admin/pedidos.php[?status=pronto] — acompanhamento geral (100 mais recentes)
// 200 | 400 status inválido | 401 | 403
define("MODO_API", true);
require_once __DIR__ . "/../../includes/auth.php";

exigirMetodo("GET");
exigirPerfil("admin");

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../includes/pedidos.php";

$filtros = ["limite" => 100];
if (isset($_GET["status"]) && $_GET["status"] !== "") {
    if (!in_array($_GET["status"], STATUS_PEDIDO, true)) {
        responderJson(400, ["erro" => "Status inválido.", "validos" => STATUS_PEDIDO]);
    }
    $filtros["status"] = [$_GET["status"]];
}

responderJson(200, listarPedidos($pdo, $filtros, "p.criado_em DESC"));
