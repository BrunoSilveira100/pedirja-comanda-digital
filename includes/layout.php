<?php
// includes/layout.php — estrutura das telas: menu lateral, cabeçalho, conteúdo e tela de entrada

require_once __DIR__ . "/resposta.php";

const ICONES_SVG = [
    "prato"    => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/>',
    "sacola"   => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/>',
    "recibo"   => '<path d="M4 2v20l3-2 3 2 3-2 3 2 3-2 2 2V2l-2 2-3-2-3 2-3-2-3 2z"/><path d="M8 8h8M8 12h8M8 16h5"/>',
    "grafico"  => '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 6-6"/>',
    "caixa"    => '<rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
    "lista"    => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    "usuarios" => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
    "panela"   => '<path d="M3 11h18v3a7 7 0 0 1-7 7h-4a7 7 0 0 1-7-7z"/><path d="M1 11h2M21 11h2M8 7c0-2 2-2 2-4M14 7c0-2 2-2 2-4"/>',
    "sino"     => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
    "sair"     => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
    "menu"     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
    "mesa"     => '<path d="M3 9h18M5 9v11M19 9v11M8 9V5h8v4"/>',
    "copiar"   => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/>',
    "alerta"   => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
];

function svg(string $nome, string $classe = ""): string
{
    $c = $classe ? " class=\"$classe\"" : "";
    return "<svg$c viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\""
         . " stroke-linejoin=\"round\" aria-hidden=\"true\">" . (ICONES_SVG[$nome] ?? "") . "</svg>";
}

/**
 * Caminho de um CSS/JS com a versão do arquivo (data de modificação), ex.: assets/js/app.js?v=1794170000.
 * Quando o arquivo muda, o endereço muda e o navegador baixa de novo, sem precisar de Ctrl+F5.
 */
function versionado(string $caminho): string
{
    $arquivo = __DIR__ . "/../" . $caminho;
    return e(urlBaseLayout() . $caminho . (is_file($arquivo) ? "?v=" . filemtime($arquivo) : ""));
}

function logoSvg(): string
{
    return '<svg viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="9" fill="#c2410c"/>'
         . '<path d="M9 13h14l-1.6 9.2a2 2 0 0 1-2 1.8h-6.8a2 2 0 0 1-2-1.8z" fill="#fff"/>'
         . '<path d="M12 13a4 4 0 0 1 8 0" fill="none" stroke="#fff" stroke-width="2"/></svg>';
}

/** Itens do menu por perfil: [arquivo, âncora, rótulo, ícone] */
function menuDoPerfil(string $perfil): array
{
    $menus = [
        "cliente" => ["Pedir" => [
            ["cliente.php", "#cardapio", "Cardápio", "prato"],
            ["cliente.php", "#pedidos", "Meus pedidos", "sacola"],
            ["cliente.php", "#conta", "Conta da mesa", "recibo"],
        ]],
        "cozinha" => ["Cozinha" => [
            ["cozinha.php", "", "Fila de pedidos", "panela"],
            ["produtos.php", "", "Cardápio", "lista"],
        ]],
        "garcom" => ["Salão" => [
            ["garcom.php", "", "Pedidos prontos", "sino"],
            ["produtos.php", "", "Cardápio", "lista"],
        ]],
        "admin" => [
            "Gestão" => [
                ["admin.php", "#visao", "Visão geral", "grafico"],
                ["admin.php", "#caixa", "Caixa", "caixa"],
                ["admin.php", "#pedidos", "Pedidos", "recibo"],
                ["admin.php", "#cardapio", "Cardápio", "prato"],
                ["admin.php", "#usuarios", "Usuários", "usuarios"],
                ["qrcodes.php", "", "QR Codes das mesas", "mesa"],
            ],
            "Telas da equipe" => [
                ["cozinha.php", "", "Cozinha", "panela"],
                ["garcom.php", "", "Garçom", "sino"],
                ["produtos.php", "", "Listagem (Exercício 1)", "lista"],
            ],
        ],
    ];
    return $menus[$perfil] ?? [];
}

/** Título com palavras que sobem em sequência; $destaque recebe a cor e o sublinhado. */
function tituloAnimado(string $texto, string $destaque = "", string $tag = "h1"): string
{
    $palavras = preg_split('/\s+/u', trim($texto));
    $html = "";
    foreach ($palavras as $i => $p) {
        $limpa = trim($p, ".,!?;:");
        $ehDestaque = $destaque !== "" && mb_strtolower($limpa) === mb_strtolower($destaque);
        $classe = $ehDestaque ? ' class="destaque"' : "";
        // o sublinhado começa depois que a última palavra subiu
        $atraso = $ehDestaque ? ' style="--i:' . $i . ';--d:' . (count($palavras) * 0.15 + 0.55) . 's"' : ' style="--i:' . $i . '"';
        $html .= '<span class="p"><span' . $classe . $atraso . '>' . e($p) . '</span></span> ';
    }
    return "<$tag class=\"titulo-animado\">" . rtrim($html) . "</$tag>";
}

/** Faixa rolante decorativa (conteúdo duplicado para o loop não ter emenda). */
function faixaRolante(array $palavras): string
{
    $grupo = "<span>" . implode("", array_map(fn($p) => e($p) . " <i>✦</i>", $palavras)) . "</span>";
    return '<div class="faixa" aria-hidden="true"><div class="faixa-trilho">' . $grupo . $grupo . '</div></div>';
}

