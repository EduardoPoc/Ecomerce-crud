import { initIcons } from '../utils/icons.js';
import { getCartCount, isAuthenticated } from '../services/cart.js';
import { getCurrentUser } from '../services/authState.js';

function initialsFor(name) {
  const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return 'U';
  if (parts.length === 1) return Array.from(parts[0]).slice(0, 2).join('').toLocaleUpperCase('pt-BR');
  return `${Array.from(parts[0])[0]}${Array.from(parts[parts.length - 1])[0]}`.toLocaleUpperCase('pt-BR');
}

/**
 * Retorna o HTML do cabeçalho da loja Além da Estante.
 * @param {Object} [options] - Opções de customização
 * @param {number|string} [options.cartCount=3] - Quantidade exibida no badge da sacola
 * @returns {string}
 */
export function getHeaderHTML({ cartCount = 0 } = {}) {
  return `
    <header class="bg-paper/95 backdrop-blur-md border-b border-outline/60 sticky top-0 z-50">
      <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-navy focus:px-3 focus:py-2 focus:text-sm focus:font-semibold focus:text-surface">Pular para o conteúdo principal</a>
      <div class="max-w-7xl mx-auto px-4 md:px-6 h-16 sm:h-20 flex items-center justify-between gap-2">
        <!-- Logo -->
        <a href="/index.html" class="flex min-w-0 items-center gap-3 group">
          <i data-lucide="book-open" class="w-6 h-6 text-navy transition-transform group-hover:scale-105"></i>
          <span class="truncate text-xl font-bold text-navy tracking-tight">Além da Estante</span>
        </a>

        <!-- Navegação de Categorias -->
        <nav aria-label="Navegação do catálogo" class="hidden md:flex items-center gap-8">
          <a href="/catalogo/" class="text-sm font-semibold text-ink hover:text-navy transition-colors">Livros</a>
        </nav>

        <!-- Ações do Cabeçalho -->
        <div class="flex shrink-0 items-center gap-1 sm:gap-4">
          <a href="/catalogo/" aria-label="Buscar livros" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg p-2 text-ink hover:text-navy transition-colors">
            <i data-lucide="search" class="w-5 h-5"></i>
          </a>
          <div data-account-control class="flex items-center"></div>
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
    this.innerHTML = getHeaderHTML({ cartCount: 0 });
    this.updateAccountControl();
    initIcons({ root: this });
    this.updateCartBadge();
    this.handleCartUpdate = () => this.updateCartBadge();
    this.handleAuthUpdate = () => this.updateAccountControl();
    this.handleStorageUpdate = () => {
      this.updateCartBadge();
      this.updateAccountControl();
    };
    window.addEventListener('cart:updated', this.handleCartUpdate);
    window.addEventListener('auth:updated', this.handleAuthUpdate);
    window.addEventListener('storage', this.handleStorageUpdate);
  }

  disconnectedCallback() {
    window.removeEventListener('cart:updated', this.handleCartUpdate);
    window.removeEventListener('auth:updated', this.handleAuthUpdate);
    window.removeEventListener('storage', this.handleStorageUpdate);
  }

  updateAccountControl() {
    const control = this.querySelector('[data-account-control]');
    if (!control) return;

    const user = getCurrentUser();
    if (!user) {
      const link = document.createElement('a');
      link.href = '/login/';
      link.className = 'inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg p-2 text-ink hover:text-navy transition-colors';
      link.setAttribute('aria-label', 'Entrar ou acessar minha conta');

      const icon = document.createElement('i');
      icon.dataset.lucide = 'user';
      icon.className = 'w-5 h-5';
      link.append(icon);
      control.replaceChildren(link);
      initIcons({ root: control });
      return;
    }

    const name = String(user.nome || user.email || '').trim();
    const avatar = document.createElement('span');
    avatar.className = 'flex h-9 w-9 items-center justify-center rounded-full border border-outline bg-sage text-xs font-bold tracking-wide text-surface';
    avatar.setAttribute('role', 'img');
    avatar.setAttribute('aria-label', name ? `Usuário autenticado: ${name}` : 'Usuário autenticado');
    if (name) avatar.title = name;
    avatar.textContent = initialsFor(name);
    const accountLink = document.createElement('a');
    accountLink.href = '/conta/';
    accountLink.className = 'rounded-full focus:outline-none focus:ring-2 focus:ring-sage';
    accountLink.setAttribute('aria-label', 'Abrir minha conta');
    accountLink.append(avatar);
    control.replaceChildren(accountLink);
  }

  updateCartBadge() {
    const badge = this.querySelector('a[href="/carrinho/"] span');
    if (!badge) return;
    if (!isAuthenticated()) {
      badge.textContent = '0';
      return;
    }
    getCartCount().then((count) => { badge.textContent = String(count); }).catch(() => { badge.textContent = '0'; });
  }
}

if (!customElements.get('app-header')) {
  customElements.define('app-header', AppHeader);
}
