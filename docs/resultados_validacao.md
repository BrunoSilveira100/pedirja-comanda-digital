# Resultado da validação automática (08/11/2026)

Gerado por `docs/validar.php` contra uma cópia do projeto com o banco `restaurante_db_validacao` (recriado do `banco/banco.sql`) e uma segunda cópia com o banco fora do ar. Explicação em `docs/VALIDACAO.md`.

Total: 224 cenários · OK: 224 · Falharam: 0

| # | Grupo | Cenário | Rota | Esperado | Obtido | Resultado |
|---|---|---|---|---|---|---|
| 1 | 1. Autenticação e sessão | Token CSRF para visitante | `GET /api/csrf.php` | 200 | 200 | ✅ |
| 2 | 1. Autenticação e sessão | Token tem 64 caracteres hex | `GET /api/csrf.php` | 64 | 64 | ✅ |
| 3 | 1. Autenticação e sessão | Quem sou eu, sem login | `GET /api/me.php` | 401 | 401 | ✅ |
| 4 | 1. Autenticação e sessão | Login sem token CSRF | `POST /api/login.php` | 403 | 403 | ✅ |
| 5 | 1. Autenticação e sessão | Login com campos vazios | `POST /api/login.php` | 400 | 400 | ✅ |
| 6 | 1. Autenticação e sessão | Login com JSON inválido | `POST /api/login.php` | 400 | 400 | ✅ |
| 7 | 1. Autenticação e sessão | Login com método errado (GET) | `GET /api/login.php` | 405 | 405 | ✅ |
| 8 | 1. Autenticação e sessão | Senha errada | `POST /api/login.php` | 401 | 401 | ✅ |
| 9 | 1. Autenticação e sessão | E-mail inexistente | `POST /api/login.php` | 401 | 401 | ✅ |
| 10 | 1. Autenticação e sessão | Mensagem genérica: corpo idêntico nos dois casos | `POST /api/login.php` | iguais | iguais | ✅ |
| 11 | 1. Autenticação e sessão | Login do admin (senha do README) | `POST /api/login.php` | 200 | 200 | ✅ |
| 12 | 1. Autenticação e sessão | Perfil devolvido = admin | `POST /api/login.php` | admin | admin | ✅ |
| 13 | 1. Autenticação e sessão | ID de sessão trocado no login (admin) | `session_regenerate_id` | trocou | trocou | ✅ |
| 14 | 1. Autenticação e sessão | Resposta não traz senha nem hash (admin) | `POST /api/login.php` | ausente | ausente | ✅ |
| 15 | 1. Autenticação e sessão | Sessão reconhecida (admin) | `GET /api/me.php` | 200 | 200 | ✅ |
| 16 | 1. Autenticação e sessão | Login do cozinha (senha do README) | `POST /api/login.php` | 200 | 200 | ✅ |
| 17 | 1. Autenticação e sessão | Perfil devolvido = cozinha | `POST /api/login.php` | cozinha | cozinha | ✅ |
| 18 | 1. Autenticação e sessão | ID de sessão trocado no login (cozinha) | `session_regenerate_id` | trocou | trocou | ✅ |
| 19 | 1. Autenticação e sessão | Resposta não traz senha nem hash (cozinha) | `POST /api/login.php` | ausente | ausente | ✅ |
| 20 | 1. Autenticação e sessão | Sessão reconhecida (cozinha) | `GET /api/me.php` | 200 | 200 | ✅ |
| 21 | 1. Autenticação e sessão | Login do garcom (senha do README) | `POST /api/login.php` | 200 | 200 | ✅ |
| 22 | 1. Autenticação e sessão | Perfil devolvido = garcom | `POST /api/login.php` | garcom | garcom | ✅ |
| 23 | 1. Autenticação e sessão | ID de sessão trocado no login (garcom) | `session_regenerate_id` | trocou | trocou | ✅ |
| 24 | 1. Autenticação e sessão | Resposta não traz senha nem hash (garcom) | `POST /api/login.php` | ausente | ausente | ✅ |
| 25 | 1. Autenticação e sessão | Sessão reconhecida (garcom) | `GET /api/me.php` | 200 | 200 | ✅ |
| 26 | 1. Autenticação e sessão | Login do cliente (senha do README) | `POST /api/login.php` | 200 | 200 | ✅ |
| 27 | 1. Autenticação e sessão | Perfil devolvido = cliente | `POST /api/login.php` | cliente | cliente | ✅ |
| 28 | 1. Autenticação e sessão | ID de sessão trocado no login (cliente) | `session_regenerate_id` | trocou | trocou | ✅ |
| 29 | 1. Autenticação e sessão | Resposta não traz senha nem hash (cliente) | `POST /api/login.php` | ausente | ausente | ✅ |
| 30 | 1. Autenticação e sessão | Sessão reconhecida (cliente) | `GET /api/me.php` | 200 | 200 | ✅ |
| 31 | 2. Cadastro e o que fica salvo no banco | Cadastro com dados inválidos | `POST /api/cadastro.php` | 400 | 400 | ✅ |
| 32 | 2. Cadastro e o que fica salvo no banco | Cadastro válido (tentando forçar perfil admin) | `POST /api/cadastro.php` | 201 | 201 | ✅ |
| 33 | 2. Cadastro e o que fica salvo no banco | Usuário gravado na tabela usuarios | `banco: usuarios` | 1 linha | 1 linha | ✅ |
| 34 | 2. Cadastro e o que fica salvo no banco | Senha gravada como hash bcrypt | `banco: usuarios.senha_hash` | $2y$... | $2y$... | ✅ |
| 35 | 2. Cadastro e o que fica salvo no banco | Senha digitada não aparece no banco | `banco: usuarios.senha_hash` | não aparece | não aparece | ✅ |
| 36 | 2. Cadastro e o que fica salvo no banco | Perfil forçado pelo navegador é ignorado | `banco: usuarios.perfil` | cliente | cliente | ✅ |
| 37 | 2. Cadastro e o que fica salvo no banco | Data de criação preenchida | `banco: usuarios.criado_em` | preenchida | preenchida | ✅ |
| 38 | 2. Cadastro e o que fica salvo no banco | Cadastro já deixa logado | `GET /api/me.php` | 200 | 200 | ✅ |
| 39 | 2. Cadastro e o que fica salvo no banco | Cadastro com e-mail repetido | `POST /api/cadastro.php` | 409 | 409 | ✅ |
| 40 | 2. Cadastro e o que fica salvo no banco | Login errado é registrado em tentativas_login | `banco: tentativas_login` | 1 | 1 | ✅ |
| 41 | 2. Cadastro e o que fica salvo no banco | Login do usuário recém-cadastrado | `POST /api/login.php` | 200 | 200 | ✅ |
| 42 | 2. Cadastro e o que fica salvo no banco | Login certo apaga as tentativas erradas | `banco: tentativas_login` | 0 | 0 | ✅ |
| 43 | 2. Cadastro e o que fica salvo no banco | Login NÃO altera a linha do usuário (não há registro de último acesso) | `banco: usuarios` | inalterada | inalterada | ✅ |
| 44 | 2. Cadastro e o que fica salvo no banco | Sessão do usuário logado fica em arquivo no servidor | `C:\xampp\tmp\sess_<id>` | existe | existe | ✅ |
| 45 | 2. Cadastro e o que fica salvo no banco | Arquivo de sessão guarda id e perfil, nunca a senha/hash | `C:\xampp\tmp\sess_<id>` | sem senha | sem senha | ✅ |
| 46 | 3. Matriz de acesso (401 / 403 / 200) | Visitante | `GET /api/cardapio.php` | 401 | 401 | ✅ |
| 47 | 3. Matriz de acesso (401 / 403 / 200) | Cliente | `GET /api/cardapio.php` | 200 | 200 | ✅ |
| 48 | 3. Matriz de acesso (401 / 403 / 200) | Cozinha | `GET /api/cardapio.php` | 200 | 200 | ✅ |
| 49 | 3. Matriz de acesso (401 / 403 / 200) | Garcom | `GET /api/cardapio.php` | 200 | 200 | ✅ |
| 50 | 3. Matriz de acesso (401 / 403 / 200) | Admin | `GET /api/cardapio.php` | 200 | 200 | ✅ |
| 51 | 3. Matriz de acesso (401 / 403 / 200) | Visitante | `GET /api/pedidos.php` | 401 | 401 | ✅ |
| 52 | 3. Matriz de acesso (401 / 403 / 200) | Cliente | `GET /api/pedidos.php` | 200 | 200 | ✅ |
| 53 | 3. Matriz de acesso (401 / 403 / 200) | Cozinha | `GET /api/pedidos.php` | 403 | 403 | ✅ |
| 54 | 3. Matriz de acesso (401 / 403 / 200) | Garcom | `GET /api/pedidos.php` | 403 | 403 | ✅ |
| 55 | 3. Matriz de acesso (401 / 403 / 200) | Admin | `GET /api/pedidos.php` | 200 | 200 | ✅ |
| 56 | 3. Matriz de acesso (401 / 403 / 200) | Visitante | `GET /api/cozinha/pedidos.php` | 401 | 401 | ✅ |
| 57 | 3. Matriz de acesso (401 / 403 / 200) | Cliente | `GET /api/cozinha/pedidos.php` | 403 | 403 | ✅ |
| 58 | 3. Matriz de acesso (401 / 403 / 200) | Cozinha | `GET /api/cozinha/pedidos.php` | 200 | 200 | ✅ |
| 59 | 3. Matriz de acesso (401 / 403 / 200) | Garcom | `GET /api/cozinha/pedidos.php` | 403 | 403 | ✅ |
| 60 | 3. Matriz de acesso (401 / 403 / 200) | Admin | `GET /api/cozinha/pedidos.php` | 200 | 200 | ✅ |
| 61 | 3. Matriz de acesso (401 / 403 / 200) | Visitante | `GET /api/garcom/pedidos.php` | 401 | 401 | ✅ |
| 62 | 3. Matriz de acesso (401 / 403 / 200) | Cliente | `GET /api/garcom/pedidos.php` | 403 | 403 | ✅ |
| 63 | 3. Matriz de acesso (401 / 403 / 200) | Cozinha | `GET /api/garcom/pedidos.php` | 403 | 403 | ✅ |
| 64 | 3. Matriz de acesso (401 / 403 / 200) | Garcom | `GET /api/garcom/pedidos.php` | 200 | 200 | ✅ |
| 65 | 3. Matriz de acesso (401 / 403 / 200) | Admin | `GET /api/garcom/pedidos.php` | 200 | 200 | ✅ |
| 66 | 3. Matriz de acesso (401 / 403 / 200) | Visitante | `GET /api/admin/dashboard.php` | 401 | 401 | ✅ |
| 67 | 3. Matriz de acesso (401 / 403 / 200) | Cliente | `GET /api/admin/dashboard.php` | 403 | 403 | ✅ |
| 68 | 3. Matriz de acesso (401 / 403 / 200) | Cozinha | `GET /api/admin/dashboard.php` | 403 | 403 | ✅ |
| 69 | 3. Matriz de acesso (401 / 403 / 200) | Garcom | `GET /api/admin/dashboard.php` | 403 | 403 | ✅ |
| 70 | 3. Matriz de acesso (401 / 403 / 200) | Admin | `GET /api/admin/dashboard.php` | 200 | 200 | ✅ |
| 71 | 3. Matriz de acesso (401 / 403 / 200) | Visitante | `GET /api/admin/pedidos.php` | 401 | 401 | ✅ |
| 72 | 3. Matriz de acesso (401 / 403 / 200) | Cliente | `GET /api/admin/pedidos.php` | 403 | 403 | ✅ |
| 73 | 3. Matriz de acesso (401 / 403 / 200) | Cozinha | `GET /api/admin/pedidos.php` | 403 | 403 | ✅ |
| 74 | 3. Matriz de acesso (401 / 403 / 200) | Garcom | `GET /api/admin/pedidos.php` | 403 | 403 | ✅ |
| 75 | 3. Matriz de acesso (401 / 403 / 200) | Admin | `GET /api/admin/pedidos.php` | 200 | 200 | ✅ |
| 76 | 3. Matriz de acesso (401 / 403 / 200) | Visitante | `GET /api/admin/caixa.php` | 401 | 401 | ✅ |
| 77 | 3. Matriz de acesso (401 / 403 / 200) | Cliente | `GET /api/admin/caixa.php` | 403 | 403 | ✅ |
| 78 | 3. Matriz de acesso (401 / 403 / 200) | Cozinha | `GET /api/admin/caixa.php` | 403 | 403 | ✅ |
| 79 | 3. Matriz de acesso (401 / 403 / 200) | Garcom | `GET /api/admin/caixa.php` | 403 | 403 | ✅ |
| 80 | 3. Matriz de acesso (401 / 403 / 200) | Admin | `GET /api/admin/caixa.php` | 200 | 200 | ✅ |
| 81 | 3. Matriz de acesso (401 / 403 / 200) | Visitante | `GET /api/admin/usuarios.php` | 401 | 401 | ✅ |
| 82 | 3. Matriz de acesso (401 / 403 / 200) | Cliente | `GET /api/admin/usuarios.php` | 403 | 403 | ✅ |
| 83 | 3. Matriz de acesso (401 / 403 / 200) | Cozinha | `GET /api/admin/usuarios.php` | 403 | 403 | ✅ |
| 84 | 3. Matriz de acesso (401 / 403 / 200) | Garcom | `GET /api/admin/usuarios.php` | 403 | 403 | ✅ |
| 85 | 3. Matriz de acesso (401 / 403 / 200) | Admin | `GET /api/admin/usuarios.php` | 200 | 200 | ✅ |
| 86 | 3. Matriz de acesso (401 / 403 / 200) | Visitante | `GET /api/admin/produtos.php` | 401 | 401 | ✅ |
| 87 | 3. Matriz de acesso (401 / 403 / 200) | Cliente | `GET /api/admin/produtos.php` | 403 | 403 | ✅ |
| 88 | 3. Matriz de acesso (401 / 403 / 200) | Cozinha | `GET /api/admin/produtos.php` | 403 | 403 | ✅ |
| 89 | 3. Matriz de acesso (401 / 403 / 200) | Garcom | `GET /api/admin/produtos.php` | 403 | 403 | ✅ |
| 90 | 3. Matriz de acesso (401 / 403 / 200) | Admin | `GET /api/admin/produtos.php` | 200 | 200 | ✅ |
| 91 | 3. Matriz de acesso (401 / 403 / 200) | Cliente sem mesa escaneada | `GET /api/cliente/pagamento.php` | 400 | 400 | ✅ |
| 92 | 3. Matriz de acesso (401 / 403 / 200) | Cozinha | `GET /api/cliente/pagamento.php` | 403 | 403 | ✅ |
| 93 | 3. Matriz de acesso (401 / 403 / 200) | Garcom | `GET /api/cliente/pagamento.php` | 403 | 403 | ✅ |
| 94 | 3. Matriz de acesso (401 / 403 / 200) | Visitante | `GET /api/cliente/pagamento.php` | 401 | 401 | ✅ |
| 95 | 3. Matriz de acesso (401 / 403 / 200) | Cliente tentando mudar status da cozinha | `PUT /api/cozinha/status.php` | 403 | 403 | ✅ |
| 96 | 3. Matriz de acesso (401 / 403 / 200) | Cozinha tentando confirmar entrega | `PUT /api/garcom/entregar.php` | 403 | 403 | ✅ |
| 97 | 3. Matriz de acesso (401 / 403 / 200) | Garçom tentando fechar conta | `POST /api/admin/caixa.php` | 403 | 403 | ✅ |
| 98 | 3. Matriz de acesso (401 / 403 / 200) | Cliente tentando excluir produto pela API | `DELETE /api/admin/produtos.php` | 403 | 403 | ✅ |
| 99 | 3. Matriz de acesso (401 / 403 / 200) | Método não permitido (com token válido) | `DELETE /api/cardapio.php` | 405 | 405 | ✅ |
| 100 | 3. Matriz de acesso (401 / 403 / 200) | Método de escrita sem token: CSRF barra antes | `DELETE /api/cardapio.php` | 403 | 403 | ✅ |
| 101 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | QR Code inválido | `GET /mesa.php?t=token-falso` | 404 | 404 | ✅ |
| 102 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | QR Code da mesa 4 | `GET /mesa.php?t=mesa-04-d0a6b1` | 302 | 302 | ✅ |
| 103 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Mesa guardada na sessão | `GET /api/me.php` | 4 | 4 | ✅ |
| 104 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Pedido sem ter escaneado a mesa | `POST /api/pedidos.php` | 400 | 400 | ✅ |
| 105 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Pedido sem itens | `POST /api/pedidos.php` | 400 | 400 | ✅ |
| 106 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Quantidade zero | `POST /api/pedidos.php` | 400 | 400 | ✅ |
| 107 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Quantidade acima de 50 | `POST /api/pedidos.php` | 400 | 400 | ✅ |
| 108 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Produto inexistente | `POST /api/pedidos.php` | 404 | 404 | ✅ |
| 109 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Produto indisponível (Pudim) | `POST /api/pedidos.php` | 409 | 409 | ✅ |
| 110 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Observação com mais de 255 caracteres | `POST /api/pedidos.php` | 400 | 400 | ✅ |
| 111 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Pedido válido (navegador manda preço falso 0,01) | `POST /api/pedidos.php` | 201 | 201 | ✅ |
| 112 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Total calculado no servidor: 2×24,90 + 6,00 | `banco: pedidos.total` | 55.80 | 55.80 | ✅ |
| 113 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Pedido nasce como recebido na mesa 4 | `banco: pedidos` | recebido/4 | recebido/4 | ✅ |
| 114 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Itens gravados na mesma transação | `banco: pedido_itens` | 2 | 2 | ✅ |
| 115 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Mesa passa a ocupada | `banco: mesas.status` | ocupada | ocupada | ✅ |
| 116 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Resposta traz cabeçalho Location do recurso criado | `POST /api/pedidos.php` | presente | presente | ✅ |
| 117 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Envio repetido (2 POST iguais seguidos) no servidor | `POST /api/pedidos.php` | 1 pedido(s) | 1 pedido(s) | ✅ |
| 118 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Fila da cozinha mostra os pedidos novos | `GET /api/cozinha/pedidos.php` | contém | contém | ✅ |
| 119 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Pular etapa (recebido → pronto) | `PUT /api/cozinha/status.php` | 409 | 409 | ✅ |
| 120 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Status que a cozinha não usa | `PUT /api/cozinha/status.php` | 400 | 400 | ✅ |
| 121 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Pedido inexistente | `PUT /api/cozinha/status.php` | 404 | 404 | ✅ |
| 122 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | pedido_id inválido | `PUT /api/cozinha/status.php` | 400 | 400 | ✅ |
| 123 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Método errado (POST em vez de PUT) | `POST /api/cozinha/status.php` | 405 | 405 | ✅ |
| 124 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | recebido → em_preparo | `PUT /api/cozinha/status.php` | 200 | 200 | ✅ |
| 125 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Garçom entregar antes de ficar pronto | `PUT /api/garcom/entregar.php` | 409 | 409 | ✅ |
| 126 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | em_preparo → pronto | `PUT /api/cozinha/status.php` | 200 | 200 | ✅ |
| 127 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Garçom vê o pedido pronto | `GET /api/garcom/pedidos.php` | contém | contém | ✅ |
| 128 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | pronto → entregue | `PUT /api/garcom/entregar.php` | 200 | 200 | ✅ |
| 129 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Entregar duas vezes | `PUT /api/garcom/entregar.php` | 409 | 409 | ✅ |
| 130 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Pedir a conta com pedidos ainda na cozinha | `POST /api/cliente/pagamento.php` | 409 | 409 | ✅ |
| 131 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Conta em aberto da mesa | `GET /api/cliente/pagamento.php` | 200 | 200 | ✅ |
| 132 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Pedir a conta (tudo entregue) | `POST /api/cliente/pagamento.php` | 200 | 200 | ✅ |
| 133 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Mesa vai para aguardando_pagamento | `banco: mesas.status` | aguardando_pagamento | aguardando_pagamento | ✅ |
| 134 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Pedir a conta de novo | `POST /api/cliente/pagamento.php` | 409 | 409 | ✅ |
| 135 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Novo pedido depois de pedir a conta | `POST /api/pedidos.php` | 409 | 409 | ✅ |
| 136 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Caixa lista a mesa 4 | `GET /api/admin/caixa.php` | contém | contém | ✅ |
| 137 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | mesa_id inválido | `POST /api/admin/caixa.php` | 400 | 400 | ✅ |
| 138 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Mesa inexistente | `POST /api/admin/caixa.php` | 404 | 404 | ✅ |
| 139 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Fechar mesa que não pediu a conta | `POST /api/admin/caixa.php` | 409 | 409 | ✅ |
| 140 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Fechar a conta da mesa 4 | `POST /api/admin/caixa.php` | 200 | 200 | ✅ |
| 141 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Mesa volta a livre | `banco: mesas.status` | livre | livre | ✅ |
| 142 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Pedidos da mesa ficam finalizado | `banco: pedidos.status` | 0 em aberto | 0 em aberto | ✅ |
| 143 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Dashboard: "hoje" igual à data do MySQL (fuso) | `GET /api/admin/dashboard.php` | 200 / 2026-11-08 | 200 / 2026-11-08 | ✅ |
| 144 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Filtro de status inválido | `GET /api/admin/pedidos.php?status=xyz` | 400 | 400 | ✅ |
| 145 | 4. Fluxo do pedido (QR → cozinha → garçom → conta → caixa) | Filtro de status válido | `GET /api/admin/pedidos.php?status=finalizado` | 200 | 200 | ✅ |
| 146 | 5. Admin: cardápio e usuários | Produto inválido | `POST /api/admin/produtos.php` | 400 | 400 | ✅ |
| 147 | 5. Admin: cardápio e usuários | Criar produto | `POST /api/admin/produtos.php` | 201 | 201 | ✅ |
| 148 | 5. Admin: cardápio e usuários | Editar produto | `PUT /api/admin/produtos.php` | 200 | 200 | ✅ |
| 149 | 5. Admin: cardápio e usuários | Edição gravada no banco | `banco: produtos` | 8.00/0 | 8.00/0 | ✅ |
| 150 | 5. Admin: cardápio e usuários | Editar produto inexistente | `PUT /api/admin/produtos.php` | 404 | 404 | ✅ |
| 151 | 5. Admin: cardápio e usuários | Excluir produto que já está em pedidos | `DELETE /api/admin/produtos.php?id=1` | 409 | 409 | ✅ |
| 152 | 5. Admin: cardápio e usuários | Excluir produto sem pedidos | `DELETE /api/admin/produtos.php?id=11` | 200 | 200 | ✅ |
| 153 | 5. Admin: cardápio e usuários | Criar usuário com perfil inválido | `POST /api/admin/usuarios.php` | 400 | 400 | ✅ |
| 154 | 5. Admin: cardápio e usuários | Criar usuário com e-mail repetido | `POST /api/admin/usuarios.php` | 409 | 409 | ✅ |
| 155 | 5. Admin: cardápio e usuários | Admin cria conta de garçom | `POST /api/admin/usuarios.php` | 201 | 201 | ✅ |
| 156 | 5. Admin: cardápio e usuários | Senha do usuário criado pelo admin também é hash | `banco: usuarios.senha_hash` | $2y$... | $2y$... | ✅ |
| 157 | 5. Admin: cardápio e usuários | Mudar perfil | `PUT /api/admin/usuarios.php` | 200 | 200 | ✅ |
| 158 | 5. Admin: cardápio e usuários | Admin rebaixar a si mesmo | `PUT /api/admin/usuarios.php` | 409 | 409 | ✅ |
| 159 | 5. Admin: cardápio e usuários | Admin excluir a si mesmo | `DELETE /api/admin/usuarios.php?id=1` | 409 | 409 | ✅ |
| 160 | 5. Admin: cardápio e usuários | Excluir cliente que tem pedidos | `DELETE /api/admin/usuarios.php?id=4` | 409 | 409 | ✅ |
| 161 | 5. Admin: cardápio e usuários | Excluir usuário inexistente | `DELETE /api/admin/usuarios.php?id=999999` | 404 | 404 | ✅ |
| 162 | 5. Admin: cardápio e usuários | Excluir usuário sem pedidos | `DELETE /api/admin/usuarios.php?id=7` | 200 | 200 | ✅ |
| 163 | 5. Admin: cardápio e usuários | Listagem de usuários não expõe hash | `GET /api/admin/usuarios.php` | sem hash | sem hash | ✅ |
| 164 | 6. Páginas protegidas (Exercício 1) | Sem login vai para o login | `GET /painel.php` | 302 → login.php | 302 → login.php | ✅ |
| 165 | 6. Páginas protegidas (Exercício 1) | Sem login vai para o login | `GET /produtos.php` | 302 → login.php | 302 → login.php | ✅ |
| 166 | 6. Páginas protegidas (Exercício 1) | Sem login vai para o login | `GET /cliente.php` | 302 → login.php | 302 → login.php | ✅ |
| 167 | 6. Páginas protegidas (Exercício 1) | Sem login vai para o login | `GET /cozinha.php` | 302 → login.php | 302 → login.php | ✅ |
| 168 | 6. Páginas protegidas (Exercício 1) | Sem login vai para o login | `GET /garcom.php` | 302 → login.php | 302 → login.php | ✅ |
| 169 | 6. Páginas protegidas (Exercício 1) | Sem login vai para o login | `GET /admin.php` | 302 → login.php | 302 → login.php | ✅ |
| 170 | 6. Páginas protegidas (Exercício 1) | Cozinha abrindo a tela do cliente | `GET /cliente.php` | 403 | 403 | ✅ |
| 171 | 6. Páginas protegidas (Exercício 1) | Cliente abrindo a tela do admin | `GET /admin.php` | 403 | 403 | ✅ |
| 172 | 6. Páginas protegidas (Exercício 1) | Admin abre qualquer tela | `GET /cozinha.php` | 200 | 200 | ✅ |
| 173 | 6. Páginas protegidas (Exercício 1) | Painel leva o cliente para a tela dele | `GET /painel.php` | cliente.php | cliente.php | ✅ |
| 174 | 6. Páginas protegidas (Exercício 1) | Listagem do tema para qualquer logado | `GET /produtos.php` | 200 | 200 | ✅ |
| 175 | 6. Páginas protegidas (Exercício 1) | Cliente excluindo produto (com token válido) | `POST /produto_excluir.php` | 403 | 403 | ✅ |
| 176 | 6. Páginas protegidas (Exercício 1) | O 403 é de perfil, não de token | `POST /produto_excluir.php` | Acesso negado | Acesso negado | ✅ |
| 177 | 6. Páginas protegidas (Exercício 1) | Excluir por GET | `GET /produto_excluir.php` | 405 | 405 | ✅ |
| 178 | 6. Páginas protegidas (Exercício 1) | Admin exclui produto em uso | `POST /produto_excluir.php` | 302 → em_uso | 302 → em_uso | ✅ |
| 179 | 6. Páginas protegidas (Exercício 1) | Admin exclui produto livre (Café expresso) | `POST /produto_excluir.php` | 302 → excluido | 302 → excluido | ✅ |
| 180 | 7. Segurança (Aula 15) | Cabeçalho Content-Security-Policy | `GET /api/me.php` | presente | presente | ✅ |
| 181 | 7. Segurança (Aula 15) | Cabeçalho X-Content-Type-Options: nosniff | `GET /api/me.php` | presente | presente | ✅ |
| 182 | 7. Segurança (Aula 15) | Cabeçalho X-Frame-Options: DENY | `GET /api/me.php` | presente | presente | ✅ |
| 183 | 7. Segurança (Aula 15) | Sem X-Powered-By | `GET /api/me.php` | ausente | ausente | ✅ |
| 184 | 7. Segurança (Aula 15) | Cookie de sessão HttpOnly e SameSite | `GET /login.php` | HttpOnly+SameSite | HttpOnly+SameSite | ✅ |
| 185 | 7. Segurança (Aula 15) | Token CSRF falso | `PUT /api/cozinha/status.php` | 403 | 403 | ✅ |
| 186 | 7. Segurança (Aula 15) | XSS no nome: página escapa o texto | `GET /cliente.php` | escapado | escapado | ✅ |
| 187 | 7. Segurança (Aula 15) | Força bruta: 5 erros e a 6ª tentativa bloqueia | `POST /api/login.php` | 401×5, 429 | 401×5, 429 | ✅ |
| 188 | 7. Segurança (Aula 15) | 429 envia Retry-After | `POST /api/login.php` | presente | presente | ✅ |
| 189 | 7. Segurança (Aula 15) | Banco fora do ar | `POST /api/login.php` | 500 | 500 | ✅ |
| 190 | 7. Segurança (Aula 15) | Erro 500 sem detalhe técnico | `POST /api/login.php` | genérico | genérico | ✅ |
| 191 | 8. Logout | Logout por GET só mostra confirmação | `GET /logout.php` | 200 | 200 | ✅ |
| 192 | 8. Logout | Continua logado depois do GET | `GET /api/me.php` | 200 | 200 | ✅ |
| 193 | 8. Logout | Logout sem token | `POST /api/logout.php` | 403 | 403 | ✅ |
| 194 | 8. Logout | Logout com token | `POST /api/logout.php` | 200 | 200 | ✅ |
| 195 | 8. Logout | Arquivo da sessão apagado no servidor | `C:\xampp\tmp\sess_<id>` | apagado | apagado | ✅ |
| 196 | 8. Logout | Reusar o cookie antigo depois do logout | `GET /api/me.php` | 401 | 401 | ✅ |
| 197 | 8. Logout | Voltar para a página protegida depois do logout | `GET /garcom.php` | 302 → login.php | 302 → login.php | ✅ |
| 198 | 9. Fotos do cardápio (admin) | Cardápio do cliente traz a foto dos produtos | `GET /api/cardapio.php` | com foto | com foto | ✅ |
| 199 | 9. Fotos do cardápio (admin) | Arquivo da foto abre pelo servidor | `GET /assets/img/produtos/x-burguer.jpg` | 200 | 200 | ✅ |
| 200 | 9. Fotos do cardápio (admin) | Cliente tentando enviar foto | `POST /api/admin/produto_imagem.php` | 403 | 403 | ✅ |
| 201 | 9. Fotos do cardápio (admin) | Envio de foto sem token CSRF | `POST /api/admin/produto_imagem.php` | 403 | 403 | ✅ |
| 202 | 9. Fotos do cardápio (admin) | Envio sem arquivo | `POST /api/admin/produto_imagem.php` | 400 | 400 | ✅ |
| 203 | 9. Fotos do cardápio (admin) | Foto para produto inexistente | `POST /api/admin/produto_imagem.php` | 404 | 404 | ✅ |
| 204 | 9. Fotos do cardápio (admin) | Arquivo falso (.jpg com código PHP) | `POST /api/admin/produto_imagem.php` | 400 | 400 | ✅ |
| 205 | 9. Fotos do cardápio (admin) | Foto maior que 3 MB | `POST /api/admin/produto_imagem.php` | 400 | 400 | ✅ |
| 206 | 9. Fotos do cardápio (admin) | Admin troca a foto do X-Burguer | `POST /api/admin/produto_imagem.php` | 200 | 200 | ✅ |
| 207 | 9. Fotos do cardápio (admin) | Nome da foto gerado pelo servidor | `banco: produtos.imagem` | p1-xxxxxxxx.jpg | p1-xxxxxxxx.jpg | ✅ |
| 208 | 9. Fotos do cardápio (admin) | Foto nova abre pelo servidor | `GET /assets/img/produtos/p1-288493d3.jpg` | 200 | 200 | ✅ |
| 209 | 9. Fotos do cardápio (admin) | Foto original do cardápio não é apagada | `GET /assets/img/produtos/x-burguer.jpg` | 200 | 200 | ✅ |
| 210 | 9. Fotos do cardápio (admin) | Trocar de novo apaga a foto enviada antes | `GET /assets/img/produtos/p1-288493d3.jpg` | 404 | 404 | ✅ |
| 211 | 9. Fotos do cardápio (admin) | Remover a foto | `POST /api/admin/produto_imagem.php` | 200 / NULL | 200 / NULL | ✅ |
| 212 | 9. Fotos do cardápio (admin) | Arquivo removido do servidor | `GET /assets/img/produtos/p1-1038b71a.jpg` | 404 | 404 | ✅ |
| 213 | 9. Fotos do cardápio (admin) | Caminho de imagem malicioso no banco é ignorado | `GET /api/cardapio.php` | NULL | NULL | ✅ |
| 214 | 10. QR Codes das mesas | Sem login vai para o login | `GET /qrcodes.php` | 302 → login.php | 302 → login.php | ✅ |
| 215 | 10. QR Codes das mesas | Cliente abrindo os QR Codes | `GET /qrcodes.php` | 403 | 403 | ✅ |
| 216 | 10. QR Codes das mesas | Admin abre os QR Codes | `GET /qrcodes.php` | 200 | 200 | ✅ |
| 217 | 10. QR Codes das mesas | Endereço da rede descoberto sozinho | `qrcodes.php` | preenchido | preenchido | ✅ |
| 218 | 10. QR Codes das mesas | Endereço não é a rede virtual do WSL/Hyper-V | `qrcodes.php` | não é 172.16–31 | não é 172.16–31 | ✅ |
| 219 | 10. QR Codes das mesas | Um QR Code por mesa do banco | `qrcodes.php` | 4 | 4 | ✅ |
| 220 | 10. QR Codes das mesas | Scripts com versão no endereço (sem cache velho) | `qrcodes.php` | ?v= | ?v= | ✅ |
| 221 | 10. QR Codes das mesas | Biblioteca de QR Code servida localmente | `GET /assets/vendor/qrcode.min.js` | 200 | 200 | ✅ |
| 222 | 11. Pedido repetido (proteção no servidor) | Primeiro envio | `POST /api/pedidos.php` | 201 | 201 | ✅ |
| 223 | 11. Pedido repetido (proteção no servidor) | Mesmo pedido de novo (itens em outra ordem) | `POST /api/pedidos.php` | 409 | 409 | ✅ |
| 224 | 11. Pedido repetido (proteção no servidor) | Pedido diferente logo em seguida é aceito | `POST /api/pedidos.php` | 201 | 201 | ✅ |


