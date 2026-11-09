<?php
// POST /api/cadastro.php  { "nome": "...", "email": "...", "senha": "..." }
// Cria sempre um cliente. 201 criado e logado | 400 dados inválidos | 409 e-mail em uso
define("MODO_API", true);
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../includes/usuarios.php";

exigirMetodo("POST");
$dados = lerEntrada();

$nome = trim((string) ($dados["nome"] ?? ""));
$email = trim((string) ($dados["email"] ?? ""));
$senha = (string) ($dados["senha"] ?? "");

$erros = validarCadastro($nome, $email, $senha);
if ($erros) {
    responderJson(400, ["erro" => "Dados inválidos.", "detalhes" => $erros]);
}

try {
    $id = inserirUsuario($pdo, $nome, $email, $senha, "cliente");
} catch (PDOException $e) {
    if ($e->getCode() === "23000") {   // violação do UNIQUE em email
        responderJson(409, ["erro" => "Não foi possível concluir o cadastro com esse e-mail."]);
    }
    error_log($e->getMessage());
    responderJson(500, ["erro" => "Erro interno. Tente novamente mais tarde."]);
}

iniciarSessaoDoUsuario(["id" => $id, "nome" => $nome, "perfil" => "cliente"]);

responderJson(201, [
    "usuario" => ["id" => $id, "nome" => $nome, "email" => $email, "perfil" => "cliente"],
    "csrf" => csrfToken(),
]);
