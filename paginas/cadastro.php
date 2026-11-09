<?php
// Cadastro público: cria sempre perfil "cliente".
// Contas de cozinha, garçom e admin são criadas pelo admin.
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../config/conexao.php";
require_once __DIR__ . "/../includes/usuarios.php";
require_once __DIR__ . "/../includes/layout.php";

if (usuarioLogado()) {
    header("Location: painel.php");
    exit;
}

$erros = [];
$nome = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    $erros = validarCadastro($nome, $email, $senha);
    if (!$erros && emailJaCadastrado($pdo, $email)) {
        $erros[] = "Não foi possível concluir o cadastro com esse e-mail.";
    }

    if (!$erros) {
        $id = inserirUsuario($pdo, $nome, $email, $senha, "cliente");
        iniciarSessaoDoUsuario(["id" => $id, "nome" => $nome, "perfil" => "cliente"]);
        header("Location: painel.php");
        exit;
    }
}

abrirEntrada("Criar conta", "Crie sua conta e peça já.", "já",
    "Leva menos de um minuto. Depois é só escolher os itens e acompanhar o pedido.");
?>
<section class="card cascata cascata-5<?= $erros ? " tremer" : "" ?>">
    <h2>Criar conta</h2>
    <p class="meta">Contas da equipe são criadas pelo gerente.</p>

    <?php if ($erros): ?>
        <div class="alerta erro" role="alert" style="margin-bottom:16px;display:block">
            <?php foreach ($erros as $msg): ?><div><?= e($msg) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="cadastro.php">
        <?= campoCsrf() ?>
        <div class="campo">
            <label for="nome">Nome</label>
            <input id="nome" type="text" name="nome" value="<?= e($nome) ?>" maxlength="100" autocomplete="name" required autofocus>
        </div>
        <div class="campo">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="<?= e($email) ?>" maxlength="150" autocomplete="email" required>
        </div>
        <div class="campo">
            <label for="senha">Senha</label>
            <input id="senha" type="password" name="senha" minlength="6" autocomplete="new-password" required>
            <small>Mínimo de 6 caracteres.</small>
        </div>
        <button class="btn btn-bloco" type="submit">Cadastrar</button>
    </form>
    <p class="meta mt">Já tem conta? <a href="login.php">Entrar</a></p>
</section>
<?php fecharEntrada(); ?>
