# PedirJá: validação completa do projeto

> As telas ficam em `paginas/`: onde este documento cita `login.php`, `painel.php`, `produto_excluir.php` etc., o caminho é `paginas/<arquivo>`.

Primeira validação em **07/11/2026** (só leitura do código). Validação final em **08/11/2026**, depois dos ajustes A2 e A3 e das novidades: fotos do cardápio, envio de foto pelo admin e QR Codes das mesas.

**Resultado final:**
- `docs/validar.php`: **224 cenários, 224 OK, 0 falhas** (inclui os 2 cenários de banco fora do ar). Tabela linha por linha em [`resultados_validacao.md`](resultados_validacao.md).
- Página de testes do projeto (`teste/`): **100 testes, 100 OK**.

## Como a validação foi feita

1. **Sem tocar no seu banco.** O `restaurante_db` real foi copiado para um backup e não foi usado. O `banco/banco.sql` foi carregado num banco separado, `restaurante_db_validacao`.
2. **Sem tocar no seu código.** Uma cópia do projeto foi servida com `php -S 127.0.0.1:8099`, com o `config/conexao.php` da cópia apontando para o banco de validação. Uma segunda cópia, com o banco apontando para um nome inexistente, simulou o "banco fora do ar".
3. **Checagem estática:** `php -l` em todos os `.php` (nenhum erro de sintaxe) e busca de `exigirLogin`/`exigirPerfil` em cada página e rota.
4. **Checagem dinâmica:** o script [`validar.php`](validar.php) faz as requisições HTTP de 5 "pessoas" ao mesmo tempo (visitante, cliente, cozinha, garçom e admin), cada uma com o seu cookie de sessão, e depois de cada passo consulta o banco para conferir o que foi gravado.

---

## 1. Aula 12: sessões e login, slide por slide

| Slide | O que a aula pede | Onde está no projeto | Validado | Resultado |
|-------|-------------------|----------------------|----------|-----------|
| 7–8 | HTTP não guarda estado; a sessão usa um cookie `PHPSESSID` no navegador e os dados ficam no servidor | `includes/seguranca.php` → `iniciarSessaoSegura()` | O cookie traz só o ID. O arquivo `C:\xampp\tmp\sess_<id>` guarda `usuario_id`, `nome`, `perfil`, `csrf` e `ultimo_acesso` | ✅ |
| 9 | `session_start()` antes de qualquer HTML; `$_SESSION` persiste; `??` para valor padrão | `includes/auth.php` é o 1º `require` de toda página e chama `session_start()` antes de qualquer saída; `??` em `exigirPerfil`, `me.php` e `layout.php` | Sessão reconhecida em `/api/me.php` nas requisições seguintes, para os 4 perfis | ✅ |
| 10 | Autenticação (quem é você? → 401 ou redirecionar) × autorização (pode fazer? → 403) | `exigirLogin()` e `exigirPerfil()` em `includes/auth.php` | Visitante: páginas → **302 para login.php**; API → **401**. Logado sem permissão → **403** (páginas e API) | ✅ |
| 11 | `password_hash` ao cadastrar, `password_verify` ao entrar, coluna `VARCHAR(255)`; nunca texto puro, MD5 ou SHA1 | `inserirUsuario()` e `autenticar()` em `includes/usuarios.php`; `senha_hash VARCHAR(255)` no `banco/banco.sql` | Senha gravada como `$2y$10$...` (bcrypt); a senha digitada não aparece no banco; vale também para usuários criados pelo admin | ✅ |
| 12 | Tabela `usuarios` (`email UNIQUE`, `perfil`) e `inserirUsuario` com PDO e prepared statement | `banco/banco.sql`, `includes/usuarios.php` | E-mail repetido → **409** (cadastro e admin). Perfil é `ENUM` (cliente/cozinha/garcom/admin) em vez do `VARCHAR(20)` do slide: mais restritivo, aceita só perfis válidos | ✅ |
| 13 | Login: buscar por e-mail, `password_verify`, `session_regenerate_id(true)`, guardar id e perfil, `header` + `exit`, mensagem genérica | `login.php`, `api/login.php`, `iniciarSessaoDoUsuario()` | ID da sessão **trocou** no login dos 4 perfis. Senha errada e e-mail inexistente devolvem **corpo idêntico**: `{"erro":"E-mail ou senha inválidos."}`. Sessão e resposta **sem senha e sem hash** | ✅ |
| 14 | `includes/auth.php` com `exigirLogin()` reutilizado em todas as páginas | `includes/auth.php` | `painel.php`, `produtos.php`, `cliente.php`, `cozinha.php`, `garcom.php`, `admin.php` sem login → 302 para `login.php` | ✅ |
| 15 | Logout: esvaziar `$_SESSION`, destruir a sessão, voltar ao login; o botão Voltar não pode mostrar a página protegida | `encerrarSessao()` em `includes/usuarios.php`, `logout.php`, `api/logout.php` | Arquivo `sess_<id>` **apagado** no servidor. Cookie antigo reaproveitado → **401**. Página protegida depois do logout → **302 login.php**. As páginas saem com `Cache-Control: no-store`, então o Voltar recarrega do servidor | ✅ |
| 16 | `exigirPerfil("admin")` → 403 para outros perfis; esconder o botão não protege | `produto_excluir.php`, `admin.php`, `api/admin/*` | Cliente enviando POST para `produto_excluir.php` **com token válido** → **403 "Acesso negado"** (é o perfil que barra, não o CSRF). Cliente tentando `DELETE /api/admin/produtos.php` → **403** | ✅ |
| 17 | Cuidados: texto puro, esquecer `exigirLogin()`, mensagem que revela demais, HTTPS fora do XAMPP | todo o projeto | Todas as 21 rotas que precisam de proteção têm `exigirLogin` ou `exigirPerfil`. As 5 sem proteção são públicas de propósito: `login`, `cadastro`, `api/csrf`, `mesa` e `index`. HTTPS: o cookie vira `Secure` sozinho quando o site roda com HTTPS | ✅ |
| 18 | Prática guiada: tabela → usuarios.php → cadastro/login → auth/painel → logout | todos os arquivos acima | Fluxo cadastro → login → painel → logout testado de ponta a ponta | ✅ |

