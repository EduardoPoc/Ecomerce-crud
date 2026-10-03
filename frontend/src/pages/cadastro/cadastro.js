import '../../main.js';
import api from '../../services/api.js';
import { saveAuthSession } from '../../services/authState.js';

const form = document.querySelector('#signup-form');
const message = document.querySelector('#signup-message');
const submit = document.querySelector('#signup-submit');

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (!form.reportValidity()) return;
  submit.disabled = true;
  submit.textContent = 'Criando conta…';
  try {
    const { data } = await api.post('/auth/cadastro', {
      nome: document.querySelector('#nome').value.trim(),
      email: document.querySelector('#email').value.trim(),
      senha: document.querySelector('#senha').value,
    });
    saveAuthSession(data, false);
    const requestedRedirect = new URLSearchParams(window.location.search).get('redirect');
    window.location.assign(requestedRedirect?.startsWith('/') ? requestedRedirect : '/');
  } catch (error) {
    message.textContent = error.response?.data?.erro || 'Não foi possível criar a conta.';
    message.className = 'rounded-lg border border-error/30 bg-error/10 px-3 py-2.5 text-sm text-error';
  } finally {
    submit.disabled = false;
    submit.textContent = 'Criar conta';
  }
});
