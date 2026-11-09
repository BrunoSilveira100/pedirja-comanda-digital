<?php
require_once "includes/auth.php";
require_once "config/conexao.php";
require_once "includes/usuarios.php";
require_once "includes/tentativas.php";
require_once "includes/layout.php";

if (usuarioLogado()) {
    header("Location: painel.php");
    exit;
}

$erro = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if (loginBloqueado($pdo, $email)) {
        http_response_code(429);
        $erro = MENSAGEM_BLOQUEIO;
    } else {
        $u = autenticar($pdo, $email, $senha);
        if ($u) {
            limparFalhasLogin($pdo, $email);
            iniciarSessaoDoUsuario($u);
            header("Location: painel.php");
            exit;
        }
        registrarFalhaLogin($pdo, $email);
        // Mensagem genérica: não revela se foi o e-mail ou a senha
        $erro = "E-mail ou senha inválidos.";
    }
}

abrirEntrada("Entrar", "Seu pedido, sem espera.", "espera",
    "Escaneie o QR Code da mesa, escolha no cardápio e acompanhe o preparo pelo celular.");
?>
<section class="card cascata cascata-5<?= $erro ? " tremer" : "" ?>">
    <h2>Entrar</h2>
    <p class="meta">Use o e-mail e a senha da sua conta.</p>

    <?php if (!empty($_SESSION["mesa_numero"])): ?>
        <div class="alerta info" style="margin-bottom:16px">Mesa <?= (int) $_SESSION["mesa_numero"] ?> identificada. Entre para fazer seu pedido.</div>
    <?php endif; ?>
    <?php if ($erro): ?>
        <div class="alerta erro" role="alert" style="margin-bottom:16px"><?= e($erro) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <?= campoCsrf() ?>
        <div class="campo">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required autofocus>
        </div>
        <div class="campo">
            <label for="senha">Senha</label>
            <input id="senha" type="password" name="senha" autocomplete="current-password" required>
        </div>
        <button class="btn btn-bloco" type="submit">Entrar</button>
    </form>
    <p class="meta mt">Ainda não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
</section>
<?php fecharEntrada(); ?>
