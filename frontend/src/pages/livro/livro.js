import '../../main.js';
import { getProduct, getProductStock, formatPrice } from '../../services/products.js';
import { addToCart, loginRedirect } from '../../services/cart.js';
import { createProductImageElement } from '../../utils/productImage.js';

const status = document.querySelector('#book-status');
const detail = document.querySelector('#book-detail');
const productId = window.location.pathname.match(/^\/livro\/(\d+)\/?$/)?.[1];

function node(tag, className, text) {
  const element = document.createElement(tag);
  element.className = className;
  if (text !== undefined) element.textContent = text;
  return element;
}

async function load() {
  if (!productId) { status.textContent = 'Livro não encontrado.'; return; }
  try {
    const product = await getProduct(productId);
    const stock = getProductStock(product);
    const imageFrame = node('div', 'aspect-[3/4] max-w-sm w-full bg-surface-soft rounded-xl overflow-hidden');
    imageFrame.append(createProductImageElement(product, 'w-full h-full object-cover'));
    const content = node('div', 'flex flex-col');
    content.append(
      node('p', 'text-xs font-bold tracking-widest text-sage uppercase', product.categoria_nome || 'Livro'),
      node('h1', 'text-3xl sm:text-4xl font-serif font-bold text-navy mt-2', product.nome),
      node('p', 'text-2xl font-bold text-navy mt-5', formatPrice(product.preco)),
      node('p', 'text-sm text-muted leading-relaxed mt-5 whitespace-pre-line', product.descricao || 'Sem descrição disponível.'),
      node('p', 'text-sm text-muted mt-5', stock > 0 ? `${stock} em estoque` : 'Indisponível'),
    );
    const add = node('button', `rounded-lg px-5 py-3 mt-6 text-sm font-semibold ${stock > 0 ? 'bg-navy text-surface' : 'bg-outline/50 text-muted cursor-not-allowed'}`, stock > 0 ? 'Adicionar à sacola' : 'Indisponível');
    add.type = 'button'; add.disabled = stock <= 0;
    add.addEventListener('click', async () => {
      add.disabled = true; add.textContent = 'Adicionando…';
      try { await addToCart(product, 1); add.textContent = 'Adicionado à sacola'; setTimeout(() => { add.textContent = 'Adicionar à sacola'; add.disabled = false; }, 2000); }
      catch (error) { if (error.response?.status === 401) { loginRedirect(`/livro/${productId}/`); return; } add.textContent = error.response?.data?.erro || 'Não foi possível adicionar'; setTimeout(() => { add.textContent = 'Adicionar à sacola'; add.disabled = false; }, 2000); }
    });
    content.append(add);
    detail.append(imageFrame, content); detail.classList.remove('hidden');
    document.title = `Além da Estante | ${product.nome}`;
    status.textContent = 'Detalhes do livro.';
  } catch (error) { status.textContent = error.response?.status === 404 ? 'Livro não encontrado.' : 'Não foi possível carregar este livro.'; }
}

load();
