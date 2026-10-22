import { memo } from 'react';

/**
 * PrivacyIcon - Ikon Vektor Mode Privasi (Pilihan 2: Gembok Garis Ramping / Outline Padlock)
 * Menampilkan siluet gembok minimalis modern berbasis garis (stroke) tanpa balok kotak padat,
 * selaras dengan ikon-ikon line art pada sidebar Koperasi App.
 *
 * Props:
 * - active: boolean (true = terkunci / mode privasi aktif, false = terbuka / nonaktif)
 * - size: number | string (default: 20)
 * - className: string
 */
function PrivacyIcon({ active = true, size = 20, className = '', ...props }) {
    const strokeColor = active ? '#16a34a' : '#94a3b8'; // Brand green saat aktif, slate saat nonaktif
    const bodyFill = active ? 'rgba(34, 197, 94, 0.10)' : 'transparent';

    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={`shrink-0 transition-all duration-200 ${className}`}
            aria-hidden="true"
            {...props}
        >
            {/* Tangkai Gembok (Shackle) */}
            {active ? (
                /* Mode Terkunci: Tangkai tertutup sempurna masuk ke badan */
                <path
                    d="M7.5 10.5V6.5C7.5 4.015 9.515 2 12 2C14.485 2 16.5 4.015 16.5 6.5V10.5"
                    stroke={strokeColor}
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            ) : (
                /* Mode Terbuka: Tangkai terangkat ke atas dan terbuka renggang */
                <path
                    d="M7.5 9V5C7.5 2.515 9.515 0.5 12 0.5C14.485 0.5 16.5 2.515 16.5 5V6.8"
                    stroke={strokeColor}
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            )}

            {/* Badan Gembok Ramping (Rounded Squircle Outline - Tanpa Balok Padat) */}
            <rect
                x="3.75"
                y="10.5"
                width="16.5"
                height="11.5"
                rx="3.2"
                stroke={strokeColor}
                strokeWidth="2"
                fill={bodyFill}
            />

            {/* Lubang Kunci Minimalis (Keyhole) */}
            <circle
                cx="12"
                cy="15"
                r="1.3"
                fill={strokeColor}
            />
            <path
                d="M12 16.2V18.5"
                stroke={strokeColor}
                strokeWidth="1.8"
                strokeLinecap="round"
            />
        </svg>
    );
}

export default memo(PrivacyIcon);
