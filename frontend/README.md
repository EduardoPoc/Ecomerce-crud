# Frontend

Frontend da Além da Estante em HTML, JavaScript puro, Vite e Tailwind CSS 4. O catálogo, a sacola e o checkout usam a API via Axios.

## Estrutura

```text
frontend/
├── index.html              # Página inicial
├── public/
│   └── favicon.svg         # Ícone provisório copiado sem processamento
├── src/
│   ├── main.js             # Entrada JS compartilhada; importa o CSS
│   ├── services/
│   │   ├── api.js          # Cliente Axios compartilhado e cabeçalho Bearer
│   │   ├── auth.js         # Login e armazenamento da sessão
│   │   ├── cart.js         # Operações da sacola autenticada na API
│   │   └── products.js     # Leitura e normalização dos produtos da API
│   ├── pages/
│   │   ├── carrinho/
│   │   │   ├── carrinho.js # Script da página de carrinho
│   │   │   └── index.html  # Entrada HTML do carrinho
│   │   ├── catalogo/
│   │   │   ├── catalogo.js # Script da página de catálogo
│   │   │   └── index.html  # Entrada HTML do catálogo
│   │   ├── pagamento/
│   │   │   ├── pagamento.js# Script da página de pagamento
│   │   │   └── index.html  # Entrada HTML do pagamento
│   │   ├── finalizado/
│   │   │   ├── finalizado.js# Script da confirmação do pedido
│   │   │   └── index.html  # Entrada HTML da confirmação
│   │   └── login/
│   │       ├── login.js    # Script da página de login
│   │       └── index.html  # Entrada HTML de autenticação
│   ├── components/
│   │   ├── Header.js       # Componente de cabeçalho (<app-header>)
│   │   └── Footer.js       # Componente de rodapé (<app-footer>)
│   ├── utils/
│   │   └── icons.js        # Utilitário de ícones com Lucide
│   └── styles/
│       ├── main.css        # Tailwind e configuração visual
│       └── tokens.css      # Valores da paleta
├── package.json            # Comandos e dependências
├── package-lock.json       # Versões resolvidas das dependências
├── .npmrc                   # Regras de instalação do npm
└── vite.config.js          # Plugin do Tailwind e entradas HTML do build
```

Cada página tem seu próprio HTML e navega por links normais. O cabeçalho e rodapé padronizados da loja são componentes nativos reutilizáveis (`<app-header></app-header>` e `<app-footer></app-footer>`), registrados globalmente pelo `src/main.js`. O contador da sacola acompanha o estado local. O JavaScript de cada página fica em seu respectivo diretório dentro de `src/pages/`. Não usamos `.gitkeep` para pastas vazias.

Arquivos em `public/` são servidos pela raiz do site: `public/favicon.svg` é referenciado como `/favicon.svg`. O Vite os copia para a raiz de `dist/` no build.

## Adicionar uma página

Crie uma subpasta em `src/pages/<nome>/` contendo seu `index.html` e seu script (ex: `<nome>.js` que importa `../../main.js`) e adicione o arquivo HTML ao objeto `input` em `vite.config.js` para que apareça no build. Faça a navegação com links relativos entre os HTMLs.

## Comunicação com a API

O Axios usa o caminho relativo `/api`. No Docker, o Nginx encaminha `/api` para `http://php:80` pela rede interna do Compose; assim, o navegador conversa somente com o frontend e não faz preflight CORS para a porta PHP.

- **Login:** `src/services/auth.js` chama `POST /auth/login` com `{ email, senha }`. A API procura `email` em `usuario.email` e compara a senha com `usuario.senha_hash`; o frontend nunca envia nem recebe o hash. A resposta inclui token Bearer e os campos públicos `id`, `nome`, `email`, `papel` e `criado_em`. O token fica em `sessionStorage` por padrão ou em `localStorage` quando “Lembrar de mim” está marcado; o Axios envia `Authorization: Bearer ...` nas chamadas seguintes.
- **Catálogo:** `src/services/products.js` chama `GET /produtos`. A tela usa `id`, `categoria_nome`, `nome`, `descricao`, `preco`, `estoque`, `imagem_url` e `ativo`, conforme a resposta de `ProdutoRepository`. Produtos inativos não são exibidos e produtos sem estoque não podem ser adicionados.
- **Sacola:** `src/services/cart.js` chama `GET /carrinho`, `POST /carrinho/itens`, `PATCH /carrinho/itens/{produto_id}` e `DELETE /carrinho/itens/{produto_id}`. O carrinho é persistido nas tabelas `carrinho` e `item_carrinho`, exclusivamente para usuários autenticados. O frontend não grava preço ou estoque como fonte confiável; esses dados são recalculados pela API.
- **Acesso à sacola:** se o visitante tentar adicionar um livro ou abrir o checkout sem login, é redirecionado para `/login/` e retorna à página original após autenticar.
- **Checkout:** a página `/pagamento/` carrega os itens pela API, exige um endereço do usuário e cria o pedido com `POST /pedidos`. O frete é fixo em R$ 30,00 e é somado ao subtotal no backend.
- **Pagamento simulado:** o botão de confirmação chama `POST /pedidos/{id}/pagar`. Não existe cobrança real; a API baixa o estoque, marca o pedido como `PAGO` e a tela `/finalizado/` confirma o pedido. Se alguma etapa falhar, o frontend tenta excluir o pedido pendente com `DELETE /pedidos/{id}`.

Catálogo e login dependem de a API e o banco MySQL estarem ativos e configurados.

## Rodar com Docker

