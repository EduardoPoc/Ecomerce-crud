const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';

export function getAuthToken() {
  return sessionStorage.getItem(TOKEN_KEY) || localStorage.getItem(TOKEN_KEY);
}

export function getCurrentUser() {
  const rawUser = sessionStorage.getItem(USER_KEY) || localStorage.getItem(USER_KEY);
  if (!rawUser) return null;

  try {
    return JSON.parse(rawUser);
  } catch {
    clearAuthSession();
    return null;
  }
}

export function saveAuthSession(data, remember = false) {
  const storage = remember ? localStorage : sessionStorage;
  const otherStorage = remember ? sessionStorage : localStorage;

  otherStorage.removeItem(TOKEN_KEY);
  otherStorage.removeItem(USER_KEY);
  storage.setItem(TOKEN_KEY, data.token);
  storage.setItem(USER_KEY, JSON.stringify(data.usuario));
  notifyAuthUpdated();
}

export function clearAuthSession() {
  for (const storage of [sessionStorage, localStorage]) {
    storage.removeItem(TOKEN_KEY);
    storage.removeItem(USER_KEY);
  }
  notifyAuthUpdated();
}

export function setCurrentUser(user) {
  const storage = sessionStorage.getItem(TOKEN_KEY) ? sessionStorage : localStorage;
  storage.setItem(USER_KEY, JSON.stringify(user));
  notifyAuthUpdated();
}

function notifyAuthUpdated() {
  window.dispatchEvent(new CustomEvent('auth:updated'));
}
