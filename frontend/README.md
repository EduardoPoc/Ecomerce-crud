# Frontend

Base inicial da Além da Estante em HTML, JavaScript puro, Vite e Tailwind CSS 4. As páginas de início, catálogo e carrinho têm apenas a estrutura mínima; conteúdo, componentes e lógica da loja ainda serão desenvolvidos.

## Estrutura

```text
frontend/
├── index.html              # Página inicial
├── public/
│   └── favicon.svg         # Ícone provisório copiado sem processamento
├── src/
│   ├── main.js             # Entrada JS compartilhada; importa o CSS
│   ├── pages/
│   │   ├── carrinho/
│   │   │   ├── carrinho.js # Script da página de carrinho
│   │   │   └── index.html  # Entrada HTML do carrinho
│   │   ├── catalogo/
│   │   │   ├── catalogo.js # Script da página de catálogo
│   │   │   └── index.html  # Entrada HTML do catálogo
│   │   └── pagamento/
│   │       ├── pagamento.js# Script da página de pagamento
│   │       └── index.html  # Entrada HTML do pagamento
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

Cada página tem seu próprio HTML e navega por links normais. O cabeçalho e rodapé padronizados da loja são componentes nativos reutilizáveis (`<app-header cart-count="3"></app-header>` e `<app-footer></app-footer>`), registrados globalmente pelo `src/main.js`. O JavaScript de cada página fica em seu respectivo diretório dentro de `src/pages/`. Crie `src/services/` e novos componentes em `src/components/` conforme o código evoluir. Não usamos `.gitkeep` para pastas vazias.

Arquivos em `public/` são servidos pela raiz do site: `public/favicon.svg` é referenciado como `/favicon.svg`. O Vite os copia para a raiz de `dist/` no build.

## Adicionar uma página

Crie uma subpasta em `src/pages/<nome>/` contendo seu `index.html` e seu script (ex: `<nome>.js` que importa `../../main.js`) e adicione o arquivo HTML ao objeto `input` em `vite.config.js` para que apareça no build. Faça a navegação com links relativos entre os HTMLs.

## Comunicação com a API

O Axios está instalado para as futuras requisições HTTP. Quando houver integração com o backend, coloque o código de acesso à API em `src/services/` e importe os serviços nas páginas que precisarem deles. CORS deve ser configurado no servidor da API; não há backend neste repositório no momento.

## Ícones com Lucide

A biblioteca `lucide` está instalada para uso com JavaScript puro e Tailwind CSS.

- **No HTML**: Utilize o elemento `<i>` (ou `<span>`) com o atributo `data-lucide="<nome-do-icone>"`:
  ```html
  <i data-lucide="handbag"></i>
  <i data-lucide="shopping-cart"></i>
  ```
- **Inicialização automática**: Os ícones estáticos no DOM são convertidos automaticamente em SVGs no carregamento da página por meio de `src/main.js`.
- **Renderização dinâmica**: Se injetar elementos via JavaScript após o carregamento inicial, utilize a função `initIcons()` de `src/utils/icons.js`:
  ```javascript
  import { initIcons } from '../../utils/icons.js';

  initIcons();
  ```

## Tailwind e cores

O Tailwind 4 usa configuração diretamente no CSS. `src/styles/main.css` contém `@import "tailwindcss"` e `@theme inline`, que expõe a paleta de `tokens.css` como classes `bg-paper`, `text-navy`, `bg-sage` e outras. Por isso não há `tailwind.config.js`. `vite.config.js` registra o plugin oficial `@tailwindcss/vite` e as três páginas para o build.

| Token | Cor | Uso inicial |
| --- | --- | --- |
| `--brand-paper` | `#fbf9f4` | Fundo |
| `--brand-surface` | `#ffffff` | Superfícies |
| `--brand-navy` | `#0b2a42` | Títulos e ações |
| `--brand-ink` | `#172a3a` | Texto |
| `--brand-muted` | `#647582` | Texto secundário |
| `--brand-sage` | `#4f7c70` | Detalhes |
| `--brand-amber` | `#f5af19` | Destaques |
| `--brand-border` | `#e2e6e8` | Bordas |

O anexo de identidade contém pequenas diferenças entre a tabela de tokens e a descrição visual. Esta base usa o fundo da tabela e as cores principais da descrição. Ajuste os valores em `tokens.css` quando definir a identidade final. A fonte Inter está indicada com fallback do sistema; os arquivos da fonte ainda não foram adicionados.

## Rodar o projeto

É necessário Node.js compatível com o Vite 8 (20.19+ ou 22.12+). Dentro de `frontend/`:

```sh
npm ci
npm run dev
```

O Vite mostra o endereço local no terminal, normalmente `http://localhost:5173/`. Para conferir o build das três páginas, use `npm run build`; para visualizar o resultado, `npm run preview`.

## Dependências e verificações

O `package-lock.json` fixa a árvore de dependências. Use `npm ci` para instalar exatamente essa árvore; ao adicionar um pacote de propósito, use `npm install <nome-do-pacote>` e registre a alteração no `package.json` e no lockfile.

O `.npmrc` impede scripts automáticos de instalação das dependências e grava versões exatas ao adicionar pacotes. Se uma dependência futura precisar de script de instalação, revise o pacote antes de permitir sua execução.

O `.gitignore` da raiz já cobre `node_modules/` e `dist/` do frontend. Não é necessário outro arquivo nessa pasta.

| Comando | Função |
| --- | --- |
| `npm run verify` | Faz o build e executa a auditoria de vulnerabilidades conhecidas |
| `npm run deps:audit` | Executa apenas a auditoria do npm |
| `npm run deps:signatures` | Confere assinaturas de pacotes quando disponíveis no registro |

Auditoria e assinaturas consultam o registro npm e exigem acesso à rede.
