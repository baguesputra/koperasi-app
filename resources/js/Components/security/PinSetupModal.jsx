import { useState, useRef, useEffect } from 'react';
import { usePage, router } from '@inertiajs/react';
import axios from 'axios';
import { ShieldCheck, Lock, AlertCircle, RefreshCw, Delete } from 'lucide-react';

export default function PinSetupModal() {
    const { auth } = usePage().props;
    const user = auth?.user;

    // Hanya tampilkan jika user sudah login tapi belum memiliki PIN
    const mustSetupPin = Boolean(user && !user.has_pin);

    const [step, setStep] = useState(1); // 1 = buat PIN, 2 = konfirmasi PIN
    const [pin, setPin] = useState('');
    const [confirmPin, setConfirmPin] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [shake, setShake] = useState(false);

    const inputRef = useRef(null);

    useEffect(() => {
        if (mustSetupPin) {
            setStep(1);
            setPin('');
            setConfirmPin('');
            setError('');
            const t = setTimeout(() => inputRef.current?.focus(), 150);
            return () => clearTimeout(t);
        }
    }, [mustSetupPin]);

    if (!mustSetupPin) {
        return null;
    }

    const currentVal = step === 1 ? pin : confirmPin;

    const handleDigitPress = (digit) => {
        if (loading) return;
        if (currentVal.length < 6) {
            const nextVal = currentVal + digit;
            if (step === 1) {
                setPin(nextVal);
                if (nextVal.length === 6) {
                    // Berpindah ke tahap konfirmasi
                    setTimeout(() => {
                        setStep(2);
                        setError('');
                    }, 180);
                }
            } else {
                setConfirmPin(nextVal);
                if (nextVal.length === 6) {
                    // Validasi kecocokan
                    if (nextVal !== pin) {
                        setShake(true);
                        setTimeout(() => setShake(false), 500);
                        setError('PIN konfirmasi tidak cocok. Silakan ulangi.');
                        setConfirmPin('');
                        return;
                    }
                    submitNewPin(pin, nextVal);
                }
            }
        }
    };

    const handleBackspace = () => {
        if (loading) return;
        if (step === 1) {
            setPin((prev) => prev.slice(0, -1));
        } else {
            if (confirmPin.length === 0) {
                // Kembali ke step 1 jika backspace di awal konfirmasi
                setStep(1);
                setPin('');
            } else {
                setConfirmPin((prev) => prev.slice(0, -1));
            }
        }
        setError('');
    };

    const submitNewPin = async (newPin, confirmed) => {
        setLoading(true);
        setError('');

        try {
            const res = await axios.post(route('security.pin.setup'), {
                pin: newPin,
                pin_confirmation: confirmed,
            });

            if (res.data?.status === 'success') {
                router.reload({ preserveScroll: true });
            }
        } catch (err) {
            setShake(true);
            setTimeout(() => setShake(false), 500);
            const msg = err.response?.data?.message || 'Gagal menyimpan PIN. Silakan coba lagi.';
            setError(msg);
            setStep(1);
            setPin('');
            setConfirmPin('');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label="Atur PIN Keamanan"
            className="fixed inset-0 z-[99998] bg-slate-950/80 backdrop-blur-md flex flex-col items-center justify-center p-4 select-none overflow-y-auto"
            onClick={() => inputRef.current?.focus()}
        >
            <input
                ref={inputRef}
                type="password"
                inputMode="numeric"
                pattern="[0-9]*"
                maxLength={6}
                value={currentVal}
                onChange={(e) => {
                    const val = e.target.value.replace(/\D/g, '').slice(0, 6);
                    if (step === 1) {
                        setPin(val);
                        if (val.length === 6) {
                            setTimeout(() => {
                                setStep(2);
                                setError('');
                            }, 180);
                        }
                    } else {
                        setConfirmPin(val);
                        if (val.length === 6) {
                            if (val !== pin) {
                                setShake(true);
                                setTimeout(() => setShake(false), 500);
                                setError('PIN konfirmasi tidak cocok. Silakan ulangi.');
                                setConfirmPin('');
                                return;
                            }
                            submitNewPin(pin, val);
                        }
                    }
                }}
                className="opacity-0 absolute pointer-events-none w-0 h-0"
                autoFocus
                tabIndex={-1}
            />

            <div className={`w-full max-w-sm bg-white border border-slate-100 rounded-3xl p-6 sm:p-7 shadow-2xl flex flex-col items-center text-center transition-transform ${shake ? 'animate-shake' : ''}`}>
                {/* Icon Header */}
                <div className="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4 shadow-sm ring-1 ring-emerald-500/20">
                    <ShieldCheck size={28} className="stroke-[2.2]" />
                </div>

                <h3 className="text-xl font-bold text-slate-800 tracking-tight">
                    {step === 1 ? 'Atur PIN Keamanan' : 'Konfirmasi PIN Anda'}
                </h3>
                <p className="text-xs text-slate-500 mt-1 max-w-[260px] leading-relaxed">
                    {step === 1
                        ? 'Buat 6 digit PIN angka untuk mengunci sesi layar dan verifikasi pengajuan pinjaman.'
                        : 'Ketik ulang 6 digit PIN yang baru saja Anda buat untuk konfirmasi.'}
                </p>

                {/* Indikator Langkah */}
                <div className="mt-4 flex items-center gap-1.5">
                    <span className={`w-2 h-2 rounded-full transition-all ${step === 1 ? 'w-6 bg-emerald-600' : 'bg-slate-300'}`} />
                    <span className={`w-2 h-2 rounded-full transition-all ${step === 2 ? 'w-6 bg-emerald-600' : 'bg-slate-300'}`} />
                </div>

                {/* Indikator 6 Digit PIN */}
                <div className="mt-5 flex items-center justify-center gap-2.5">
                    {[0, 1, 2, 3, 4, 5].map((idx) => {
                        const isFilled = currentVal.length > idx;
                        const isCurrent = currentVal.length === idx;
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

                {/* Pesan Error / Status */}
                <div className="mt-3 min-h-[32px] flex items-center justify-center px-2">
                    {loading ? (
                        <div className="inline-flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                            <RefreshCw size={13} className="animate-spin text-emerald-600" />
                            Menyimpan PIN Keamanan...
                        </div>
                    ) : error ? (
                        <div className="inline-flex items-center gap-1.5 text-xs font-semibold text-red-600 animate-pulse">
                            <AlertCircle size={14} className="shrink-0" />
                            <span>{error}</span>
                        </div>
                    ) : (
                        <p className="text-[11px] text-slate-400">
                            {step === 1 ? 'Ketik 6 digit angka' : 'Ulangi 6 digit angka yang sama'}
                        </p>
                    )}
                </div>

                {/* Touch Keypad */}
                <div className="mt-2 w-full grid grid-cols-3 gap-2 max-w-[260px]">
                    {[1, 2, 3, 4, 5, 6, 7, 8, 9].map((digit) => (
                        <button
                            key={digit}
                            type="button"
                            disabled={loading}
                            onClick={() => handleDigitPress(String(digit))}
                            className="h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 active:bg-slate-300 active:scale-95 text-slate-800 text-lg font-bold flex items-center justify-center transition-all disabled:opacity-40"
                        >
                            {digit}
                        </button>
                    ))}
                    <button
                        type="button"
                        disabled={loading || step === 1}
                        onClick={() => {
                            setStep(1);
                            setConfirmPin('');
                            setError('');
                        }}
                        className="h-12 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-500 text-xs font-semibold flex items-center justify-center transition-all disabled:opacity-40"
                    >
                        Ulangi
                    </button>
                    <button
                        type="button"
                        disabled={loading}
                        onClick={() => handleDigitPress('0')}
                        className="h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 active:bg-slate-300 active:scale-95 text-slate-800 text-lg font-bold flex items-center justify-center transition-all disabled:opacity-40"
                    >
                        0
                    </button>
                    <button
                        type="button"
                        disabled={loading || currentVal.length === 0}
                        onClick={handleBackspace}
                        aria-label="Hapus satu angka"
                        className="h-12 rounded-2xl bg-slate-50 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition-all disabled:opacity-40"
                    >
                        <Delete size={18} />
                    </button>
                </div>

                <div className="mt-4 pt-3 border-t border-slate-100 w-full text-center">
                    <p className="text-[11px] text-slate-400 flex items-center justify-center gap-1">
                        <Lock size={11} className="inline text-slate-400" />
                        Terenkripsi aman &amp; tidak dapat dilihat oleh siapa pun
                    </p>
                </div>
            </div>
        </div>
    );
}

