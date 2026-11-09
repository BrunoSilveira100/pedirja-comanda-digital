<?php
require_once "includes/auth.php";

header("Location: " . (usuarioLogado() ? "painel.php" : "login.php"));
exit;
