import React from 'react';

interface TableProps {
    children: React.ReactNode;
    className?: string;
}

export function Table({ children, className = '' }: TableProps) {
    return (
        <div className={`overflow-x-auto ${className}`}>
            <table className="min-w-full divide-y divide-gray-200">
                {children}
            </table>
        </div>
    );
}

interface TableHeadProps {
    children: React.ReactNode;
}

export function TableHead({ children }: TableHeadProps) {
    return (
        <thead className="bg-gray-50">
            {children}
        </thead>
    );
}

interface TableBodyProps {
    children: React.ReactNode;
}

export function TableBody({ children }: TableBodyProps) {
    return (
        <tbody className="bg-white divide-y divide-gray-200">
            {children}
        </tbody>
    );
}

interface TableRowProps {
    children: React.ReactNode;
    className?: string;
    onClick?: () => void;
}

export function TableRow({ children, className = '', onClick }: TableRowProps) {
    return (
        <tr className={`hover:bg-gray-50 transition-colors ${className}`} onClick={onClick}>
            {children}
        </tr>
    );
}

interface TableHeaderProps {
    children: React.ReactNode;
    className?: string;
}

export function TableHeader({ children, className = '' }: TableHeaderProps) {
    return (
        <th
            scope="col"
            className={`px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ${className}`}
        >
            {children}
        </th>
    );
}

interface TableCellProps {
    children: React.ReactNode;
    className?: string;
}

export function TableCell({ children, className = '' }: TableCellProps) {
    return (
        <td className={`px-6 py-4 whitespace-nowrap text-sm text-gray-900 ${className}`}>
            {children}
        </td>
    );
}

interface EmptyStateProps {
    title: string;
    description?: string;
    icon?: React.ReactNode;
    action?: React.ReactNode;
}

export function EmptyState({ title, description, icon, action }: EmptyStateProps) {
    return (
        <div className="text-center py-12">
            {icon && (
                <div className="mx-auto h-12 w-12 text-gray-400">
                    {icon}
                </div>
            )}
            <h3 className="mt-2 text-sm font-medium text-gray-900">{title}</h3>
            {description && (
                <p className="mt-1 text-sm text-gray-500">{description}</p>
            )}
            {action && (
                <div className="mt-6">
                    {action}
                </div>
            )}
        </div>
    );
}