### Exercício 1: critérios de correção (slide 20)

| Critério | Evidência |
|----------|-----------|
| Senha guardada com `password_hash` | `usuarios.senha_hash` = `$2y$10$...`; testes #34–35 |
| Páginas protegidas com `exigirLogin()` | `painel.php` e `produtos.php` (`exigirLogin()`); telas por perfil com `exigirPerfil()`; testes #164–169 |
| Mensagem de login genérica | resposta idêntica para e-mail inexistente e senha errada; teste #10 |
| Ação restrita ao administrador, com 403 | `produto_excluir.php` → 403 para cliente; testes #175–176 |
| Entregáveis: pasta/GitHub, `banco/banco.sql` com dados, README | presentes (`banco/banco.sql` tem 4 usuários, 4 mesas e 10 produtos) |
| Tema com entidade de ≥ 4 campos | `produtos`: nome, descrição, preço, categoria, disponível |

### Uma diferença em relação ao slide 16 (proposital)

No slide, `exigirPerfil("admin")` compara igualdade exata. No projeto, `exigirPerfil(...$perfis)` aceita **vários perfis** e o **admin passa em qualquer um**, porque a especificação do PedirJá diz que o gerente/caixa tem acesso a tudo. Para a ação restrita da Etapa 1 o resultado é o mesmo: só o admin exclui. Vale citar isso na apresentação, se o professor perguntar.

---

## 2. O usuário logado fica salvo no banco?

Resposta curta: **a conta fica salva no banco; o "estar logado" fica na sessão, no servidor, como a Aula 12 ensina.**

| O que acontece | Onde fica gravado | Validado |
|----------------|-------------------|----------|
| Cadastro (`cadastro.php` ou `POST /api/cadastro.php`) | **Banco**: nova linha em `usuarios` com nome, e-mail, `senha_hash` (bcrypt), `perfil = cliente` e `criado_em` | ✅ linha criada; o cadastro já deixa logado |
| Tentar se cadastrar como admin mandando `"perfil":"admin"` | ignorado: sempre `cliente` | ✅ |
| Login certo | **Sessão** (arquivo `C:\xampp\tmp\sess_<id>`): `usuario_id`, `nome`, `perfil`. A linha do usuário no banco **não muda** | ✅ conferido: linha idêntica antes e depois |
| Login errado | **Banco**: uma linha em `tentativas_login` (IP + e-mail + horário) | ✅ |
| Login certo depois de errar | as linhas de `tentativas_login` daquele IP + e-mail são **apagadas** | ✅ |
| Logout | a sessão é destruída e o arquivo `sess_<id>` é apagado | ✅ |
| Usuário criado pelo admin | **Banco**: `usuarios`, também com hash | ✅ |

