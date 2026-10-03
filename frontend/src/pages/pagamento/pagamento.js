import '../../main.js';
import api from '../../services/api.js';
import { getCart, isAuthenticated, loginRedirect } from '../../services/cart.js';
import { formatPrice, repairMojibake } from '../../services/products.js';

const status = document.querySelector('#checkout-status');
const itemList = document.querySelector('#checkout-items');
const countLabel = document.querySelector('#checkout-count');
const subtotalLabel = document.querySelector('#checkout-subtotal');
const addressSelect = document.querySelector('#address-select');
const addressStatus = document.querySelector('#address-status');
const addressForm = document.querySelector('#address-form');
const newAddressButton = document.querySelector('#new-address');
const submitButton = document.querySelector('#checkout-submit');
let selectedAddressId = null;

function element(tag, classes, text) {
  const node = document.createElement(tag);
  if (classes) node.className = classes;
  if (text !== undefined) node.textContent = text;
  return node;
}

function renderCart(cart) {
  countLabel.textContent = String(cart.quantidade || 0);
  subtotalLabel.textContent = formatPrice(cart.subtotal || 0);
  itemList.replaceChildren(...(cart.itens || []).map((item) => {
    const name = repairMojibake(item.nome);
    const row = element('li', 'py-4 flex items-start justify-between gap-4');
    row.append(
      element('div', 'min-w-0', `${name} — ${item.quantidade} × ${formatPrice(item.preco)}`),
      element('p', 'text-sm font-bold text-navy whitespace-nowrap', formatPrice(item.subtotal)),
    );
    return row;
  }));
}

async function loadAddresses() {
  const { data } = await api.get('/enderecos');
  addressSelect.replaceChildren();
  data.forEach((address) => {
    const option = document.createElement('option');
    option.value = address.id;
    option.textContent = `${address.destinatario} — ${address.logradouro}, ${address.numero} — ${address.cidade}/${address.uf}`;
    addressSelect.append(option);
  });
  selectedAddressId = data[0]?.id || null;
  addressSelect.value = selectedAddressId || '';
  addressStatus.textContent = data.length ? 'Endereço selecionado para entrega.' : 'Cadastre um endereço para continuar.';
  submitButton.disabled = !selectedAddressId;
}

async function saveAddress(event) {
  event.preventDefault();
  const data = Object.fromEntries(new FormData(addressForm));
  data.padrao = true;
  try {
    const { data: address } = await api.post('/enderecos', data);
    addressForm.classList.add('hidden');
    await loadAddresses();
    addressSelect.value = address.id;
    selectedAddressId = address.id;
  } catch (error) {
    addressStatus.textContent = error.response?.data?.erro || 'Não foi possível salvar o endereço.';
  }
}

async function pay() {
  if (!selectedAddressId) return;
  submitButton.disabled = true;
  submitButton.textContent = 'Processando pagamento…';
  let orderId = null;
  try {
    const { data: order } = await api.post('/pedidos', { endereco_id: Number(selectedAddressId) });
    orderId = order.id;
    await api.post(`/pedidos/${orderId}/pagar`);
    window.location.assign(`/finalizado/?pedido=${orderId}`);
  } catch (error) {
    if (orderId) {
      try { await api.delete(`/pedidos/${orderId}`); } catch { /* mantém o erro original para o usuário */ }
    }
    status.textContent = error.response?.data?.erro || 'Não foi possível concluir o pagamento simulado.';
    submitButton.disabled = false;
    submitButton.textContent = 'Pagar e confirmar pedido';
  }
}

async function initialize() {
  if (!isAuthenticated()) { loginRedirect('/pagamento/'); return; }
  try {
    const cart = await getCart();
    if (!cart.itens?.length) {
      status.textContent = 'Sua sacola está vazia. Adicione livros antes de revisar a compra.';
      submitButton.disabled = true;
      return;
    }
    renderCart(cart);
    await loadAddresses();
    status.textContent = 'Revise os itens e confirme o pagamento simulado.';
  } catch {
    status.textContent = 'Não foi possível carregar a revisão da compra.';
  }
}

addressSelect.addEventListener('change', () => { selectedAddressId = Number(addressSelect.value) || null; submitButton.disabled = !selectedAddressId; });
newAddressButton.addEventListener('click', () => addressForm.classList.toggle('hidden'));
addressForm.addEventListener('submit', saveAddress);
submitButton.addEventListener('click', pay);
initialize();
