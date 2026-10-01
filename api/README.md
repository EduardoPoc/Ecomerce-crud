Api do projeto Ecomerce

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