Conteúdo real do arquivo de sessão de um usuário logado, capturado na validação (token encurtado):

```
ultimo_acesso|i:1794086833;csrf|s:64:"…";usuario_id|i:5;nome|s:19:"Cliente Validação";perfil|s:7:"cliente";
```

Ou seja: **não existe no banco um registro de "quem está logado agora" nem de "último acesso"**. Isso não é erro pela Aula 12, mas se você quiser essa informação no banco, veja o ajuste **A1**.

---

## 3. Todas as rotas da API

Legenda: **V** visitante, **C** cliente, **Z** cozinha, **G** garçom, **A** admin. Todas as linhas abaixo foram testadas.

### Acesso por perfil (GET)

| Rota | V | C | Z | G | A |
|------|---|---|---|---|---|
| `GET /api/csrf.php` | 200 | 200 | 200 | 200 | 200 |
| `GET /api/me.php` | 401 | 200 | 200 | 200 | 200 |
| `GET /api/cardapio.php` | 401 | 200 | 200 | 200 | 200 |
| `GET /api/pedidos.php` | 401 | 200 | 403 | 403 | 200 |
| `GET /api/cliente/pagamento.php` | 401 | 200 (400 sem mesa) | 403 | 403 | — |
| `GET /api/cozinha/pedidos.php` | 401 | 403 | 200 | 403 | 200 |
| `GET /api/garcom/pedidos.php` | 401 | 403 | 403 | 200 | 200 |
| `GET /api/admin/dashboard.php` | 401 | 403 | 403 | 403 | 200 |
| `GET /api/admin/pedidos.php` | 401 | 403 | 403 | 403 | 200 |
| `GET /api/admin/caixa.php` | 401 | 403 | 403 | 403 | 200 |
| `GET /api/admin/usuarios.php` | 401 | 403 | 403 | 403 | 200 |
| `GET /api/admin/produtos.php` | 401 | 403 | 403 | 403 | 200 |

### Rotas que alteram dados: sucesso e erros

| Rota | Cenários validados (status) |
|------|-----------------------------|
| `POST /api/login.php` | 200 nos 4 perfis · 400 campos vazios · 400 JSON inválido · 401 senha errada · 401 e-mail inexistente (mesma mensagem) · 403 sem token CSRF · 405 com GET · 429 na 6ª tentativa errada, com `Retry-After` · 500 genérico com o banco fora |
| `POST /api/cadastro.php` | 201 cria cliente e já loga · 400 dados inválidos · 409 e-mail repetido |
| `POST /api/logout.php` | 200 · 403 sem token · depois dele `/api/me.php` → 401 |
| `POST /api/pedidos.php` | 201 com total calculado no servidor · 400 sem mesa · 400 sem itens · 400 quantidade 0 ou > 50 · 400 observação > 255 · 404 produto inexistente · 409 produto indisponível · 409 mesa com a conta pedida |
| `PUT /api/cozinha/status.php` | 200 recebido→em_preparo→pronto · 400 status que a cozinha não usa · 400 `pedido_id` inválido · 404 pedido inexistente · 405 com POST · 409 pular etapa · 403 cliente |
| `PUT /api/garcom/entregar.php` | 200 pronto→entregue · 409 antes de ficar pronto · 409 entregar duas vezes · 403 cozinha |
| `POST /api/cliente/pagamento.php` | 200 tudo entregue → mesa `aguardando_pagamento` · 409 ainda há pedidos na cozinha · 409 conta já pedida |
| `POST /api/admin/caixa.php` | 200 fecha a conta (pedidos `finalizado`, mesa `livre`) · 400 `mesa_id` inválido · 404 mesa inexistente · 409 mesa não pediu a conta · 403 garçom |
| `GET /api/admin/pedidos.php?status=` | 200 status válido · 400 status inválido |
| `/api/admin/produtos.php` | 201 POST · 400 POST inválido · 200 PUT (gravado no banco) · 404 PUT inexistente · 200 DELETE sem pedidos · 409 DELETE produto usado em pedidos · 403 cliente |
| `/api/admin/usuarios.php` | 201 POST (senha com hash) · 400 perfil inválido · 409 e-mail repetido · 200 PUT perfil · 409 admin rebaixar a si mesmo · 409 admin excluir a si mesmo · 409 excluir cliente com pedidos · 404 inexistente · 200 DELETE · GET sem hash na resposta |
| Qualquer rota com método errado | 405 (com token válido) · 403 sem token (o CSRF é checado antes do método) |

