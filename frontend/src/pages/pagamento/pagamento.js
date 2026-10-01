import '../../main.js';
import { getProducts, getProductStock, isProductActive, formatPrice } from '../../services/products.js';
import { getCartItems, getCartCount } from '../../services/cart.js';

const status = document.querySelector('#checkout-status');
const itemList = document.querySelector('#checkout-items');
const countLabel = document.querySelector('#checkout-count');
const subtotalLabel = document.querySelector('#checkout-subtotal');

function element(tag, classes, text) {
  const node = document.createElement(tag);
  if (classes) node.className = classes;
  if (text !== undefined) node.textContent = text;
  return node;
}

function renderEmpty() {
  status.textContent = 'Sua sacola está vazia. Adicione livros antes de revisar a compra.';
  const item = element('li', 'py-5 text-sm text-muted', 'Nenhum item para revisar.');
  itemList.replaceChildren(item);
  countLabel.textContent = '0';
  subtotalLabel.textContent = formatPrice(0);
}

async function renderCheckout() {
  const cart = getCartItems();
  if (cart.length === 0) {
    renderEmpty();
    return;
  }
  countLabel.textContent = String(getCartCount());
  subtotalLabel.textContent = '—';

  try {
    const products = await getProducts();
    const byId = new Map(products.map((product) => [Number(product.id), product]));
    let subtotalCents = 0;
    const rows = cart.map((item) => {
      const product = byId.get(item.produto_id);
      const available = product && isProductActive(product) && getProductStock(product) >= item.quantidade;
      const name = product?.nome || `Produto ${item.produto_id}`;
      const row = element('li', 'py-4 flex items-start justify-between gap-4');
      const details = element('div', 'min-w-0');
      details.append(
        element('p', 'text-sm font-semibold text-navy', name),
        element('p', 'text-xs text-muted mt-1', `${item.quantidade} × ${product ? formatPrice(product.preco) : 'preço indisponível'}`),
        ...(!available ? [element('p', 'text-xs text-error mt-1', 'Este item não está disponível na quantidade selecionada.')] : [])
      );
      const total = product ? Math.round(Number(product.preco) * 100) * item.quantidade : 0;
      subtotalCents += total;
      row.append(details, element('p', 'text-sm font-bold text-navy whitespace-nowrap', product ? formatPrice(total / 100) : '—'));
      return row;
    });

    itemList.replaceChildren(...rows);
    countLabel.textContent = String(getCartCount());
    subtotalLabel.textContent = formatPrice(subtotalCents / 100);
    status.textContent = 'Resumo atualizado com os dados atuais do catálogo.';
  } catch {
    status.textContent = 'Não foi possível atualizar o resumo. Confira a conexão com a API.';
    itemList.replaceChildren(element('li', 'py-5 text-sm text-error', 'Preços e estoque não puderam ser confirmados.'));
  }
}

renderCheckout();
