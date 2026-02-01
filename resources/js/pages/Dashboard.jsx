import { useState, useEffect } from 'react';
import Card, { CardBody, CardHeader } from '../components/ui/Card';
import { analyticsApi } from '../api/client';

export default function Dashboard() {
    const [summary, setSummary] = useState(null);
    const [byGame, setByGame] = useState([]);
    const [byDrawTime, setByDrawTime] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        try {
            const [summaryRes, gameRes, timeRes] = await Promise.all([
                analyticsApi.summary(),
                analyticsApi.byGame(),
                analyticsApi.byDrawTime(),
            ]);
            setSummary(summaryRes.data);
            setByGame(gameRes.data.data);
            setByDrawTime(timeRes.data.data);
        } catch (error) {
            console.error('Failed to load analytics:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
        );
    }

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
        }).format(amount || 0);
    };

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>

            {/* Summary Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardBody>
                        <p className="text-sm font-medium text-gray-500">Total Bets</p>
                        <p className="text-2xl font-bold text-gray-900">
                            {formatCurrency(summary?.financial?.total_bets)}
                        </p>
                        <p className="text-sm text-gray-500">
                            {summary?.transactions?.total || 0} transactions
                        </p>
                    </CardBody>
                </Card>

                <Card>
                    <CardBody>
                        <p className="text-sm font-medium text-gray-500">Total Winnings</p>
                        <p className="text-2xl font-bold text-red-600">
                            {formatCurrency(summary?.financial?.total_winnings)}
                        </p>
                        <p className="text-sm text-gray-500">
                            {summary?.transactions?.won || 0} winners
                        </p>
                    </CardBody>
                </Card>

                <Card>
                    <CardBody>
                        <p className="text-sm font-medium text-gray-500">Net Earnings</p>
                        <p className="text-2xl font-bold text-green-600">
                            {formatCurrency(summary?.financial?.net_earnings)}
                        </p>
                        <p className="text-sm text-gray-500">
                            After 15% commission
                        </p>
                    </CardBody>
                </Card>

                <Card>
                    <CardBody>
                        <p className="text-sm font-medium text-gray-500">Active Devices</p>
                        <p className="text-2xl font-bold text-gray-900">
                            {summary?.devices?.active_last_24h || 0}
                        </p>
                        <p className="text-sm text-gray-500">
                            of {summary?.devices?.total || 0} total
                        </p>
                    </CardBody>
                </Card>
            </div>

            {/* Transaction Status */}
            <Card>
                <CardHeader>
                    <h2 className="text-lg font-semibold">Transaction Status</h2>
                </CardHeader>
                <CardBody>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="text-center p-4 bg-yellow-50 rounded-lg">
                            <p className="text-2xl font-bold text-yellow-600">
                                {summary?.transactions?.pending || 0}
                            </p>
                            <p className="text-sm text-gray-600">Pending</p>
                        </div>
                        <div className="text-center p-4 bg-green-50 rounded-lg">
                            <p className="text-2xl font-bold text-green-600">
                                {summary?.transactions?.won || 0}
                            </p>
                            <p className="text-sm text-gray-600">Won</p>
                        </div>
                        <div className="text-center p-4 bg-red-50 rounded-lg">
                            <p className="text-2xl font-bold text-red-600">
                                {summary?.transactions?.lost || 0}
                            </p>
                            <p className="text-sm text-gray-600">Lost</p>
                        </div>
                        <div className="text-center p-4 bg-blue-50 rounded-lg">
                            <p className="text-2xl font-bold text-blue-600">
                                {summary?.transactions?.claimed || 0}
                            </p>
                            <p className="text-sm text-gray-600">Claimed</p>
                        </div>
                    </div>
                </CardBody>
            </Card>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* By Game Type */}
                <Card>
                    <CardHeader>
                        <h2 className="text-lg font-semibold">By Game Type</h2>
                    </CardHeader>
                    <CardBody>
                        <div className="space-y-4">
                            {byGame.map((game) => (
                                <div key={game.game_type} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p className="font-medium">{game.game_type}</p>
                                        <p className="text-sm text-gray-500">{game.total_bets} bets</p>
                                    </div>
                                    <div className="text-right">
                                        <p className="font-medium">{formatCurrency(game.total_amount)}</p>
                                        <p className="text-sm text-green-600">{formatCurrency(game.net_earnings)} net</p>
                                    </div>
                                </div>
                            ))}
                            {byGame.length === 0 && (
                                <p className="text-gray-500 text-center py-4">No data available</p>
                            )}
                        </div>
                    </CardBody>
                </Card>

                {/* By Draw Time */}
                <Card>
                    <CardHeader>
                        <h2 className="text-lg font-semibold">By Draw Time</h2>
                    </CardHeader>
                    <CardBody>
                        <div className="space-y-4">
                            {byDrawTime.map((time) => (
                                <div key={time.draw_time} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p className="font-medium">{time.draw_time}</p>
                                        <p className="text-sm text-gray-500">{time.total_bets} bets</p>
                                    </div>
                                    <div className="text-right">
                                        <p className="font-medium">{formatCurrency(time.total_amount)}</p>
                                        <p className="text-sm text-green-600">{formatCurrency(time.net_earnings)} net</p>
                                    </div>
                                </div>
                            ))}
                            {byDrawTime.length === 0 && (
                                <p className="text-gray-500 text-center py-4">No data available</p>
                            )}
                        </div>
                    </CardBody>
                </Card>
            </div>
        </div>
    );
}
