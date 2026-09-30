# Frontend

Estrutura inicial do frontend em JavaScript puro (módulos ES) e HTML. O Tailwind CSS será usado para os estilos quando a configuração de dependências for adicionada. Por enquanto, não há framework, gerenciador de pacotes ou biblioteca instalada.

## Árvore do projeto

```text
frontend/
├── README.md                 # Este guia
├── index.html                # Página HTML inicial
├── assets/                   # Arquivos estáticos servidos sem processamento
│   ├── icons/                # Ícones
│   └── images/               # Imagens
└── src/
    ├── main.js               # Ponto de entrada da aplicação
    ├── components/           # Partes reutilizáveis da interface
    ├── pages/                # Código específico de cada página
    ├── services/             # Comunicação com APIs e fontes externas
    ├── styles/
    │   └── main.css          # Estilos básicos; futura entrada do Tailwind
    └── utils/                # Funções auxiliares independentes da interface
```

As pastas vazias contêm `.gitkeep` para permanecerem no Git. Remova esse arquivo quando adicionar conteúdo à pasta.

## Como as partes se conectam

1. `index.html` carrega `src/main.js` como módulo e `src/styles/main.css` como folha de estilos.
2. `main.js` inicia a interface dentro do elemento `#app`.
3. Cada página pode ter seu próprio módulo em `src/pages/` e compor elementos de `src/components/`.
4. As páginas usam `src/services/` para chamadas de API; funções genéricas ficam em `src/utils/`.
5. Imagens e ícones que não precisam de processamento ficam em `assets/`.

Use importações relativas com a extensão `.js`. Mantenha módulos de página focados na página e componentes focados em partes reutilizáveis. Quando o Tailwind for configurado, concentre a integração e os estilos globais em `src/styles/`; documente aqui os comandos necessários para instalar e executar o projeto.

## Executar agora

Na pasta `frontend`, inicie um servidor estático local, por exemplo:

```sh
python3 -m http.server 8000
```

Abra `http://localhost:8000`. Módulos ES devem ser servidos por HTTP. O CSS atual é apenas uma base mínima; as classes do Tailwind ainda não estão disponíveis até a futura configuração da ferramenta.