### Regras de negócio conferidas no banco

- O navegador mandou `preco_unitario: 0.01` e `total: 0.01`; o banco gravou **R$ 55,80** (2 × 24,90 + 6,00), com o preço da tabela `produtos`.
- Pedido e itens são gravados juntos (transação); a mesa passa para `ocupada`.
- Depois de pedir a conta, a mesa não aceita novo pedido (409) até o caixa fechar.
- Ao fechar a conta: todos os pedidos da mesa ficam `finalizado` e a mesa volta a `livre`.

---

## 4. Matriz de cenários do Fechamento do Trabalho 1 (slide 8)

| Cenário | Como foi provocado | Esperado | Obtido |
|---------|--------------------|----------|--------|
| Sucesso | pedido válido / login válido | 200 / 201 | ✅ 200 / 201 |
| Parâmetro inválido | campos vazios, quantidade 0, JSON quebrado | 400 | ✅ 400 |
| Item inexistente | produto 9999, pedido 999999, mesa 999 | 404 | ✅ 404 |
| Banco fora | cópia com `conexao.php` apontando para banco inexistente | 500 sem detalhes técnicos | ✅ `{"erro":"Erro interno. Tente novamente mais tarde."}` |
| Envio repetido | 2 POST idênticos seguidos em `/api/pedidos.php` | um único registro | ✅ 1 pedido gravado; o segundo envio recebe **409** "Este mesmo pedido acabou de ser enviado" (ajuste A3) |
| API externa fora (502) | — | — | não se aplica: o PedirJá não consome API externa |

### Segurança (Aula 15), conferida junto

