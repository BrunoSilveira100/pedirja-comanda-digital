<?php
// PUT /api/garcom/entregar.php  { "pedido_id": 1 }
// Confirma a entrega na mesa: pronto → entregue
// 200 | 400 | 404 pedido inexistente | 409 pedido ainda não está pronto | 403
define("MODO_API", true);
require_once __DIR__ . "/../../includes/auth.php";

exigirMetodo("PUT", "PATCH");
exigirPerfil("garcom");

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../includes/pedidos.php";

$dados = lerEntrada();
$pedidoId = exigirId($dados, "pedido_id");

responderJson(200, mudarStatusPedido($pdo, $pedidoId, "entregue", $_SESSION["perfil"]));
