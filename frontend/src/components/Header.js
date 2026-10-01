import { initIcons } from '../utils/icons.js';

/**
 * Retorna o HTML do cabeçalho da loja Além da Estante.
 * @param {Object} [options] - Opções de customização
 * @param {number|string} [options.cartCount=3] - Quantidade exibida no badge da sacola
 * @returns {string}
 */
export function getHeaderHTML({ cartCount = 3 } = {}) {
  return `
    <header class="bg-paper/95 backdrop-blur-md border-b border-outline/60 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 md:px-6 h-20 flex items-center justify-between">
        <!-- Logo -->
        <a href="/index.html" class="flex items-center gap-3 group">
          <i data-lucide="book-open" class="w-6 h-6 text-navy transition-transform group-hover:scale-105"></i>
          <span class="text-xl font-bold text-navy tracking-tight">Além da Estante</span>
        </a>

        <!-- Navegação de Categorias -->
        <nav aria-label="Navegação do catálogo" class="hidden md:flex items-center gap-8">
          <a href="/catalogo/" class="text-sm font-semibold text-ink hover:text-navy transition-colors">Livros</a>
          <a href="/catalogo/" class="text-sm font-semibold text-ink hover:text-navy transition-colors">Categorias</a>
          <a href="/catalogo/" class="text-sm font-semibold text-ink hover:text-navy transition-colors">Curadoria</a>
        </nav>

        <!-- Ações do Cabeçalho -->
        <div class="flex items-center gap-2 sm:gap-4">
          <button aria-label="Buscar livros" type="button" class="p-2 text-ink hover:text-navy transition-colors">
            <i data-lucide="search" class="w-5 h-5"></i>
          </button>
          <a href="/login/" aria-label="Minha conta" class="p-2 text-ink hover:text-navy transition-colors">
            <i data-lucide="user" class="w-5 h-5"></i>
          </a>
          <a href="/carrinho/" aria-label="Sacola de compras" class="relative p-2 text-ink hover:text-navy transition-colors">
            <i data-lucide="shopping-bag" class="w-5 h-5"></i>
            <span class="absolute -top-1 -right-1 bg-navy text-white text-[11px] font-bold w-4 h-4 rounded-full flex items-center justify-center">${cartCount}</span>
          </a>
        </div>
      </div>
    </header>
  `;
}

/**
 * Web Component <app-header>
 * Permite usar <app-header cart-count="3"></app-header> no HTML.
 */
export class AppHeader extends HTMLElement {
  connectedCallback() {
    const cartCount = this.getAttribute('cart-count') || 3;
    this.innerHTML = getHeaderHTML({ cartCount });
    initIcons({ root: this });
  }
}

if (!customElements.get('app-header')) {
  customElements.define('app-header', AppHeader);
}
