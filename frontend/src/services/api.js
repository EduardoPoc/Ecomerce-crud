import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  timeout: 5000,
  headers: {
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');

  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

export default api;
