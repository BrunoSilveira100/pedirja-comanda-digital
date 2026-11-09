<?php
// /api/pedidos.php — pedidos do cliente
//
// GET  → pedidos em aberto do cliente logado (para acompanhar o status)       200
// POST → cria pedido na mesa do QR Code (guardada na sessão)                   201
//        { "itens": [ { "produto_id": 1, "quantidade": 2, "observacao": "sem cebola" } ] }
//        400 dados inválidos ou sem mesa | 404 produto inexistente | 409 indisponível / conta pedida
define("MODO_API", true);
require_once __DIR__ . "/../includes/auth.php";

exigirMetodo("GET", "POST");
exigirPerfil("cliente");

require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../includes/pedidos.php";

$clienteId = (int) $_SESSION["usuario_id"];

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $abertos = array_values(array_diff(STATUS_PEDIDO, ["finalizado"]));
    responderJson(200, listarPedidos($pdo, ["cliente_id" => $clienteId, "status" => $abertos], "p.criado_em DESC"));
}

$mesaId = $_SESSION["mesa_id"] ?? null;
if (!$mesaId) {
    responderJson(400, ["erro" => "Mesa não identificada. Escaneie o QR Code da mesa."]);
}

$dados = lerEntrada();
$itens = validarItens($dados["itens"] ?? null);

$pedidoId = criarPedido($pdo, $clienteId, (int) $mesaId, $itens);

header("Location: " . urlBase() . "api/pedidos.php?id=$pedidoId");
responderJson(201, buscarPedidoCompleto($pdo, $pedidoId));
