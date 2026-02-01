import { createBrowserRouter } from 'react-router-dom';
import Layout from './components/Layout';
import PrivateRoute from './components/PrivateRoute';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import DeviceIndex from './pages/devices/Index';
import DeviceShow from './pages/devices/Show';
import DeviceCreate from './pages/devices/Create';
import TransactionIndex from './pages/transactions/Index';
import TransactionShow from './pages/transactions/Show';
import DrawResultIndex from './pages/draw-results/Index';
import DrawResultCreate from './pages/draw-results/Create';
import DrawResultEdit from './pages/draw-results/Edit';
import SyncLogIndex from './pages/sync-logs/Index';

const router = createBrowserRouter([
    {
        path: '/login',
        element: <Login />,
    },
    {
        path: '/',
        element: (
            <PrivateRoute>
                <Layout />
            </PrivateRoute>
        ),
        children: [
            {
                index: true,
                element: <Dashboard />,
            },
            {
                path: 'devices',
                element: <DeviceIndex />,
            },
            {
                path: 'devices/create',
                element: <DeviceCreate />,
            },
            {
                path: 'devices/:id',
                element: <DeviceShow />,
            },
            {
                path: 'transactions',
                element: <TransactionIndex />,
            },
            {
                path: 'transactions/:id',
                element: <TransactionShow />,
            },
            {
                path: 'draw-results',
                element: <DrawResultIndex />,
            },
            {
                path: 'draw-results/create',
                element: <DrawResultCreate />,
            },
            {
                path: 'draw-results/:id/edit',
                element: <DrawResultEdit />,
            },
            {
                path: 'sync-logs',
                element: <SyncLogIndex />,
            },
        ],
    },
]);

export default router;
