import '../../main.js';
import api from '../../services/api.js';
import { clearAuthSession, getCurrentUser, setCurrentUser } from '../../services/authState.js';
import { isAuthenticated, loginRedirect } from '../../services/cart.js';
import { repairMojibake } from '../../services/products.js';

const status = document.querySelector('#account-status');
const profileForm = document.querySelector('#profile-form');
const profileName = document.querySelector('#profile-name');
const profileEmail = document.querySelector('#profile-email');
const profileMessage = document.querySelector('#profile-message');
const addressList = document.querySelector('#address-list');
const addressForm = document.querySelector('#address-form');
const addressMessage = document.querySelector('#address-message');
const adminPanelLink = document.querySelector('#admin-panel-link');

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
