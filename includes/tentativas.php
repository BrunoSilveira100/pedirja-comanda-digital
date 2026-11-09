<?php
// includes/tentativas.php — limite de tentativas de login (contra força bruta)
//
// 5 erros do mesmo IP para o mesmo e-mail em 15 minutos bloqueiam esse par.
// 50 erros do mesmo IP em 15 minutos bloqueiam o IP (alguém testando vários e-mails).
// Contar por IP + e-mail evita que um atacante bloqueie a conta de outra pessoa.

const JANELA_MINUTOS = 15;
const MAX_POR_EMAIL = 5;
const MAX_POR_IP = 50;

function ipDoCliente(): string
{
    return substr($_SERVER["REMOTE_ADDR"] ?? "desconhecido", 0, 45);
}

function loginBloqueado(PDO $pdo, string $email): bool
{
    $ip = ipDoCliente();
    $st = $pdo->prepare("SELECT
            SUM(email = :e) AS por_email,
            COUNT(*) AS por_ip
          FROM tentativas_login
         WHERE ip = :ip AND criado_em > NOW() - INTERVAL " . JANELA_MINUTOS . " MINUTE");
    $st->execute([":e" => mb_strtolower($email), ":ip" => $ip]);
    $r = $st->fetch();
    return (int) $r["por_email"] >= MAX_POR_EMAIL || (int) $r["por_ip"] >= MAX_POR_IP;
}

function registrarFalhaLogin(PDO $pdo, string $email): void
{
    $st = $pdo->prepare("INSERT INTO tentativas_login (ip, email) VALUES (:ip, :e)");
    $st->execute([":ip" => ipDoCliente(), ":e" => mb_substr(mb_strtolower($email), 0, 150)]);
}

function limparFalhasLogin(PDO $pdo, string $email): void
{
    $st = $pdo->prepare("DELETE FROM tentativas_login WHERE ip = :ip AND email = :e");
    $st->execute([":ip" => ipDoCliente(), ":e" => mb_strtolower($email)]);
    // aproveita para apagar registros antigos
    $pdo->exec("DELETE FROM tentativas_login WHERE criado_em < NOW() - INTERVAL 1 DAY");
}

const MENSAGEM_BLOQUEIO = "Muitas tentativas de login. Aguarde 15 minutos e tente de novo.";
