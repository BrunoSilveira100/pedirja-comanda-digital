<?php
// /api/admin/produtos.php — gestão do cardápio
//
// GET                                                         → todos (inclusive indisponíveis)  200
// POST   { nome, preco, categoria, descricao?, disponivel? }  → cria                              201 | 400
// PUT    { id, ...campos }                                    → altera só os campos enviados      200 | 400 | 404
// DELETE ?id=3                                                → exclui                            200 | 404 | 409
//
// Produto que já aparece em pedidos não pode ser excluído: marque "disponivel": false.
define("MODO_API", true);
require_once __DIR__ . "/../../includes/auth.php";

exigirMetodo("GET", "POST", "PUT", "PATCH", "DELETE");
exigirPerfil("admin");

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../includes/produtos.php";

$metodo = $_SERVER["REQUEST_METHOD"];

if ($metodo === "GET") {
    responderJson(200, listarProdutos($pdo, false));
}

$dados = lerEntrada();

if ($metodo === "POST") {
    $id = inserirProduto($pdo, validarProduto($dados));
    responderJson(201, normalizarProduto(buscarProduto($pdo, $id)));
}

$id = exigirId($dados, "id");
if (!buscarProduto($pdo, $id)) {
    responderJson(404, ["erro" => "Produto não encontrado."]);
}

if ($metodo === "PUT" || $metodo === "PATCH") {
    unset($dados["id"]);
    atualizarProduto($pdo, $id, validarProduto($dados, true));
    responderJson(200, normalizarProduto(buscarProduto($pdo, $id)));
}

// DELETE
if (!excluirProduto($pdo, $id)) {
    responderJson(409, ["erro" => "Produto já aparece em pedidos. Marque-o como indisponível."]);
}
responderJson(200, ["mensagem" => "Produto excluído.", "id" => $id]);
