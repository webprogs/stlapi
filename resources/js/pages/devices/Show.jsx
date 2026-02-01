import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import Card, { CardBody, CardHeader, CardFooter } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import { StatusBadge } from '../../components/ui/Badge';
import Modal from '../../components/ui/Modal';
import Pagination from '../../components/ui/Pagination';
import { deviceApi, syncLogApi, transactionApi, drawResultApi, analyticsApi } from '../../api/client';

export default function DeviceShow() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [device, setDevice] = useState(null);
    const [loading, setLoading] = useState(true);
    const [editing, setEditing] = useState(false);
    const [showApiKey, setShowApiKey] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showRegenerateModal, setShowRegenerateModal] = useState(false);
    const [formData, setFormData] = useState({ device_name: '', is_active: true });
    const [saving, setSaving] = useState(false);
    const [activeTab, setActiveTab] = useState('info');
    const [syncLogs, setSyncLogs] = useState([]);
    const [syncLogsLoading, setSyncLogsLoading] = useState(false);
    const [syncLogsMeta, setSyncLogsMeta] = useState(null);
    const [syncLogsPage, setSyncLogsPage] = useState(1);
    const [transactions, setTransactions] = useState([]);
    const [transactionsLoading, setTransactionsLoading] = useState(false);
    const [transactionsMeta, setTransactionsMeta] = useState(null);
    const [transactionsPage, setTransactionsPage] = useState(1);
    const [drawResults, setDrawResults] = useState([]);
    const [drawResultsLoading, setDrawResultsLoading] = useState(false);
    const [drawResultsMeta, setDrawResultsMeta] = useState(null);
    const [drawResultsPage, setDrawResultsPage] = useState(1);
    const [analytics, setAnalytics] = useState(null);
    const [analyticsLoading, setAnalyticsLoading] = useState(false);
    const [analyticsPeriod, setAnalyticsPeriod] = useState('month');

    useEffect(() => {
        loadDevice();
    }, [id]);

    useEffect(() => {
        if (activeTab === 'sync-logs') {
            loadSyncLogs();
        }
    }, [activeTab, syncLogsPage]);

    useEffect(() => {
        if (activeTab === 'transactions') {
            loadTransactions();
        }
    }, [activeTab, transactionsPage]);

    useEffect(() => {
        if (activeTab === 'draw-results') {
            loadDrawResults();
        }
    }, [activeTab, drawResultsPage]);

    useEffect(() => {
        if (activeTab === 'analytics') {
            loadAnalytics();
        }
    }, [activeTab, analyticsPeriod]);

    const loadDevice = async () => {
        try {
            const response = await deviceApi.get(id);
            setDevice(response.data);
            setFormData({
                device_name: response.data.device_name,
                is_active: response.data.is_active,
            });
        } catch (error) {
            console.error('Failed to load device:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadSyncLogs = async () => {
        setSyncLogsLoading(true);
        try {
            const response = await syncLogApi.list({
                device_id: id,
                page: syncLogsPage,
                per_page: 20,
            });
            setSyncLogs(response.data.data);
            setSyncLogsMeta(response.data);
        } catch (error) {
            console.error('Failed to load sync logs:', error);
        } finally {
            setSyncLogsLoading(false);
        }
    };

    const groupLogsByDate = (logs) => {
        const groups = {};
        logs.forEach((log) => {
            const date = new Date(log.created_at).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
            if (!groups[date]) {
                groups[date] = [];
            }
            groups[date].push(log);
        });
        return groups;
    };

    const getSyncTypeBadgeColor = (type) => {
        switch (type) {
            case 'push':
                return 'bg-blue-100 text-blue-800';
            case 'pull':
                return 'bg-purple-100 text-purple-800';
            case 'batch':
                return 'bg-orange-100 text-orange-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const loadTransactions = async () => {
        setTransactionsLoading(true);
        try {
            const response = await transactionApi.list({
                device_id: id,
                page: transactionsPage,
                per_page: 20,
            });
            setTransactions(response.data.data);
            setTransactionsMeta(response.data);
        } catch (error) {
            console.error('Failed to load transactions:', error);
        } finally {
            setTransactionsLoading(false);
        }
    };

    const groupTransactionsByDate = (txns) => {
        const groups = {};
        txns.forEach((txn) => {
            const date = new Date(txn.draw_date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
            if (!groups[date]) {
                groups[date] = [];
            }
            groups[date].push(txn);
        });
        return groups;
    };

    const getGameTypeBadgeColor = (gameType) => {
        switch (gameType) {
            case 'SWER2':
                return 'bg-green-100 text-green-800';
            case 'SWER3':
                return 'bg-blue-100 text-blue-800';
            case 'SWER4':
                return 'bg-purple-100 text-purple-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusBadgeColor = (status) => {
        switch (status) {
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'won':
                return 'bg-green-100 text-green-800';
            case 'lost':
                return 'bg-red-100 text-red-800';
            case 'claimed':
                return 'bg-blue-100 text-blue-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
        }).format(amount);
    };

    const loadDrawResults = async () => {
        setDrawResultsLoading(true);
        try {
            const response = await drawResultApi.list({
                page: drawResultsPage,
                per_page: 20,
            });
            setDrawResults(response.data.data);
            setDrawResultsMeta(response.data);
        } catch (error) {
            console.error('Failed to load draw results:', error);
        } finally {
            setDrawResultsLoading(false);
        }
    };

    const groupDrawResultsByDate = (results) => {
        const groups = {};
        results.forEach((result) => {
            const date = new Date(result.draw_date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
            if (!groups[date]) {
                groups[date] = [];
            }
            groups[date].push(result);
        });
        return groups;
    };

    const getDrawTimeOrder = (time) => {
        switch (time) {
            case '11AM': return 1;
            case '4PM': return 2;
            case '9PM': return 3;
            default: return 4;
        }
    };

    const sortDrawResultsByTime = (results) => {
        return [...results].sort((a, b) => {
            const timeOrder = getDrawTimeOrder(a.draw_time) - getDrawTimeOrder(b.draw_time);
            if (timeOrder !== 0) return timeOrder;
            return a.game_type.localeCompare(b.game_type);
        });
    };

    const formatWinningNumbers = (numbers) => {
        if (Array.isArray(numbers)) {
            return numbers.join(' - ');
        }
        return numbers;
    };

    const loadAnalytics = async () => {
        setAnalyticsLoading(true);
        try {
            const response = await analyticsApi.device(id, { period: analyticsPeriod });
            setAnalytics(response.data);
        } catch (error) {
            console.error('Failed to load analytics:', error);
        } finally {
            setAnalyticsLoading(false);
        }
    };

    const getPeriodLabel = (period) => {
        switch (period) {
            case 'day': return 'Today';
            case 'week': return 'This Week';
            case 'month': return 'This Month';
            case 'year': return 'This Year';
            case 'all': return 'All Time';
            default: return 'This Month';
        }
    };

    const handleUpdate = async (e) => {
        e.preventDefault();
        setSaving(true);
        try {
            await deviceApi.update(id, formData);
            await loadDevice();
            setEditing(false);
        } catch (error) {
            console.error('Failed to update device:', error);
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async () => {
        try {
            await deviceApi.delete(id);
            navigate('/devices');
        } catch (error) {
            console.error('Failed to delete device:', error);
        }
    };

    const handleRegenerateKey = async () => {
        try {
            const response = await deviceApi.regenerateKey(id);
            setDevice({ ...device, api_key: response.data.api_key });
            setShowRegenerateModal(false);
            setShowApiKey(true);
        } catch (error) {
            console.error('Failed to regenerate key:', error);
        }
    };

    const formatDate = (date) => {
        if (!date) return 'Never';
        return new Date(date).toLocaleString();
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
        );
    }

    if (!device) {
        return (
            <div className="text-center py-12">
                <p className="text-gray-500">Device not found</p>
                <Link to="/devices" className="text-blue-600 hover:underline mt-2 inline-block">
                    Back to devices
                </Link>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <Link to="/devices" className="text-blue-600 hover:underline text-sm">
                        &larr; Back to devices
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 mt-2">{device.device_name}</h1>
                </div>
                <div className="flex gap-2">
                    {!editing && (
                        <>
                            <Button variant="secondary" onClick={() => setEditing(true)}>Edit</Button>
                            <Button variant="danger" onClick={() => setShowDeleteModal(true)}>Delete</Button>
                        </>
                    )}
                </div>
            </div>

            {/* Tabs */}
            <div className="border-b border-gray-200">
                <nav className="-mb-px flex space-x-8">
                    <button
                        onClick={() => setActiveTab('info')}
                        className={`py-2 px-1 border-b-2 font-medium text-sm ${
                            activeTab === 'info'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                    >
                        Device Info
                    </button>
                    <button
                        onClick={() => setActiveTab('sync-logs')}
                        className={`py-2 px-1 border-b-2 font-medium text-sm ${
                            activeTab === 'sync-logs'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                    >
                        Sync Logs
                    </button>
                    <button
                        onClick={() => setActiveTab('transactions')}
                        className={`py-2 px-1 border-b-2 font-medium text-sm ${
                            activeTab === 'transactions'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                    >
                        Transactions
                    </button>
                    <button
                        onClick={() => setActiveTab('draw-results')}
                        className={`py-2 px-1 border-b-2 font-medium text-sm ${
                            activeTab === 'draw-results'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                    >
                        Draw Results
                    </button>
                    <button
                        onClick={() => setActiveTab('analytics')}
                        className={`py-2 px-1 border-b-2 font-medium text-sm ${
                            activeTab === 'analytics'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                    >
                        Analytics
                    </button>
                </nav>
            </div>

            {activeTab === 'info' && editing ? (
                <Card>
                    <form onSubmit={handleUpdate}>
                        <CardHeader>
                            <h2 className="text-lg font-semibold">Edit Device</h2>
                        </CardHeader>
                        <CardBody className="space-y-4">
                            <Input
                                label="Device Name"
                                value={formData.device_name}
                                onChange={(e) => setFormData({ ...formData, device_name: e.target.value })}
                                required
                            />
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    checked={formData.is_active}
                                    onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                    className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                                <label htmlFor="is_active" className="text-sm font-medium text-gray-700">
                                    Active
                                </label>
                            </div>
                        </CardBody>
                        <CardFooter className="flex justify-end gap-2">
                            <Button variant="secondary" onClick={() => setEditing(false)}>Cancel</Button>
                            <Button type="submit" loading={saving}>Save Changes</Button>
                        </CardFooter>
                    </form>
                </Card>
            ) : activeTab === 'info' ? (
                <>
                    <Card>
                        <CardHeader>
                            <h2 className="text-lg font-semibold">Device Information</h2>
                        </CardHeader>
                        <CardBody>
                            <dl className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Device Name</dt>
                                    <dd className="mt-1 text-gray-900">{device.device_name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Status</dt>
                                    <dd className="mt-1">
                                        <StatusBadge status={device.is_active ? 'active' : 'inactive'} />
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">UUID</dt>
                                    <dd className="mt-1 font-mono text-sm text-gray-900">{device.uuid}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Last Sync</dt>
                                    <dd className="mt-1 text-gray-900">{formatDate(device.last_sync_at)}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Last IP</dt>
                                    <dd className="mt-1 text-gray-900">{device.last_ip || '-'}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Created</dt>
                                    <dd className="mt-1 text-gray-900">{formatDate(device.created_at)}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Total Transactions</dt>
                                    <dd className="mt-1 text-gray-900">{device.transactions_count || 0}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Device Users</dt>
                                    <dd className="mt-1 text-gray-900">{device.device_users_count || 0}</dd>
                                </div>
                            </dl>
                        </CardBody>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-semibold">API Credentials</h2>
                                <Button variant="danger" size="sm" onClick={() => setShowRegenerateModal(true)}>
                                    Regenerate Key
                                </Button>
                            </div>
                        </CardHeader>
                        <CardBody className="space-y-4">
                            <div>
                                <label className="text-sm font-medium text-gray-500">API Key</label>
                                <div className="mt-1 flex items-center gap-2">
                                    <code className="flex-1 p-2 bg-gray-100 rounded font-mono text-sm">
                                        {showApiKey ? device.api_key : '••••••••••••••••••••••••••••••••'}
                                    </code>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setShowApiKey(!showApiKey)}
                                    >
                                        {showApiKey ? 'Hide' : 'Show'}
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => navigator.clipboard.writeText(device.api_key)}
                                    >
                                        Copy
                                    </Button>
                                </div>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-500">Device ID (X-Device-ID header)</label>
                                <div className="mt-1 flex items-center gap-2">
                                    <code className="flex-1 p-2 bg-gray-100 rounded font-mono text-sm">
                                        {device.uuid}
                                    </code>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => navigator.clipboard.writeText(device.uuid)}
                                    >
                                        Copy
                                    </Button>
                                </div>
                            </div>
                        </CardBody>
                    </Card>
                </>
            ) : activeTab === 'sync-logs' ? (
                /* Sync Logs Tab */
                <Card>
                    <CardHeader>
                        <h2 className="text-lg font-semibold">Sync History</h2>
                    </CardHeader>
                    <CardBody>
                        {syncLogsLoading ? (
                            <div className="flex items-center justify-center h-32">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            </div>
                        ) : syncLogs.length === 0 ? (
                            <p className="text-center text-gray-500 py-8">No sync logs found for this device.</p>
                        ) : (
                            <div className="space-y-6">
                                {Object.entries(groupLogsByDate(syncLogs)).map(([date, logs]) => (
                                    <div key={date}>
                                        <h3 className="text-sm font-semibold text-gray-700 bg-gray-50 px-3 py-2 rounded-md mb-3">
                                            {date}
                                        </h3>
                                        <div className="space-y-2">
                                            {logs.map((log) => (
                                                <div
                                                    key={log.id}
                                                    className={`p-3 border rounded-lg hover:bg-gray-50 ${
                                                        log.status === 'failed'
                                                            ? 'border-red-200 bg-red-50'
                                                            : 'border-gray-200'
                                                    }`}
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center gap-4">
                                                            <span className="text-sm text-gray-500 w-20">
                                                                {new Date(log.created_at).toLocaleTimeString('en-US', {
                                                                    hour: '2-digit',
                                                                    minute: '2-digit',
                                                                })}
                                                            </span>
                                                            <span
                                                                className={`px-2 py-1 rounded-full text-xs font-medium ${getSyncTypeBadgeColor(
                                                                    log.sync_type
                                                                )}`}
                                                            >
                                                                {log.sync_type}
                                                            </span>
                                                            <span className="text-sm text-gray-700">
                                                                {log.records_synced} record{log.records_synced !== 1 ? 's' : ''}
                                                            </span>
                                                        </div>
                                                        <div className="flex items-center gap-4">
                                                            <span className="text-xs text-gray-400 font-mono">
                                                                {log.ip_address}
                                                            </span>
                                                            <StatusBadge status={log.status} />
                                                        </div>
                                                    </div>
                                                    {log.error_message && (
                                                        <p className="mt-2 text-sm text-red-600 ml-24">
                                                            {log.error_message}
                                                        </p>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardBody>
                    {syncLogsMeta && syncLogsMeta.last_page > 1 && (
                        <CardFooter>
                            <Pagination meta={syncLogsMeta} onPageChange={setSyncLogsPage} />
                        </CardFooter>
                    )}
                </Card>
            ) : activeTab === 'transactions' ? (
                /* Transactions Tab */
                <Card>
                    <CardHeader>
                        <h2 className="text-lg font-semibold">Transactions</h2>
                    </CardHeader>
                    <CardBody>
                        {transactionsLoading ? (
                            <div className="flex items-center justify-center h-32">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            </div>
                        ) : transactions.length === 0 ? (
                            <p className="text-center text-gray-500 py-8">No transactions found for this device.</p>
                        ) : (
                            <div className="space-y-6">
                                {Object.entries(groupTransactionsByDate(transactions)).map(([date, txns]) => (
                                    <div key={date}>
                                        <h3 className="text-sm font-semibold text-gray-700 bg-gray-50 px-3 py-2 rounded-md mb-3">
                                            {date}
                                        </h3>
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200">
                                                <thead>
                                                    <tr className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        <th className="px-3 py-2">Transaction ID</th>
                                                        <th className="px-3 py-2">Game</th>
                                                        <th className="px-3 py-2">Numbers</th>
                                                        <th className="px-3 py-2">Draw Time</th>
                                                        <th className="px-3 py-2 text-right">Amount</th>
                                                        <th className="px-3 py-2">Status</th>
                                                        <th className="px-3 py-2 text-right">Win Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-gray-100">
                                                    {txns.map((txn) => (
                                                        <tr
                                                            key={txn.id}
                                                            className={`hover:bg-gray-50 ${
                                                                txn.status === 'won'
                                                                    ? 'bg-green-50'
                                                                    : txn.status === 'claimed'
                                                                    ? 'bg-blue-50'
                                                                    : ''
                                                            }`}
                                                        >
                                                            <td className="px-3 py-2">
                                                                <Link
                                                                    to={`/transactions/${txn.id}`}
                                                                    className="text-blue-600 hover:underline font-mono text-sm"
                                                                >
                                                                    {txn.transaction_id}
                                                                </Link>
                                                            </td>
                                                            <td className="px-3 py-2">
                                                                <span
                                                                    className={`px-2 py-1 rounded-full text-xs font-medium ${getGameTypeBadgeColor(
                                                                        txn.game_type
                                                                    )}`}
                                                                >
                                                                    {txn.game_type}
                                                                </span>
                                                            </td>
                                                            <td className="px-3 py-2 font-mono text-sm font-semibold">
                                                                {Array.isArray(txn.numbers)
                                                                    ? txn.numbers.join('-')
                                                                    : txn.numbers}
                                                            </td>
                                                            <td className="px-3 py-2 text-sm text-gray-600">
                                                                {txn.draw_time}
                                                            </td>
                                                            <td className="px-3 py-2 text-right text-sm">
                                                                {formatCurrency(txn.amount)}
                                                            </td>
                                                            <td className="px-3 py-2">
                                                                <span
                                                                    className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusBadgeColor(
                                                                        txn.status
                                                                    )}`}
                                                                >
                                                                    {txn.status}
                                                                </span>
                                                            </td>
                                                            <td className="px-3 py-2 text-right text-sm font-semibold">
                                                                {txn.status === 'won' || txn.status === 'claimed' ? (
                                                                    <span className="text-green-600">
                                                                        {formatCurrency(txn.win_amount)}
                                                                    </span>
                                                                ) : txn.status === 'lost' ? (
                                                                    <span className="text-gray-400">-</span>
                                                                ) : (
                                                                    <span className="text-gray-400">pending</span>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardBody>
                    {transactionsMeta && transactionsMeta.last_page > 1 && (
                        <CardFooter>
                            <Pagination meta={transactionsMeta} onPageChange={setTransactionsPage} />
                        </CardFooter>
                    )}
                </Card>
            ) : activeTab === 'draw-results' ? (
                /* Draw Results Tab */
                <Card>
                    <CardHeader>
                        <h2 className="text-lg font-semibold">Draw Results</h2>
                    </CardHeader>
                    <CardBody>
                        {drawResultsLoading ? (
                            <div className="flex items-center justify-center h-32">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            </div>
                        ) : drawResults.length === 0 ? (
                            <p className="text-center text-gray-500 py-8">No draw results found.</p>
                        ) : (
                            <div className="space-y-6">
                                {Object.entries(groupDrawResultsByDate(drawResults)).map(([date, results]) => (
                                    <div key={date}>
                                        <h3 className="text-sm font-semibold text-gray-700 bg-gray-50 px-3 py-2 rounded-md mb-3">
                                            {date}
                                        </h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            {sortDrawResultsByTime(results).map((result) => (
                                                <div
                                                    key={result.id}
                                                    className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                                                >
                                                    <div className="flex items-center justify-between mb-3">
                                                        <span className="text-sm font-medium text-gray-600">
                                                            {result.draw_time}
                                                        </span>
                                                        <span
                                                            className={`px-2 py-1 rounded-full text-xs font-medium ${getGameTypeBadgeColor(
                                                                result.game_type
                                                            )}`}
                                                        >
                                                            {result.game_type}
                                                        </span>
                                                    </div>
                                                    <div className="text-center py-3">
                                                        <div className="text-2xl font-bold font-mono text-gray-900 tracking-wider">
                                                            {formatWinningNumbers(result.winning_numbers)}
                                                        </div>
                                                        <p className="text-xs text-gray-500 mt-1">Winning Numbers</p>
                                                    </div>
                                                    <div className="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                                                        <span className="text-xs text-gray-400">
                                                            {result.is_official ? (
                                                                <span className="text-green-600">Official</span>
                                                            ) : (
                                                                <span className="text-yellow-600">Unofficial</span>
                                                            )}
                                                        </span>
                                                        {result.set_by && (
                                                            <span className="text-xs text-gray-400">
                                                                by {result.set_by.name}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardBody>
                    {drawResultsMeta && drawResultsMeta.last_page > 1 && (
                        <CardFooter>
                            <Pagination meta={drawResultsMeta} onPageChange={setDrawResultsPage} />
                        </CardFooter>
                    )}
                </Card>
            ) : (
                /* Analytics Tab */
                <div className="space-y-6">
                    {/* Period Filter */}
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-gray-900">Device Analytics</h2>
                        <div className="flex gap-2">
                            {['day', 'week', 'month', 'year', 'all'].map((period) => (
                                <button
                                    key={period}
                                    onClick={() => setAnalyticsPeriod(period)}
                                    className={`px-3 py-1.5 text-sm font-medium rounded-md transition-colors ${
                                        analyticsPeriod === period
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    {getPeriodLabel(period)}
                                </button>
                            ))}
                        </div>
                    </div>

                    {analyticsLoading ? (
                        <div className="flex items-center justify-center h-64">
                            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        </div>
                    ) : analytics ? (
                        <>
                            {/* Summary Cards */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <Card>
                                    <CardBody className="text-center">
                                        <p className="text-sm font-medium text-gray-500">Total Bets</p>
                                        <p className="text-2xl font-bold text-gray-900 mt-1">
                                            {formatCurrency(analytics.summary.total_bets)}
                                        </p>
                                        <p className="text-xs text-gray-400 mt-1">
                                            {analytics.transactions.total} transactions
                                        </p>
                                    </CardBody>
                                </Card>
                                <Card>
                                    <CardBody className="text-center">
                                        <p className="text-sm font-medium text-gray-500">Total Winnings</p>
                                        <p className="text-2xl font-bold text-red-600 mt-1">
                                            {formatCurrency(analytics.summary.total_winnings)}
                                        </p>
                                        <p className="text-xs text-gray-400 mt-1">
                                            {analytics.transactions.winning_total} winners
                                        </p>
                                    </CardBody>
                                </Card>
                                <Card>
                                    <CardBody className="text-center">
                                        <p className="text-sm font-medium text-gray-500">Net Earnings</p>
                                        <p className={`text-2xl font-bold mt-1 ${
                                            analytics.summary.net_earnings >= 0 ? 'text-green-600' : 'text-red-600'
                                        }`}>
                                            {formatCurrency(analytics.summary.net_earnings)}
                                        </p>
                                        <p className="text-xs text-gray-400 mt-1">
                                            After payouts
                                        </p>
                                    </CardBody>
                                </Card>
                                <Card>
                                    <CardBody className="text-center">
                                        <p className="text-sm font-medium text-gray-500">Win Rate</p>
                                        <p className="text-2xl font-bold text-blue-600 mt-1">
                                            {analytics.transactions.total > 0
                                                ? ((analytics.transactions.winning_total / analytics.transactions.total) * 100).toFixed(1)
                                                : 0}%
                                        </p>
                                        <p className="text-xs text-gray-400 mt-1">
                                            {analytics.transactions.winning_total} of {analytics.transactions.total}
                                        </p>
                                    </CardBody>
                                </Card>
                            </div>

                            {/* Transaction Status Breakdown */}
                            <Card>
                                <CardHeader>
                                    <h3 className="text-md font-semibold">Transaction Status</h3>
                                </CardHeader>
                                <CardBody>
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div className="text-center p-3 bg-yellow-50 rounded-lg">
                                            <p className="text-2xl font-bold text-yellow-600">{analytics.transactions.pending}</p>
                                            <p className="text-sm text-gray-600">Pending</p>
                                        </div>
                                        <div className="text-center p-3 bg-green-50 rounded-lg">
                                            <p className="text-2xl font-bold text-green-600">{analytics.transactions.won}</p>
                                            <p className="text-sm text-gray-600">Won</p>
                                        </div>
                                        <div className="text-center p-3 bg-red-50 rounded-lg">
                                            <p className="text-2xl font-bold text-red-600">{analytics.transactions.lost}</p>
                                            <p className="text-sm text-gray-600">Lost</p>
                                        </div>
                                        <div className="text-center p-3 bg-blue-50 rounded-lg">
                                            <p className="text-2xl font-bold text-blue-600">{analytics.transactions.claimed}</p>
                                            <p className="text-sm text-gray-600">Claimed</p>
                                        </div>
                                    </div>
                                </CardBody>
                            </Card>

                            {/* By Game Type & Draw Time */}
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <Card>
                                    <CardHeader>
                                        <h3 className="text-md font-semibold">By Game Type</h3>
                                    </CardHeader>
                                    <CardBody>
                                        {analytics.by_game.length === 0 ? (
                                            <p className="text-center text-gray-500 py-4">No data available</p>
                                        ) : (
                                            <div className="space-y-3">
                                                {analytics.by_game.map((game) => (
                                                    <div key={game.game_type} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                        <div className="flex items-center gap-3">
                                                            <span className={`px-2 py-1 rounded-full text-xs font-medium ${getGameTypeBadgeColor(game.game_type)}`}>
                                                                {game.game_type}
                                                            </span>
                                                            <span className="text-sm text-gray-600">
                                                                {game.total_bets} bets
                                                            </span>
                                                        </div>
                                                        <div className="text-right">
                                                            <p className="text-sm font-semibold text-gray-900">{formatCurrency(game.total_amount)}</p>
                                                            <p className="text-xs text-green-600">{game.winning_bets} winners</p>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </CardBody>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <h3 className="text-md font-semibold">By Draw Time</h3>
                                    </CardHeader>
                                    <CardBody>
                                        {analytics.by_draw_time.length === 0 ? (
                                            <p className="text-center text-gray-500 py-4">No data available</p>
                                        ) : (
                                            <div className="space-y-3">
                                                {analytics.by_draw_time.map((time) => (
                                                    <div key={time.draw_time} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                        <div className="flex items-center gap-3">
                                                            <span className="px-2 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-800">
                                                                {time.draw_time}
                                                            </span>
                                                            <span className="text-sm text-gray-600">
                                                                {time.total_bets} bets
                                                            </span>
                                                        </div>
                                                        <div className="text-right">
                                                            <p className="text-sm font-semibold text-gray-900">{formatCurrency(time.total_amount)}</p>
                                                            <p className="text-xs text-green-600">{time.winning_bets} winners</p>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </CardBody>
                                </Card>
                            </div>

                            {/* Daily Breakdown */}
                            <Card>
                                <CardHeader>
                                    <h3 className="text-md font-semibold">Daily Breakdown</h3>
                                </CardHeader>
                                <CardBody>
                                    {analytics.daily.length === 0 ? (
                                        <p className="text-center text-gray-500 py-4">No data available for this period</p>
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200">
                                                <thead>
                                                    <tr className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        <th className="px-3 py-2">Date</th>
                                                        <th className="px-3 py-2 text-center">Bets</th>
                                                        <th className="px-3 py-2 text-center">Winners</th>
                                                        <th className="px-3 py-2 text-right">Total Amount</th>
                                                        <th className="px-3 py-2 text-right">Winnings</th>
                                                        <th className="px-3 py-2 text-right">Net Earnings</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-gray-100">
                                                    {analytics.daily.map((day) => (
                                                        <tr key={day.date} className="hover:bg-gray-50">
                                                            <td className="px-3 py-2 text-sm text-gray-900">
                                                                {new Date(day.date).toLocaleDateString('en-US', {
                                                                    weekday: 'short',
                                                                    month: 'short',
                                                                    day: 'numeric',
                                                                })}
                                                            </td>
                                                            <td className="px-3 py-2 text-sm text-center text-gray-600">{day.total_bets}</td>
                                                            <td className="px-3 py-2 text-sm text-center">
                                                                <span className="text-green-600 font-medium">{day.winning_bets}</span>
                                                            </td>
                                                            <td className="px-3 py-2 text-sm text-right text-gray-900">{formatCurrency(day.total_amount)}</td>
                                                            <td className="px-3 py-2 text-sm text-right text-red-600">{formatCurrency(day.total_winnings)}</td>
                                                            <td className={`px-3 py-2 text-sm text-right font-semibold ${
                                                                day.net_earnings >= 0 ? 'text-green-600' : 'text-red-600'
                                                            }`}>
                                                                {formatCurrency(day.net_earnings)}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </CardBody>
                            </Card>
                        </>
                    ) : (
                        <p className="text-center text-gray-500 py-8">No analytics data available</p>
                    )}
                </div>
            )}

            {/* Delete Modal */}
            <Modal
                isOpen={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                title="Delete Device"
            >
                <p className="text-gray-600">
                    Are you sure you want to delete this device? This action cannot be undone.
                    All transactions and sync logs associated with this device will also be deleted.
                </p>
                <div className="mt-6 flex justify-end gap-2">
                    <Button variant="secondary" onClick={() => setShowDeleteModal(false)}>Cancel</Button>
                    <Button variant="danger" onClick={handleDelete}>Delete Device</Button>
                </div>
            </Modal>

            {/* Regenerate Key Modal */}
            <Modal
                isOpen={showRegenerateModal}
                onClose={() => setShowRegenerateModal(false)}
                title="Regenerate API Key"
            >
                <p className="text-gray-600">
                    Are you sure you want to regenerate the API key? The current key will no longer work
                    and you will need to update the key on the device.
                </p>
                <div className="mt-6 flex justify-end gap-2">
                    <Button variant="secondary" onClick={() => setShowRegenerateModal(false)}>Cancel</Button>
                    <Button variant="danger" onClick={handleRegenerateKey}>Regenerate</Button>
                </div>
            </Modal>
        </div>
    );
}
