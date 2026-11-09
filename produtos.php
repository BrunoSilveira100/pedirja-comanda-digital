<?php
// Listagem do tema (cardápio): qualquer usuário logado. (Exercício 1)
require_once "includes/auth.php";
exigirLogin();

require_once "config/conexao.php";
require_once "includes/produtos.php";
require_once "includes/layout.php";

$ehAdmin = ($_SESSION["perfil"] === "admin");
$produtos = listarProdutos($pdo, !$ehAdmin);   // quem não é admin só vê o que está disponível

$msg = $_GET["msg"] ?? "";
$mensagens = [
    "excluido" => ["ok", "Produto excluído."],
    "em_uso"   => ["erro", "Esse produto já aparece em pedidos e não pode ser excluído. Marque-o como indisponível."],
    "nao_encontrado" => ["erro", "Produto não encontrado."],
];

cabecalho("Cardápio");
?>
<?php if (isset($mensagens[$msg])): ?>
    <div class="alerta <?= $mensagens[$msg][0] ?> entra" role="status" style="margin-bottom:20px"><?= e($mensagens[$msg][1]) ?></div>
<?php endif; ?>

<?php if (!$produtos): ?>
    <div class="vazio"><?= svg("prato") ?><strong>Cardápio vazio</strong>Ainda não há produtos cadastrados.</div>
<?php else: ?>
    <div class="tabela-rolagem cascata cascata-3">
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th class="num">Preço</th>
                    <?php if ($ehAdmin): ?><th>Situação</th><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($produtos as $p): ?>
                <tr>
                    <td>
                        <div class="produto-linha">
                            <?php if ($p["imagem"]): ?>
                                <img class="miniatura" src="<?= e($p["imagem"]) ?>" alt="" width="48" height="48" loading="lazy">
                            <?php else: ?>
                                <span class="miniatura sem-foto" aria-hidden="true"><?= svg("prato") ?></span>
                            <?php endif; ?>
                            <div><strong><?= e($p["nome"]) ?></strong><small><?= e($p["descricao"]) ?></small></div>
                        </div>
                    </td>
                    <td><?= e($p["categoria"]) ?></td>
                    <td class="num">R$ <?= number_format($p["preco"], 2, ",", ".") ?></td>
                    <?php if ($ehAdmin): ?>
                        <td><span class="selo <?= $p["disponivel"] ? "livre" : "finalizado" ?>"><?= $p["disponivel"] ? "Disponível" : "Indisponível" ?></span></td>
                        <td class="num">
                            <form method="post" action="produto_excluir.php" data-confirmar="Excluir este produto?">
                                <?= campoCsrf() ?>
                                <input type="hidden" name="id" value="<?= (int) $p["id"] ?>">
                                <button class="btn btn-sec" type="submit">Excluir</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php rodape(); ?>
