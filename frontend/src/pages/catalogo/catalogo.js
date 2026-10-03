import '../../main.js';
import api from '../../services/api.js';
import { getProducts, getProductStock, formatPrice, repairMojibake } from '../../services/products.js';
import { addToCart, loginRedirect } from '../../services/cart.js';
import { createProductImageElement } from '../../utils/productImage.js';

const status = document.querySelector('#catalog-status');
const productList = document.querySelector('#product-list');
const pagination = document.querySelector('#catalog-pagination');
const searchInput = document.querySelector('#catalog-search');
const categorySelect = document.querySelector('#catalog-category');
const sortSelect = document.querySelector('#catalog-sort');
const clearFiltersButton = document.querySelector('#clear-filters');
const PAGE_SIZE = 12;
let totalProducts = 0;
let currentProducts = [];
let currentPage = 1;
let requestTimer;

function normalizeSearch(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('pt-BR')
    .trim();
}

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
  const detailsLink = makeElement('a', 'text-sm font-semibold text-sage hover:underline mt-3', 'Ver detalhes');
  detailsLink.href = `/livro/${product.id}/`;
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

  content.append(category, title, description, price, stockText, detailsLink, button);
  card.append(productImage(product), content);
  return card;
}

function renderPagination() {
  const pageCount = Math.ceil(totalProducts / PAGE_SIZE);
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
    loadCatalog(false);
  });

  const pageLabel = makeElement('span', 'min-w-28 text-center text-sm text-muted', `Página ${currentPage} de ${pageCount}`);
  pageLabel.setAttribute('aria-live', 'polite');

  const next = makeElement('button', 'rounded-lg border border-outline px-4 py-2 text-sm font-semibold text-navy transition-colors hover:bg-surface-soft disabled:cursor-not-allowed disabled:opacity-50', 'Próxima');
  next.type = 'button';
  next.disabled = currentPage === pageCount;
  next.setAttribute('aria-controls', 'product-list');
  next.addEventListener('click', () => {
    currentPage += 1;
    loadCatalog(false);
  });

  pagination.replaceChildren(previous, pageLabel, next);
}

function renderCatalogPage() {
  productList.replaceChildren(...currentProducts.map(renderProduct));
  renderPagination();
}

function updateCatalogStatus(page) {
  if (!page.itens.length) {
    status.textContent = totalProducts
      ? 'Nenhum livro corresponde aos filtros selecionados.'
      : 'Não há livros disponíveis no catálogo neste momento.';
    return;
  }
  const hasFilters = normalizeSearch(searchInput.value) || categorySelect.value || sortSelect.value !== 'relevancia';
  status.textContent = hasFilters
    ? `${totalProducts} livro(s) encontrado(s).`
    : `${totalProducts} livros no catálogo. Itens sem estoque aparecem como indisponíveis.`;
}

async function populateCategories() {
  const { data } = await api.get('/categorias');
  categorySelect.replaceChildren(new Option('Todas as categorias', ''));
  data.forEach((category) => categorySelect.add(new Option(repairMojibake(category.nome), category.id)));
}

async function loadCatalog(resetPage = true) {
  if (resetPage) currentPage = 1;
  status.textContent = 'Carregando livros…';

  try {
    const page = await getProducts({
      pagina: currentPage,
      limite: PAGE_SIZE,
      busca: searchInput.value.trim(),
      categoria_id: categorySelect.value || undefined,
      ordenar: sortSelect.value,
    });
    totalProducts = page.total;
    currentProducts = page.itens;
    renderCatalogPage();
    updateCatalogStatus(page);
  } catch (error) {
    status.textContent = error.response
      ? 'Não foi possível carregar o catálogo. Tente novamente mais tarde.'
      : error.request
        ? 'Não foi possível conectar à API. Confirme que o backend e o banco de dados estão em execução.'
        : 'A API retornou uma resposta inválida para o catálogo.';
    return;
  }
}

function scheduleSearch() {
  clearTimeout(requestTimer);
  requestTimer = setTimeout(() => loadCatalog(), 300);
}

searchInput.addEventListener('input', scheduleSearch);
categorySelect.addEventListener('change', () => loadCatalog());
sortSelect.addEventListener('change', () => loadCatalog());
clearFiltersButton.addEventListener('click', () => {
  searchInput.value = '';
  categorySelect.value = '';
  sortSelect.value = 'relevancia';
  loadCatalog();
  searchInput.focus();
});

async function initialize() {
  try { await populateCategories(); } catch { status.textContent = 'Não foi possível carregar as categorias.'; }
  await loadCatalog();
}

initialize();
