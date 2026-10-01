import api from './api.js';

/** Retorna produtos no formato exposto por ProdutoRepository. */
export async function getProducts() {
  const { data } = await api.get('/produtos');
  if (!Array.isArray(data)) {
    throw new Error('A resposta de produtos da API está em um formato inesperado.');
  }
  return data;
}

export function isProductActive(product) {
  return product.ativo === true || product.ativo === 1 || product.ativo === '1';
}

export function getProductStock(product) {
  const stock = Number(product.estoque);
  return Number.isFinite(stock) ? Math.max(0, Math.floor(stock)) : 0;
}

export function formatPrice(value) {
  const price = Number(value);
  if (!Number.isFinite(price)) return 'Preço indisponível';
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(price);
}