Os comandos abaixo devem ser executados na raiz do repositório, pois o Compose e o Dockerfile do frontend ficam fora desta pasta. É necessário Docker com Docker Compose. Se `api/.env` ainda não existir, copie o exemplo de ambiente da API:

```sh
cp -n api/.env.example api/.env
```

Para iniciar o frontend e suas dependências:

```sh
docker compose up --build frontend
```

Esse comando também inicia PHP e MySQL por causa das dependências declaradas no Compose. Para subir todos os serviços definidos no Compose de uma vez, use:

```sh
docker compose up --build
```

Na primeira criação do volume `mysql_data`, o MySQL executa `api/database/schema.sql` e depois `api/database/seed.sql`. A seed cria livros, categorias e um único usuário administrativo para desenvolvimento: `admin@livraria.com`, senha `senha123`. Essas credenciais são públicas e não devem ser usadas em produção. Clientes, endereços, carrinhos e pedidos são criados pelos fluxos da aplicação. A seed não é executada novamente ao recriar apenas o container PHP ou MySQL, pois o volume do banco persiste.

Se o banco já estiver inicializado e ainda não tiver os dados da seed, importe-a uma vez, a partir da raiz:

```sh
docker compose exec -T mysql mysql -uloja -ploja loja < api/database/seed.sql
```

A seed deve ser importada em tabelas vazias; não repita a importação em um banco que já tenha esses registros. Para recriar o banco do zero e fazer o Compose executar schema e seed automaticamente, remova o volume e suba a stack novamente:

```sh
docker compose down -v
docker compose up -d --build
```

`down -v` apaga permanentemente os dados locais do MySQL, incluindo pedidos e demais registros.

Abra <http://localhost:8081>. No Docker, o Nginx encaminha `/api` e `/uploads` para `http://php:80` pela rede interna; não configure no navegador a URL direta da API para este fluxo. A API fica disponível em <http://localhost:8080/api/health> para diagnóstico.

Para acompanhar o frontend e encerrar os serviços:

```sh
docker compose logs -f frontend
docker compose down
```

## Ícones com Lucide

A biblioteca `lucide` está instalada para uso com JavaScript puro e Tailwind CSS.

- **No HTML**: Utilize o elemento `<i>` (ou `<span>`) com o atributo `data-lucide="<nome-do-icone>"`:
  ```html
  <i data-lucide="handbag"></i> <i data-lucide="shopping-cart"></i>
  ```
- **Inicialização automática**: Os ícones estáticos no DOM são convertidos automaticamente em SVGs no carregamento da página por meio de `src/main.js`.
- **Renderização dinâmica**: Se injetar elementos via JavaScript após o carregamento inicial, utilize a função `initIcons()` de `src/utils/icons.js`:

  ```javascript
  import { initIcons } from '../../utils/icons.js';

  initIcons();
  ```

## Tailwind e cores

O Tailwind 4 usa configuração diretamente no CSS. `src/styles/main.css` contém `@import "tailwindcss"` e `@theme inline`, que expõe a paleta de `tokens.css` como classes `bg-paper`, `text-navy`, `bg-sage` e outras. Por isso não há `tailwind.config.js`. `vite.config.js` registra o plugin oficial `@tailwindcss/vite` e as três páginas para o build.

| Token             | Cor       | Uso inicial      |
| ----------------- | --------- | ---------------- |
| `--brand-paper`   | `#fbf9f4` | Fundo            |
| `--brand-surface` | `#ffffff` | Superfícies      |
| `--brand-navy`    | `#0b2a42` | Títulos e ações  |
| `--brand-ink`     | `#172a3a` | Texto            |
| `--brand-muted`   | `#647582` | Texto secundário |
| `--brand-sage`    | `#4f7c70` | Detalhes         |
| `--brand-amber`   | `#f5af19` | Destaques        |
| `--brand-border`  | `#e2e6e8` | Bordas           |

O anexo de identidade contém pequenas diferenças entre a tabela de tokens e a descrição visual. Esta base usa o fundo da tabela e as cores principais da descrição. Ajuste os valores em `tokens.css` quando definir a identidade final. A fonte Inter está indicada com fallback do sistema; os arquivos da fonte ainda não foram adicionados.

## Rodar o projeto

É necessário Node.js compatível com o Vite 8 (20.19+ ou 22.12+). Dentro de `frontend/`:

```sh
npm ci
npm run dev
```

Para gerar o build de todas as páginas, use `npm run build`. O resultado em `dist/` deve ser servido por Nginx ou outro servidor HTTP de arquivos estáticos.

## Dependências e verificações

O `package-lock.json` fixa a árvore de dependências. Use `npm ci` para instalar exatamente essa árvore; ao adicionar um pacote de propósito, use `npm install <nome-do-pacote>` e registre a alteração no `package.json` e no lockfile.

O `.npmrc` impede scripts automáticos de instalação das dependências e grava versões exatas ao adicionar pacotes. Se uma dependência futura precisar de script de instalação, revise o pacote antes de permitir sua execução.

O `.gitignore` da raiz já cobre `node_modules/` e `dist/` do frontend. Não é necessário outro arquivo nessa pasta.

| Comando                   | Função                                                           |
| ------------------------- | ---------------------------------------------------------------- |
| `npm run verify`          | Faz o build e executa a auditoria de vulnerabilidades conhecidas |
| `npm run deps:audit`      | Executa apenas a auditoria do npm                                |
| `npm run deps:signatures` | Confere assinaturas de pacotes quando disponíveis no registro    |

Auditoria e assinaturas consultam o registro npm e exigem acesso à rede.
