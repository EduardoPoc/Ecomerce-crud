import { resolve } from 'node:path';
import fs from 'node:fs';
import { defineConfig, loadEnv } from 'vite';
import tailwindcss from '@tailwindcss/vite';

/**
 * Plugin para permitir URLs limpas no navegador (sem 'src/pages')
 * tanto no servidor de desenvolvimento quanto no build de produção.
 */
function cleanUrlsPlugin() {
  const pages = ['catalogo', 'carrinho', 'pagamento', 'finalizado', 'login', 'cadastro', 'conta', 'admin', 'livro'];

  return {
    name: 'clean-urls-plugin',
    configureServer(server) {
      server.middlewares.use((req, res, next) => {
        const [pathname, search] = (req.url || '').split('?');
        const match = pathname.match(/^\/([^/]+)(\/.*)?$/);
        const segment = match ? match[1] : '';

        if (pages.includes(segment)) {
          const rest = match[2] || '';
          if (segment === 'livro') {
            req.url = `/src/pages/${segment}/index.html${search ? `?${search}` : ''}`;
          } else if (rest === '' || rest === '/') {
            req.url = `/src/pages/${segment}/index.html${search ? `?${search}` : ''}`;
          } else if (!rest.startsWith('/src/')) {
            req.url = `/src/pages/${segment}${rest}${search ? `?${search}` : ''}`;
          }
        }
        next();
      });
    },
    closeBundle() {
      const distDir = resolve(import.meta.dirname, 'dist');
      const srcPagesDir = resolve(distDir, 'src/pages');
      if (fs.existsSync(srcPagesDir)) {
        const pages = fs.readdirSync(srcPagesDir);
        for (const page of pages) {
          const pageDir = resolve(srcPagesDir, page);
          const targetDir = resolve(distDir, page);
          if (fs.statSync(pageDir).isDirectory()) {
            fs.cpSync(pageDir, targetDir, { recursive: true });
          }
        }
        fs.rmSync(resolve(distDir, 'src'), { recursive: true, force: true });
      }
    },
  };
}

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const apiProxyTarget = process.env.VITE_API_PROXY_TARGET
    || env.VITE_API_PROXY_TARGET
    || 'http://localhost:8080';

  return {
    plugins: [tailwindcss(), cleanUrlsPlugin()],
    server: {
      proxy: {
        '/api': {
          target: apiProxyTarget,
          changeOrigin: false,
          headers: { host: 'localhost' },
        },
        '/uploads': {
          target: apiProxyTarget,
          changeOrigin: false,
          headers: { host: 'localhost' },
        },
      },
    },
    build: {
      rolldownOptions: {
        input: {
          inicio: resolve(import.meta.dirname, 'index.html'),
          catalogo: resolve(import.meta.dirname, 'src/pages/catalogo/index.html'),
          carrinho: resolve(import.meta.dirname, 'src/pages/carrinho/index.html'),
          pagamento: resolve(import.meta.dirname, 'src/pages/pagamento/index.html'),
          finalizado: resolve(import.meta.dirname, 'src/pages/finalizado/index.html'),
          login: resolve(import.meta.dirname, 'src/pages/login/index.html'),
          cadastro: resolve(import.meta.dirname, 'src/pages/cadastro/index.html'),
          conta: resolve(import.meta.dirname, 'src/pages/conta/index.html'),
          admin: resolve(import.meta.dirname, 'src/pages/admin/index.html'),
          livro: resolve(import.meta.dirname, 'src/pages/livro/index.html'),
        },
      },
    },
  };
});
