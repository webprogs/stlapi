import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import Card, { CardBody, CardHeader, CardFooter } from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import { deviceApi } from '../../api/client';

export default function DeviceCreate() {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [createdDevice, setCreatedDevice] = useState(null);
    const [formData, setFormData] = useState({
        device_name: '',
    });

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await deviceApi.create(formData);
            setCreatedDevice(response.data.device);
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to create device');
        } finally {
            setLoading(false);
        }
    };

    if (createdDevice) {
        return (
            <div className="space-y-6">
                <div>
                    <Link to="/devices" className="text-blue-600 hover:underline text-sm">
                        &larr; Back to devices
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 mt-2">Device Created</h1>
                </div>

                <Card>
                    <CardHeader>
                        <h2 className="text-lg font-semibold text-green-600">Device created successfully!</h2>
                        <p className="text-sm text-gray-500 mt-1">
                            Please save the API credentials below. The API key will not be shown again.
                        </p>
                    </CardHeader>
                    <CardBody className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500">Device Name</label>
                            <p className="mt-1 text-gray-900">{createdDevice.device_name}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">Device UUID (X-Device-ID header)</label>
                            <div className="mt-1 flex items-center gap-2">
                                <code className="flex-1 p-2 bg-gray-100 rounded font-mono text-sm break-all">
                                    {createdDevice.uuid}
                                </code>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => navigator.clipboard.writeText(createdDevice.uuid)}
                                >
                                    Copy
                                </Button>
                            </div>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">API Key (X-API-Key header)</label>
                            <div className="mt-1 flex items-center gap-2">
                                <code className="flex-1 p-2 bg-yellow-50 border border-yellow-200 rounded font-mono text-sm break-all">
                                    {createdDevice.api_key}
                                </code>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => navigator.clipboard.writeText(createdDevice.api_key)}
                                >
                                    Copy
                                </Button>
                            </div>
                            <p className="mt-1 text-xs text-yellow-600">
                                Save this key now - it won't be shown again!
                            </p>
                        </div>
                    </CardBody>
                    <CardFooter>
                        <div className="flex justify-end gap-2">
                            <Button variant="secondary" onClick={() => {
                                setCreatedDevice(null);
                                setFormData({ device_name: '' });
                            }}>
                                Create Another
                            </Button>
                            <Button onClick={() => navigate(`/devices/${createdDevice.id}`)}>
                                View Device
                            </Button>
                        </div>
                    </CardFooter>
                </Card>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div>
                <Link to="/devices" className="text-blue-600 hover:underline text-sm">
                    &larr; Back to devices
                </Link>
                <h1 className="text-2xl font-bold text-gray-900 mt-2">Add Device</h1>
            </div>

            <Card>
                <form onSubmit={handleSubmit}>
                    <CardHeader>
                        <h2 className="text-lg font-semibold">Device Information</h2>
                    </CardHeader>
                    <CardBody className="space-y-4">
                        {error && (
                            <div className="p-3 rounded-lg bg-red-50 text-red-700 text-sm">
                                {error}
                            </div>
                        )}
                        <Input
                            label="Device Name"
                            placeholder="e.g., Store 1 - Main Terminal"
                            value={formData.device_name}
                            onChange={(e) => setFormData({ ...formData, device_name: e.target.value })}
                            required
                            autoFocus
                        />
                        <p className="text-sm text-gray-500">
                            A unique identifier for this device. This will be visible in reports and analytics.
                        </p>
                    </CardBody>
                    <CardFooter className="flex justify-end gap-2">
                        <Link to="/devices">
                            <Button variant="secondary" type="button">Cancel</Button>
                        </Link>
                        <Button type="submit" loading={loading}>Create Device</Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    );
}
