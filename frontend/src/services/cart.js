import api from './api.js';

const EMPTY_CART = { id: null, itens: [], quantidade: 0, subtotal: 0 };

export function isAuthenticated() {
  return Boolean(sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token'));
}

export function loginRedirect(path = `${window.location.pathname}${window.location.search}`) {
  const redirect = path.startsWith('/') ? path : '/';
  window.location.assign(`/login/?redirect=${encodeURIComponent(redirect)}`);
}

export async function getCart() {
  if (!isAuthenticated()) return EMPTY_CART;
  const { data } = await api.get('/carrinho');
  return data;
}

export async function getCartItems() {
  return (await getCart()).itens || [];
}

export async function getCartCount() {
  return Number((await getCart()).quantidade || 0);
}

export async function addToCart(product, quantity = 1) {
  const productId = Number(product?.id);
  const itemQuantity = Number(quantity) > 0 ? Number(quantity) : 1;
  const { data } = await api.post('/carrinho/itens', { produto_id: productId, quantidade: itemQuantity });
  notifyCartUpdated(data);
  return data;
}

export async function setCartQuantity(productId, quantity) {
  if (Number(quantity) <= 0) return removeFromCart(productId);
  const { data } = await api.patch(`/carrinho/itens/${Number(productId)}`, { quantidade: Number(quantity) });
  notifyCartUpdated(data);
  return data;
}

export async function removeFromCart(productId) {
  const { data } = await api.delete(`/carrinho/itens/${Number(productId)}`);
  notifyCartUpdated(data);
  return data;
}

function notifyCartUpdated(cart) {
  window.dispatchEvent(new CustomEvent('cart:updated', { detail: { cart } }));
}
