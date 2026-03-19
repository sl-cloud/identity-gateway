import React from 'react';

interface CardProps {
    children: React.ReactNode;
    className?: string;
    padding?: 'none' | 'sm' | 'md' | 'lg';
}

export function Card({ children, className = '', padding = 'md' }: CardProps) {
    const paddingClasses = {
        none: '',
        sm: 'p-4',
        md: 'p-6',
        lg: 'p-8',
    };

    return (
        <div className={`bg-white rounded-lg shadow border border-gray-200 ${paddingClasses[padding]} ${className}`}>
            {children}
        </div>
    );
}

interface CardHeaderProps {
    title: string;
    description?: string;
    action?: React.ReactNode;
}

export function CardHeader({ title, description, action }: CardHeaderProps) {
    return (
        <div className="flex items-start justify-between mb-4">
            <div>
                <h3 className="text-lg font-medium text-gray-900">{title}</h3>
                {description && (
                    <p className="mt-1 text-sm text-gray-500">{description}</p>
                )}
            </div>
            {action && <div>{action}</div>}
        </div>
    );
}

interface CardStatProps {
    title: string;
    value: string | number;
    icon?: React.ReactNode;
    trend?: {
        value: number;
        isPositive: boolean;
    };
}

export function CardStat({ title, value, icon, trend }: CardStatProps) {
    return (
        <div className="bg-white rounded-lg shadow p-6 border border-gray-200">
            <div className="flex items-center">
                {icon && (
                    <div className="flex-shrink-0 p-3 rounded-md bg-indigo-50 text-indigo-600">
                        {icon}
                    </div>
                )}
                <div className={`${icon ? 'ml-4' : ''}`}>
                    <p className="text-sm font-medium text-gray-500">{title}</p>
                    <p className="text-2xl font-semibold text-gray-900">{value}</p>
                    {trend && (
                        <p className={`text-sm ${trend.isPositive ? 'text-green-600' : 'text-red-600'}`}>
                            {trend.isPositive ? '+' : ''}{trend.value}%
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
