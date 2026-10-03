import './styles/main.css';
import './components/Header.js';
import './components/Footer.js';
import { initIcons } from './utils/icons.js';

// Inicializa os ícones Lucide nos elementos com [data-lucide]
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => initIcons());
} else {
  initIcons();
}

export { initIcons };
