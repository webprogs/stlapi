export default function Badge({ children, variant = 'default', className = '' }) {
    const variants = {
        default: 'bg-gray-100 text-gray-800',
        success: 'bg-green-100 text-green-800',
        warning: 'bg-yellow-100 text-yellow-800',
        danger: 'bg-red-100 text-red-800',
        info: 'bg-blue-100 text-blue-800',
    };

    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${variants[variant]} ${className}`}>
            {children}
        </span>
    );
}

export function StatusBadge({ status }) {
    const statusConfig = {
        pending: { variant: 'warning', label: 'Pending' },
        won: { variant: 'success', label: 'Won' },
        lost: { variant: 'danger', label: 'Lost' },
        claimed: { variant: 'info', label: 'Claimed' },
        active: { variant: 'success', label: 'Active' },
        inactive: { variant: 'danger', label: 'Inactive' },
        success: { variant: 'success', label: 'Success' },
        partial: { variant: 'warning', label: 'Partial' },
        failed: { variant: 'danger', label: 'Failed' },
    };

    const config = statusConfig[status] || { variant: 'default', label: status };

    return <Badge variant={config.variant}>{config.label}</Badge>;
}
