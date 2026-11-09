<?php
// Painel: leva cada perfil para a sua tela.
require_once __DIR__ . "/../includes/auth.php";
exigirLogin();
// daqui para baixo, só roda para quem está logado

$telas = [
    "cliente" => "cliente.php",
    "cozinha" => "cozinha.php",
    "garcom"  => "garcom.php",
    "admin"   => "admin.php",
];

header("Location: " . ($telas[$_SESSION["perfil"]] ?? "logout.php"));
exit;
