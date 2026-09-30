# Frontend

Estrutura inicial do frontend em JavaScript puro (módulos ES) e HTML para a livraria Além da Estante. A paleta já funciona com CSS nativo. O projeto também está preparado para Tailwind CSS 4 pela CLI oficial, sem framework JavaScript. As dependências estão declaradas, mas não foram instaladas.

## Árvore do projeto

```text
frontend/
├── README.md                 # Este guia
├── index.html                # Página HTML inicial
├── package.json              # Scripts e dependências futuras do Tailwind
└── src/
    ├── main.js               # Ponto de entrada da aplicação
    ├── styles/
    │   ├── tokens.css        # Cores e decisões visuais compartilhadas
    │   ├── main.css          # Base visual usada pela página hoje
    │   └── tailwind.css      # Entrada da compilação do Tailwind 4
```

Crie `src/components/`, `src/pages/`, `src/services/` e `src/utils/` quando houver código para cada responsabilidade. Use `assets/icons/` e `assets/images/` para arquivos estáticos. O Git não registra pastas vazias, então a árvore acima mostra apenas os arquivos versionados; nenhum `.gitkeep` é necessário.

## Como as partes se conectam

1. `index.html` carrega `src/main.js` como módulo e `src/styles/main.css` como folha de estilos.
2. `main.js` inicia a interface dentro do elemento `#app`.
3. Cada página pode ter seu próprio módulo em `src/pages/` e compor elementos de `src/components/`.
4. As páginas usam `src/services/` para chamadas de API; funções genéricas ficam em `src/utils/`.
5. `tokens.css` é a fonte das cores. `main.css` usa esses tokens no CSS nativo; `tailwind.css` os expõe a utilitários do Tailwind.

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

Abra `http://localhost:8000`. Módulos ES devem ser servidos por HTTP. A página já usa as cores sem instalar nada. A fonte Inter está definida com fallback para fontes do sistema até que seus arquivos sejam adicionados.

## Ativar o Tailwind quando necessário

Com Node.js e npm disponíveis, execute na pasta `frontend`:

```sh
npm install
npm run css:watch
```

Para gerar CSS de produção, use `npm run css:build`. A compilação cria `dist/styles.css` com Tailwind e os estilos de `main.css`. Quando começar a usar classes utilitárias nas páginas, troque em `index.html` a referência de `./src/styles/main.css` por `./dist/styles.css`. O diretório `dist/` é gerado e ignorado pelo Git. Exemplos de classes disponíveis após a compilação: `bg-paper`, `text-navy`, `bg-sage`, `border-outline` e `rounded-card`.
