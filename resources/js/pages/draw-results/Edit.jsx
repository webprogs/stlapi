import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import Card, { CardBody, CardHeader, CardFooter } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import { drawResultApi } from '../../api/client';

const GAME_CONFIGS = {
    SWER2: { digits: 2, multiplier: 80 },
    SWER3: { digits: 3, multiplier: 400 },
    SWER4: { digits: 4, multiplier: 4000 },
};

export default function DrawResultEdit() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [result, setResult] = useState(null);
    const [numbers, setNumbers] = useState(['', '', '', '']);

    useEffect(() => {
        loadResult();
    }, [id]);

    const loadResult = async () => {
        try {
            const response = await drawResultApi.get(id);
            const data = response.data;
            setResult(data);
            setNumbers(data.winning_numbers.map(String));
        } catch (error) {
            console.error('Failed to load result:', error);
        } finally {
            setLoading(false);
        }
    };

    const gameConfig = result ? GAME_CONFIGS[result.game_type] : null;
    const digitCount = gameConfig?.digits || 0;

    const handleNumberChange = (index, value) => {
        if (value.length > 1) value = value.slice(-1);
        if (value && !/^[0-9]$/.test(value)) return;

        const newNumbers = [...numbers];
        newNumbers[index] = value;
        setNumbers(newNumbers);

        if (value && index < digitCount - 1) {
            const nextInput = document.getElementById(`digit-${index + 1}`);
            if (nextInput) nextInput.focus();
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setError('');

        const winningNumbers = numbers.slice(0, digitCount).map(n => parseInt(n, 10));

        if (winningNumbers.some(n => isNaN(n))) {
            setError('Please enter all winning numbers');
            setSaving(false);
            return;
        }

        try {
            await drawResultApi.update(id, { winning_numbers: winningNumbers });
            navigate('/draw-results');
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to update draw result');
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
        );
    }

    if (!result) {
        return (
            <div className="text-center py-12">
                <p className="text-gray-500">Draw result not found</p>
                <Link to="/draw-results" className="text-blue-600 hover:underline mt-2 inline-block">
                    Back to draw results
                </Link>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div>
                <Link to="/draw-results" className="text-blue-600 hover:underline text-sm">
                    &larr; Back to draw results
                </Link>
                <h1 className="text-2xl font-bold text-gray-900 mt-2">Edit Draw Result</h1>
            </div>

            <Card>
                <form onSubmit={handleSubmit}>
                    <CardHeader>
                        <h2 className="text-lg font-semibold">
                            {result.game_type} - {result.draw_date} {result.draw_time}
                        </h2>
                    </CardHeader>
                    <CardBody className="space-y-6">
                        {error && (
                            <div className="p-3 rounded-lg bg-red-50 text-red-700 text-sm">
                                {error}
                            </div>
                        )}

                        <div className="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <span className="text-gray-500">Draw Date:</span>
                                <span className="ml-2 font-medium">{result.draw_date}</span>
                            </div>
                            <div>
                                <span className="text-gray-500">Draw Time:</span>
                                <span className="ml-2 font-medium">{result.draw_time}</span>
                            </div>
                            <div>
                                <span className="text-gray-500">Game:</span>
                                <span className="ml-2 font-medium">{result.game_type}</span>
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Winning Numbers ({digitCount} digits)
                            </label>
                            <div className="flex gap-4">
                                {[...Array(digitCount)].map((_, index) => (
                                    <input
                                        key={index}
                                        id={`digit-${index}`}
                                        type="text"
                                        inputMode="numeric"
                                        maxLength={1}
                                        value={numbers[index]}
                                        onChange={(e) => handleNumberChange(index, e.target.value)}
                                        className="w-16 h-16 text-center text-3xl font-bold border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        required
                                    />
                                ))}
                            </div>
                        </div>

                        <div className="p-4 bg-yellow-50 rounded-lg">
                            <p className="text-yellow-800 text-sm">
                                <strong>Warning:</strong> Updating this result will re-process all transactions
                                for this draw. Transactions that were previously marked as won/lost may change status.
                            </p>
                        </div>
                    </CardBody>
                    <CardFooter className="flex justify-end gap-2">
                        <Link to="/draw-results">
                            <Button variant="secondary" type="button">Cancel</Button>
                        </Link>
                        <Button type="submit" loading={saving}>
                            Update Result
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    );
}
