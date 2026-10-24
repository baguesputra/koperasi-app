import { useState, useEffect, useRef, useCallback } from 'react';
import { usePage, router } from '@inertiajs/react';
import axios from 'axios';
import { usePrivacy } from '@/Contexts/PrivacyContext';
import { Lock, LogOut, ShieldAlert, Delete, RefreshCw } from 'lucide-react';
import FotoAnggota from '@/Components/ui/FotoAnggota';

export default function ScreenLockModal() {
    const { isScreenLocked, unlockScreen } = usePrivacy();
    const { auth } = usePage().props;
    const user = auth?.user;

    const [pin, setPin] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [remaining, setRemaining] = useState(null);
    const [isLockedOut, setIsLockedOut] = useState(false);
    const [shake, setShake] = useState(false);

    const inputRef = useRef(null);

    // Otomatis fokus ke input saat layar terkunci
    useEffect(() => {
        if (isScreenLocked) {
            setPin('');
            setError('');
            setRemaining(null);
            setIsLockedOut(false);
            const t = setTimeout(() => {
                inputRef.current?.focus();
            }, 100);
            return () => clearTimeout(t);
        }
    }, [isScreenLocked]);

    // Handle submit PIN ke backend
    const submitPin = useCallback(async (pinToVerify) => {
        if (loading || isLockedOut || pinToVerify.length !== 6) return;

        setLoading(true);
        setError('');

        try {
            const res = await axios.post(route('security.pin.verify-unlock'), {
                pin: pinToVerify,
            });

            if (res.data?.status === 'success') {
                setPin('');
                setError('');
                setRemaining(null);
                unlockScreen();
            }
        } catch (err) {
            const res = err.response;
            setShake(true);
            setTimeout(() => setShake(false), 500);

            if (res?.status === 423) {
                // Sesi terkunci permanen & force logout
                setIsLockedOut(true);
                setError(res.data?.message || 'Percobaan habis. Sesi ditutup demi keamanan.');
                setTimeout(() => {
                    window.location.href = res.data?.redirect || route('login');
                }, 1800);
            } else if (res?.status === 422) {
                const sisa = res.data?.remaining ?? (remaining !== null ? remaining - 1 : 2);
                setRemaining(sisa);
                setError(res.data?.message || `PIN salah. Sisa kesempatan: ${sisa} kali.`);
                setPin('');
                inputRef.current?.focus();
            } else {
                setError('Gagal memverifikasi PIN. Silakan coba lagi.');
                setPin('');
                inputRef.current?.focus();
            }
        } finally {
            setLoading(false);
        }
    }, [loading, isLockedOut, remaining, unlockScreen]);

    // Handle input fisik atau on-screen keypad
    const handleDigitPress = (digit) => {
        if (loading || isLockedOut) return;
        if (pin.length < 6) {
            const newPin = pin + digit;
            setPin(newPin);
            if (newPin.length === 6) {
                submitPin(newPin);
            }
        }
    };

    const handleBackspace = () => {
        if (loading || isLockedOut) return;
        setPin((prev) => prev.slice(0, -1));
        setError('');
    };

    const handleClear = () => {
        if (loading || isLockedOut) return;
        setPin('');
        setError('');
    };

    // Keyboard global listener saat modal aktif
    useEffect(() => {
        if (!isScreenLocked || isLockedOut) return undefined;

        const handleKeyDown = (e) => {
            // Cegah inspect element atau navigasi tombol jika mau, namun tetap izinkan typing
            if (e.key >= '0' && e.key <= '9') {
                e.preventDefault();
                handleDigitPress(e.key);
            } else if (e.key === 'Backspace') {
                e.preventDefault();
                handleBackspace();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                handleClear();
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isScreenLocked, isLockedOut, pin]);

    // Jika tidak terkunci atau tidak ada sesi login, jangan tampilkan modal
    if (!isScreenLocked || !user) {
        return null;
    }

    const handleLogout = () => {
        router.post(route('logout'));
    };

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label="Layar Terkunci"
            className="fixed inset-0 z-[99999] bg-slate-950/85 backdrop-blur-lg flex flex-col items-center justify-center p-4 select-none overflow-y-auto"
            onClick={() => inputRef.current?.focus()}
        >
            {/* Input tak kasat mata untuk mempertahankan fokus keyboard mobile/desktop */}
            <input
                ref={inputRef}
                type="password"
                inputMode="numeric"
                pattern="[0-9]*"
                maxLength={6}
                value={pin}
                onChange={(e) => {
                    const val = e.target.value.replace(/\D/g, '').slice(0, 6);
                    setPin(val);
                    if (val.length === 6) submitPin(val);
                }}
                className="opacity-0 absolute pointer-events-none w-0 h-0"
                autoFocus
                tabIndex={-1}
            />

            <div className={`w-full max-w-sm bg-white/95 backdrop-blur-md border border-white/20 rounded-3xl p-6 sm:p-7 shadow-2xl flex flex-col items-center text-center transition-transform ${shake ? 'animate-shake' : ''}`}>
                {/* Avatar Pengguna dengan Icon Gembok */}
                <div className="relative mb-3">
                    <FotoAnggota
                        nama={user.name}
                        fotoUrl={user.foto_url}
                        ukuran="lg"
                        className="w-16 h-16 ring-4 ring-emerald-500/30 shadow-lg"
                    />
                    <div className="absolute -bottom-1 -right-1 bg-emerald-600 text-white p-1.5 rounded-full shadow-md">
                        <Lock size={14} className="stroke-[2.5]" />
                    </div>
                </div>

                {/* Nama & Info Sesi */}
                <h3 className="text-lg font-bold text-slate-800 tracking-tight leading-snug">
                    {user.name}
                </h3>
                <p className="text-xs text-slate-500 mt-0.5">
                    {user.no_karyawan ? `NIK: ${user.no_karyawan}` : 'Sesi Terkunci'}
                </p>

                {/* Badge Sesi Terkunci */}
                <div className="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-600 text-xs font-medium">
                    <span className="w-2 h-2 rounded-full bg-amber-500 animate-pulse" />
                    Sesi dikunci karena inaktivitas
                </div>

                {/* Indikator 6 Digit PIN */}
                <div className="mt-6 flex items-center justify-center gap-2.5">
                    {[0, 1, 2, 3, 4, 5].map((idx) => {
                        const isFilled = pin.length > idx;
                        const isCurrent = pin.length === idx;
                        return (
                            <div
                                key={idx}
                                className={`w-10 h-12 rounded-xl border flex items-center justify-center transition-all duration-200 ${
                                    isFilled
                                        ? 'border-emerald-500 bg-emerald-50/50 scale-105 shadow-sm'
                                        : isCurrent
                                        ? 'border-emerald-400 bg-white ring-2 ring-emerald-400/30'
                                        : 'border-slate-200 bg-slate-50/60'
                                }`}
                            >
                                {isFilled ? (
                                    <div className="w-3.5 h-3.5 rounded-full bg-emerald-600 shadow-sm animate-scale-up" />
                                ) : (
                                    <span className="text-slate-300 text-xs">•</span>
                                )}
                            </div>
                        );
                    })}
                </div>

                {/* Pesan Error / Sisa Kesempatan */}
                <div className="mt-3 min-h-[36px] flex items-center justify-center px-2">
                    {loading ? (
                        <div className="inline-flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                            <RefreshCw size={13} className="animate-spin text-emerald-600" />
                            Memverifikasi PIN...
                        </div>
                    ) : error ? (
                        <div
                            className={`inline-flex items-center gap-1.5 text-xs font-semibold ${
                                isLockedOut || remaining === 1
                                    ? 'text-red-600 animate-pulse'
                                    : 'text-amber-600'
                            }`}
                        >
                            <ShieldAlert size={14} className="shrink-0" />
                            <span>{error}</span>
                        </div>
                    ) : (
                        <p className="text-xs text-slate-400">
                            Masukkan 6 digit PIN untuk membuka kunci
                        </p>
                    )}
                </div>

                {/* Touch Keypad (Untuk Smartphone / Tablet) */}
                <div className="mt-3 w-full grid grid-cols-3 gap-2 max-w-[260px]">
                    {[1, 2, 3, 4, 5, 6, 7, 8, 9].map((digit) => (
                        <button
                            key={digit}
                            type="button"
                            disabled={loading || isLockedOut}
                            onClick={() => handleDigitPress(String(digit))}
                            className="h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 active:bg-slate-300 active:scale-95 text-slate-800 text-lg font-bold flex items-center justify-center transition-all disabled:opacity-40"
                        >
                            {digit}
                        </button>
                    ))}
                    <button
                        type="button"
                        disabled={loading || isLockedOut || pin.length === 0}
                        onClick={handleClear}
                        className="h-12 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-500 text-xs font-semibold flex items-center justify-center transition-all disabled:opacity-40"
                    >
                        Hapus
                    </button>
                    <button
                        type="button"
                        disabled={loading || isLockedOut}
                        onClick={() => handleDigitPress('0')}
                        className="h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 active:bg-slate-300 active:scale-95 text-slate-800 text-lg font-bold flex items-center justify-center transition-all disabled:opacity-40"
                    >
                        0
                    </button>
                    <button
                        type="button"
                        disabled={loading || isLockedOut || pin.length === 0}
                        onClick={handleBackspace}
                        aria-label="Hapus satu angka"
                        className="h-12 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-all disabled:opacity-40"
                    >
                        <Delete size={18} />
                    </button>
                </div>

                {/* Tombol Ganti Akun / Logout */}
                <div className="mt-6 pt-4 border-t border-slate-100 w-full flex items-center justify-between text-xs">
                    <span className="text-slate-400">Bukan akun Anda?</span>
                    <button
                        type="button"
                        onClick={handleLogout}
                        disabled={loading}
                        className="inline-flex items-center gap-1.5 text-red-600 hover:text-red-700 font-semibold px-2 py-1 rounded-lg hover:bg-red-50 transition-colors"
                    >
                        <LogOut size={13} />
                        Ganti Akun (Logout)
                    </button>
                </div>
            </div>
        </div>
    );
}

