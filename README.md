# PedirJá — Comanda digital

Sistema de pedidos para restaurante, padaria ou lanchonete: o cliente escaneia o QR Code da mesa, entra, vê o cardápio e faz o pedido. A cozinha prepara, o garçom entrega e o caixa (admin) fecha a conta.

Trabalho de Linguagem de Programação VIII — UNIFEG. O projeto reúne as etapas do bimestre:

| Etapa | Aula | O que está pronto |
|-------|------|-------------------|
| 1 | Aula 12 · Sessões e login | cadastro com `password_hash`, login com `password_verify`, sessão, `exigirLogin()`, ação restrita ao admin com 403 |
| 2 | Aula 13 · API própria | API JSON em `api/` para os 4 perfis, com status HTTP 200/201/400/401/403/404/405/409/429/500 |
| 3 | Aula 14 · Front-end e API | telas do cliente (PWA), cozinha, garçom e admin usando `fetch()`, cardápio com fotos e QR Codes das mesas |
| — | Aula 15 · Segurança | CSRF, XSS (escape + CSP), sessão segura, limite de tentativas de login, envio de foto validado pelo conteúdo |

**Documentação:** [`docs/COMO_RODAR.md`](docs/COMO_RODAR.md) (passo a passo e links de acesso) e [`docs/VALIDACAO.md`](docs/VALIDACAO.md) (validação completa: 224 cenários automáticos, todos passando).

## Fluxo

```
Navegador (HTML / PWA)  ──fetch / formulário──▶  Servidor PHP (sessão + auth.php)  ──PDO──▶  MySQL (restaurante_db)
```

O navegador guarda apenas o cookie `PHPSESSID`. Os dados do usuário (id, nome, perfil, mesa) ficam em `$_SESSION`, no servidor.

## Como rodar no XAMPP

1. Copie a pasta do projeto para `C:\xampp\htdocs\pedirja` (use o nome sem acento na URL).
2. Inicie **Apache** e **MySQL** no XAMPP Control Panel.
3. Abra `http://localhost/phpmyadmin`, vá em **SQL** e execute o conteúdo de `banco/banco.sql`. Ele cria o banco `restaurante_db` com tabelas e dados de teste.
4. Copie `config/conexao.exemplo.php` para `config/conexao.php` e ajuste usuário e senha do MySQL se não forem `root` sem senha.
5. Acesse `http://localhost/pedirja/`.

## Usuários de teste

| Perfil  | E-mail                    | Senha        |
|---------|---------------------------|--------------|
| admin   | admin@restaurante.com     | Admin@123    |
| cozinha | cozinha@restaurante.com   | Cozinha@123  |
| garcom  | garcom@restaurante.com    | Garcom@123   |
| cliente | cliente@restaurante.com   | Cliente@123  |

As senhas estão guardadas como hash (`password_hash`). No phpMyAdmin a coluna `senha_hash` não mostra a senha digitada.

## QR Codes das mesas

Cada mesa tem um token em `mesas.qr_code_token`. O QR Code aponta para:

```
http://localhost/pedirja/mesa.php?t=mesa-01-a7f3c9
```

O servidor valida o token e guarda a mesa na sessão. O pedido sempre usa a mesa da sessão, nunca um número enviado pelo navegador.

## Perfis e permissões

| Perfil  | O que pode                                              |
|---------|---------------------------------------------------------|
| cliente | ver cardápio, fazer pedido, pedir a conta               |
| cozinha | fila de pedidos, mudar para "em preparo" / "pronto"     |
| garcom  | pedidos prontos, confirmar entrega                      |
| admin   | tudo, inclusive excluir produtos e gerenciar usuários   |

- `exigirLogin()`: sem sessão, a página redireciona para `login.php` e a API responde **401**.
- `exigirPerfil(...)`: perfil sem permissão recebe **403**. O admin passa em qualquer perfil.
- A verificação é feita no servidor. Esconder o botão na tela não protege.

## Estrutura

