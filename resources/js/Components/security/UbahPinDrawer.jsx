import { router } from '@inertiajs/react';
import axios from 'axios';
import { CheckCircle2, Eye, EyeOff, KeyRound, Lock, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import Button from '@/Components/ui/Button';
import Drawer from '@/Components/ui/Drawer';
import { DrawerBody, DrawerFooter, DrawerFooterActions } from '@/Components/ui/DrawerSection';
import FormField from '@/Components/ui/FormField';
import TextField from '@/Components/ui/TextField';

const hanyaAngka = (nilai) => nilai.replace(/\D/g, '').slice(0, 6);

export default function UbahPinDrawer({ show = false, onClose = () => {}, hasPin = true }) {
    const [oldPin, setOldPin] = useState('');
    const [pin, setPin] = useState('');
    const [pinConfirmation, setPinConfirmation] = useState('');
    const [showDigits, setShowDigits] = useState(false);
    const [loading, setLoading] = useState(false);
    const [berhasil, setBerhasil] = useState(false);
    const [errorUmum, setErrorUmum] = useState('');
    const [fieldErrors, setFieldErrors] = useState({});

    useEffect(() => {
        if (!show) return;
        setOldPin('');
        setPin('');
        setPinConfirmation('');
        setShowDigits(false);
        setLoading(false);
        setBerhasil(false);
        setErrorUmum('');
        setFieldErrors({});
    }, [show]);

    async function handleSubmit(e) {
        e.preventDefault();
        if (loading || berhasil) return;

        setErrorUmum('');
        const errs = {};

        if (hasPin && oldPin.length !== 6) {
            errs.old_pin = 'PIN saat ini harus tepat 6 digit angka.';
        }
        if (pin.length !== 6) {
            errs.pin = 'PIN baru harus tepat 6 digit angka.';
        } else if (/^(\d)\1{5}$/.test(pin)) {
            errs.pin = 'PIN terlalu lemah (hindari 6 angka yang sama).';
        }
        if (pinConfirmation !== pin) {
            errs.pin_confirmation = 'Konfirmasi PIN baru tidak cocok.';
        }

        if (Object.keys(errs).length > 0) {
            setFieldErrors(errs);
            return;
        }

        setFieldErrors({});
        setLoading(true);

        try {
            const payload = {
                pin,
                pin_confirmation: pinConfirmation,
                ...(hasPin ? { old_pin: oldPin } : {}),
            };
            const res = await axios.post(route('security.pin.setup', undefined, false), payload);

            if (res.data?.status === 'success') {
                setBerhasil(true);
                router.reload({ only: ['auth', 'pengguna'] });
            }
        } catch (err) {
            const serverErrors = err.response?.data?.errors ?? {};
            setFieldErrors({
                old_pin: serverErrors.old_pin?.[0],
                pin: serverErrors.pin?.[0],
                pin_confirmation: serverErrors.pin_confirmation?.[0],
            });
            setErrorUmum(
                err.response?.data?.message || 'Gagal memperbarui PIN. Periksa kembali isian Anda.',
            );
        } finally {
            setLoading(false);
        }
    }

    return (
        <Drawer
            show={show}
            onClose={onClose}
            layout="split"
            maxWidth="lg"
            title={hasPin ? 'Ubah PIN Keamanan' : 'Atur PIN Keamanan'}
            description="PIN 6 digit digunakan untuk membuka Screen Lock dan otorisasi pengajuan mandiri."
        >
            {berhasil ? (
                <>
                    <DrawerBody className="px-5 py-8 sm:px-6 flex flex-col items-center justify-center text-center">
                        <div className="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-200 text-brand-green-dark flex items-center justify-center mb-4">
                            <CheckCircle2 size={34} aria-hidden="true" />
                        </div>
                        <h3 className="text-lg font-bold text-slate-900">PIN Keamanan Diperbarui</h3>
                        <p className="text-sm text-slate-500 mt-1.5 max-w-xs leading-relaxed">
                            PIN baru Anda sudah aktif dan langsung berlaku pada seluruh sesi login maupun pengajuan di Portal Anggota.
                        </p>
                    </DrawerBody>
                    <DrawerFooter className="px-5 py-3.5 sm:px-6 sm:py-4">
                        <DrawerFooterActions cols={1}>
                            <Button type="button" variant="secondary" size="drawer" onClick={onClose}>
                                Selesai
                            </Button>
                        </DrawerFooterActions>
                    </DrawerFooter>
                </>
            ) : (
                <form onSubmit={handleSubmit} className="flex-1 min-h-0 flex flex-col">
                    <DrawerBody className="px-5 py-5 sm:px-6 sm:py-6 space-y-5">
                        <div className="rounded-2xl bg-white border border-slate-200/85 p-4 flex items-start gap-3">
                            <span className="w-9 h-9 rounded-xl bg-slate-100 text-brand-navy flex items-center justify-center shrink-0">
                                <ShieldCheck size={18} aria-hidden="true" />
                            </span>
                            <div className="text-xs text-slate-600 leading-relaxed">
                                <p className="font-semibold text-slate-800">Standar Keamanan PIN</p>
                                <p className="mt-0.5">
                                    Gunakan tepat 6 digit angka yang mudah Anda ingat namun tidak berpola tunggal (seperti 111111).
                                </p>
                            </div>
                        </div>

                        {errorUmum && !fieldErrors.old_pin && !fieldErrors.pin && (
                            <div className="rounded-xl border border-rose-200 bg-rose-50/70 px-4 py-3 text-xs font-medium text-rose-800">
                                {errorUmum}
                            </div>
                        )}

                        <div className="rounded-2xl bg-white border border-slate-200/85 p-5 space-y-4">
                            <div className="flex items-center justify-between gap-2 pb-3 border-b border-slate-100">
                                <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    <KeyRound size={14} aria-hidden="true" />
                                    <span>Kredensial PIN 6 Digit</span>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setShowDigits((v) => !v)}
                                    className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-brand-navy cursor-pointer"
                                >
                                    {showDigits ? <EyeOff size={14} /> : <Eye size={14} />}
                                    <span>{showDigits ? 'Sembunyikan' : 'Tampilkan'}</span>
                                </button>
                            </div>

                            {hasPin && (
                                <FormField label="PIN Saat Ini" required error={fieldErrors.old_pin}>
                                    <TextField
                                        type={showDigits ? 'text' : 'password'}
                                        inputMode="numeric"
                                        autoComplete="current-password"
                                        maxLength={6}
                                        value={oldPin}
                                        onChange={(e) => setOldPin(hanyaAngka(e.target.value))}
                                        placeholder="••••••"
                                        className="font-mono tracking-[0.35em]"
                                        invalid={Boolean(fieldErrors.old_pin)}
                                        data-autofocus="true"
                                    />
                                </FormField>
                            )}

                            <FormField label="PIN Baru" required error={fieldErrors.pin}>
                                <TextField
                                    type={showDigits ? 'text' : 'password'}
                                    inputMode="numeric"
                                    autoComplete="new-password"
                                    maxLength={6}
                                    value={pin}
                                    onChange={(e) => setPin(hanyaAngka(e.target.value))}
                                    placeholder="••••••"
                                    className="font-mono tracking-[0.35em]"
                                    invalid={Boolean(fieldErrors.pin)}
                                    data-autofocus={!hasPin ? 'true' : undefined}
                                />
                            </FormField>

                            <FormField label="Konfirmasi PIN Baru" required error={fieldErrors.pin_confirmation}>
                                <TextField
                                    type={showDigits ? 'text' : 'password'}
                                    inputMode="numeric"
                                    autoComplete="new-password"
                                    maxLength={6}
                                    value={pinConfirmation}
                                    onChange={(e) => setPinConfirmation(hanyaAngka(e.target.value))}
                                    placeholder="••••••"
                                    className="font-mono tracking-[0.35em]"
                                    invalid={Boolean(fieldErrors.pin_confirmation)}
                                />
                            </FormField>
                        </div>

                        <p className="flex items-start gap-2 text-xs text-slate-500 px-1">
                            <Lock size={14} className="shrink-0 mt-0.5 text-slate-400" aria-hidden="true" />
                            <span>
                                Lupa PIN saat ini? Hubungi Super Admin / Sekretaris untuk melakukan reset PIN melalui Pengaturan Pengguna.
                            </span>
                        </p>
                    </DrawerBody>

                    <DrawerFooter className="px-5 py-3.5 sm:px-6 sm:py-4">
                        <DrawerFooterActions cols={2}>
                            <Button type="button" variant="neutral" size="drawer" onClick={onClose} disabled={loading}>
                                Batal
                            </Button>
                            <Button type="submit" variant="secondary" size="drawer" disabled={loading}>
                                {loading ? 'Menyimpan...' : 'Simpan PIN'}
                            </Button>
                        </DrawerFooterActions>
                    </DrawerFooter>
                </form>
            )}
        </Drawer>
    );
}
