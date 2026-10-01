const CART_KEY = 'alem-da-estante-cart-v1';

function readCart() {
  try {
    const parsed = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
    if (!Array.isArray(parsed)) return [];

    return parsed.filter((item) =>
      Number.isSafeInteger(Number(item?.produto_id)) && Number(item.produto_id) > 0 &&
      Number.isSafeInteger(Number(item?.quantidade)) && Number(item.quantidade) > 0
    ).map((item) => ({
      produto_id: Number(item.produto_id),
      quantidade: Number(item.quantidade),
    }));
  } catch {
    return [];
  }
}

function writeCart(items) {
  localStorage.setItem(CART_KEY, JSON.stringify(items));
  window.dispatchEvent(new CustomEvent('cart:updated', { detail: { items } }));
}

export function getCartItems() {
  return readCart();
}

export function getCartCount() {
  return readCart().reduce((total, item) => total + item.quantidade, 0);
}

export function addToCart(product, maxStock) {
  const productId = Number(product?.id);
  const stock = Math.max(0, Math.floor(Number(maxStock) || 0));
  if (!Number.isSafeInteger(productId) || productId <= 0 || stock < 1) return false;

  const items = readCart();
  const existing = items.find((item) => item.produto_id === productId);
  if (existing) {
    if (existing.quantidade >= stock) return false;
    existing.quantidade += 1;
  } else {
    items.push({ produto_id: productId, quantidade: 1 });
  }

  writeCart(items);
  return true;
}

export function setCartQuantity(productId, quantity, maxStock) {
  const id = Number(productId);
  const nextQuantity = Math.floor(Number(quantity));
  const stock = Math.max(0, Math.floor(Number(maxStock) || 0));
  const items = readCart();
  const item = items.find((entry) => entry.produto_id === id);
  if (!item) return false;

  if (nextQuantity < 1) return removeFromCart(id);
  if (stock < 1 || nextQuantity > stock) return false;

  item.quantidade = nextQuantity;
  writeCart(items);
  return true;
}

export function removeFromCart(productId) {
  const id = Number(productId);
  const items = readCart().filter((item) => item.produto_id !== id);
  writeCart(items);
}

export function clearCart() {
  writeCart([]);
}