```
banco/banco.sql           tabelas + dados de teste
banco/migracoes/          atualizam um banco já existente sem apagar dados
config/conexao.exemplo.php modelo da conexão PDO (o conexao.php real fica fora do Git)
includes/auth.php         sessão, exigirLogin(), exigirPerfil()
includes/seguranca.php    cabeçalhos HTTP, sessão segura e CSRF (Aula 15)
includes/tentativas.php   limite de tentativas de login
includes/usuarios.php     inserirUsuario, buscarUsuarioPorEmail, autenticar...
includes/produtos.php     listarProdutos, excluirProduto...
includes/resposta.php     responderJson, lerEntrada, e() (escape HTML)
includes/layout.php       cabeçalho e rodapé das páginas
login.php / cadastro.php / logout.php
painel.php                área protegida (exigirLogin): leva cada perfil para a sua tela
cliente.php, cozinha.php, garcom.php, admin.php   telas de cada perfil (Etapa 3)
assets/css/app.css, assets/js/app.js    design, animações e utilitários (API, modal, avisos, polling)
assets/js/cliente.js, fila.js, cozinha.js, garcom.js, admin.js, qrcodes.js   lógica de cada tela
assets/img/               ícone e fotos do cardápio (produtos/)
manifest.json, sw.js      instalação como PWA
produtos.php              listagem do cardápio (exigirLogin)
produto_excluir.php       ação restrita (exigirPerfil("admin") → 403 para os outros)
mesa.php                  destino do QR Code
qrcodes.php               QR Codes das mesas (admin); o IP da rede é descoberto sozinho por includes/rede.php
assets/vendor/qrcode.min.js   biblioteca QRCode.js (MIT), guardada localmente por causa da CSP
includes/pedidos.php      ciclo do pedido, transações, conta da mesa e caixa
api/                      endpoints JSON (cliente/, cozinha/, garcom/, admin/)
teste/                    página de testes (apagar antes de publicar)
```

## API

Todas as respostas são JSON. Sem login → **401**; perfil errado ou sem token CSRF → **403**; método errado → **405**.

| Método | Endpoint | Proteção | O que faz | Status |
|--------|----------|----------|-----------|--------|
| GET | `/api/csrf.php` | pública | token CSRF da sessão | 200 |
| POST | `/api/login.php` | pública | autentica e regenera o ID da sessão | 200, 400, 401, 429 |
| POST | `/api/cadastro.php` | pública | cria cliente e já entra | 201, 400, 409 |
| POST | `/api/logout.php` | logado | destrói a sessão | 200 |
| GET | `/api/me.php` | logado | usuário e mesa da sessão | 200 |
| GET | `/api/cardapio.php` | logado | produtos disponíveis | 200 |
| GET | `/api/pedidos.php` | cliente | meus pedidos em aberto | 200 |
| POST | `/api/pedidos.php` | cliente | cria pedido na mesa do QR | 201, 400, 404, 409 |
| GET | `/api/cliente/pagamento.php` | cliente | conta em aberto da mesa | 200, 400 |
| POST | `/api/cliente/pagamento.php` | cliente | pede a conta | 200, 400, 409 |
| GET | `/api/cozinha/pedidos.php` | cozinha | fila: recebido e em preparo | 200 |
| PUT | `/api/cozinha/status.php` | cozinha | `em_preparo` ou `pronto` | 200, 400, 404, 409 |
| GET | `/api/garcom/pedidos.php` | garçom | pedidos prontos | 200 |
| PUT | `/api/garcom/entregar.php` | garçom | pronto → entregue | 200, 400, 404, 409 |
| GET | `/api/admin/dashboard.php` | admin | métricas do dia | 200 |
| GET | `/api/admin/pedidos.php?status=` | admin | 100 pedidos mais recentes | 200, 400 |
| GET | `/api/admin/caixa.php` | admin | mesas aguardando pagamento | 200 |
| POST | `/api/admin/caixa.php` | admin | fecha a conta e libera a mesa | 200, 400, 404, 409 |
| GET/POST/PUT/DELETE | `/api/admin/usuarios.php` | admin | gestão de usuários | 200, 201, 400, 404, 409 |
| GET/POST/PUT/DELETE | `/api/admin/produtos.php` | admin | gestão do cardápio | 200, 201, 400, 404, 409 |
| POST (multipart) | `/api/admin/produto_imagem.php` | admin | envia (`imagem`) ou remove (`remover=1`) a foto do produto `id`; só JPG/PNG/WEBP até 3 MB, conferidos pelo conteúdo | 200, 400, 404 |

### Regras do pedido