function inicioHtml(string $titulo): void
{
    ?>
<!doctype html>
<html lang="pt-BR" data-base="<?= e(urlBaseLayout()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#c2410c">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <title><?= e($titulo) ?> · PedirJá</title>
    <link rel="manifest" href="manifest.json">
    <link rel="icon" href="<?= e(urlBaseLayout()) ?>assets/img/icone.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= e(urlBaseLayout()) ?>assets/img/icone.svg">
    <link rel="stylesheet" href="<?= versionado("assets/css/app.css") ?>">
</head>
<body>
    <?php
}

function urlBaseLayout(): string
{
    return function_exists("urlBase") ? urlBase() : "./";
}

/**
 * Abre a tela com menu lateral e cabeçalho (usuário logado).
 * $acoes: HTML extra no cabeçalho (gerado pelo próprio código).
 */
function cabecalho(string $titulo, string $acoes = ""): void
{
    inicioHtml($titulo);
    $logado = !empty($_SESSION["usuario_id"]);
    if (!$logado) {
        echo '<main class="container">';
        return;
    }
    $perfil = $_SESSION["perfil"] ?? "";
    $nome = $_SESSION["nome"] ?? "";
    $pagina = basename($_SERVER["SCRIPT_NAME"]);
    $rotulosPerfil = ["cliente" => "Cliente", "cozinha" => "Cozinha", "garcom" => "Garçom", "admin" => "Gerente / Caixa"];
    ?>
<div class="app">
    <aside class="lateral cascata cascata-1" id="menu" aria-label="Menu principal">
        <a class="marca" href="painel.php"><?= logoSvg() ?><span>Pedir<b>Já</b></span></a>
        <nav>
        <?php foreach (menuDoPerfil($perfil) as $grupo => $itens): ?>
            <div class="nav-titulo"><?= e($grupo) ?></div>
            <?php $primeiroDaPagina = true; ?>
            <?php foreach ($itens as [$arquivo, $ancora, $rotulo, $icone]):
                $mesmaPagina = ($arquivo === $pagina);
                $attrs = "";
                if ($mesmaPagina && $ancora !== "") {
                    $attrs = ' data-hash="' . e($ancora) . '"' . ($primeiroDaPagina ? " data-padrao" : "");
                    $primeiroDaPagina = false;
                } elseif ($mesmaPagina) {
                    $attrs = ' aria-current="page"';
                }
                ?>
                <a class="nav-link" href="<?= e($arquivo . $ancora) ?>"<?= $attrs ?>><?= svg($icone) ?><?= e($rotulo) ?></a>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </nav>
        <div class="lateral-rodape">
            <div class="usuario-chip">
                <span class="avatar"><?= e(mb_strtoupper(mb_substr($nome, 0, 1))) ?></span>
                <span><strong><?= e($nome) ?></strong><small><?= e($rotulosPerfil[$perfil] ?? $perfil) ?></small></span>
            </div>
            <form method="post" action="logout.php">
                <?= campoCsrf() ?>
                <button class="nav-link btn-sair" type="submit"><?= svg("sair") ?>Sair</button>
            </form>
        </div>
    </aside>
    <div class="fundo-gaveta"></div>

    <div class="principal">
        <header class="cabecalho cascata cascata-2">
            <button class="btn btn-sec btn-icone btn-menu" type="button" aria-controls="menu" aria-expanded="false" aria-label="Abrir menu"><?= svg("menu") ?></button>
            <h1><?= e($titulo) ?></h1>
            <div class="acoes"><?= $acoes ?></div>
        </header>
        <main class="container" id="conteudo">
    <?php
}

/** Fecha a tela. $scripts: arquivos JS da página (além do app.js). */
function rodape(array $scripts = []): void
{
    $logado = !empty($_SESSION["usuario_id"]);
    echo $logado ? "</main></div></div>\n" : "</main>\n";
    echo '<script src="' . versionado("assets/js/app.js") . '"></script>' . "\n";
    foreach ($scripts as $s) {
        echo '<script src="' . versionado($s) . '"></script>' . "\n";
    }
    echo "</body>\n</html>\n";
}

/** Tela de entrada (login e cadastro): arte à esquerda, formulário à direita. */
function abrirEntrada(string $titulo, string $chamada, string $destaque, string $sub): void
{
    inicioHtml($titulo);
    ?>
<div class="entrada">
    <section class="entrada-arte">
        <a class="marca cascata cascata-1" href="login.php"><?= logoSvg() ?><span>Pedir<b>Já</b></span></a>
        <div class="hero">
            <?= tituloAnimado($chamada, $destaque, "p") ?>
            <p class="sub cascata cascata-4"><?= e($sub) ?></p>
        </div>
        <?= faixaRolante(["Lanches", "Padaria", "Bebidas", "Porções", "Sobremesas", "Salgados"]) ?>
    </section>
    <main class="entrada-form">
    <?php
}

function fecharEntrada(): void
{
    echo "</main></div>\n";
    echo '<script src="' . versionado("assets/js/app.js") . '"></script>' . "\n</body>\n</html>\n";
}
