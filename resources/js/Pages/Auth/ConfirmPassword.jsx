import { useState, useRef, useEffect } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { ShieldAlert, LogOut, Lock, RefreshCw, Delete } from 'lucide-react';
import Button from '@/Components/ui/Button';

export default function ConfirmPassword() {
    const { props } = usePage();
    const csrfToken = typeof document !== 'undefined'
        ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        : null;

    const [pin, setPin] = useState('');
    const [shake, setShake] = useState(false);
    const inputRef = useRef(null);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        pin: '',
    });

    useEffect(() => {
        const t = setTimeout(() => inputRef.current?.focus(), 150);
        return () => clearTimeout(t);
    }, []);

    useEffect(() => {
        if (errors.pin) {
            setShake(true);
            const t = setTimeout(() => setShake(false), 500);
            setPin('');
            setData('pin', '');
            inputRef.current?.focus();
            return () => clearTimeout(t);
        }
    }, [errors.pin]);

    const submitPin = (pinToSubmit) => {
        if (processing || pinToSubmit.length !== 6) return;
        setData('pin', pinToSubmit);
        post(route('password.confirm'), {
            data: { pin: pinToSubmit },
            preserveScroll: true,
            onError: () => {
                setPin('');
                setData('pin', '');
            },
        });
    };

    const handleDigitPress = (digit) => {
        if (processing) return;
        clearErrors();
        if (pin.length < 6) {
            const nextVal = pin + digit;
            setPin(nextVal);
            setData('pin', nextVal);
            if (nextVal.length === 6) {
                submitPin(nextVal);
            }
        }
    };

    const handleBackspace = () => {
        if (processing) return;
        clearErrors();
        setPin((prev) => prev.slice(0, -1));
        setData('pin', (prev) => prev.slice(0, -1));
    };

    const handleClear = () => {
        if (processing) return;
        clearErrors();
        setPin('');
        setData('pin', '');
        inputRef.current?.focus();
    };

    function handleSubmit(e) {
        e.preventDefault();
        submitPin(pin);
    }

    return (
        <div
            className="min-h-screen flex items-center justify-center bg-gradient-to-b from-brand-navy/5 to-slate-50 px-4 py-8 select-none"
            onClick={() => inputRef.current?.focus()}
        >
            <Head title="Konfirmasi PIN Area Sensitif" />

            {/* Hidden Input untuk fokus keyboard fisik & mobile */}
            <input
                ref={inputRef}
                type="password"
                inputMode="numeric"
                pattern="[0-9]*"
                maxLength={6}
                value={pin}
                onChange={(e) => {
                    clearErrors();
                    const val = e.target.value.replace(/\D/g, '').slice(0, 6);
                    setPin(val);
                    setData('pin', val);
                    if (val.length === 6) {
                        submitPin(val);
                    }
                }}
                className="opacity-0 absolute pointer-events-none w-0 h-0"
                autoFocus
                tabIndex={-1}
            />

            <div className={`w-full max-w-sm transition-transform ${shake ? 'animate-shake' : ''}`}>
                <div className="bg-white rounded-3xl border border-slate-200/80 shadow-xl p-6 sm:p-7 flex flex-col items-center text-center">
                    {/* Header Icon */}
                    <div className="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200/60 flex items-center justify-center mb-4 shadow-sm">
                        <ShieldAlert size={28} className="stroke-[2.2]" />
                    </div>

                    <h1 className="text-xl font-bold text-slate-800 tracking-tight leading-tight">
                        Area Sensitif Terkunci
                    </h1>
                    <p className="text-xs text-slate-500 mt-1 max-w-[280px] leading-relaxed">
                        Masukkan 6 digit PIN Keamanan akun Anda untuk membuka akses ke <strong>Pengaturan</strong> dan <strong>Kelola Pengguna</strong>.
                    </p>

                    {/* 6 Digit PIN Boxes */}
                    <div className="mt-6 flex items-center justify-center gap-2.5">
                        {[0, 1, 2, 3, 4, 5].map((idx) => {
                            const isFilled = pin.length > idx;
                            const isCurrent = pin.length === idx;
                            return (
                                <div
                                    key={idx}
                                    className={`w-10 h-12 rounded-xl border flex items-center justify-center transition-all duration-200 ${
                                        isFilled
                                            ? 'border-brand-green bg-brand-green/5 scale-105 shadow-sm'
                                            : isCurrent
                                            ? 'border-brand-green bg-white ring-2 ring-brand-green/30'
                                            : 'border-slate-200 bg-slate-50/60'
                                    }`}
                                >
                                    {isFilled ? (
                                        <div className="w-3.5 h-3.5 rounded-full bg-brand-green-dark shadow-sm animate-scale-up" />
                                    ) : (
                                        <span className="text-slate-300 text-xs">•</span>
                                    )}
                                </div>
                            );
                        })}
                    </div>

                    {/* Status & Error Message */}
                    <div className="mt-3 min-h-[32px] flex items-center justify-center px-2">
                        {processing ? (
                            <div className="inline-flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                                <RefreshCw size={13} className="animate-spin text-brand-green" />
                                Memverifikasi PIN...
                            </div>
                        ) : errors.pin ? (
                            <div className="inline-flex items-center gap-1.5 text-xs font-semibold text-red-600 animate-pulse">
                                <Lock size={13} className="shrink-0" />
                                <span>{errors.pin}</span>
                            </div>
                        ) : (
                            <p className="text-xs text-slate-400">
                                Ketik 6 digit PIN Anda
                            </p>
                        )}
                    </div>

                    {/* Touch Keypad */}
                    <div className="mt-2 w-full grid grid-cols-3 gap-2 max-w-[260px]">
                        {[1, 2, 3, 4, 5, 6, 7, 8, 9].map((digit) => (
                            <button
                                key={digit}
                                type="button"
                                disabled={processing}
                                onClick={() => handleDigitPress(String(digit))}
                                className="h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 active:bg-slate-300 active:scale-95 text-slate-800 text-lg font-bold flex items-center justify-center transition-all disabled:opacity-40"
                            >
                                {digit}
                            </button>
                        ))}
                        <button
                            type="button"
                            disabled={processing || pin.length === 0}
                            onClick={handleClear}
                            className="h-12 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-500 text-xs font-semibold flex items-center justify-center transition-all disabled:opacity-40"
                        >
                            Hapus
                        </button>
                        <button
                            type="button"
                            disabled={processing}
                            onClick={() => handleDigitPress('0')}
                            className="h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 active:bg-slate-300 active:scale-95 text-slate-800 text-lg font-bold flex items-center justify-center transition-all disabled:opacity-40"
                        >
                            0
                        </button>
                        <button
                            type="button"
                            disabled={processing || pin.length === 0}
                            onClick={handleBackspace}
                            aria-label="Hapus satu digit"
                            className="h-12 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-all disabled:opacity-40"
                        >
                            <Delete size={18} />
                        </button>
                    </div>

                    <form onSubmit={handleSubmit} className="w-full mt-4">
                        <Button
                            type="submit"
                            disabled={processing || pin.length !== 6}
                            className="w-full rounded-xl shadow-md shadow-brand-green/20 min-h-[44px]"
                        >
                            {processing ? 'Memverifikasi...' : 'Buka Area Sensitif'}
                        </Button>
                    </form>
                </div>

                {/* Logout Option */}
                <form method="POST" action={route('logout')} className="mt-4 text-center">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <button
                        type="submit"
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-slate-600 transition-colors py-1 px-2 rounded-lg hover:bg-slate-100"
                    >
                        <LogOut size={13} />
                        Bukan akun Anda? Keluar
                    </button>
                </form>
                {props.flash?.status && (
                    <p className="text-xs text-brand-green-dark text-center mt-2 font-medium">{props.flash.status}</p>
                )}
            </div>
        </div>
    );
}
