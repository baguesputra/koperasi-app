import { useState, useEffect } from 'react';
import { Download, X, Share, PlusSquare } from 'lucide-react';

export default function PwaInstallPrompt({ className = '' }) {
    const [deferredPrompt, setDeferredPrompt] = useState(null);
    const [isIos, setIsIos] = useState(false);
    const [isStandalone, setIsStandalone] = useState(true);
    const [showIosGuide, setShowIosGuide] = useState(false);
    const [dismissed, setDismissed] = useState(true);

    useEffect(() => {
        // Cek apakah sudah berjalan di mode standalone/PWA
        const standalone =
            window.matchMedia('(display-mode: standalone)').matches ||
            window.matchMedia('(display-mode: fullscreen)').matches ||
            window.navigator.standalone === true;

        setIsStandalone(standalone);

        // Jika sudah di PWA, tidak perlu tampilkan banner
        if (standalone) return;

        // Cek apakah pernah ditutup sebelumnya
        const sudahTutup = localStorage.getItem('pwa-prompt-dismissed') === '1';
        setDismissed(sudahTutup);

        // Deteksi iOS Safari
        const userAgent = window.navigator.userAgent.toLowerCase();
        const iosDevice = /iphone|ipad|ipod/.test(userAgent);
        setIsIos(iosDevice);

        // Tangkap event install Android Chrome
        function handleBeforeInstallPrompt(e) {
            e.preventDefault();
            setDeferredPrompt(e);
        }

        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

        return () => {
            window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        };
    }, []);

    if (isStandalone || dismissed) {
        return null;
    }

    // Hanya tampilkan jika event prompt tertangkap (Android) atau di perangkat iOS
    if (!deferredPrompt && !isIos) {
        return null;
    }

    async function handleInstall() {
        if (!deferredPrompt) {
            if (isIos) {
                setShowIosGuide(!showIosGuide);
            }
            return;
        }

        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        if (outcome === 'accepted') {
            setDeferredPrompt(null);
            setDismissed(true);
        }
    }

    function handleTutup() {
        setDismissed(true);
        localStorage.setItem('pwa-prompt-dismissed', '1');
    }

    return (
        <div
            className={`rounded-2xl border border-brand-green/30 bg-gradient-to-r from-brand-navy/90 to-brand-navy p-4 text-white shadow-lg transition-all animate-in fade-in sm:hidden ${className}`}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-brand-green text-white flex items-center justify-center shrink-0 shadow-md shadow-brand-green/30">
                        <Download size={20} />
                    </div>
                    <div className="min-w-0">
                        <p className="text-sm font-bold leading-tight">Pasang Aplikasi Koperasi</p>
                        <p className="text-xs text-slate-300 mt-0.5 leading-snug">
                            Buka full screen tanpa navbar browser dari Layar Utama HP
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    onClick={handleTutup}
                    aria-label="Tutup banner"
                    className="w-7 h-7 rounded-full text-slate-400 hover:text-white hover:bg-white/10 flex items-center justify-center shrink-0 transition-colors"
                >
                    <X size={16} />
                </button>
            </div>

            <div className="mt-3 flex items-center gap-2">
                <button
                    type="button"
                    onClick={handleInstall}
                    className="flex-1 py-2 px-3 text-xs font-bold rounded-xl bg-brand-green hover:bg-brand-green-dark text-white flex items-center justify-center gap-1.5 shadow-md shadow-brand-green/20 transition-all active:scale-95"
                >
                    <Download size={14} />
                    {isIos ? 'Petunjuk Pasang di iPhone' : 'Pasang di Layar Utama (1-Klik)'}
                </button>
            </div>

            {isIos && showIosGuide && (
                <div className="mt-3 p-3 bg-white/10 rounded-xl text-xs space-y-1.5 border border-white/10">
                    <p className="font-semibold text-brand-green-light">Cara Pasang di Safari iPhone:</p>
                    <div className="flex items-center gap-2 text-slate-200">
                        <Share size={14} className="shrink-0 text-brand-green-light" />
                        <span>1. Tekan tombol <strong>Bagikan (Share)</strong> di baris bawah Safari.</span>
                    </div>
                    <div className="flex items-center gap-2 text-slate-200">
                        <PlusSquare size={14} className="shrink-0 text-brand-green-light" />
                        <span>2. Gulir ke bawah, pilih <strong>'Tambahkan ke Layar Utama'</strong>.</span>
                    </div>
                </div>
            )}
        </div>
    );
}

