import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import Card, { CardBody, CardHeader, CardFooter } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input, { Select } from '../../components/ui/Input';
import { drawResultApi } from '../../api/client';

const GAME_CONFIGS = {
    SWER2: { digits: 2, multiplier: 80 },
    SWER3: { digits: 3, multiplier: 400 },
    SWER4: { digits: 4, multiplier: 4000 },
};

export default function DrawResultCreate() {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [formData, setFormData] = useState({
        draw_date: new Date().toISOString().split('T')[0],
        draw_time: '',
        game_type: '',
        winning_numbers: ['', '', '', ''],
    });

    const gameConfig = GAME_CONFIGS[formData.game_type];
    const digitCount = gameConfig?.digits || 0;

    const handleNumberChange = (index, value) => {
        // Only allow single digits
        if (value.length > 1) value = value.slice(-1);
        if (value && !/^[0-9]$/.test(value)) return;

        const newNumbers = [...formData.winning_numbers];
        newNumbers[index] = value;
        setFormData({ ...formData, winning_numbers: newNumbers });

        // Auto-focus next input
        if (value && index < digitCount - 1) {
            const nextInput = document.getElementById(`digit-${index + 1}`);
            if (nextInput) nextInput.focus();
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        const numbers = formData.winning_numbers.slice(0, digitCount).map(n => parseInt(n, 10));

        if (numbers.some(n => isNaN(n))) {
            setError('Please enter all winning numbers');
            setLoading(false);
            return;
        }

        try {
            await drawResultApi.create({
                draw_date: formData.draw_date,
                draw_time: formData.draw_time,
                game_type: formData.game_type,
                winning_numbers: numbers,
            });
            navigate('/draw-results');
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to create draw result');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-6">
            <div>
                <Link to="/draw-results" className="text-blue-600 hover:underline text-sm">
                    &larr; Back to draw results
                </Link>
                <h1 className="text-2xl font-bold text-gray-900 mt-2">Enter Draw Result</h1>
            </div>

            <Card>
                <form onSubmit={handleSubmit}>
                    <CardHeader>
                        <h2 className="text-lg font-semibold">Draw Information</h2>
                    </CardHeader>
                    <CardBody className="space-y-6">
                        {error && (
                            <div className="p-3 rounded-lg bg-red-50 text-red-700 text-sm">
                                {error}
                            </div>
                        )}

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Input
                                label="Draw Date"
                                type="date"
                                value={formData.draw_date}
                                onChange={(e) => setFormData({ ...formData, draw_date: e.target.value })}
                                required
                            />
                            <Select
                                label="Draw Time"
                                value={formData.draw_time}
                                onChange={(e) => setFormData({ ...formData, draw_time: e.target.value })}
                                required
                            >
                                <option value="">Select time</option>
                                <option value="11AM">11AM</option>
                                <option value="4PM">4PM</option>
                                <option value="9PM">9PM</option>
                            </Select>
                            <Select
                                label="Game Type"
                                value={formData.game_type}
                                onChange={(e) => setFormData({
                                    ...formData,
                                    game_type: e.target.value,
                                    winning_numbers: ['', '', '', ''],
                                })}
                                required
                            >
                                <option value="">Select game</option>
                                <option value="SWER2">SWER2 (2 digits)</option>
                                <option value="SWER3">SWER3 (3 digits)</option>
                                <option value="SWER4">SWER4 (4 digits)</option>
                            </Select>
                        </div>

                        {formData.game_type && (
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
                                            value={formData.winning_numbers[index]}
                                            onChange={(e) => handleNumberChange(index, e.target.value)}
                                            className="w-16 h-16 text-center text-3xl font-bold border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                            required
                                        />
                                    ))}
                                </div>
                                <p className="mt-2 text-sm text-gray-500">
                                    Enter each digit (0-9). {gameConfig.multiplier}x multiplier for winners.
                                </p>
                            </div>
                        )}

                        {formData.game_type && formData.draw_time && (
                            <div className="p-4 bg-blue-50 rounded-lg">
                                <p className="text-blue-800 text-sm">
                                    <strong>Note:</strong> Entering this result will automatically update all
                                    pending {formData.game_type} bets for {formData.draw_date} {formData.draw_time} draw
                                    to either "won" or "lost" status.
                                </p>
                            </div>
                        )}
                    </CardBody>
                    <CardFooter className="flex justify-end gap-2">
                        <Link to="/draw-results">
                            <Button variant="secondary" type="button">Cancel</Button>
                        </Link>
                        <Button type="submit" loading={loading} disabled={!formData.game_type}>
                            Save Result
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    );
}
