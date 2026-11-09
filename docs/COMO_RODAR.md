# PedirJá: como rodar, passo a passo

Guia para colocar o PedirJá no ar no XAMPP (Windows), abrir no computador e no celular e fazer um pedido completo, do QR Code da mesa até o fechamento da conta.

---

## Links de acesso (resumo)

Os links abaixo usam a pasta **`perdirja`**, que é o nome da pasta em `C:\xampp\htdocs`. O IP **`10.111.43.204`** é o do computador conectado ao **hotspot do celular** (rede BH) em 08/11/2026. Ele muda quando a rede muda, mas a página **QR Codes das mesas** descobre o IP atual sozinha (passo 8).

### No computador

| O que | Link |
|-------|------|
| Sistema (abre o login) | http://localhost/perdirja/ |
| Login | http://localhost/perdirja/paginas/login.php |
| Criar conta de cliente | http://localhost/perdirja/paginas/cadastro.php |
| Cardápio (lista com fotos) | http://localhost/perdirja/paginas/produtos.php |
| Página de testes | http://localhost/perdirja/teste/ |
| Banco de dados (phpMyAdmin) | http://localhost/phpmyadmin |
| **QR Codes das mesas** (admin, IP automático) | http://localhost/perdirja/paginas/qrcodes.php |

### Mesas (link que o QR Code abre)

| Mesa | No computador | No celular (mesma rede) |
|------|---------------|--------------------------|
| 1 | http://localhost/perdirja/paginas/mesa.php?t=mesa-01-a7f3c9 | http://10.111.43.204/perdirja/paginas/mesa.php?t=mesa-01-a7f3c9 |
| 2 | http://localhost/perdirja/paginas/mesa.php?t=mesa-02-b81d4e | http://10.111.43.204/perdirja/paginas/mesa.php?t=mesa-02-b81d4e |
| 3 | http://localhost/perdirja/paginas/mesa.php?t=mesa-03-c92e5f | http://10.111.43.204/perdirja/paginas/mesa.php?t=mesa-03-c92e5f |
| 4 | http://localhost/perdirja/paginas/mesa.php?t=mesa-04-d0a6b1 | http://10.111.43.204/perdirja/paginas/mesa.php?t=mesa-04-d0a6b1 |

### Usuários de teste

| Perfil | E-mail | Senha | Tela depois do login |
|--------|--------|-------|----------------------|
| admin | `admin@restaurante.com` | `Admin@123` | visão geral, caixa, pedidos, cardápio e usuários |
| cozinha | `cozinha@restaurante.com` | `Cozinha@123` | fila de preparo |
| garcom | `garcom@restaurante.com` | `Garcom@123` | pedidos prontos para entregar |
| cliente | `cliente@restaurante.com` | `Cliente@123` | cardápio com fotos, carrinho e conta |

---

## Passo 1. O que você precisa

| Item | Observação |
|------|------------|
| XAMPP (Apache + MySQL/MariaDB + PHP) | usado com PHP 8.2.12 e MariaDB 10.4 |
| Navegador | Chrome, Edge ou Firefox |
| Celular (opcional) | na mesma rede do computador (Wi-Fi ou hotspot do celular), para escanear o QR Code |
| Postman (opcional) | para testar a API |

---

## Passo 2. Colocar o projeto no XAMPP

1. Copie a pasta **inteira** do projeto para `C:\xampp\htdocs\perdirja`.
2. Confira se ficou assim:

   ```
   C:\xampp\htdocs\perdirja\index.php
   C:\xampp\htdocs\perdirja\banco.sql
   C:\xampp\htdocs\perdirja\assets\img\produtos\x-burguer.jpg
   C:\xampp\htdocs\perdirja\api\login.php
   ```

> O nome da pasta vira parte do endereço. Use um nome **sem acento**: a pasta original se chama `PedirJá`, e o "á" atrapalha na URL. Se você preferir `pedirja`, renomeie a pasta e troque `perdirja` por `pedirja` em todos os links deste guia.

---

## Passo 3. Ligar o Apache e o MySQL

