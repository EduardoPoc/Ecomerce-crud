import '../../main.js';
import { getProducts, isProductActive, getProductStock, formatPrice } from '../../services/products.js';
import { addToCart, getCartItems } from '../../services/cart.js';
import { initIcons } from '../../utils/icons.js';

const status = document.querySelector('#catalog-status');
const productList = document.querySelector('#product-list');

function makeElement(tag, className, text) {
  const element = document.createElement(tag);
  if (className) element.className = className;
  if (text !== undefined) element.textContent = text;
  return element;
}

function productImage(product) {
  const frame = makeElement('div', 'aspect-[3/4] bg-surface-soft rounded-lg overflow-hidden flex items-center justify-center');
  const source = typeof product.imagem_url === 'string' ? product.imagem_url.trim() : '';

  if (source) {
    try {
      const imageUrl = new URL(source, window.location.origin);
      if (imageUrl.protocol === 'http:' || imageUrl.protocol === 'https:') {
        const image = makeElement('img', 'w-full h-full object-cover');
        image.src = imageUrl.href;
        image.alt = `Capa de ${product.nome}`;
        image.loading = 'lazy';
        image.addEventListener('error', () => {
          const icon = makeElement('i', 'w-10 h-10 text-sage');
          icon.dataset.lucide = 'book-open';
          frame.replaceChildren(icon);
          initIcons({ root: frame });
        }, { once: true });
        frame.append(image);
        return frame;
      }
    } catch {
      // URL inválida: exibe o ícone de capa ausente.
    }
  }

  const icon = makeElement('i', 'w-10 h-10 text-sage');
  icon.dataset.lucide = 'book-open';
  frame.append(icon);
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

  const currentQuantity = () => getCartItems().find((item) => item.produto_id === Number(product.id))?.quantidade || 0;
  if (currentQuantity() > 0 && available) button.textContent = `Na sacola (${currentQuantity()}) · Adicionar`;

  button.addEventListener('click', () => {
    const added = addToCart(product, stock);
    button.textContent = added
      ? `Na sacola (${currentQuantity()}) · Adicionar`
      : `Limite em estoque (${stock})`;
  });

  content.append(category, title, description, price, stockText, button);
  card.append(productImage(product), content);
  return card;
}

async function renderCatalog() {
  try {
    const products = await getProducts();
    const visibleProducts = products.filter(isProductActive);
    productList.replaceChildren(...visibleProducts.map(renderProduct));

    status.textContent = visibleProducts.length === 0
      ? 'Não há livros disponíveis no catálogo neste momento.'
      : `${visibleProducts.length} livros no catálogo. Itens sem estoque aparecem como indisponíveis.`;
  } catch (error) {
    status.textContent = error.response
      ? 'Não foi possível carregar o catálogo. Tente novamente mais tarde.'
      : 'Não foi possível conectar à API. Confirme que o backend e o banco de dados estão em execução.';
  }
}

renderCatalog();
