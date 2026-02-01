import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import Button from '../../components/ui/Button';
import { StatusBadge } from '../../components/ui/Badge';
import Pagination from '../../components/ui/Pagination';
import Input, { Select } from '../../components/ui/Input';
import { transactionApi } from '../../api/client';

export default function TransactionIndex() {
    const [transactions, setTransactions] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        search: '',
        game_type: '',
        draw_time: '',
        status: '',
        date_from: '',
        date_to: '',
    });
    const [page, setPage] = useState(1);

    useEffect(() => {
        loadTransactions();
    }, [page]);

    const loadTransactions = async () => {
        try {
            setLoading(true);
            const params = { page, per_page: 15 };
            Object.keys(filters).forEach(key => {
                if (filters[key]) params[key] = filters[key];
            });
            const response = await transactionApi.list(params);
            setTransactions(response.data.data);
            setMeta(response.data);
        } catch (error) {
            console.error('Failed to load transactions:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleFilter = (e) => {
        e.preventDefault();
        setPage(1);
        loadTransactions();
    };

    const clearFilters = () => {
        setFilters({
            search: '',
            game_type: '',
            draw_time: '',
            status: '',
            date_from: '',
            date_to: '',
        });
        setPage(1);
        setTimeout(loadTransactions, 0);
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
        }).format(amount || 0);
    };

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold text-gray-900">Transactions</h1>

            <Card>
                <CardHeader>
                    <form onSubmit={handleFilter} className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <Input
                                placeholder="Transaction ID..."
                                value={filters.search}
                                onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                            />
                            <Select
                                value={filters.game_type}
                                onChange={(e) => setFilters({ ...filters, game_type: e.target.value })}
                            >
                                <option value="">All Games</option>
                                <option value="SWER2">SWER2</option>
                                <option value="SWER3">SWER3</option>
                                <option value="SWER4">SWER4</option>
                            </Select>
                            <Select
                                value={filters.draw_time}
                                onChange={(e) => setFilters({ ...filters, draw_time: e.target.value })}
                            >
                                <option value="">All Draw Times</option>
                                <option value="11AM">11AM</option>
                                <option value="4PM">4PM</option>
                                <option value="9PM">9PM</option>
                            </Select>
                            <Select
                                value={filters.status}
                                onChange={(e) => setFilters({ ...filters, status: e.target.value })}
                            >
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="won">Won</option>
                                <option value="lost">Lost</option>
                                <option value="claimed">Claimed</option>
                            </Select>
                            <Input
                                type="date"
                                value={filters.date_from}
                                onChange={(e) => setFilters({ ...filters, date_from: e.target.value })}
                                placeholder="From Date"
                            />
                            <Input
                                type="date"
                                value={filters.date_to}
                                onChange={(e) => setFilters({ ...filters, date_to: e.target.value })}
                                placeholder="To Date"
                            />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit">Filter</Button>
                            <Button type="button" variant="secondary" onClick={clearFilters}>Clear</Button>
                        </div>
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
                                        <Th>Transaction ID</Th>
                                        <Th>Device</Th>
                                        <Th>Game</Th>
                                        <Th>Numbers</Th>
                                        <Th>Amount</Th>
                                        <Th>Draw</Th>
                                        <Th>Status</Th>
                                        <Th>Win Amount</Th>
                                        <Th>Actions</Th>
                                    </Tr>
                                </Thead>
                                <Tbody>
                                    {transactions.map((tx) => (
                                        <Tr key={tx.id}>
                                            <Td className="font-mono text-xs">{tx.transaction_id}</Td>
                                            <Td>{tx.device?.device_name || '-'}</Td>
                                            <Td>{tx.game_type}</Td>
                                            <Td className="font-mono font-bold">{tx.numbers?.join('-')}</Td>
                                            <Td>{formatCurrency(tx.amount)}</Td>
                                            <Td>
                                                <div className="text-sm">
                                                    <div>{tx.draw_date}</div>
                                                    <div className="text-gray-500">{tx.draw_time}</div>
                                                </div>
                                            </Td>
                                            <Td><StatusBadge status={tx.status} /></Td>
                                            <Td className={tx.win_amount ? 'text-green-600 font-medium' : ''}>
                                                {tx.win_amount ? formatCurrency(tx.win_amount) : '-'}
                                            </Td>
                                            <Td>
                                                <Link to={`/transactions/${tx.id}`}>
                                                    <Button variant="ghost" size="sm">View</Button>
                                                </Link>
                                            </Td>
                                        </Tr>
                                    ))}
                                    {transactions.length === 0 && (
                                        <Tr>
                                            <Td colSpan={9} className="text-center text-gray-500 py-8">
                                                No transactions found
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
