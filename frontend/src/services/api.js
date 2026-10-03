import axios from 'axios';
import { clearAuthSession, getAuthToken } from './authState.js';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  timeout: 5000,
  headers: {
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = getAuthToken();

  if (token) config.headers.Authorization = `Bearer ${token}`;
  if (config.data instanceof FormData) delete config.headers['Content-Type'];
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && !window.location.pathname.startsWith('/login')) {
      clearAuthSession();
      const redirect = `${window.location.pathname}${window.location.search}`;
      window.location.assign(`/login/?redirect=${encodeURIComponent(redirect)}`);
    }
    return Promise.reject(error);
  },
);

export default api;
