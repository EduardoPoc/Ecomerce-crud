# API do projeto Ecomerce

## Execução

Com Docker Compose, a API fica disponível em `http://localhost:8080`:

```sh
cp docker/php/app.env.example docker/php/app.env
docker compose up --build
```

Todas as rotas abaixo usam o prefixo `/api`.

## Autenticação

As rotas públicas não exigem autenticação. As rotas protegidas usam um token JWT no cabeçalho:

```http
Authorization: Bearer <token>
```

O login retorna um token de usuário. As rotas de usuários exigem um token de um usuário com papel `ADMIN`.

Usuário administrador incluído no seed local:

```text
e-mail: admin@livraria.com
senha: senha123
```

## Rotas implementadas

### Saúde da API

| Método | Rota | Auth | Descrição |
| --- | --- | --- | --- |
| `GET` | `/api/health` | Pública | Verifica se a API e o banco estão disponíveis. Retorna `200` ou `503`. |

### Autenticação

| Método | Rota | Auth | Descrição |
| --- | --- | --- | --- |
| `POST` | `/api/auth/login` | Pública | Autentica por e-mail e senha e retorna um token JWT. |
| `POST` | `/api/auth/cadastro` | Pública | Cria um usuário com papel `CLIENTE` e retorna um token. |
| `GET` | `/api/auth/me` | Bearer | Retorna os dados do usuário autenticado. |

Exemplo de login:

```sh
curl -X POST http://localhost:8080/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@livraria.com","senha":"senha123"}'
```

Exemplo de cadastro:

```sh
curl -X POST http://localhost:8080/api/auth/cadastro \
  -H 'Content-Type: application/json' \
  -d '{"nome":"Ana Silva","email":"ana@email.com","senha":"senha123"}'
```

### Produtos

| Método | Rota | Auth | Descrição |
| --- | --- | --- | --- |
| `GET` | `/api/produtos` | Pública | Lista produtos ativos com paginação, busca, categoria e ordenação. |
| `GET` | `/api/produtos/{id}` | Pública | Retorna um produto pelo ID. |
| `POST` | `/api/produtos` | Admin | Cadastra um produto. |
| `POST` | `/api/produtos/{id}/imagem` | Admin | Envia a capa local do produto via `multipart/form-data`. |
| `PUT`/`PATCH` | `/api/produtos/{id}` | Admin | Atualiza um produto. |
| `DELETE` | `/api/produtos/{id}` | Admin | Desativa um produto sem apagar o histórico. |
| `DELETE` | `/api/produtos/{id}/hard` | Admin | Apaga definitivamente um produto inativo sem pedidos vinculados. |

Consulta do catálogo:

```http
GET /api/produtos?pagina=1&limite=12&busca=duna&categoria_id=1&ordenar=relevancia
```

`ordenar` aceita `relevancia`, `nome`, `preco_asc`, `preco_desc` e `recentes`. Na relevância, correspondências no título aparecem antes das correspondências apenas na descrição.

Resposta:

```json
{
  "itens": [],
  "pagina": 1,
  "limite": 12,
  "total": 0,
  "paginas": 0
}
```

O painel administrativo usa `GET /api/produtos/admin`, protegido por `ADMIN`, para incluir produtos inativos.

O upload aceita `JPG`, `PNG` e `WebP` até 5 MB no campo `imagem`. A API salva a imagem em `api/public/uploads/livros` com nome aleatório e retorna o caminho local em `imagem_url`.

### Categorias

| Método | Rota | Auth | Descrição |
| --- | --- | --- | --- |
| `GET` | `/api/categorias` | Pública | Lista as categorias. |
| `GET` | `/api/categorias/{id}` | Pública | Retorna uma categoria pelo ID. |

### Usuários — somente administradores

| Método | Rota | Auth | Descrição |
| --- | --- | --- | --- |
| `GET` | `/api/usuarios` | Admin | Lista usuários com paginação. |
| `GET` | `/api/usuarios/{id}` | Admin | Retorna um usuário pelo ID. |
| `POST` | `/api/usuarios` | Admin | Cria um usuário; permite `CLIENTE` ou `ADMIN`. |
| `PUT` | `/api/usuarios/{id}` | Admin | Atualiza os campos enviados. |
| `PATCH` | `/api/usuarios/{id}` | Admin | Atualiza parcialmente os campos enviados. |
| `DELETE` | `/api/usuarios/{id}` | Admin | Exclui um usuário, exceto a própria conta. |

