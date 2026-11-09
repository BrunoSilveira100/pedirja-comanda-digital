<?php
// includes/usuarios.php — acesso à tabela usuarios (PDO + prepared statements)

const PERFIS_VALIDOS = ["cliente", "cozinha", "garcom", "admin"];

function inserirUsuario(PDO $pdo, string $nome, string $email, string $senha, string $perfil = "cliente"): int
{
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (:n, :e, :h, :p)";
    $st = $pdo->prepare($sql);
    $st->execute([":n" => $nome, ":e" => $email, ":h" => $hash, ":p" => $perfil]);
    return (int) $pdo->lastInsertId();
}

function buscarUsuarioPorEmail(PDO $pdo, string $email): ?array
{
    $st = $pdo->prepare("SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = :e");
    $st->execute([":e" => $email]);
    $u = $st->fetch();
    return $u ?: null;
}

function listarUsuarios(PDO $pdo): array
{
    $lista = $pdo->query("SELECT id, nome, email, perfil, criado_em FROM usuarios ORDER BY perfil, nome")->fetchAll();
    foreach ($lista as &$u) {
        $u["id"] = (int) $u["id"];
    }
    return $lista;
}

function buscarUsuarioPorId(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare("SELECT id, nome, email, perfil, criado_em FROM usuarios WHERE id = :id");
    $st->execute([":id" => $id]);
    $u = $st->fetch();
    if ($u) {
        $u["id"] = (int) $u["id"];
    }
    return $u ?: null;
}

function emailJaCadastrado(PDO $pdo, string $email): bool
{
    $st = $pdo->prepare("SELECT 1 FROM usuarios WHERE email = :e");
    $st->execute([":e" => $email]);
    return (bool) $st->fetchColumn();
}

/**
 * Valida os dados de cadastro. Devolve a lista de erros (vazia = ok).
 */
function validarCadastro(string $nome, string $email, string $senha): array
{
    $erros = [];
    if (mb_strlen($nome) < 2 || mb_strlen($nome) > 100) {
        $erros[] = "Informe um nome entre 2 e 100 caracteres.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $erros[] = "Informe um e-mail válido.";
    }
    if (strlen($senha) < 6) {
        $erros[] = "A senha precisa ter pelo menos 6 caracteres.";
    }
    return $erros;
}

/**
 * Confere e-mail e senha. Devolve o usuário (sem o hash) ou null.
 */
function autenticar(PDO $pdo, string $email, string $senha): ?array
{
    $u = buscarUsuarioPorEmail($pdo, $email);
    if ($u && password_verify($senha, $u["senha_hash"])) {
        unset($u["senha_hash"]);
        return $u;
    }
    return null;
}

/**
 * Grava na sessão só o necessário. Nunca a senha nem o hash.
 */
function iniciarSessaoDoUsuario(array $u): void
{
    session_regenerate_id(true);   // novo ID de sessão: evita fixação de sessão
    renovarCsrf();                 // novo token: o "dono" da sessão mudou
    $_SESSION["usuario_id"] = (int) $u["id"];
    $_SESSION["nome"] = $u["nome"];
    $_SESSION["perfil"] = $u["perfil"];
}

function encerrarSessao(): void
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), "", time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
}
