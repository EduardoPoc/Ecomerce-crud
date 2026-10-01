import api from './api.js';

const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';

/** Autentica na API e guarda a sessão nesta aba ou de forma persistente. */
export async function login({ email, senha }, lembrar = false) {
  const { data } = await api.post('/auth/login', { email, senha });
  const storage = lembrar ? localStorage : sessionStorage;
  const otherStorage = lembrar ? sessionStorage : localStorage;

  otherStorage.removeItem(TOKEN_KEY);
  otherStorage.removeItem(USER_KEY);
  storage.setItem(TOKEN_KEY, data.token);
  storage.setItem(USER_KEY, JSON.stringify(data.usuario));

  return data.usuario;
}

export function getCurrentUser() {
  const rawUser = sessionStorage.getItem(USER_KEY) || localStorage.getItem(USER_KEY);
  if (!rawUser) return null;

  try {
    return JSON.parse(rawUser);
  } catch {
    logout();
    return null;
  }
}

export function logout() {
  for (const storage of [sessionStorage, localStorage]) {
    storage.removeItem(TOKEN_KEY);
    storage.removeItem(USER_KEY);
  }
}