| Item | Resultado |
|------|-----------|
| `Content-Security-Policy`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` | ✅ presentes |
| `X-Powered-By` (versão do PHP) | ✅ ausente |
| Cookie de sessão `HttpOnly; SameSite=Lax` | ✅ |
| Token CSRF falso | ✅ 403 |
| XSS: cadastro com nome `<script>alert(1)</script>` | ✅ a página mostra o texto escapado (`&lt;script&gt;`) |
| Logout por GET | ✅ só mostra a confirmação; continua logado |
| Página `teste/` | ✅ responde 404 para quem não está na própria máquina |

---

## 5. Ajustes encontrados na validação e o que foi feito

| # | O que foi encontrado | Situação |
|---|----------------------|----------|
| **A1** | O login não grava nada na tabela `usuarios` (não há "último acesso" nem histórico). O estado de logado fica só na sessão. | **Mantido assim, de propósito.** Está correto pela Aula 12 (slide 8: dados da sessão ficam no servidor). Se um dia quiser o histórico, basta uma coluna `ultimo_login` atualizada em `iniciarSessaoDoUsuario()`. |
| **A2** | O PHP do XAMPP vem com fuso `Europe/Berlin` e o MySQL usa `America/Sao_Paulo` (4 h de diferença). A partir das 20h a visão geral do admin aparecia zerada. | ✅ **Corrigido.** `api/admin/dashboard.php` pega "hoje" do MySQL (`CURDATE()`), o mesmo relógio que grava `criado_em`, e `includes/seguranca.php` fixa o PHP em `America/Sao_Paulo`. Teste #143 confere que as duas datas batem. |
| **A3** | Dois POST iguais seguidos em `/api/pedidos.php` gravavam dois pedidos. | ✅ **Corrigido.** `criarPedido()` recusa com **409** um pedido com os mesmos itens (em qualquer ordem), do mesmo cliente e mesa, feito há menos de 10 s. A verificação roda depois do `SELECT ... FOR UPDATE` da mesa, então dois cliques simultâneos não escapam. Um pedido diferente logo depois continua sendo aceito. Testes #117 e #222–224. |
| **A4** | O `README.md` dizia "Esta é a Etapa 1". | ✅ **Corrigido.** A abertura do README descreve o projeto completo e aponta para `docs/`. |
| **A5** | A pasta `teste/` e o `docs/validar.php` são ferramentas de desenvolvimento. | Mantidos. `teste/` só abre na própria máquina e `validar.php` só roda pelo terminal e recusa o banco real. Apagar `teste/` antes de publicar em hospedagem. |
| **A6** | O `banco/banco.sql` começa com `DROP DATABASE`. | Aviso no `COMO_RODAR.md` (passo 4). Para atualizar um banco que já existe sem apagar nada, use os arquivos de `banco/migracoes/`. |

Os critérios do Exercício 1 da Aula 12 estão **todos atendidos**.

---

## 6. Novidades validadas (08/11/2026)

### Fotos do cardápio e envio de foto pelo admin (testes #198–213)

| Cenário | Resultado |
|---------|-----------|
| Cardápio do cliente traz a foto de cada produto; o arquivo abre pelo servidor | ✅ |
| Cliente tentando enviar foto | ✅ 403 |
| Envio sem token CSRF | ✅ 403 |
| Sem arquivo / produto inexistente | ✅ 400 / 404 |
| Arquivo falso (`.jpg` com código PHP dentro) | ✅ 400 "Envie uma imagem JPG, PNG ou WEBP." (o tipo é conferido pelo conteúdo) |
| Foto maior que 3 MB | ✅ 400 |
| Admin troca a foto: nome gerado pelo servidor (`p1-xxxxxxxx.jpg`) | ✅ 200 |
| Trocar de novo apaga a foto enviada antes; a foto original do cardápio nunca é apagada | ✅ |
| Remover a foto: o produto volta a ficar sem foto e o arquivo some | ✅ |
| Caminho malicioso gravado direto no banco (`../../config/conexao.php`) | ✅ a API devolve `null` (só aceita `assets/img/produtos/...`) |

### QR Codes das mesas (testes #214–221)

| Cenário | Resultado |
|---------|-----------|
| Visitante / cliente / admin abrindo `qrcodes.php` | ✅ login / 403 / 200 |
| Endereço da rede descoberto sozinho, nunca a placa virtual do WSL/Hyper-V (172.16–31) | ✅ |
| Um QR Code por mesa cadastrada no banco | ✅ |
| CSS/JS com versão no endereço (`?v=`), para o navegador não usar arquivo velho | ✅ |
| Leitura dos códigos | ✅ prints da página decodificados com um leitor de QR (jsQR): as 4 mesas lidas, inclusive com o Windows em 125% e no modo ampliado |

---

## 7. Como repetir esta validação

> ⚠️ Nunca rode o `validar.php` no banco real: o roteiro cria pedidos, fecha a conta da mesa 4, **exclui o produto 9 (Café expresso)** e troca fotos de produtos. O script se recusa a rodar se o banco informado for `restaurante_db`.

No terminal (Git Bash ou PowerShell), com o MySQL ligado:

```bash
# 1. Banco de validação separado (cópia do banco/banco.sql com outro nome)
sed 's/restaurante_db\b/restaurante_db_validacao/g' banco/banco.sql > banco_validacao.sql
C:/xampp/mysql/bin/mysql.exe -u root --default-character-set=utf8mb4 < banco_validacao.sql

# 2. Cópia do projeto apontando para esse banco
#    copie a pasta do projeto para, por exemplo, C:\temp\pedirja_validacao
#    e troque, no config/conexao.php DA CÓPIA, "restaurante_db" por "restaurante_db_validacao"

# 3. Servir a cópia
cd C:/temp/pedirja_validacao
C:/xampp/php/php.exe -S 127.0.0.1:8099

# 4. Em outro terminal, rodar o roteiro
C:/xampp/php/php.exe docs/validar.php http://127.0.0.1:8099/ restaurante_db_validacao
```

Opcional, para o cenário "banco fora": faça uma segunda cópia com o banco apontando para um nome que não existe, sirva em `127.0.0.1:8098` e passe a URL como 3º argumento.

O script imprime o resumo e grava a tabela completa em `docs/resultados.md`. Rode o passo 1 de novo antes de cada execução, para começar com o banco limpo.
