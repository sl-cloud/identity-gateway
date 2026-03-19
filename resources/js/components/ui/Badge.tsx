import React from 'react';

interface BadgeProps {
    children: React.ReactNode;
    variant?: 'default' | 'success' | 'warning' | 'error' | 'info' | 'neutral';
    size?: 'sm' | 'md';
}

export function Badge({ children, variant = 'default', size = 'md' }: BadgeProps) {
    const variantClasses = {
        default: 'bg-indigo-100 text-indigo-800 border-indigo-200',
        success: 'bg-green-100 text-green-800 border-green-200',
        warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        error: 'bg-red-100 text-red-800 border-red-200',
        info: 'bg-blue-100 text-blue-800 border-blue-200',
        neutral: 'bg-gray-100 text-gray-800 border-gray-200',
    };

    const sizeClasses = {
        sm: 'px-2 py-0.5 text-xs',
        md: 'px-2.5 py-0.5 text-sm',
    };

    return (
        <span className={`inline-flex items-center font-medium rounded-full border ${variantClasses[variant]} ${sizeClasses[size]}`}>
            {children}
        </span>
    );
}
