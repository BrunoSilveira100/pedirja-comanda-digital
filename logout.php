<?php
// Logout só por POST com token CSRF: um link ou imagem em outro site não consegue
// deslogar o usuário. Em GET, mostra um botão de confirmação.
require_once "includes/auth.php";   // aqui o CSRF do POST já foi verificado
require_once "includes/usuarios.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    encerrarSessao();
    header("Location: login.php");
    exit;
}

if (!usuarioLogado()) {
    header("Location: login.php");
    exit;
}

require_once "includes/layout.php";
cabecalho("Sair");
?>
<div class="vazio entra" style="max-width:520px;margin:40px auto">
    <?= svg("sair") ?>
    <strong>Deseja sair?</strong>
    <p>Sua sessão será encerrada neste aparelho.</p>
    <form method="post" action="logout.php" class="mt">
        <?= campoCsrf() ?>
        <button class="btn" type="submit">Sair agora</button>
        <a class="btn btn-sec" href="painel.php">Cancelar</a>
    </form>
</div>
<?php rodape(); ?>