1. Abra o **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`).
2. Clique em **Start** no **Apache** e no **MySQL**. Os dois devem ficar verdes.

> Se o MySQL não ligar e aparecer "port 3306 in use", já existe outro MySQL rodando. Feche o processo `mysqld.exe` pelo Gerenciador de Tarefas e clique em Start de novo.

---

## Passo 4. Criar ou atualizar o banco de dados

Escolha **um** dos dois caminhos.

### 4A. Primeira vez (banco novo)

1. Abra http://localhost/phpmyadmin.
2. Clique na aba **SQL** (no topo, sem selecionar nenhum banco).
3. Abra o arquivo `banco/banco.sql`, copie todo o conteúdo, cole na caixa e clique em **Executar**.
4. O banco **`restaurante_db`** aparece na esquerda, com 6 tabelas:

   | Tabela | Para quê |
   |--------|----------|
   | `usuarios` | contas (a senha fica guardada como hash) e perfil |
   | `mesas` | mesas e o token do QR Code |
   | `produtos` | cardápio, com a foto de cada produto |
   | `pedidos` | pedidos, status e total |
   | `pedido_itens` | itens de cada pedido, com o preço do momento |
   | `tentativas_login` | logins errados (limite contra força bruta) |

> ⚠️ O `banco/banco.sql` começa com `DROP DATABASE`: ele **apaga e recria** tudo. Se você já tem usuários ou pedidos que quer manter, use o caminho 4B.

### 4B. Já tenho o banco e só quero as fotos do cardápio

1. Abra http://localhost/phpmyadmin e clique em **`restaurante_db`** na esquerda.
2. Aba **SQL** → cole o conteúdo de `banco/migracoes/2026-11-08_imagens_produtos.sql` → **Executar**.
3. Essa migração só adiciona a coluna `imagem` e liga cada produto à sua foto. Ela **não apaga** usuários, pedidos nem produtos e pode ser rodada mais de uma vez.

---

## Passo 5. Conferir a conexão

O `config/conexao.php` já está pronto para o XAMPP padrão: usuário `root`, sem senha, banco `restaurante_db`.

- Se o seu MySQL tem senha, abra `config/conexao.php` e preencha `$DB_SENHA`.
- Se o arquivo não existir (por exemplo, depois de baixar do GitHub), copie `config/conexao.exemplo.php` para `config/conexao.php`.

---

## Passo 6. Abrir o sistema no computador

1. Abra **http://localhost/perdirja/**. Sem login, você vai para a tela de login.
2. Entre com um dos usuários de teste. Cada perfil cai na sua própria tela.
3. Se você já tinha aberto o sistema antes e não vê as fotos do cardápio, aperte **Ctrl+F5** para o navegador carregar a versão nova.

---

## Passo 7. Fazer um pedido completo (roteiro de demonstração)

Use **duas janelas do navegador**: uma normal e uma anônima (**Ctrl+Shift+N**). Cada janela tem a sua sessão, então dá para ficar logado com dois perfis ao mesmo tempo.

| # | Quem | Onde | O que fazer |
|---|------|------|-------------|
| 1 | Cliente | janela anônima | Abra o link da **mesa 1**: http://localhost/perdirja/paginas/mesa.php?t=mesa-01-a7f3c9. A mesa fica guardada e abre o login. |
| 2 | Cliente | mesma janela | Entre com `cliente@restaurante.com` / `Cliente@123`. No topo deve aparecer o selo **Mesa 1**. |
| 3 | Cliente | mesma janela | No cardápio com fotos, toque em **Adicionar** nos itens. Depois, na barra de baixo, toque em **Ver pedido** e em **Enviar pedido**. |
| 4 | Cozinha | janela normal | Entre com `cozinha@restaurante.com`. O pedido aparece em **Recebidos**. Toque em **Em preparo** e depois em **Pronto**. |
| 5 | Garçom | janela normal | Saia e entre com `garcom@restaurante.com`. O pedido aparece na lista. Toque em **Entregar**. |
| 6 | Cliente | janela anônima | Em **Conta da mesa**, toque em **Pedir a conta**. |
| 7 | Admin | janela normal | Saia e entre com `admin@restaurante.com`. Vá em **Caixa** e feche a conta da mesa 1. A mesa volta a ficar livre. |

> **Sem a mesa, o pedido não sai.** Se o cliente entrar direto pelo login, sem abrir o link da mesa, o botão **Enviar pedido** fica desabilitado. Abra o link da mesa na mesma janela, antes ou depois do login. Sair (logout) apaga a mesa da sessão; depois de entrar de novo, abra o link outra vez.

### Roteiro da Etapa 1 (Aula 12: sessões e login)

| # | Teste | Resultado esperado |
|---|-------|--------------------|
| 1 | Sem login, abra http://localhost/perdirja/paginas/painel.php | volta para o login |
| 2 | Erre a senha no login | "E-mail ou senha inválidos." (não diz qual dos dois errou) |
| 3 | Entre como cliente e abra http://localhost/perdirja/paginas/admin.php | página **403 Acesso negado** |
| 4 | Entre como admin, abra http://localhost/perdirja/paginas/produtos.php e exclua um produto que não está em nenhum pedido | produto excluído |
| 5 | Clique em **Sair**, confirme e use o botão **Voltar** do navegador | a página protegida não volta |
| 6 | No phpMyAdmin, abra a tabela `usuarios` | a coluna `senha_hash` mostra `$2y$10$...`, nunca a senha digitada |

---

## Passo 8. Abrir no celular e escanear o QR Code

O sistema **não abre a câmera**. Quem lê o QR Code é a câmera do próprio celular (app **Câmera** ou **Google Lens**): ela reconhece o código e abre o link da mesa no navegador.

1. Coloque celular e computador **na mesma rede**, de um destes dois jeitos:
   - **Hotspot do celular (internet roteada):** ligue o roteador Wi-Fi do celular e conecte o computador a ele. Os dados móveis do celular podem ficar ligados. É assim que está configurado hoje (rede **BH**, IP do computador **10.111.43.204**).
   - **Wi-Fi comum:** celular e computador no mesmo Wi-Fi. Nesse caso, o celular não pode estar usando os dados móveis.
2. No **computador**, entre como **admin** e abra **QR Codes das mesas** no menu (ou http://localhost/perdirja/paginas/qrcodes.php).
   A página descobre sozinha o IP atual do computador e monta um QR Code por mesa. **Trocou de rede? Abra a página de novo**; não precisa rodar `ipconfig` nem digitar IP.
3. No **celular**, abra a câmera e aponte para o código de uma mesa na tela do computador.
4. Toque no link que aparecer. A mesa fica guardada e abre o login.
5. Entre como cliente, escolha os itens e toque em **Ver pedido** e depois em **Enviar pedido**.

Para testar sem QR Code, digite no navegador do celular o link que aparece embaixo do código de cada mesa (por exemplo **http://192.168.0.86/perdirja/paginas/mesa.php?t=mesa-01-a7f3c9**).

**Na página QR Codes das mesas:**
- **Endereço automático:** não há nada para escolher. A página usa o IP da placa de rede real (Wi-Fi ou cabo) e ignora as placas virtuais do Windows (Hyper-V/WSL 172.x, VirtualBox, VMware), que o celular não alcança.
- **Ampliar:** clique no código para vê-lo sozinho e bem grande. É o jeito mais fácil de ler com a câmera apontada para o monitor.
- **Imprimir:** o botão **Imprimir** gera uma folha só com os códigos, sem o menu. Lembre que o código impresso vale só para a rede daquele momento.
- **Copiar link:** cada mesa tem um botão **Copiar link**, para mandar o link por mensagem.
- **Mesas novas:** como as mesas vêm do banco, uma mesa nova também ganha QR Code.
- **Acesso:** só o **admin** abre a página; outros perfis recebem 403.

**Observações:**
- **`localhost` não funciona no celular**, porque lá ele aponta para o próprio celular. Por isso os códigos usam o IP do computador.
- **Se o celular não abrir a página**: Windows → Firewall → **Permitir um aplicativo** → marque "Apache HTTP Server" no tipo da rede usada. O hotspot do celular costuma entrar como rede **Pública**, e o Wi-Fi de casa como **Privada**. Hoje a regra do Apache já está liberada para rede Pública.

---

## Passo 9. Testar a API no Postman (opcional)

Toda resposta da API é JSON. Requisições que **alteram dados** (POST, PUT, PATCH, DELETE) precisam do token CSRF no cabeçalho `X-CSRF-Token`; sem ele a resposta é **403**.

1. `GET http://localhost/perdirja/api/csrf.php` → copie o valor de `csrf`.
2. `POST http://localhost/perdirja/api/login.php`
   - Headers: `X-CSRF-Token: <token>` e `Content-Type: application/json`
   - Body → raw → JSON:

     ```json
     { "email": "admin@restaurante.com", "senha": "Admin@123" }
     ```

