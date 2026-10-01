import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [tailwindcss()],
  build: {
    rolldownOptions: {
      input: {
        inicio: resolve(import.meta.dirname, 'index.html'),
        catalogo: resolve(import.meta.dirname, 'src/pages/catalogo/index.html'),
        carrinho: resolve(import.meta.dirname, 'src/pages/carrinho/index.html'),
        pagamento: resolve(import.meta.dirname, 'src/pages/pagamento/index.html'),
        finalizado: resolve(import.meta.dirname, 'src/pages/finalizado/index.html'),
        login: resolve(import.meta.dirname, 'src/pages/login/index.html'),
      },
    },
  },
});
