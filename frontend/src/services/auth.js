import api from './api.js';
import { clearAuthSession, getCurrentUser, saveAuthSession } from './authState.js';

export { getCurrentUser };

/** Autentica na API e guarda a sessão nesta aba ou de forma persistente. */
export async function login({ email, senha }, lembrar = false) {
  const { data } = await api.post('/auth/login', { email, senha });
  saveAuthSession(data, lembrar);

  return data.usuario;
}

export function logout() {
  clearAuthSession();
}
