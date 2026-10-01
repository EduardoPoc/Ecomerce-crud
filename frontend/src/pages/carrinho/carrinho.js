import '../../main.js';
import { getProducts, getProductStock, isProductActive, formatPrice } from '../../services/products.js';
import { getCartItems, getCartCount, setCartQuantity, removeFromCart } from '../../services/cart.js';

const status = document.querySelector('#cart-status');
const itemsContainer = document.querySelector('#cart-items');
const summary = document.querySelector('#cart-summary');
let productsById = new Map();

function element(tag, classes, text) {
  const node = document.createElement(tag);
  if (classes) node.className = classes;
  if (text !== undefined) node.textContent = text;
  return node;
}

function productCard(item, product) {
  const stock = product ? getProductStock(product) : 0;
  const inStock = Boolean(product && isProductActive(product) && stock > 0);
  const available = inStock && item.quantidade <= stock;
  const row = element('article', 'bg-surface rounded-xl border border-outline/50 p-4 flex flex-col sm:flex-row gap-4 sm:items-center');
  const cover = element('div', 'w-20 h-28 shrink-0 bg-surface-soft rounded-lg flex items-center justify-center text-sage');

  if (product?.imagem_url) {
    try {
      const url = new URL(product.imagem_url, window.location.origin);
      if (url.protocol === 'http:' || url.protocol === 'https:') {
        const image = element('img', 'w-full h-full object-cover rounded-lg');
        image.src = url.href;
        image.alt = `Capa de ${product.nome}`;
        image.loading = 'lazy';
        image.addEventListener('error', () => image.remove(), { once: true });
        cover.append(image);
      }
    } catch {
      // Mantém o espaço da capa sem imagem.
    }
  }

  const details = element('div', 'flex-1 min-w-0');
  const title = element('h2', 'text-lg font-serif font-bold text-navy', product?.nome || `Produto ${item.produto_id}`);
  const category = element('p', 'text-xs text-sage mt-1', product?.categoria_nome || 'Livro');
  const availabilityMessage = !product || !isProductActive(product) || stock < 1
    ? 'Produto indisponível no catálogo'
    : available ? `${stock} em estoque` : `Estoque atual: ${stock}. Reduza a quantidade.`;
  const availability = element('p', `text-xs mt-2 ${available ? 'text-muted' : 'text-error'}`, availabilityMessage);
  const unitPrice = element('p', 'text-sm font-semibold text-navy mt-2', product ? formatPrice(product.preco) : 'Preço indisponível');
  details.append(title, category, availability, unitPrice);

  const actions = element('div', 'flex items-center gap-3 sm:flex-col sm:items-end');
  const controls = element('div', 'inline-flex items-center gap-3 border border-outline rounded-lg px-2 py-1');
  const decrement = element('button', 'w-7 h-7 text-navy font-bold disabled:text-muted/40', '−');
  decrement.type = 'button';
  decrement.setAttribute('aria-label', `Diminuir quantidade de ${product?.nome || 'produto'}`);
  decrement.disabled = item.quantidade <= 1 || !inStock;
  decrement.addEventListener('click', () => setCartQuantity(item.produto_id, item.quantidade - 1, stock));
  const quantity = element('span', 'min-w-5 text-center text-sm font-semibold text-navy', String(item.quantidade));
  const increment = element('button', 'w-7 h-7 text-navy font-bold disabled:text-muted/40', '+');
  increment.type = 'button';
  increment.setAttribute('aria-label', `Aumentar quantidade de ${product?.nome || 'produto'}`);
  increment.disabled = !inStock || item.quantidade >= stock;
  increment.addEventListener('click', () => setCartQuantity(item.produto_id, item.quantidade + 1, stock));
  controls.append(decrement, quantity, increment);

  const lineTotal = element('p', 'text-sm font-bold text-navy', product ? formatPrice(Number(product.preco) * item.quantidade) : '—');
  const remove = element('button', 'text-xs text-error hover:underline', 'Remover');
  remove.type = 'button';
  remove.addEventListener('click', () => removeFromCart(item.produto_id));
  actions.append(controls, lineTotal, remove);
  row.append(cover, details, actions);
  return { row, available, totalCents: product ? Math.round(Number(product.preco) * 100) * item.quantidade : 0 };
}

function renderSummary(items, totalCents, allAvailable) {
  const title = element('h2', 'text-lg font-serif font-bold text-navy border-b border-outline/50 pb-4', 'Resumo da sacola');
  const count = element('div', 'flex justify-between text-sm text-muted py-4', '');
  count.append(element('span', '', 'Quantidade de livros'), element('strong', 'text-navy', String(getCartCount())));
  const subtotal = element('div', 'flex justify-between text-sm text-muted border-t border-outline/50 pt-4', '');
  subtotal.append(element('span', '', 'Subtotal'), element('strong', 'text-navy', allAvailable ? formatPrice(totalCents / 100) : '—'));
  const shipping = element('p', 'text-xs text-muted mt-3', 'Frete e total final serão calculados quando a API de pedidos estiver disponível.');
  const checkout = element('a', 'block text-center rounded-lg px-4 py-3 mt-5 text-sm font-semibold');
  checkout.href = '/pagamento/';
  checkout.textContent = allAvailable && items.length ? 'Continuar para revisão' : 'Indisponível para continuar';
  checkout.classList.add(allAvailable && items.length ? 'bg-navy' : 'bg-outline/50', allAvailable && items.length ? 'text-surface' : 'text-muted');
  if (!allAvailable || items.length === 0) {
    checkout.removeAttribute('href');
    checkout.setAttribute('aria-disabled', 'true');
  }
  const notice = element('p', 'text-xs text-muted mt-4 leading-relaxed', 'A sacola fica salva neste navegador. Ela ainda não é sincronizada com a conta ou com o banco da loja.');
  summary.replaceChildren(title, count, subtotal, shipping, checkout, notice);
}

function renderCart(pricesReady = true) {
  const items = getCartItems();
  if (items.length === 0) {
    status.textContent = 'Sua sacola está vazia.';
    const empty = element('div', 'bg-surface rounded-xl border border-outline/50 p-8 text-center lg:col-span-2');
    empty.append(element('p', 'text-navy font-semibold', 'Escolha um livro para começar.'), element('a', 'inline-block mt-4 text-sm font-semibold text-sage hover:underline', 'Ir para o catálogo'));
    empty.lastElementChild.href = '/catalogo/';
    itemsContainer.replaceChildren(empty);
    renderSummary(items, 0, false);
    return;
  }

  const rendered = items.map((item) => productCard(item, productsById.get(item.produto_id)));
  const allAvailable = pricesReady && rendered.every((item) => item.available);
  const totalCents = rendered.reduce((total, item) => total + item.totalCents, 0);
  itemsContainer.replaceChildren(...rendered.map((item) => item.row));
  status.textContent = pricesReady
    ? `${getCartCount()} livro(s) na sacola.`
    : `${getCartCount()} livro(s) na sacola. Preços e estoque indisponíveis sem conexão com a API.`;
  renderSummary(items, totalCents, allAvailable);
}

async function initializeCart() {
  if (getCartItems().length === 0) {
    renderCart();
    return;
  }

  try {
    const products = await getProducts();
    productsById = new Map(products.map((product) => [Number(product.id), product]));
    renderCart();
  } catch {
    renderCart(false);
  }
}

window.addEventListener('cart:updated', renderCart);
window.addEventListener('storage', renderCart);
initializeCart();
