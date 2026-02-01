import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import Button from '../../components/ui/Button';
import { StatusBadge } from '../../components/ui/Badge';
import Pagination from '../../components/ui/Pagination';
import Input from '../../components/ui/Input';
import { deviceApi } from '../../api/client';

export default function DeviceIndex() {
    const [devices, setDevices] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    useEffect(() => {
        loadDevices();
    }, [page, search]);

    const loadDevices = async () => {
        try {
            setLoading(true);
            const response = await deviceApi.list({ page, search, per_page: 15 });
            setDevices(response.data.data);
            setMeta(response.data);
        } catch (error) {
            console.error('Failed to load devices:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        setPage(1);
        loadDevices();
    };

    const formatDate = (date) => {
        if (!date) return 'Never';
        return new Date(date).toLocaleString();
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold text-gray-900">Devices</h1>
                <Link to="/devices/create">
                    <Button>Add Device</Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <form onSubmit={handleSearch} className="flex gap-4">
                        <Input
                            placeholder="Search devices..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="max-w-xs"
                        />
                        <Button type="submit" variant="secondary">Search</Button>
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
                                        <Th>Device Name</Th>
                                        <Th>UUID</Th>
                                        <Th>Status</Th>
                                        <Th>Last Sync</Th>
                                        <Th>Last IP</Th>
                                        <Th>Actions</Th>
                                    </Tr>
                                </Thead>
                                <Tbody>
                                    {devices.map((device) => (
                                        <Tr key={device.id}>
                                            <Td className="font-medium">{device.device_name}</Td>
                                            <Td className="font-mono text-xs">{device.uuid}</Td>
                                            <Td>
                                                <StatusBadge status={device.is_active ? 'active' : 'inactive'} />
                                            </Td>
                                            <Td className="text-gray-500">{formatDate(device.last_sync_at)}</Td>
                                            <Td className="text-gray-500">{device.last_ip || '-'}</Td>
                                            <Td>
                                                <Link to={`/devices/${device.id}`}>
                                                    <Button variant="ghost" size="sm">View</Button>
                                                </Link>
                                            </Td>
                                        </Tr>
                                    ))}
                                    {devices.length === 0 && (
                                        <Tr>
                                            <Td colSpan={6} className="text-center text-gray-500 py-8">
                                                No devices found
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