3. A resposta traz um **token novo** em `csrf` (ele muda a cada login e logout). Use esse nas próximas requisições.
4. O Postman guarda o cookie de sessão sozinho. Para trocar de perfil, faça `POST /api/logout.php` (com o token) e entre de novo.

| Ação | Método e link | Corpo (JSON) |
|------|---------------|--------------|
| Cardápio (com fotos) | `GET http://localhost/perdirja/api/cardapio.php` | — |
| Fazer pedido (cliente) | `POST http://localhost/perdirja/api/pedidos.php` | `{ "itens": [ { "produto_id": 1, "quantidade": 2, "observacao": "sem cebola" } ] }` |
| Mudar status (cozinha) | `PUT http://localhost/perdirja/api/cozinha/status.php` | `{ "pedido_id": 1, "status": "em_preparo" }` |
| Entregar (garçom) | `PUT http://localhost/perdirja/api/garcom/entregar.php` | `{ "pedido_id": 1 }` |
| Pedir a conta (cliente) | `POST http://localhost/perdirja/api/cliente/pagamento.php` | — |
| Fechar a conta (admin) | `POST http://localhost/perdirja/api/admin/caixa.php` | `{ "mesa_id": 1 }` |

> Para fazer pedido pelo Postman, primeiro abra o link da mesa **no próprio Postman** (`GET http://localhost/perdirja/paginas/mesa.php?t=mesa-01-a7f3c9`), para a mesa ficar na sessão.