- Ciclo: `recebido` → `em_preparo` → `pronto` → `entregue` → `solicitado_pagamento` → `finalizado`. Pular etapa responde **409**.
- O **total é calculado no servidor** com o preço do banco; preço enviado pelo navegador é ignorado. Pedido e itens são gravados numa transação.
- **Pedido repetido:** o mesmo cliente mandando os mesmos itens, na mesma mesa, em menos de 10 s (duplo clique, rede lenta) recebe **409** e só um pedido é gravado. A verificação roda dentro da transação, com a mesa travada (`FOR UPDATE`).
- A conta é **da mesa**: só pode ser pedida quando todos os pedidos da mesa foram entregues. Depois disso a mesa não aceita novos pedidos até o caixa fechar a conta.
- Produto ou usuário que já aparece em pedidos não é excluído (**409**); o produto pode ser marcado como indisponível.

### Usando no Postman

Toda requisição que altera dados (POST, PUT, PATCH, DELETE) precisa do **token CSRF** no cabeçalho `X-CSRF-Token`; sem ele a resposta é **403**.

1. `GET http://localhost/pedirja/api/csrf.php` → copie o valor de `csrf`.
2. `POST http://localhost/pedirja/api/login.php`, cabeçalho `X-CSRF-Token: <token>`, corpo **raw / JSON**:

```json
{ "email": "admin@restaurante.com", "senha": "Admin@123" }
```

3. O login devolve um **token novo** em `csrf` (ele muda a cada login e logout). Use esse nas próximas requisições.

O Postman guarda o cookie de sessão e o envia sozinho.

## Segurança (Aula 15)

| Ameaça | Proteção | Onde |
|--------|----------|------|
| **XSS** (script injetado) | Toda saída do PHP passa por `e()` (`htmlspecialchars`); no JavaScript, dados da API entram só com `textContent`. A **Content-Security-Policy** só executa scripts do próprio servidor, então nem um script injetado roda. Sem `onclick=`/`onsubmit=` no HTML. | `includes/resposta.php`, `assets/js/app.js`, `includes/seguranca.php` |
| **CSRF** (outro site agindo em seu nome) | Token aleatório de 64 caracteres por sessão, conferido com `hash_equals` em todo POST/PUT/PATCH/DELETE (formulários e API). Troca a cada login. Cookie `SameSite=Lax` como segunda camada. Logout só por POST. | `includes/seguranca.php`, `includes/auth.php` |
| **Roubo de sessão** | Cookie `HttpOnly` (JavaScript não lê), `Secure` com HTTPS, `use_strict_mode`, `session_regenerate_id` no login, sessão expira após 2 h parada. | `includes/seguranca.php` |
| **Força bruta no login** | 5 senhas erradas do mesmo IP para o mesmo e-mail em 15 min → **429** com `Retry-After`; 50 erros do mesmo IP bloqueiam o IP. | `includes/tentativas.php`, tabela `tentativas_login` |
| **SQL Injection** | Só prepared statements (PDO); colunas dinâmicas vêm de uma lista validada. | `includes/*.php` |
| **Clickjacking** | `X-Frame-Options: DENY` e `frame-ancestors 'none'`. | `includes/seguranca.php` |
| **Vazamento de detalhes** | `display_errors` desligado; erros vão para o log e o usuário vê mensagem genérica. Sem `X-Powered-By`. Mensagem de login não diz se o e-mail existe. | `includes/seguranca.php`, `includes/resposta.php` |
| **Credenciais no Git** | `config/conexao.php` fora do repositório (`.gitignore`); só o modelo `conexao.exemplo.php` é versionado. | `.gitignore` |

A página de testes cobre esses cenários na seção **Segurança (Aula 15)**: requisições sem token ou com token falso, cabeçalhos HTTP, XSS no nome do usuário, logout por GET e bloqueio por força bruta.

## Telas (Etapa 3)

Depois do login, `painel.php` leva cada perfil para a sua tela. Todas usam só a API do próprio servidor (`fetch`) e verificam o perfil no servidor.

| Tela | Perfil | O que tem |
|------|--------|-----------|
| `cliente.php` | cliente | cardápio com filtro por categoria, carrinho com observação por item, envio do pedido, acompanhamento ao vivo (linha do tempo e avisos quando muda o status) e pedido da conta |
| `cozinha.php` | cozinha | fila em duas colunas (Recebidos / Em preparo), tempo de espera (vermelho após 15 min), botões para avançar o status, aviso de pedido novo |
| `garcom.php` | garçom | pedidos prontos com a mesa em destaque, aviso e vibração quando chega pedido pronto, confirmação de entrega |
| `admin.php` | admin | visão geral do dia, caixa (fechar conta), pedidos com filtro, cardápio (criar, editar, disponível, excluir) e usuários (criar, mudar perfil, excluir) |

