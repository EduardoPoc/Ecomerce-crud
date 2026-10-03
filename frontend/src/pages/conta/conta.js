import '../../main.js';
import api from '../../services/api.js';
import { clearAuthSession, getCurrentUser, setCurrentUser } from '../../services/authState.js';
import { isAuthenticated, loginRedirect } from '../../services/cart.js';
import { formatPrice, repairMojibake } from '../../services/products.js';

const status = document.querySelector('#account-status');
const profileForm = document.querySelector('#profile-form');
const profileName = document.querySelector('#profile-name');
const profileEmail = document.querySelector('#profile-email');
const profileMessage = document.querySelector('#profile-message');
const addressList = document.querySelector('#address-list');
const addressForm = document.querySelector('#address-form');
const addressMessage = document.querySelector('#address-message');
const adminPanelLink = document.querySelector('#admin-panel-link');
const ordersCount = document.querySelector('#orders-count');
const ordersMessage = document.querySelector('#orders-message');
const ordersList = document.querySelector('#orders-list');

const ORDER_STATUS = {
  AGUARDANDO_PAGAMENTO: 'Aguardando pagamento',
  PAGO: 'Pago',
  ENVIADO: 'Enviado',
  ENTREGUE: 'Entregue',
  CANCELADO: 'Cancelado',
};

function formatOrderDate(value) {
  if (!value) return 'Data não informada';
  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
}

function statusClasses(statusValue) {
  if (statusValue === 'PAGO' || statusValue === 'ENTREGUE') return 'border-sage/30 bg-sage/10 text-sage';
  if (statusValue === 'CANCELADO') return 'border-error/30 bg-error/10 text-error';
  return 'border-amber/30 bg-amber/10 text-amber-700';
}

function renderOrders(orders) {
  ordersCount.textContent = orders.length ? `${orders.length} pedido${orders.length === 1 ? '' : 's'}` : '';
  if (!orders.length) {
    ordersMessage.textContent = 'Você ainda não realizou nenhum pedido.';
    ordersList.replaceChildren();
    return;
  }

  ordersMessage.textContent = '';
  ordersList.replaceChildren(...orders.map((order) => {
    const card = document.createElement('article');
    card.className = 'rounded-xl border border-outline/50 bg-surface-soft p-4 sm:p-5';

    const header = document.createElement('div');
    header.className = 'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3';
    const title = document.createElement('div');
    const orderNumber = document.createElement('p');
    orderNumber.className = 'font-semibold text-navy';
    orderNumber.textContent = `Pedido #${order.id}`;
    const date = document.createElement('p');
    date.className = 'text-sm text-muted mt-1';
    date.textContent = formatOrderDate(order.criado_em);
    title.append(orderNumber, date);

    const badge = document.createElement('span');
    badge.className = `self-start rounded-full border px-3 py-1 text-xs font-semibold ${statusClasses(order.status)}`;
    badge.textContent = ORDER_STATUS[order.status] || repairMojibake(order.status || 'Status desconhecido');
    header.append(title, badge);

    const footer = document.createElement('div');
    footer.className = 'flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mt-4 pt-3 border-t border-outline/50';
    const delivery = document.createElement('p');
    delivery.className = 'text-sm text-muted';
    delivery.textContent = order.destinatario
      ? `Entrega para ${repairMojibake(order.destinatario)} — ${repairMojibake(order.cidade || '')}/${order.uf || ''}`
      : 'Endereço de entrega não informado';
    const total = document.createElement('p');
    total.className = 'font-bold text-navy';
    total.textContent = `Total: ${formatPrice(order.total)}`;
    footer.append(delivery, total);

    card.append(header, footer);
    return card;
  }));
}

async function loadOrders() {
  try {
    const { data } = await api.get('/pedidos');
    renderOrders(Array.isArray(data) ? data : []);
  } catch {
    ordersCount.textContent = '';
    ordersMessage.textContent = 'Não foi possível carregar seu histórico de pedidos.';
    ordersList.replaceChildren();
  }
}

function showMessage(node, text, error = false) {
  node.textContent = text;
  node.className = `rounded-lg border px-3 py-2.5 text-sm ${error ? 'border-error/30 bg-error/10 text-error' : 'border-sage/30 bg-sage/10 text-sage'}`;
}

function renderAddresses(addresses) {
  if (!addresses.length) {
    addressList.replaceChildren(document.createTextNode('Nenhum endereço cadastrado.'));
    addressList.className = 'text-sm text-muted mt-5';
    return;
  }
  addressList.className = 'space-y-3 mt-5';
  addressList.replaceChildren(...addresses.map((address) => {
    const card = document.createElement('article');
    card.className = 'rounded-xl border border-outline/50 bg-surface-soft p-4';
    const title = document.createElement('p');
    title.className = 'font-semibold text-navy';
    title.textContent = repairMojibake(`${address.destinatario}${Number(address.padrao) ? ' · Padrão' : ''}`);
    const text = document.createElement('p');
    text.className = 'text-sm text-muted mt-1';
    text.textContent = `${address.logradouro}, ${address.numero}${address.complemento ? `, ${address.complemento}` : ''} — ${address.bairro}, ${address.cidade}/${address.uf} — CEP ${address.cep}`;
    const remove = document.createElement('button');
    remove.type = 'button'; remove.className = 'text-xs text-error hover:underline mt-3'; remove.textContent = 'Remover';
    remove.addEventListener('click', async () => { await removeAddress(address.id); });
    card.append(title, text, remove);
    return card;
  }));
}

async function loadAccount() {
  if (!isAuthenticated()) { loginRedirect('/conta/'); return; }
  try {
    const [{ data: user }, { data: addresses }] = await Promise.all([api.get('/auth/me'), api.get('/enderecos')]);
    profileName.value = repairMojibake(user.nome || '');
    profileEmail.value = user.email || '';
    if (user.papel === 'ADMIN') adminPanelLink.classList.remove('hidden');
    renderAddresses(addresses);
    await loadOrders();
    status.textContent = 'Dados da conta carregados.';
  } catch { status.textContent = 'Não foi possível carregar sua conta.'; }
}

profileForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  try {
    const { data } = await api.patch('/auth/me', { nome: profileName.value.trim() });
    setCurrentUser(data);
    showMessage(profileMessage, 'Nome atualizado com sucesso.');
  } catch (error) { showMessage(profileMessage, error.response?.data?.erro || 'Não foi possível atualizar seus dados.', true); }
});

addressForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  addressMessage.classList.add('hidden');
  try {
    await api.post('/enderecos', Object.fromEntries(new FormData(addressForm)));
    addressForm.reset(); addressForm.classList.add('hidden'); await loadAccount();
  } catch (error) {
    const message = error.response?.data?.erro || 'Não foi possível salvar o endereço.';
    addressMessage.textContent = message;
    addressMessage.className = 'rounded-lg border border-error/30 bg-error/10 px-3 py-2.5 text-sm text-error sm:col-span-2';
  }
});

async function removeAddress(id) {
  try { await api.delete(`/enderecos/${id}`); await loadAccount(); }
  catch (error) { status.textContent = error.response?.data?.erro || 'Não foi possível remover o endereço.'; }
}

document.querySelector('#new-address').addEventListener('click', () => addressForm.classList.remove('hidden'));
document.querySelector('#cancel-address').addEventListener('click', () => { addressForm.reset(); addressForm.classList.add('hidden'); });
document.querySelector('#logout').addEventListener('click', () => { clearAuthSession(); window.location.assign('/'); });
loadAccount();
