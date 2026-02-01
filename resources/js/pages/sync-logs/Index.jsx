import { useState, useEffect } from 'react';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import Button from '../../components/ui/Button';
import { StatusBadge } from '../../components/ui/Badge';
import Pagination from '../../components/ui/Pagination';
import Input, { Select } from '../../components/ui/Input';
import { syncLogApi } from '../../api/client';

export default function SyncLogIndex() {
    const [logs, setLogs] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        sync_type: '',
        status: '',
        date_from: '',
        date_to: '',
    });
    const [page, setPage] = useState(1);

    useEffect(() => {
        loadLogs();
    }, [page]);

    const loadLogs = async () => {
        try {
            setLoading(true);
            const params = { page, per_page: 20 };
            Object.keys(filters).forEach(key => {
                if (filters[key]) params[key] = filters[key];
            });
            const response = await syncLogApi.list(params);
            setLogs(response.data.data);
            setMeta(response.data);
        } catch (error) {
            console.error('Failed to load logs:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleFilter = (e) => {
        e.preventDefault();
        setPage(1);
        loadLogs();
    };

    const formatDate = (date) => {
        return new Date(date).toLocaleString();
    };

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold text-gray-900">Sync Logs</h1>

            <Card>
                <CardHeader>
                    <form onSubmit={handleFilter} className="flex flex-wrap gap-4">
                        <Select
                            value={filters.sync_type}
                            onChange={(e) => setFilters({ ...filters, sync_type: e.target.value })}
                            className="w-auto"
                        >
                            <option value="">All Types</option>
                            <option value="push">Push</option>
                            <option value="pull">Pull</option>
                            <option value="batch">Batch</option>
                        </Select>
                        <Select
                            value={filters.status}
                            onChange={(e) => setFilters({ ...filters, status: e.target.value })}
                            className="w-auto"
                        >
                            <option value="">All Status</option>
                            <option value="success">Success</option>
                            <option value="partial">Partial</option>
                            <option value="failed">Failed</option>
                        </Select>
                        <Input
                            type="date"
                            value={filters.date_from}
                            onChange={(e) => setFilters({ ...filters, date_from: e.target.value })}
                            className="w-auto"
                        />
                        <Input
                            type="date"
                            value={filters.date_to}
                            onChange={(e) => setFilters({ ...filters, date_to: e.target.value })}
                            className="w-auto"
                        />
                        <Button type="submit">Filter</Button>
                    </form>
                </CardHeader>
                <CardBody className="p-0">
                    {loading ? (
                        <div className="flex items-center justify-center h-64">
                            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        </div>
                    ) : (
                        <>
                            <Table>
                                <Thead>
                                    <Tr>
                                        <Th>Time</Th>
                                        <Th>Device</Th>
                                        <Th>Type</Th>
                                        <Th>Records</Th>
                                        <Th>Status</Th>
                                        <Th>IP Address</Th>
                                        <Th>Error</Th>
                                    </Tr>
                                </Thead>
                                <Tbody>
                                    {logs.map((log) => (
                                        <Tr key={log.id}>
                                            <Td className="text-sm">{formatDate(log.created_at)}</Td>
                                            <Td>{log.device?.device_name || '-'}</Td>
                                            <Td>
                                                <span className={`inline-flex items-center px-2 py-1 rounded text-xs font-medium ${
                                                    log.sync_type === 'push' ? 'bg-blue-100 text-blue-800' :
                                                    log.sync_type === 'pull' ? 'bg-purple-100 text-purple-800' :
                                                    'bg-orange-100 text-orange-800'
                                                }`}>
                                                    {log.sync_type}
                                                </span>
                                            </Td>
                                            <Td className="font-medium">{log.records_synced}</Td>
                                            <Td><StatusBadge status={log.status} /></Td>
                                            <Td className="text-gray-500 font-mono text-xs">{log.ip_address || '-'}</Td>
                                            <Td className="max-w-xs truncate text-red-600 text-sm">
                                                {log.error_message || '-'}
                                            </Td>
                                        </Tr>
                                    ))}
                                    {logs.length === 0 && (
                                        <Tr>
                                            <Td colSpan={7} className="text-center text-gray-500 py-8">
                                                No sync logs found
                                            </Td>
                                        </Tr>
                                    )}
                                </Tbody>
                            </Table>
                            <Pagination meta={meta} onPageChange={setPage} />
                        </>
                    )}
                </CardBody>
            </Card>
        </div>
    );
}