A lista completa de rotas, com o status esperado em cada caso, está em `docs/VALIDACAO.md`.

---

## Passo 10. Página de testes do projeto (opcional)

Abra **http://localhost/perdirja/teste/**. Ela só funciona na própria máquina.

- **Roteiro automático:** um clique roda os cenários de sucesso e de erro da API. O roteiro cria pedidos reais na mesa 4.
- **Requisição manual:** todos os endpoints, com login rápido por perfil e botões que simulam o QR Code de cada mesa.

**Apague a pasta `teste/` antes de publicar o sistema fora do seu computador.**

---

## Checklist antes da apresentação

- [ ] Apache e MySQL verdes no XAMPP
- [ ] Banco criado pelo `banco/banco.sql` (dados limpos) ou atualizado pela migração das fotos
- [ ] http://localhost/perdirja/ abre o login, e o cardápio mostra as fotos
- [ ] Login dos 4 perfis testado
- [ ] Computador no hotspot do celular (ou no mesmo Wi-Fi) e **QR Codes das mesas** aberta como admin, com o celular abrindo o link de uma mesa
- [ ] Página dos QR Codes aberta no computador
- [ ] Um caso de erro preparado (cliente em `admin.php` → 403; senha errada 6 vezes → 429)
- [ ] Plano B: prints das telas e o `docs/VALIDACAO.md`

---

## Problemas comuns

| Sintoma | Causa provável | O que fazer |
|---------|----------------|-------------|
| 404 ao abrir o link | nome da pasta diferente do link | confira se a pasta é `C:\xampp\htdocs\perdirja` |
| "Erro interno. Tente novamente mais tarde." | MySQL desligado ou banco não criado | ligue o MySQL e faça o passo 4 |
| Cardápio sem fotos | navegador com a versão antiga, ou banco sem a coluna `imagem` | aperte Ctrl+F5; se continuar, rode a migração do passo 4B |
| Botão "Enviar pedido" desabilitado | cliente não abriu o link da mesa | abra o link da mesa (passo 7) |
| Celular não abre o sistema | outra rede, IP mudou ou firewall | veja as observações do passo 8 |
| 403 "Token CSRF ausente ou inválido" | token antigo (muda no login e no logout) | `GET /api/csrf.php` de novo, ou use o `csrf` da resposta do login |
| "Esta página expirou" num formulário | página aberta antes de entrar ou sair | recarregue a página (F5) e envie de novo |
| 429 "Muitas tentativas de login" | 5 senhas erradas em 15 minutos | espere 15 minutos ou apague as linhas de `tentativas_login` |
| "Este mesmo pedido acabou de ser enviado" (409) | o mesmo pedido foi enviado duas vezes em menos de 10 segundos | é a proteção contra pedido duplicado; o primeiro já foi para a cozinha, confira em "Meus pedidos" |
