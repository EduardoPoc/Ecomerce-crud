# Frontend

Estrutura inicial do frontend em JavaScript puro (módulos ES) e HTML para a livraria Além da Estante. A paleta já funciona com CSS nativo. O projeto também está preparado para Tailwind CSS 4 pela CLI oficial, sem framework JavaScript. As dependências estão declaradas, mas não foram instaladas.

## Árvore do projeto

```text
frontend/
├── README.md                 # Este guia
├── index.html                # Página inicial
├── package.json              # Scripts e dependências futuras do Tailwind
├── pages/
│   ├── catalogo.html         # Página de catálogo
│   └── carrinho.html         # Página do carrinho
├── public/
│   └── favicon.svg           # Arquivo estático compartilhado
└── src/
    ├── main.js               # Comportamento compartilhado entre páginas
    ├── styles/
    │   ├── tokens.css        # Cores e decisões visuais compartilhadas
    │   ├── main.css          # Base visual usada pela página hoje
    │   └── tailwind.css      # Entrada da compilação do Tailwind 4
```

Crie `src/components/`, `src/pages/`, `src/services/` e `src/utils/` quando houver código para cada responsabilidade. Acrescente imagens e outros arquivos estáticos em `public/`. O Git não registra pastas vazias; nenhum `.gitkeep` é necessário.

## Como as partes se conectam

1. Cada endereço tem seu próprio HTML: `index.html` para início e arquivos em `pages/` para as outras páginas. Links comuns do navegador fazem a navegação. Não há roteador nem renderização obrigatória de toda a página pelo JavaScript.
2. O conteúdo principal e a navegação estão no HTML e funcionam mesmo sem JavaScript. `src/main.js` cuida apenas de comportamento compartilhado, como atualizar o ano do rodapé.
3. Quando uma página precisar de interações específicas, crie um módulo em `src/pages/` e carregue-o apenas no HTML daquela página. Partes reutilizáveis do código ficam em `src/components/`.
4. As páginas usam `src/services/` para chamadas de API; funções genéricas ficam em `src/utils/`.
5. `public/` guarda arquivos servidos diretamente, sem processamento. Como ainda não há bundler, os caminhos no HTML incluem `public/`, como `./public/favicon.svg` na raiz e `../public/favicon.svg` em `pages/`.
6. `tokens.css` é a fonte das cores. `main.css` usa esses tokens no CSS nativo; `tailwind.css` os expõe a utilitários do Tailwind.

Use importações relativas com a extensão `.js`. Mantenha módulos de página focados na página e componentes focados em partes reutilizáveis.

## Cores iniciais

| Token CSS | Valor | Uso sugerido |
| --- | --- | --- |
| `--brand-paper` | `#fbf9f4` | Fundo de página |
| `--brand-surface` | `#ffffff` | Cartões e campos |
| `--brand-navy` | `#0b2a42` | Títulos, navegação e ações secundárias |
| `--brand-ink` | `#172a3a` | Texto principal |
| `--brand-muted` | `#647582` | Texto de apoio |
| `--brand-sage` | `#4f7c70` | Marcadores e categorias |
| `--brand-amber` | `#f5af19` | Destaques e ações principais |
| `--brand-border` | `#e2e6e8` | Divisórias e bordas |

O anexo contém valores levemente diferentes na tabela de tokens e na descrição da marca. Para esta base, o fundo segue a tabela (`#fbf9f4`) e as cores de identidade seguem a descrição (`#0b2a42`, `#4f7c70`, `#f5af19`). Mude os valores apenas em `tokens.css` para manter CSS e Tailwind alinhados.

## Executar agora

Na pasta `frontend`, inicie um servidor estático local, por exemplo:

```sh
python3 -m http.server 8000
```

Abra `http://localhost:8000`, `http://localhost:8000/pages/catalogo.html` ou `http://localhost:8000/pages/carrinho.html`. Módulos ES devem ser servidos por HTTP. As páginas já usam as cores sem instalar nada. A fonte Inter está definida com fallback para fontes do sistema até que seus arquivos sejam adicionados.

## Ativar o Tailwind quando necessário

Com Node.js e npm disponíveis, execute na pasta `frontend`:

```sh
npm install
npm run css:watch
```

Para gerar CSS de produção, use `npm run css:build`. A compilação cria `dist/styles.css` com Tailwind e os estilos de `main.css`. Quando começar a usar classes utilitárias, troque as referências a `main.css` nos três HTMLs pelo arquivo compilado: `./dist/styles.css` em `index.html` e `../dist/styles.css` nas páginas de `pages/`. O diretório `dist/` é gerado e ignorado pelo Git. Exemplos de classes disponíveis após a compilação: `bg-paper`, `text-navy`, `bg-sage`, `border-outline` e `rounded-card`.