Observações:
- **#10 Mensagem genérica: corpo idêntico nos dois casos:** {"erro":"E-mail ou senha inválidos."}
- **#43 Login NÃO altera a linha do usuário (não há registro de último acesso):** Comportamento atual, ver ajuste sugerido A1
- **#44 Sessão do usuário logado fica em arquivo no servidor:** conteúdo: ultimo_acesso|i:1794185731;csrf|s:64:"…";usuario_id|i:5;nome|s:19:"Cliente Validação";perfil|s:7:"cliente";
- **#102 QR Code da mesa 4:** Location: painel.php
- **#105 Pedido sem itens:** Informe pelo menos um item.
- **#106 Quantidade zero:** Item 1: quantidade deve ser de 1 a 50.
- **#107 Quantidade acima de 50:** Item 1: quantidade deve ser de 1 a 50.
- **#108 Produto inexistente:** Produto 9999 não existe.
- **#109 Produto indisponível (Pudim):** "Pudim" está indisponível no momento.
- **#110 Observação com mais de 255 caracteres:** Item 1: observação com mais de 255 caracteres.
- **#117 Envio repetido (2 POST iguais seguidos) no servidor:** Segundo envio: 409 Este mesmo pedido acabou de ser enviado. Confira em "Meus pedidos".
- **#130 Pedir a conta com pedidos ainda na cozinha:** Ainda há 1 pedido(s) sendo preparados ou entregues.
- **#131 Conta em aberto da mesa:** total R$ 67.8
- **#140 Fechar a conta da mesa 4:** total R$ 67.8
- **#143 Dashboard: "hoje" igual à data do MySQL (fuso):** faturamento=67.8
- **#190 Erro 500 sem detalhe técnico:** {"erro":"Erro interno. Tente novamente mais tarde."}
- **#198 Cardápio do cliente traz a foto dos produtos:** 8 produto(s) com foto
- **#204 Arquivo falso (.jpg com código PHP):** Envie uma imagem JPG, PNG ou WEBP.
- **#206 Admin troca a foto do X-Burguer:** assets/img/produtos/p1-288493d3.jpg
- **#217 Endereço da rede descoberto sozinho:** http://192.168.0.86:8099
- **#223 Mesmo pedido de novo (itens em outra ordem):** Este mesmo pedido acabou de ser enviado. Confira em "Meus pedidos".
