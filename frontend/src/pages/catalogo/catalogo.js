import '../../main.js';
import { getProducts, isProductActive, getProductStock, formatPrice } from '../../services/products.js';
import { addToCart, loginRedirect } from '../../services/cart.js';
import { createProductImageElement } from '../../utils/productImage.js';

const status = document.querySelector('#catalog-status');
const productList = document.querySelector('#product-list');
const pagination = document.querySelector('#catalog-pagination');
const PAGE_SIZE = 12;
let visibleProducts = [];
let currentPage = 1;

function makeElement(tag, className, text) {
  const element = document.createElement(tag);
  if (className) element.className = className;
  if (text !== undefined) element.textContent = text;
  return element;
}

function productImage(product) {
  const frame = makeElement('div', 'aspect-[3/4] bg-surface-soft rounded-lg overflow-hidden flex items-center justify-center');
  frame.append(createProductImageElement(product, 'w-full h-full object-cover'));
  return frame;
}

function renderProduct(product) {
  const stock = getProductStock(product);
  const available = stock > 0;
  const card = makeElement('article', 'bg-surface rounded-2xl border border-outline/50 p-4 shadow-card flex flex-col');
  const content = makeElement('div', 'pt-4 flex flex-col flex-1');
  const category = makeElement('span', 'text-xs font-semibold text-sage', product.categoria_nome || 'Livros');
  const title = makeElement('h2', 'text-lg font-serif font-bold text-navy mt-1', product.nome || 'Livro sem título');
  const description = makeElement('p', 'text-sm text-muted mt-2 line-clamp-3', product.descricao || 'Sem descrição disponível.');
  const price = makeElement('p', 'text-lg font-bold text-navy mt-4', formatPrice(product.preco));
  const stockText = makeElement('p', 'text-xs text-muted mt-1', available ? `${stock} em estoque` : 'Indisponível');
  const button = makeElement('button', 'mt-4 w-full rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors');
  button.type = 'button';
  button.textContent = available ? 'Adicionar à sacola' : 'Indisponível';
  button.disabled = !available;
  button.classList.add(available ? 'bg-navy' : 'bg-outline/50', available ? 'text-surface' : 'text-muted');
  button.setAttribute('aria-label', `${available ? 'Adicionar' : 'Indisponível'}: ${product.nome || 'livro'}`);

  const defaultLabel = 'Adicionar à sacola';
  let feedbackTimer;

  button.addEventListener('click', async () => {
    button.disabled = true;
    status.textContent = 'Adicionando à sacola…';
    clearTimeout(feedbackTimer);
    try {
      await addToCart(product, 1);
      button.textContent = 'Adicionado à sacola';
      button.classList.remove('bg-navy');
      button.classList.add('bg-sage');
      feedbackTimer = setTimeout(() => {
        button.textContent = defaultLabel;
        button.classList.remove('bg-sage');
        button.classList.add('bg-navy');
      }, 2000);
    } catch (error) {
      if (error.response?.status === 401) {
        loginRedirect();
        return;
      }
      const message = error.response?.data?.erro || error.message || 'Não foi possível adicionar';
      button.textContent = message;
      status.textContent = message;
      feedbackTimer = setTimeout(() => { button.textContent = defaultLabel; }, 2000);
    } finally {
      button.disabled = false;
    }
  });

  content.append(category, title, description, price, stockText, button);
  card.append(productImage(product), content);
  return card;
}

function renderPagination() {
  const pageCount = Math.ceil(visibleProducts.length / PAGE_SIZE);
  pagination.replaceChildren();

  if (pageCount <= 1) {
    pagination.hidden = true;
    return;
  }

  pagination.hidden = false;

  const previous = makeElement('button', 'rounded-lg border border-outline px-4 py-2 text-sm font-semibold text-navy transition-colors hover:bg-surface-soft disabled:cursor-not-allowed disabled:opacity-50', 'Anterior');
  previous.type = 'button';
  previous.disabled = currentPage === 1;
  previous.setAttribute('aria-controls', 'product-list');
  previous.addEventListener('click', () => {
    currentPage -= 1;
    renderCatalogPage();
  });

  const pageLabel = makeElement('span', 'min-w-28 text-center text-sm text-muted', `Página ${currentPage} de ${pageCount}`);
  pageLabel.setAttribute('aria-live', 'polite');

  const next = makeElement('button', 'rounded-lg border border-outline px-4 py-2 text-sm font-semibold text-navy transition-colors hover:bg-surface-soft disabled:cursor-not-allowed disabled:opacity-50', 'Próxima');
  next.type = 'button';
  next.disabled = currentPage === pageCount;
  next.setAttribute('aria-controls', 'product-list');
  next.addEventListener('click', () => {
    currentPage += 1;
    renderCatalogPage();
  });

  pagination.replaceChildren(previous, pageLabel, next);
}

function renderCatalogPage() {
  const start = (currentPage - 1) * PAGE_SIZE;
  const pageProducts = visibleProducts.slice(start, start + PAGE_SIZE);
  productList.replaceChildren(...pageProducts.map(renderProduct));
  renderPagination();
}

async function renderCatalog() {
  let products;

  try {
    products = await getProducts();
  } catch (error) {
    status.textContent = error.response
      ? 'Não foi possível carregar o catálogo. Tente novamente mais tarde.'
      : error.request
        ? 'Não foi possível conectar à API. Confirme que o backend e o banco de dados estão em execução.'
        : 'A API retornou uma resposta inválida para o catálogo.';
    return;
  }

  visibleProducts = products.filter(isProductActive);
  currentPage = 1;

  try {
    renderCatalogPage();
    status.textContent = visibleProducts.length === 0
      ? 'Não há livros disponíveis no catálogo neste momento.'
      : `${visibleProducts.length} livros no catálogo. Itens sem estoque aparecem como indisponíveis.`;
  } catch {
    status.textContent = 'Não foi possível exibir o catálogo. Atualize a página para tentar novamente.';
  }
}

renderCatalog();
