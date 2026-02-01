import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import Card, { CardBody, CardHeader } from '../../components/ui/Card';
import Table, { Thead, Tbody, Tr, Th, Td } from '../../components/ui/Table';
import Button from '../../components/ui/Button';
import Pagination from '../../components/ui/Pagination';
import Input, { Select } from '../../components/ui/Input';
import Modal from '../../components/ui/Modal';
import { drawResultApi } from '../../api/client';

export default function DrawResultIndex() {
    const [results, setResults] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        draw_date: '',
        game_type: '',
        draw_time: '',
    });
    const [page, setPage] = useState(1);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [deleteId, setDeleteId] = useState(null);

    useEffect(() => {
        loadResults();
    }, [page]);

    const loadResults = async () => {
        try {
            setLoading(true);
            const params = { page, per_page: 15 };
            Object.keys(filters).forEach(key => {
                if (filters[key]) params[key] = filters[key];
            });
            const response = await drawResultApi.list(params);
            setResults(response.data.data);
            setMeta(response.data);
        } catch (error) {
            console.error('Failed to load results:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleFilter = (e) => {
        e.preventDefault();
        setPage(1);
        loadResults();
    };

    const handleDelete = async () => {
        try {
            await drawResultApi.delete(deleteId);
            setShowDeleteModal(false);
            setDeleteId(null);
            loadResults();
        } catch (error) {
            console.error('Failed to delete:', error);
        }
    };

    const formatDate = (date) => {
        return new Date(date).toLocaleDateString();
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold text-gray-900">Draw Results</h1>
                <Link to="/draw-results/create">
                    <Button>Enter Results</Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <form onSubmit={handleFilter} className="flex flex-wrap gap-4">
                        <Input
                            type="date"
                            value={filters.draw_date}
                            onChange={(e) => setFilters({ ...filters, draw_date: e.target.value })}
                            className="w-auto"
                        />
                        <Select
                            value={filters.game_type}
                            onChange={(e) => setFilters({ ...filters, game_type: e.target.value })}
                            className="w-auto"
                        >
                            <option value="">All Games</option>
                            <option value="SWER2">SWER2</option>
                            <option value="SWER3">SWER3</option>
                            <option value="SWER4">SWER4</option>
                        </Select>
                        <Select
                            value={filters.draw_time}
                            onChange={(e) => setFilters({ ...filters, draw_time: e.target.value })}
                            className="w-auto"
                        >
                            <option value="">All Times</option>
                            <option value="11AM">11AM</option>
                            <option value="4PM">4PM</option>
                            <option value="9PM">9PM</option>
                        </Select>
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
                                        <Th>Date</Th>
                                        <Th>Draw Time</Th>
                                        <Th>Game</Th>
                                        <Th>Winning Numbers</Th>
                                        <Th>Set By</Th>
                                        <Th>Created At</Th>
                                        <Th>Actions</Th>
                                    </Tr>
                                </Thead>
                                <Tbody>
                                    {results.map((result) => (
                                        <Tr key={result.id}>
                                            <Td className="font-medium">{formatDate(result.draw_date)}</Td>
                                            <Td>{result.draw_time}</Td>
                                            <Td>{result.game_type}</Td>
                                            <Td className="font-mono font-bold text-lg text-green-600">
                                                {result.winning_numbers?.join(' - ')}
                                            </Td>
                                            <Td>{result.set_by?.name || '-'}</Td>
                                            <Td className="text-gray-500 text-sm">
                                                {new Date(result.created_at).toLocaleString()}
                                            </Td>
                                            <Td>
                                                <div className="flex gap-1">
                                                    <Link to={`/draw-results/${result.id}/edit`}>
                                                        <Button variant="ghost" size="sm">Edit</Button>
                                                    </Link>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-red-600 hover:text-red-700"
                                                        onClick={() => {
                                                            setDeleteId(result.id);
                                                            setShowDeleteModal(true);
                                                        }}
                                                    >
                                                        Delete
                                                    </Button>
                                                </div>
                                            </Td>
                                        </Tr>
                                    ))}
                                    {results.length === 0 && (
                                        <Tr>
                                            <Td colSpan={7} className="text-center text-gray-500 py-8">
                                                No draw results found
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

            <Modal
                isOpen={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                title="Delete Draw Result"
            >
                <p className="text-gray-600">
                    Are you sure you want to delete this draw result? This will not affect
                    transaction statuses that were already updated.
                </p>
                <div className="mt-6 flex justify-end gap-2">
                    <Button variant="secondary" onClick={() => setShowDeleteModal(false)}>Cancel</Button>
                    <Button variant="danger" onClick={handleDelete}>Delete</Button>
                </div>
            </Modal>
        </div>
    );
}
