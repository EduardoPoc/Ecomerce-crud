import '../../main.js';
import api from '../../services/api.js';
import { clearAuthSession, getCurrentUser, setCurrentUser } from '../../services/authState.js';
import { formatPrice, repairMojibake } from '../../services/products.js';
import { isAuthenticated, loginRedirect } from '../../services/cart.js';

const status = document.querySelector('#admin-status');
const profile = document.querySelector('#admin-profile');
const list = document.querySelector('#product-list');
const form = document.querySelector('#product-form');
const message = document.querySelector('#product-message');
const formTitle = document.querySelector('#form-title');
const cancelEdit = document.querySelector('#cancel-edit');
const saveButton = document.querySelector('#save-product');
const fields = Object.fromEntries([...form.elements].filter((field) => field.name).map((field) => [field.name, field]));
let products = [];

function showMessage(text, error = false) {
  message.textContent = text;
  message.className = `rounded-lg border px-3 py-2.5 text-sm ${error ? 'border-error/30 bg-error/10 text-error' : 'border-sage/30 bg-sage/10 text-sage'}`;
}

function apiError(error, fallback) {
  return repairMojibake(error.response?.data?.erro || error.response?.data?.message || fallback);
}

function isActive(product) {
  return product.ativo === true || product.ativo === 1 || product.ativo === '1';
}

function renderProducts() {
  if (!products.length) {
    list.innerHTML = '<p class="p-5 text-sm text-muted">Nenhum livro cadastrado.</p>';
    return;
  }
  list.replaceChildren(...products.map((product) => {
    const row = document.createElement('article');
    row.className = 'p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4';
    const info = document.createElement('div');
    info.className = 'min-w-0';
    const name = document.createElement('h3');
    name.className = 'font-semibold text-navy truncate';
    name.textContent = repairMojibake(product.nome);
    const details = document.createElement('p');
    details.className = 'text-sm text-muted mt-1';
    details.textContent = `${repairMojibake(product.categoria_nome || 'Sem categoria')} · ${formatPrice(product.preco)} · Estoque: ${product.estoque}`;
    const state = document.createElement('span');
    state.className = `inline-flex mt-2 rounded-full px-2 py-1 text-xs font-semibold ${isActive(product) ? 'bg-sage/15 text-sage' : 'bg-error/10 text-error'}`;
    state.textContent = isActive(product) ? 'Ativo' : 'Inativo';
    info.append(name, details, state);
    const actions = document.createElement('div');
    actions.className = 'flex shrink-0 gap-3';
    const edit = document.createElement('button');
    edit.type = 'button'; edit.className = 'text-sm font-semibold text-navy hover:underline'; edit.textContent = 'Editar';
    edit.addEventListener('click', () => fillForm(product));
    const toggle = document.createElement('button');
    toggle.type = 'button'; toggle.className = 'text-sm font-semibold text-error hover:underline'; toggle.textContent = isActive(product) ? 'Desativar' : 'Ativar';
    toggle.addEventListener('click', () => toggleProduct(product));
    actions.append(edit, toggle); row.append(info, actions); return row;
  }));
}

function fillForm(product = null) {
  form.reset();
  fields.id?.remove();
  if (product) {
    const id = document.createElement('input'); id.type = 'hidden'; id.name = 'id'; id.value = product.id; form.prepend(id); fields.id = id;
    fields.nome.value = repairMojibake(product.nome || '');
    fields.categoria_id.value = product.categoria_id;
    fields.descricao.value = repairMojibake(product.descricao || '');
    fields.preco.value = product.preco;
    fields.estoque.value = product.estoque;
    fields.imagem_url.value = product.imagem_url || '';
    fields.ativo.checked = isActive(product);
    formTitle.textContent = `Editar livro #${product.id}`;
    cancelEdit.classList.remove('hidden');
  } else {
    fields.ativo.checked = true;
    formTitle.textContent = 'Novo livro';
    cancelEdit.classList.add('hidden');
  }
  message.classList.add('hidden');
}

async function toggleProduct(product) {
  const action = isActive(product) ? 'desativar' : 'ativar';
  if (!window.confirm(`Deseja ${action} “${repairMojibake(product.nome)}”?`)) return;
  try {
    await api.put(`/produtos/${product.id}`, payload(product, !isActive(product)));
    await loadProducts();
    status.textContent = `Livro ${action === 'ativar' ? 'ativado' : 'desativado'} com sucesso.`;
  } catch (error) { status.textContent = apiError(error, `Não foi possível ${action} o livro.`); }
}

function payload(source = null, active = fields.ativo.checked) {
  const fromProduct = source !== null;
  return {
    categoria_id: Number(fromProduct ? source.categoria_id : fields.categoria_id.value),
    nome: fromProduct ? source.nome : fields.nome.value.trim(),
    descricao: fromProduct ? (source.descricao || '') : fields.descricao.value.trim(),
    preco: Number(fromProduct ? source.preco : fields.preco.value),
    estoque: Number(fromProduct ? source.estoque : fields.estoque.value),
    imagem_url: fromProduct ? (source.imagem_url || '') : fields.imagem_url.value.trim(),
    ativo: active,
  };
}

async function loadProducts() {
  const { data } = await api.get('/produtos');
  products = Array.isArray(data) ? data : [];
  renderProducts();
}

async function loadAdmin() {
  if (!isAuthenticated()) { loginRedirect('/admin/'); return; }
  try {
    const [{ data: user }, { data: categories }] = await Promise.all([api.get('/auth/me'), api.get('/categorias')]);
    if (user.papel !== 'ADMIN') { window.location.assign('/'); return; }
    setCurrentUser(user);
    profile.textContent = `${repairMojibake(user.nome)} · ${user.email} · Administrador`; 
    const options = Array.isArray(categories) ? categories : [];
    fields.categoria_id.replaceChildren(new Option('Selecione uma categoria', ''));
    options.forEach((category) => fields.categoria_id.add(new Option(repairMojibake(category.nome), category.id)));
    await loadProducts();
    status.textContent = 'Livros carregados.';
  } catch (error) {
    if (error.response?.status === 403) { window.location.assign('/'); return; }
    status.textContent = apiError(error, 'Não foi possível carregar o painel.');
  }
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (!form.reportValidity()) return;
  saveButton.disabled = true; saveButton.textContent = 'Salvando…';
  try {
    const id = fields.id?.value;
    const response = id ? await api.put(`/produtos/${id}`, payload()) : await api.post('/produtos', payload());
    await loadProducts(); fillForm(); showMessage(repairMojibake(response.data?.message || 'Livro salvo com sucesso.'));
  } catch (error) { showMessage(apiError(error, 'Não foi possível salvar o livro.'), true); }
  finally { saveButton.disabled = false; saveButton.textContent = 'Salvar livro'; }
});

document.querySelector('#new-product').addEventListener('click', () => { fillForm(); fields.nome.focus(); });
cancelEdit.addEventListener('click', () => fillForm());
document.querySelector('#logout')?.addEventListener('click', () => { clearAuthSession(); window.location.assign('/'); });
loadAdmin();
