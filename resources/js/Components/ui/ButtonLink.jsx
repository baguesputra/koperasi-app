import { Link } from '@inertiajs/react';
import { buttonBase, buttonVariants, buttonSizes } from './Button';

export default function ButtonLink({
    children,
    variant = 'primary',
    size = 'md',
    className = '',
    ...props
}) {
    return (
        <Link
            className={`${buttonBase} ${buttonVariants[variant]} ${buttonSizes[size]} ${className}`}
            {...props}
        >
            {children}
        </Link>
    );
}