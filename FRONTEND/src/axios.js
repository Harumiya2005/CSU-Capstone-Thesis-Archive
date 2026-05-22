import axios from 'axios';

const api = axios.create({
    baseURL: 'http://127.0.0.1:8000/api',
    // <-- Removed the withCredentials line here!
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});

// Add an interceptor to automatically attach the token if it exists
api.interceptors.request.use(config => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;