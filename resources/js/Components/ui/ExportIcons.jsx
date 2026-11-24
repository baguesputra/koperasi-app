export function ExcelIcon({ size = 18, className = '' }) {
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
            className={`shrink-0 ${className}`}
        >
            <rect x="7" y="3" width="14.5" height="18" rx="2.2" fill="#E6F4EC" stroke="#1D6F42" strokeWidth="1.5" />
            <path
                d="M14.5 8.5H18.5M14.5 12H18.5M14.5 15.5H18.5"
                stroke="#1D6F42"
                strokeWidth="1.25"
                strokeLinecap="round"
                opacity="0.7"
            />
            <rect x="2.5" y="6" width="12" height="12" rx="2.5" fill="#1D6F42" />
            <path
                d="M6.1 9.2L10.9 14.8M10.9 9.2L6.1 14.8"
                stroke="#FFFFFF"
                strokeWidth="1.9"
                strokeLinecap="round"
            />
        </svg>
    );
}

export function PdfIcon({ size = 18, className = '' }) {
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
            className={`shrink-0 ${className}`}
        >
            <path
                d="M7.2 2.5H14.5L19 7V19.3C19 20.5 18.1 21.5 16.9 21.5H7.2C6 21.5 5 20.5 5 19.3V4.7C5 3.5 6 2.5 7.2 2.5Z"
                fill="#FEF2F2"
                stroke="#DC2626"
                strokeWidth="1.5"
                strokeLinejoin="round"
            />
            <path
                d="M14.5 2.5V7H19"
                fill="#FECACA"
                stroke="#DC2626"
                strokeWidth="1.4"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <rect x="2" y="10.5" width="16" height="8" rx="2" fill="#DC2626" />
            <path
                d="M4.4 16.6V12.4H5.9C6.55 12.4 6.95 12.8 6.95 13.45C6.95 14.1 6.55 14.5 5.9 14.5H4.4"
                stroke="#FFFFFF"
                strokeWidth="1.35"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M8.6 16.6V12.4H9.9C10.95 12.4 11.6 13.25 11.6 14.5C11.6 15.75 10.95 16.6 9.9 16.6H8.6Z"
                stroke="#FFFFFF"
                strokeWidth="1.35"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M13.3 16.6V12.4H15.6M13.3 14.5H15.1"
                stroke="#FFFFFF"
                strokeWidth="1.35"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
