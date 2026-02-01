import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import { StatusBadge } from '../../components/ui/Badge';
import { transactionApi } from '../../api/client';

export default function TransactionShow() {
    const { id } = useParams();
    const [transaction, setTransaction] = useState(null);
    const [loading, setLoading] = useState(true);
    const [claiming, setClaiming] = useState(false);

    useEffect(() => {
        loadTransaction();
    }, [id]);

    const loadTransaction = async () => {
        try {
            const response = await transactionApi.get(id);
            setTransaction(response.data);
        } catch (error) {
            console.error('Failed to load transaction:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleClaim = async () => {
        if (!confirm('Mark this transaction as claimed?')) return;
        setClaiming(true);
        try {
            await transactionApi.claim(id);
            await loadTransaction();
        } catch (error) {
            alert(error.response?.data?.message || 'Failed to claim transaction');
        } finally {
            setClaiming(false);
        }
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
        }).format(amount || 0);
    };

    const formatDate = (date) => {
        if (!date) return '-';
        return new Date(date).toLocaleString();
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
        );
    }

    if (!transaction) {
        return (
            <div className="text-center py-12">
                <p className="text-gray-500">Transaction not found</p>
                <Link to="/transactions" className="text-blue-600 hover:underline mt-2 inline-block">
                    Back to transactions
                </Link>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <Link to="/transactions" className="text-blue-600 hover:underline text-sm">
                        &larr; Back to transactions
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 mt-2">Transaction Details</h1>
                </div>
                {transaction.status === 'won' && (
                    <Button onClick={handleClaim} loading={claiming}>
                        Mark as Claimed
                    </Button>
                )}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <h2 className="text-lg font-semibold">Bet Information</h2>
                    </CardHeader>
                    <CardBody>
                        <dl className="space-y-4">
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Transaction ID</dt>
                                <dd className="font-mono text-sm">{transaction.transaction_id}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Game Type</dt>
                                <dd className="font-medium">{transaction.game_type}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Numbers</dt>
                                <dd className="font-mono font-bold text-lg">{transaction.numbers?.join(' - ')}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Bet Amount</dt>
                                <dd className="font-medium">{formatCurrency(transaction.amount)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Draw Date</dt>
                                <dd>{transaction.draw_date}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Draw Time</dt>
                                <dd>{transaction.draw_time}</dd>
                            </div>
                        </dl>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader>
                        <h2 className="text-lg font-semibold">Result</h2>
                    </CardHeader>
                    <CardBody>
                        <dl className="space-y-4">
                            <div className="flex justify-between items-center">
                                <dt className="text-gray-500">Status</dt>
                                <dd><StatusBadge status={transaction.status} /></dd>
                            </div>
                            {transaction.win_amount && (
                                <div className="flex justify-between">
                                    <dt className="text-gray-500">Win Amount</dt>
                                    <dd className="font-bold text-green-600 text-lg">
                                        {formatCurrency(transaction.win_amount)}
                                    </dd>
                                </div>
                            )}
                            {transaction.claimed_at && (
                                <div className="flex justify-between">
                                    <dt className="text-gray-500">Claimed At</dt>
                                    <dd>{formatDate(transaction.claimed_at)}</dd>
                                </div>
                            )}
                        </dl>

                        {transaction.status === 'pending' && (
                            <div className="mt-6 p-4 bg-yellow-50 rounded-lg">
                                <p className="text-yellow-800 text-sm">
                                    This transaction is pending. Results will be updated when the draw result is entered.
                                </p>
                            </div>
                        )}

                        {transaction.status === 'won' && (
                            <div className="mt-6 p-4 bg-green-50 rounded-lg">
                                <p className="text-green-800 text-sm">
                                    This is a winning ticket! Click "Mark as Claimed" when the prize has been paid out.
                                </p>
                            </div>
                        )}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader>
                        <h2 className="text-lg font-semibold">Device Information</h2>
                    </CardHeader>
                    <CardBody>
                        <dl className="space-y-4">
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Device Name</dt>
                                <dd>{transaction.device?.device_name || '-'}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Device UUID</dt>
                                <dd className="font-mono text-xs">{transaction.device?.uuid || '-'}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Local User ID</dt>
                                <dd>{transaction.local_user_id || '-'}</dd>
                            </div>
                        </dl>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader>
                        <h2 className="text-lg font-semibold">Timestamps</h2>
                    </CardHeader>
                    <CardBody>
                        <dl className="space-y-4">
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Created Locally</dt>
                                <dd>{formatDate(transaction.local_created_at)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Synced At</dt>
                                <dd>{formatDate(transaction.created_at)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-gray-500">Last Updated</dt>
                                <dd>{formatDate(transaction.updated_at)}</dd>
                            </div>
                        </dl>
                    </CardBody>
                </Card>
            </div>
        </div>
    );
}
