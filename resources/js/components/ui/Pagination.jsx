import Button from './Button';

export default function Pagination({ meta, onPageChange }) {
    if (!meta || meta.last_page <= 1) return null;

    const { current_page, last_page, from, to, total } = meta;

    return (
        <div className="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
            <div className="flex-1 flex justify-between sm:hidden">
                <Button
                    variant="secondary"
                    disabled={current_page === 1}
                    onClick={() => onPageChange(current_page - 1)}
                >
                    Previous
                </Button>
                <Button
                    variant="secondary"
                    disabled={current_page === last_page}
                    onClick={() => onPageChange(current_page + 1)}
                >
                    Next
                </Button>
            </div>
            <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p className="text-sm text-gray-700">
                        Showing <span className="font-medium">{from}</span> to{' '}
                        <span className="font-medium">{to}</span> of{' '}
                        <span className="font-medium">{total}</span> results
                    </p>
                </div>
                <div>
                    <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <button
                            onClick={() => onPageChange(current_page - 1)}
                            disabled={current_page === 1}
                            className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>
                        {[...Array(last_page)].map((_, index) => {
                            const page = index + 1;
                            // Show first page, last page, and pages around current
                            if (
                                page === 1 ||
                                page === last_page ||
                                (page >= current_page - 1 && page <= current_page + 1)
                            ) {
                                return (
                                    <button
                                        key={page}
                                        onClick={() => onPageChange(page)}
                                        className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                            page === current_page
                                                ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                        }`}
                                    >
                                        {page}
                                    </button>
                                );
                            } else if (
                                page === current_page - 2 ||
                                page === current_page + 2
                            ) {
                                return (
                                    <span
                                        key={page}
                                        className="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                                    >
                                        ...
                                    </span>
                                );
                            }
                            return null;
                        })}
                        <button
                            onClick={() => onPageChange(current_page + 1)}
                            disabled={current_page === last_page}
                            className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    );
}
