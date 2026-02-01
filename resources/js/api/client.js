import axios from 'axios';

const client = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Add token to requests
client.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Handle 401 responses
client.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default client;

// Auth API
export const authApi = {
    login: (credentials) => client.post('/admin/login', credentials),
    logout: () => client.post('/admin/logout'),
    me: () => client.get('/admin/me'),
};

// Device API
export const deviceApi = {
    list: (params) => client.get('/admin/devices', { params }),
    get: (id) => client.get(`/admin/devices/${id}`),
    create: (data) => client.post('/admin/devices', data),
    update: (id, data) => client.put(`/admin/devices/${id}`, data),
    delete: (id) => client.delete(`/admin/devices/${id}`),
    regenerateKey: (id) => client.post(`/admin/devices/${id}/regenerate-key`),
};

// Transaction API
export const transactionApi = {
    list: (params) => client.get('/admin/transactions', { params }),
    get: (id) => client.get(`/admin/transactions/${id}`),
    claim: (id) => client.post(`/admin/transactions/${id}/claim`),
};

// Draw Result API
export const drawResultApi = {
    list: (params) => client.get('/admin/draw-results', { params }),
    get: (id) => client.get(`/admin/draw-results/${id}`),
    create: (data) => client.post('/admin/draw-results', data),
    update: (id, data) => client.put(`/admin/draw-results/${id}`, data),
    delete: (id) => client.delete(`/admin/draw-results/${id}`),
    today: () => client.get('/draw-results/today'),
};

// Analytics API
export const analyticsApi = {
    summary: (params) => client.get('/admin/analytics/summary', { params }),
    byGame: (params) => client.get('/admin/analytics/by-game', { params }),
    byDrawTime: (params) => client.get('/admin/analytics/by-draw-time', { params }),
    byDevice: (params) => client.get('/admin/analytics/by-device', { params }),
    daily: (params) => client.get('/admin/analytics/daily', { params }),
    topNumbers: (params) => client.get('/admin/analytics/top-numbers', { params }),
    device: (id, params) => client.get(`/admin/analytics/device/${id}`, { params }),
};

// Reports API
export const reportApi = {
    daily: (params) => client.get('/admin/reports/daily', { params }),
};

// Sync Logs API
export const syncLogApi = {
    list: (params) => client.get('/admin/sync-logs', { params }),
};
