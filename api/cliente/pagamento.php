<?php
// /api/cliente/pagamento.php — conta da mesa do cliente
//
// GET  → resumo da conta em aberto da mesa                                     200
// POST → pede a conta: pedidos "entregue" → "solicitado_pagamento" e
//        mesa → "aguardando_pagamento"                                          200
//        400 sem mesa | 409 pedidos ainda em preparo, nada em aberto ou conta já pedida
define("MODO_API", true);
require_once __DIR__ . "/../../includes/auth.php";

exigirMetodo("GET", "POST");
exigirPerfil("cliente");

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../includes/pedidos.php";

$mesaId = $_SESSION["mesa_id"] ?? null;
if (!$mesaId) {
    responderJson(400, ["erro" => "Mesa não identificada. Escaneie o QR Code da mesa."]);
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    responderJson(200, contaDaMesa($pdo, (int) $mesaId));
}

responderJson(200, solicitarPagamento($pdo, (int) $mesaId));