Paginação de usuários:

```http
GET /api/usuarios?pagina=1&limite=20
```

`pagina` começa em `1`; `limite` varia de `1` a `100` e o padrão é `20`.

Exemplo de consulta autenticada:

```sh
curl http://localhost:8080/api/usuarios \
  -H 'Authorization: Bearer <token-admin>'
```

### Carrinho — somente usuários autenticados

| Método | Rota | Auth | Descrição |
| --- | --- | --- | --- |
| `GET` | `/api/carrinho` | Bearer | Retorna os itens e o subtotal do carrinho do usuário. |
| `POST` | `/api/carrinho/itens` | Bearer | Adiciona `produto_id` e `quantidade`, respeitando o estoque. |
| `PATCH` | `/api/carrinho/itens/{produto_id}` | Bearer | Define a quantidade do item. |
| `DELETE` | `/api/carrinho/itens/{produto_id}` | Bearer | Remove o item. |

Visitantes não possuem carrinho na API e devem fazer login antes de adicionar produtos.

### Endereços — somente usuários autenticados

| Método | Rota | Auth | Descrição |
| --- | --- | --- | --- |
| `GET` | `/api/enderecos` | Bearer | Lista os endereços ativos do usuário. |
| `POST` | `/api/enderecos` | Bearer | Cria um endereço. |
| `PUT`/`PATCH` | `/api/enderecos/{id}` | Bearer | Atualiza um endereço próprio. |
| `DELETE` | `/api/enderecos/{id}` | Bearer | Desativa um endereço próprio. |

### Pedidos e pagamento simulado

| Método | Rota | Auth | Descrição |
| --- | --- | --- | --- |
| `POST` | `/api/pedidos` | Bearer | Cria um pedido pendente usando o carrinho e `endereco_id`. |
| `POST` | `/api/pedidos/{id}/pagar` | Bearer | Simula o pagamento, reduz estoque, limpa o carrinho e marca como `PAGO`. |
| `DELETE` | `/api/pedidos/{id}` | Bearer | Exclui um pedido próprio ainda pendente de pagamento. |
| `GET` | `/api/pedidos` | Bearer | Cliente vê os próprios pedidos; admin vê todos. |
| `GET` | `/api/pedidos/{id}` | Bearer | Cliente vê os próprios pedidos; admin pode consultar qualquer pedido. |
| `PATCH` | `/api/pedidos/{id}/status` | Admin | Atualiza o status operacional do pedido. |

Corpo para criar ou atualizar usuário:

```json
{
  "nome": "Ana Silva",
  "email": "ana@email.com",
  "senha": "senha123",
  "papel": "CLIENTE"
}
```

## Respostas e erros

Respostas JSON de erro usam o formato `{"erro":"..."}`. Quando aplicável, a resposta também inclui `detalhes`.

Toda resposta inclui o cabeçalho `X-Request-Id`. A API registra uma linha JSON por requisição no log do container PHP, com timestamp UTC, método, rota, status, duração e esse identificador. Exceções inesperadas também registram classe, mensagem e localização no servidor, sem enviar detalhes técnicos ao cliente.

Exemplo de log:

```json
{"timestamp":"2026-10-03T02:21:41+00:00","level":"info","service":"api","message":"http.request","request_id":"...","method":"GET","path":"/api/health","status":200,"duration_ms":2.11}
```

Para acompanhar os logs durante o desenvolvimento:

```sh
docker compose logs -f php
```

Status mais comuns:

- `200`: operação concluída.
- `201`: recurso criado.
- `204`: recurso excluído sem conteúdo.
- `400`: requisição inválida.
- `401`: token ausente, inválido ou expirado.
- `403`: usuário autenticado sem permissão.
- `404`: recurso ou rota não encontrada.
- `409`: conflito, como e-mail já cadastrado.
- `422`: dados de entrada inválidos.
- `500`: erro interno inesperado.
- `503`: API sem conexão com o banco, no endpoint `/api/health`.

## Regras operacionais

- Categorias são fixas e somente leitura pela API.
- Produtos são desativados, e não apagados fisicamente, para preservar o histórico dos pedidos.
- O pagamento é sempre simulado; não existe integração com gateway.
- O estoque só é reduzido quando o usuário confirma o pagamento.
- Vendedores com papel `ADMIN` gerenciam produtos, visualizam todos os pedidos e atualizam seus status.

