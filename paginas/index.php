<?php
require_once __DIR__ . "/../includes/auth.php";

header("Location: " . (usuarioLogado() ? "painel.php" : "login.php"));
exit;
