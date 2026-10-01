import '../../main.js';
import { getCartCount } from '../../services/cart.js';

const summary = document.querySelector('#confirmation-summary');
const count = getCartCount();

summary.textContent = count > 0
  ? `Sua sacola ainda contém ${count} livro(s); os itens não foram enviados como pedido.`
  : 'Sua sacola está vazia e nenhum pedido foi confirmado nesta sessão.';
