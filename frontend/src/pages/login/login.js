import '../../main.js';
import { login } from '../../services/auth.js';
import { initIcons } from '../../utils/icons.js';

const form = document.querySelector('#login-form');
const emailInput = document.querySelector('#email');
const passwordInput = document.querySelector('#senha');
const rememberInput = document.querySelector('#lembrar');
const message = document.querySelector('#login-message');
const submitButton = document.querySelector('#login-submit');
const submitLabel = document.querySelector('#login-submit-label');
const passwordToggle = document.querySelector('#toggle-password');
const signupLink = document.querySelector('#signup-link');
const redirectParam = new URLSearchParams(window.location.search).get('redirect');

if (redirectParam?.startsWith('/') && signupLink) {
  signupLink.href = `/cadastro/?redirect=${encodeURIComponent(redirectParam)}`;
}

function showMessage(text, type = 'error') {
  message.textContent = text;
  message.classList.remove('hidden', 'border-error/30', 'bg-error/10', 'text-error', 'border-sage/30', 'bg-sage/10', 'text-sage');
  message.classList.add(...(type === 'success'
    ? ['border-sage/30', 'bg-sage/10', 'text-sage']
    : ['border-error/30', 'bg-error/10', 'text-error']));
}

function errorMessage(error) {
  if (!error.response) {
    return 'Não foi possível conectar à livraria. Verifique se a API está em execução e tente novamente.';
  }

  const { status, data } = error.response;
  if (status === 401) return 'E-mail ou senha inválidos.';
  if (status === 422) return data?.erro || 'Confira os dados informados e tente novamente.';
  if (status >= 500) return 'A API encontrou um problema. Tente novamente em instantes.';
  return data?.erro || 'Não foi possível entrar. Tente novamente.';
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  message.classList.add('hidden');
  if (!form.reportValidity()) return;

  submitButton.disabled = true;
  submitButton.setAttribute('aria-busy', 'true');
  submitLabel.textContent = 'Entrando...';
  const submitIcon = submitButton.querySelector('[data-lucide]');
  submitIcon.dataset.lucide = 'loader-circle';
  submitIcon.classList.add('animate-spin');
  initIcons({ root: submitButton });

  try {
    await login({ email: emailInput.value.trim(), senha: passwordInput.value }, rememberInput.checked);
    showMessage('Login realizado. Redirecionando...', 'success');
    const redirect = redirectParam?.startsWith('/') ? redirectParam : '/';
    window.location.assign(redirect);
  } catch (error) {
    showMessage(errorMessage(error));
  } finally {
    submitButton.disabled = false;
    submitButton.removeAttribute('aria-busy');
    submitLabel.textContent = 'Entrar na conta';
    const currentIcon = submitButton.querySelector('[data-lucide]');
    currentIcon.dataset.lucide = 'arrow-right';
    currentIcon.classList.remove('animate-spin');
    initIcons({ root: submitButton });
  }
});

passwordToggle.addEventListener('click', () => {
  const isVisible = passwordInput.type === 'text';
  passwordInput.type = isVisible ? 'password' : 'text';
  passwordToggle.setAttribute('aria-pressed', String(!isVisible));
  passwordToggle.setAttribute('aria-label', isVisible ? 'Mostrar senha' : 'Ocultar senha');
  passwordToggle.querySelector('[data-lucide]')?.setAttribute('data-lucide', isVisible ? 'eye' : 'eye-off');
  initIcons({ root: passwordToggle });
  passwordInput.focus();
});
