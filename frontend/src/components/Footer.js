import { initIcons } from '../utils/icons.js';

/**
 * Retorna o HTML do rodapé da loja Além da Estante.
 * @returns {string}
 */
export function getFooterHTML() {
  return `
    <footer class="bg-surface-soft border-t border-outline mt-16 pt-12 pb-8">
      <div class="max-w-7xl mx-auto px-4 md:px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 pb-10 border-b border-outline/60">
          <!-- Coluna 1: Sobre a Livraria -->
          <div class="space-y-3">
            <h3 class="text-lg font-bold text-navy">Além da Estante</h3>
            <p class="text-xs text-muted leading-relaxed">
              Um refúgio literário dedicado a edições primorosas, curadorias atenciosas e o prazer do livro físico em mãos.
            </p>
          </div>

          <!-- Coluna 2: Navegação -->
          <div>
            <h4 class="text-xs font-bold text-navy tracking-wider uppercase mb-3">Navegação</h4>
            <ul class="space-y-2 text-xs text-muted">
              <li><a href="/catalogo/" class="hover:text-navy transition-colors">Livros & Lançamentos</a></li>
              <li><a href="/catalogo/" class="hover:text-navy transition-colors">Coleções & Gêneros</a></li>
              <li><a href="/catalogo/" class="hover:text-navy transition-colors">Destaques da Equipe</a></li>
            </ul>
          </div>

          <!-- Coluna 3: Atendimento -->
          <div>
            <h4 class="text-xs font-bold text-navy tracking-wider uppercase mb-3">Atendimento</h4>
            <ul class="space-y-2 text-xs text-muted">
              <li><a href="#" class="hover:text-navy transition-colors">Meus Pedidos</a></li>
              <li><a href="#" class="hover:text-navy transition-colors">Envios & Devoluções</a></li>
              <li><a href="#" class="hover:text-navy transition-colors">Fale com o Livreiro</a></li>
            </ul>
          </div>

          <!-- Coluna 4: Segurança -->
          <div>
            <h4 class="text-xs font-bold text-navy tracking-wider uppercase mb-3">Segurança & Confiança</h4>
            <p class="text-xs text-muted leading-relaxed">
              Transações seguras com criptografia de ponta a ponta e frete com embalagem protetora especial para colecionadores.
            </p>
          </div>
        </div>

        <!-- Linha Inferior de Copyright e Termos -->
        <div class="pt-6 flex flex-col md:flex-row items-center justify-between text-xs text-muted gap-4">
          <p>© 2025 Além da Estante Livraria Independente Ltda. Todos os direitos reservados.</p>
          <div class="flex items-center gap-6">
            <a href="#" class="hover:text-navy transition-colors">Termos de Serviço</a>
            <a href="#" class="hover:text-navy transition-colors">Política de Privacidade</a>
            <a href="#" class="hover:text-navy transition-colors">Trocas e Devoluções</a>
          </div>
        </div>
      </div>
    </footer>
  `;
}

/**
 * Web Component <app-footer>
 * Permite usar <app-footer></app-footer> no HTML.
 */
export class AppFooter extends HTMLElement {
  connectedCallback() {
    this.innerHTML = getFooterHTML();
    initIcons({ root: this });
  }
}

if (!customElements.get('app-footer')) {
  customElements.define('app-footer', AppFooter);
}