## Organização do backend

ENTRADA E ROTAS

api/public/index.php
  Comparação: public/index.php do CRUD.
  Recebe as requisições HTTP e inicia o processamento da API. No CRUD, esse arquivo identifica o caminho e encaminha para src/api.php. No e-commerce, ele iniciará o fluxo usando o Router e as rotas da API.

api/src/Routes/routes.php
  Comparação: seleção de caminhos em public/index.php e métodos em src/api.php do CRUD.
  Vai associar cada método e endereço a uma ação. Por exemplo: GET /produtos será encaminhado ao controller de produtos.

api/src/Core/Router.php
  Comparação: lógica de direcionamento dividida entre public/index.php e src/api.php do CRUD.
  Vai comparar o método e o endereço recebidos com as rotas registradas e chamar o controller correspondente.

REQUISIÇÃO E BANCO DE DADOS
api/src/Core/Request.php
  Comparação: leitura direta de $_SERVER, $_GET e php://input em public/index.php e src/controllers.php do CRUD.
  Vai reunir método HTTP, endereço, parâmetros, cabeçalhos e corpo JSON em um objeto que poderá ser usado pelos controllers.

api/src/Config/Database.php
  Comparação: config/config.php do CRUD.
  No CRUD, a configuração indica o arquivo JSON usado para guardar dados. No e-commerce, Database.php fornece a conexão PDO com o MySQL a partir das configurações do ambiente.

CONTROLLERS

api/src/Controllers/UsuarioController.php
  Comparação: funções handleGet(), handlePost(), handlePut(), handlePatch() e handleDelete() de src/controllers.php do CRUD.
  Vai receber as requisições HTTP de usuários, chamar o service adequado e preparar a resposta para quem chamou a API.

api/src/Controllers/AuthController.php
  Comparação: não há um controller equivalente no CRUD.
  Vai receber as requisições relacionadas a autenticação, como login ou cadastro, conforme as funcionalidades definidas pela equipe.

SERVICES

api/src/Services/UsuarioService.php
  Comparação: funções getAllUsers(), createUser(), editUser() e removeUser() de src/services.php do CRUD.
  Vai coordenar as operações de usuário e concentrar regras de negócio. Diferente do CRUD, que chama funções de validação e persistência diretamente, o service do e-commerce usará o repository para acessar o banco.

api/src/Services/AuthService.php
  Comparação: não há um service de autenticação equivalente no CRUD.
  Vai organizar as operações e regras relacionadas a autenticação.

ACESSO AOS DADOS

api/src/Repositories/UsuarioRepository.php
  Comparação: src/data.php do CRUD.
  O CRUD lê e grava os dados em data/data.json. O repository do e-commerce fará as consultas SQL de usuários no MySQL usando PDO.

api/src/Repositories/AuthRepository.php
  Comparação: não há um repository de autenticação equivalente no CRUD.
  Poderá concentrar consultas necessárias à autenticação. Se as consultas forem as mesmas de usuários, a equipe pode usar UsuarioRepository para evitar duplicação.

VALIDAÇÃO

src/validation.php do CRUD verifica campos obrigatórios e formato dos dados antes de criar ou editar usuários. No e-commerce, a equipe decidirá onde organizar essas verificações: pode criar uma camada própria de validação ou começar com validações simples junto ao fluxo do controller/service.

AUTENTICAÇÃO E PERMISSÕES

api/src/Core/Auth.php
  Não há arquivo equivalente no CRUD. Vai centralizar recursos comuns para identificar o usuário autenticado quando a autenticação for definida.

api/src/Middlewares/AuthMiddleware.php
  Não há arquivo equivalente no CRUD. Vai verificar se a pessoa está autenticada antes de acessar rotas protegidas.

api/src/Middlewares/AdminMiddleware.php
  Não há arquivo equivalente no CRUD. Vai verificar se a pessoa tem permissão administrativa antes de acessar rotas restritas a administradores.

FLUXO GERAL

No CRUD:
public/index.php -> src/api.php -> src/controllers.php -> src/services.php -> src/validation.php e src/data.php

No e-commerce:
public/index.php -> Router/Routes -> Middleware (quando necessário) -> Controller -> Service -> Repository -> PDO/MySQL

A principal diferença é que o CRUD guarda os dados em JSON e organiza boa parte do fluxo em funções. O e-commerce usará classes e MySQL, mantendo cada responsabilidade em uma camada própria.
