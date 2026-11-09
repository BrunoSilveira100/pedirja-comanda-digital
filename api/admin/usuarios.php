<?php
// /api/admin/usuarios.php — gestão de usuários (equipe e clientes)
//
// GET                                         → lista (sem senha/hash)          200
// POST   { nome, email, senha, perfil }       → cria qualquer perfil            201 | 400 | 409
// PUT    { id, perfil }                       → muda o perfil                   200 | 400 | 404 | 409
// DELETE ?id=5                                → exclui                          200 | 404 | 409
//
// O admin não pode rebaixar nem excluir a própria conta (evita ficar sem admin).
define("MODO_API", true);
require_once __DIR__ . "/../../includes/auth.php";

exigirMetodo("GET", "POST", "PUT", "PATCH", "DELETE");
exigirPerfil("admin");

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../includes/usuarios.php";

$metodo = $_SERVER["REQUEST_METHOD"];
$meuId = (int) $_SESSION["usuario_id"];

if ($metodo === "GET") {
    responderJson(200, listarUsuarios($pdo));
}

$dados = lerEntrada();

if ($metodo === "POST") {
    $nome = trim((string) ($dados["nome"] ?? ""));
    $email = trim((string) ($dados["email"] ?? ""));
    $senha = (string) ($dados["senha"] ?? "");
    $perfil = (string) ($dados["perfil"] ?? "");

    $erros = validarCadastro($nome, $email, $senha);
    if (!in_array($perfil, PERFIS_VALIDOS, true)) {
        $erros[] = "Perfil deve ser um de: " . implode(", ", PERFIS_VALIDOS) . ".";
    }
    if ($erros) {
        responderJson(400, ["erro" => "Dados inválidos.", "detalhes" => $erros]);
    }
    if (emailJaCadastrado($pdo, $email)) {
        responderJson(409, ["erro" => "Já existe um usuário com esse e-mail."]);
    }
    $id = inserirUsuario($pdo, $nome, $email, $senha, $perfil);
    responderJson(201, buscarUsuarioPorId($pdo, $id));
}

$id = exigirId($dados, "id");
$alvo = buscarUsuarioPorId($pdo, $id);
if (!$alvo) {
    responderJson(404, ["erro" => "Usuário não encontrado."]);
}

if ($metodo === "PUT" || $metodo === "PATCH") {
    $perfil = (string) ($dados["perfil"] ?? "");
    if (!in_array($perfil, PERFIS_VALIDOS, true)) {
        responderJson(400, ["erro" => "Perfil deve ser um de: " . implode(", ", PERFIS_VALIDOS) . "."]);
    }
    if ($id === $meuId && $perfil !== "admin") {
        responderJson(409, ["erro" => "Você não pode remover o seu próprio perfil de admin."]);
    }
    $pdo->prepare("UPDATE usuarios SET perfil = :p WHERE id = :id")->execute([":p" => $perfil, ":id" => $id]);
    responderJson(200, buscarUsuarioPorId($pdo, $id));
}

// DELETE
if ($id === $meuId) {
    responderJson(409, ["erro" => "Você não pode excluir a sua própria conta."]);
}
try {
    $pdo->prepare("DELETE FROM usuarios WHERE id = :id")->execute([":id" => $id]);
} catch (PDOException $e) {
    if ($e->getCode() === "23000") {
        responderJson(409, ["erro" => "Usuário tem pedidos registrados e não pode ser excluído."]);
    }
    throw $e;
}
responderJson(200, ["mensagem" => "Usuário excluído.", "id" => $id]);
