<?php
// POST /api/admin/produto_imagem.php — foto do produto (somente admin), multipart/form-data
//
//   id=3 + imagem=<arquivo>   → troca a foto       200 com o produto
//   id=3 + remover=1          → tira a foto        200 com o produto
//   400 arquivo inválido/grande | 404 produto inexistente | 403 perfil ou sem token CSRF
//
// Segurança: só JPG, PNG ou WEBP de até 3 MB, conferidos pelo conteúdo (não pela extensão);
// o nome do arquivo é gerado pelo servidor, então ninguém consegue enviar um .php.
define("MODO_API", true);
require_once __DIR__ . "/../../includes/auth.php";

exigirMetodo("POST");
exigirPerfil("admin");

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../includes/produtos.php";

const TAMANHO_MAXIMO = 3 * 1024 * 1024;
const TIPOS_ACEITOS = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
const PASTA_RELATIVA = "assets/img/produtos/";

$id = exigirId($_POST, "id");
$produto = buscarProduto($pdo, $id);
if (!$produto) {
    responderJson(404, ["erro" => "Produto não encontrado."]);
}
$pasta = __DIR__ . "/../../" . PASTA_RELATIVA;

if (!empty($_POST["remover"])) {
    trocarImagem($pdo, $id, null, $produto["imagem"], $pasta);
    responderJson(200, normalizarProduto(buscarProduto($pdo, $id)));
}

$arq = $_FILES["imagem"] ?? null;
if (!$arq || !is_array($arq) || ($arq["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    responderJson(400, ["erro" => "Escolha uma foto para enviar."]);
}
if ($arq["error"] === UPLOAD_ERR_INI_SIZE || $arq["error"] === UPLOAD_ERR_FORM_SIZE || $arq["size"] > TAMANHO_MAXIMO) {
    responderJson(400, ["erro" => "A foto deve ter no máximo 3 MB."]);
}
if ($arq["error"] !== UPLOAD_ERR_OK || !is_uploaded_file($arq["tmp_name"])) {
    responderJson(400, ["erro" => "Não foi possível receber a foto. Tente de novo."]);
}

// Confere o tipo pelo conteúdo do arquivo, não pelo nome
$tipo = (new finfo(FILEINFO_MIME_TYPE))->file($arq["tmp_name"]);
if (!isset(TIPOS_ACEITOS[$tipo]) || !@getimagesize($arq["tmp_name"])) {
    responderJson(400, ["erro" => "Envie uma imagem JPG, PNG ou WEBP."]);
}

// Nome gerado pelo servidor: p<id>-<aleatório>.<ext>
$nome = "p{$id}-" . bin2hex(random_bytes(4)) . "." . TIPOS_ACEITOS[$tipo];
if (!is_dir($pasta) || !move_uploaded_file($arq["tmp_name"], $pasta . $nome)) {
    throw new RuntimeException("Falha ao gravar a foto em " . PASTA_RELATIVA);
}

trocarImagem($pdo, $id, PASTA_RELATIVA . $nome, $produto["imagem"], $pasta);
responderJson(200, normalizarProduto(buscarProduto($pdo, $id)));

/**
 * Grava o novo caminho e apaga a foto antiga, mas só se ela foi enviada por esta tela
 * (nome p<id>-...). As fotos originais do cardápio nunca são apagadas.
 */
function trocarImagem(PDO $pdo, int $id, ?string $nova, ?string $antiga, string $pasta): void
{
    $pdo->prepare("UPDATE produtos SET imagem = :img WHERE id = :id")->execute([":img" => $nova, ":id" => $id]);
    if ($antiga && $antiga !== $nova && preg_match('#^assets/img/produtos/(p' . $id . '-[a-f0-9]{8}\.(jpg|png|webp))$#', $antiga, $m)) {
        @unlink($pasta . $m[1]);
    }
}
