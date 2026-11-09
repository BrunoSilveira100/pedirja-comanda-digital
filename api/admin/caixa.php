<?php
// /api/admin/caixa.php — controle de caixa e fechamento de contas
//
// GET  → mesas com conta pedida (aguardando_pagamento), com pedidos e total     200
// POST { "mesa_id": 1 } → fecha a conta: pedidos → finalizado, mesa → livre     200
//      400 mesa_id inválido | 404 mesa inexistente | 409 mesa não pediu a conta
define("MODO_API", true);
require_once __DIR__ . "/../../includes/auth.php";

exigirMetodo("GET", "POST");
exigirPerfil("admin");

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../includes/pedidos.php";

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $ids = $pdo->query("SELECT id FROM mesas WHERE status = 'aguardando_pagamento' ORDER BY numero")
               ->fetchAll(PDO::FETCH_COLUMN);
    responderJson(200, array_map(fn($id) => contaDaMesa($pdo, (int) $id), $ids));
}

$dados = lerEntrada();
$mesaId = exigirId($dados, "mesa_id");

responderJson(200, fecharConta($pdo, $mesaId));
