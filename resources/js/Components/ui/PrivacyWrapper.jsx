import { useState, useRef } from 'react';
import { usePrivacy } from '@/Contexts/PrivacyContext';

/**
 * Komponen pembungkus serbaguna untuk menyembunyikan/menyamarkan data sensitif.
 *
 * Props:
 * - children: Elemen atau teks yang dibungkus
 * - maskType: 'blur' | 'placeholder' (default: 'blur')
 * - allowPeek: boolean (default: true) -> arahkan kursor untuk mengintip
 * - placeholder: string (default: '••••••••')
 * - className: string
 * - as: string (default: 'span')
 */
export default function PrivacyWrapper({
    children,
    maskType = 'blur',
    allowPeek = true,
    placeholder = '••••••••',
    className = '',
    as: Component = 'span',
    ...rest
}) {
    const { isPrivacyActive } = usePrivacy();
    const [isPeeking, setIsPeeking] = useState(false);
    const peekTimerRef = useRef(null);

    const handleMouseEnter = () => {
        if (!isPrivacyActive || !allowPeek) return;
        peekTimerRef.current = setTimeout(() => {
            setIsPeeking(true);
        }, 180);
    };

    const handleMouseLeave = () => {
        if (peekTimerRef.current) {
            clearTimeout(peekTimerRef.current);
            peekTimerRef.current = null;
        }
        setIsPeeking(false);
    };

    if (!isPrivacyActive) {
        return <Component className={className} {...rest}>{children}</Component>;
    }

    if (maskType === 'placeholder') {
        return (
            <Component
                className={`inline-block font-mono tracking-wider transition-opacity ${className} ${allowPeek ? 'cursor-pointer' : ''}`}
                title={allowPeek ? 'Tahan kursor untuk mengintip' : undefined}
                onMouseEnter={handleMouseEnter}
                onMouseLeave={handleMouseLeave}
                {...rest}
            >
                {isPeeking ? children : placeholder}
            </Component>
        );
    }

    // Default: 'blur'
    return (
        <Component
            className={`inline-block transition-[filter,opacity] duration-250 ease-out select-none ${
                isPeeking ? 'filter-none opacity-100' : 'blur-[6px] opacity-75'
            } ${allowPeek ? 'cursor-pointer hover:filter-none hover:opacity-100' : ''} ${className}`}
            title={allowPeek ? 'Tahan kursor untuk mengintip' : undefined}
            onMouseEnter={handleMouseEnter}
            onMouseLeave={handleMouseLeave}
            {...rest}
        >
            {children}
        </Component>
    );
}

