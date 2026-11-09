<?php
// PUT /api/cozinha/status.php  { "pedido_id": 1, "status": "em_preparo" | "pronto" }
// 200 pedido atualizado | 400 dados inválidos | 404 pedido inexistente
// 409 transição fora de ordem (ex.: recebido → pronto) | 403 perfil
define("MODO_API", true);
require_once __DIR__ . "/../../includes/auth.php";

exigirMetodo("PUT", "PATCH");
exigirPerfil("cozinha");

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../includes/pedidos.php";

$dados = lerEntrada();
$pedidoId = exigirId($dados, "pedido_id");
$status = (string) ($dados["status"] ?? "");

if (!in_array($status, ["em_preparo", "pronto"], true)) {
    responderJson(400, ["erro" => "A cozinha só muda o status para \"em_preparo\" ou \"pronto\"."]);
}

responderJson(200, mudarStatusPedido($pdo, $pedidoId, $status, $_SESSION["perfil"]));
