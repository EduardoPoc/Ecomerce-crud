import '../../main.js';
import { getCart, setCartQuantity, removeFromCart, isAuthenticated, loginRedirect } from '../../services/cart.js';
import { formatPrice, repairMojibake } from '../../services/products.js';
import { createProductImageElement } from '../../utils/productImage.js';

const status = document.querySelector('#cart-status');
const itemsContainer = document.querySelector('#cart-items');
const summary = document.querySelector('#cart-summary');

function element(tag, classes, text) {
  const node = document.createElement(tag);
  if (classes) node.className = classes;
  if (text !== undefined) node.textContent = text;
  return node;
}

function renderSummary(cart) {
  const title = element('h2', 'text-lg font-serif font-bold text-navy border-b border-outline/50 pb-4', 'Resumo da sacola');
  const count = element('div', 'flex justify-between text-sm text-muted py-4', '');
  count.append(element('span', '', 'Quantidade de livros'), element('strong', 'text-navy', String(cart.quantidade || 0)));
  const subtotal = element('div', 'flex justify-between text-sm text-muted border-t border-outline/50 pt-4', '');
  subtotal.append(element('span', '', 'Subtotal'), element('strong', 'text-navy', formatPrice(cart.subtotal)));
  const checkout = element('a', 'block text-center rounded-lg px-4 py-3 mt-5 text-sm font-semibold bg-navy text-surface', 'Continuar para revisão');
  checkout.href = cart.itens?.length ? '/pagamento/' : '#';
  if (!cart.itens?.length) { checkout.classList.add('opacity-50', 'pointer-events-none'); }
  summary.replaceChildren(title, count, subtotal, element('p', 'text-xs text-muted mt-3', 'Frete será calculado na revisão.'), checkout);
}

function renderItem(item) {
  item = {
    ...item,
    nome: repairMojibake(item.nome),
    descricao: repairMojibake(item.descricao),
    categoria_nome: repairMojibake(item.categoria_nome),
  };
  const available = Boolean(Number(item.ativo) && Number(item.estoque) >= Number(item.quantidade));
  const row = element('article', 'bg-surface rounded-xl border border-outline/50 p-4 flex flex-col sm:flex-row gap-4 sm:items-center');
  const cover = element('div', 'w-20 h-28 shrink-0 bg-surface-soft rounded-lg flex items-center justify-center overflow-hidden');
  cover.append(createProductImageElement(item, 'w-full h-full object-cover rounded-lg'));
  const details = element('div', 'flex-1 min-w-0');
  details.append(
    element('h2', 'text-lg font-serif font-bold text-navy', item.nome || `Produto ${item.produto_id}`),
    element('p', 'text-xs text-sage mt-1', item.categoria_nome || 'Livro'),
    element('p', `text-xs mt-2 ${available ? 'text-muted' : 'text-error'}`, available ? `${item.estoque} em estoque` : 'Produto indisponível ou sem estoque'),
    element('p', 'text-sm font-semibold text-navy mt-2', formatPrice(item.preco)),
  );
  const actions = element('div', 'flex items-center gap-3 sm:flex-col sm:items-end');
  const controls = element('div', 'inline-flex items-center gap-3 border border-outline rounded-lg px-2 py-1');
  const decrement = element('button', 'w-7 h-7 text-navy font-bold', '−');
  const quantity = element('span', 'min-w-5 text-center text-sm font-semibold text-navy', String(item.quantidade));
  const increment = element('button', 'w-7 h-7 text-navy font-bold', '+');
  decrement.type = increment.type = 'button';
  decrement.disabled = !available;
  increment.disabled = !available || item.quantidade >= Number(item.estoque);
  decrement.addEventListener('click', () => updateQuantity(item, item.quantidade - 1));
  increment.addEventListener('click', () => updateQuantity(item, item.quantidade + 1));
  controls.append(decrement, quantity, increment);
  const remove = element('button', 'text-xs text-error hover:underline', 'Remover');
  remove.type = 'button'; remove.addEventListener('click', () => removeItem(item.produto_id));
  actions.append(controls, element('p', 'text-sm font-bold text-navy', formatPrice(Number(item.preco) * Number(item.quantidade))), remove);
  row.append(cover, details, actions);
  return row;
}

async function updateQuantity(item, quantity) {
  try {
    await setCartQuantity(Number(item.produto_id), Number(quantity));
    await initializeCart();
  } catch (error) {
    status.textContent = error.response?.data?.erro || error.message || 'Não foi possível atualizar a quantidade.';
  }
}

async function removeItem(productId) {
  try { await removeFromCart(productId); await initializeCart(); }
  catch { status.textContent = 'Não foi possível remover o item.'; }
}

async function initializeCart() {
  if (!isAuthenticated()) { loginRedirect('/carrinho/'); return; }
  try {
    const cart = await getCart();
    if (!cart.itens?.length) {
      status.textContent = 'Sua sacola está vazia.';
      itemsContainer.replaceChildren(element('div', 'bg-surface rounded-xl border border-outline/50 p-8 text-center lg:col-span-2', 'Escolha um livro para começar.'));
    } else {
      status.textContent = `${cart.quantidade} livro(s) na sacola.`;
      itemsContainer.replaceChildren(...cart.itens.map(renderItem));
    }
    renderSummary(cart);
  } catch { status.textContent = 'Não foi possível carregar sua sacola.'; }
}

window.addEventListener('cart:updated', initializeCart);
initializeCart();
