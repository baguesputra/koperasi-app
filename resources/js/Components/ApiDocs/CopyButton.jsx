import { useState } from 'react';
import { Check, Copy } from 'lucide-react';

/**
 * Tombol salin teks ke clipboard dengan fallback execCommand untuk konteks non-HTTPS.
 */
export default function CopyButton({ text, label = 'Salin', className = '', tone = 'light' }) {
    const [copied, setCopied] = useState(false);

    const handleCopy = async (event) => {
        event.stopPropagation();

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(text);
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }

            setCopied(true);
            setTimeout(() => setCopied(false), 1800);
        } catch {
            // Abaikan kegagalan izin clipboard dari browser
        }
    };

    const toneClasses =
        tone === 'dark'
            ? copied
                ? 'border-brand-green/50 bg-brand-green/15 text-emerald-300'
                : 'border-white/15 bg-white/5 text-slate-300 hover:border-white/25 hover:bg-white/10 hover:text-white'
            : copied
              ? 'border-brand-green/40 bg-brand-green-light text-brand-green-dark'
              : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900';

    return (
        <button
            type="button"
            onClick={handleCopy}
            className={`inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-xs font-medium transition ${toneClasses} ${className}`}
            title={copied ? 'Disalin' : label}
        >
            {copied ? (
                <>
                    <Check className="h-3.5 w-3.5" aria-hidden="true" />
                    <span>Disalin</span>
                </>
            ) : (
                <>
                    <Copy className="h-3.5 w-3.5" aria-hidden="true" />
                    <span>{label}</span>
                </>
            )}
        </button>
    );
}
