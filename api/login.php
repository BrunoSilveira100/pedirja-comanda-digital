<?php
// POST /api/login.php  { "email": "...", "senha": "..." }   + cabeçalho X-CSRF-Token (ver /api/csrf.php)
// 200 com os dados do usuário e o novo token | 400 campos faltando | 401 credenciais inválidas
// 403 sem token CSRF | 429 muitas tentativas erradas
define("MODO_API", true);
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../includes/usuarios.php";
require_once __DIR__ . "/../includes/tentativas.php";

exigirMetodo("POST");
$dados = lerEntrada();

$email = trim((string) ($dados["email"] ?? ""));
$senha = (string) ($dados["senha"] ?? "");

if ($email === "" || $senha === "") {
    responderJson(400, ["erro" => "Informe e-mail e senha."]);
}

if (loginBloqueado($pdo, $email)) {
    header("Retry-After: " . (JANELA_MINUTOS * 60));
    responderJson(429, ["erro" => MENSAGEM_BLOQUEIO]);
}

$u = autenticar($pdo, $email, $senha);
if (!$u) {
    registrarFalhaLogin($pdo, $email);
    responderJson(401, ["erro" => "E-mail ou senha inválidos."]);
}

limparFalhasLogin($pdo, $email);
iniciarSessaoDoUsuario($u);

responderJson(200, [
    "usuario" => [
        "id" => (int) $u["id"],
        "nome" => $u["nome"],
        "email" => $u["email"],
        "perfil" => $u["perfil"],
    ],
    "mesa" => $_SESSION["mesa_numero"] ?? null,
    "csrf" => csrfToken(),
]);