- **Atualização ao vivo:** consulta a API a cada 4–5 s (15 s na visão geral) e pausa quando a aba fica escondida.
- **Movimento:** entrada do título palavra por palavra, cascata do menu e cabeçalho, cards escalonados, skeleton enquanto carrega, check desenhado no sucesso, tremida no erro, modais e avisos animados. Tudo é desligado quando o sistema pede menos movimento (`prefers-reduced-motion`).
- **Celular:** o menu lateral vira gaveta; botões têm no mínimo 44 px de altura.
- **PWA:** `manifest.json` e `sw.js` permitem instalar o sistema na tela inicial. O service worker guarda só CSS, JS e ícone; pedidos e páginas sempre vêm do servidor.
- **Segurança na tela:** todo texto vindo da API é inserido com `textContent`, nunca como HTML.

## Página de testes

Abra `http://localhost/pedirja/teste/` (só funciona na própria máquina).

- **Roteiro automático:** um clique executa 100 cenários: autenticação, segurança, pedido do cliente, cozinha, garçom, conta e admin, com os casos de sucesso e de erro (400, 401, 403, 404, 405, 409, 429). Cada linha mostra o status esperado e o obtido; clique nela para ver o corpo enviado e a resposta.
- **Requisição manual:** lista de todos os endpoints com corpo de exemplo, login rápido por perfil e botões para simular o QR Code de cada mesa.

O roteiro usa a mesa 4 e cria pedidos reais; os usuários e produtos de teste são apagados no final. **Apague a pasta `teste/` antes de publicar o sistema.**

## Como testar a Etapa 1

1. Sem login, abra `painel.php`: deve voltar para o login.
2. Erre a senha: aparece "E-mail ou senha inválidos." (não diz qual campo errou).
3. Entre como **cliente** e envie um POST para `produto_excluir.php`: resposta **403**.
4. Entre como **admin** e exclua um produto que ainda não está em nenhum pedido.
5. Clique em **Sair** e use o botão Voltar do navegador: a página protegida não pode reaparecer com dados.

## Fotos do cardápio

Cada produto tem uma foto em `assets/img/produtos/` (coluna `produtos.imagem`). A tela do cliente mostra o cardápio em cartões com a foto no topo; o admin e `produtos.php` mostram uma miniatura. Produto sem foto aparece com um ícone no lugar.

O **admin** cadastra e edita produtos com foto em **Gestão → Cardápio** (botão **Novo produto** ou o lápis de cada linha): campo **Foto** com pré-visualização e opção **Remover a foto atual**. A foto enviada recebe um nome gerado pelo servidor (`p<id>-<aleatório>.jpg`); ao trocar, a foto enviada antes é apagada, e as fotos originais do cardápio nunca são apagadas.

- **Banco novo:** o `banco/banco.sql` já cria a coluna e liga cada produto à sua foto.
- **Banco já existente:** rode `banco/migracoes/2026-11-08_imagens_produtos.sql` no phpMyAdmin. Ele só adiciona a coluna e preenche as fotos, sem apagar usuários nem pedidos.
- Por segurança, a API só devolve caminhos dentro de `assets/img/produtos/` (`imagemValida()` em `includes/produtos.php`), e a Content-Security-Policy só carrega imagens do próprio servidor.

Fotos do [Pexels](https://www.pexels.com/license/), de uso livre:

| Produto | Foto |
|---------|------|
| X-Burguer | https://www.pexels.com/photo/19247575/ |
| X-Bacon | https://www.pexels.com/photo/33068078/ |
| Misto quente | https://www.pexels.com/photo/15110224/ |
| Pão de queijo | https://www.pexels.com/photo/37778433/ |
| Coxinha | https://www.pexels.com/photo/4842865/ |
| Batata frita | https://www.pexels.com/photo/1583891/ |
| Refrigerante lata | https://www.pexels.com/photo/6920721/ |
| Suco natural | https://www.pexels.com/photo/17612820/ |
| Café expresso | https://www.pexels.com/photo/2310785/ |
| Pudim | https://www.pexels.com/photo/33674417/ |

## Limitações conhecidas

- O "tempo real" é feito por consulta periódica (polling), não por WebSocket.
- O service worker só funciona em `localhost` ou com HTTPS (regra dos navegadores).
- Fora do XAMPP local é obrigatório usar HTTPS, porque senha e cookie trafegariam abertos.
