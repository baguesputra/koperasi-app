import { useState, useEffect } from 'react';
import ErrorModal from './ui/ErrorModal';

const STATUS_MODAL = [403, 404, 419, 500];

export default function ErrorModalProvider({ children }) {
    const [errorStatus, setErrorStatus] = useState(null);

    useEffect(() => {
        // Respons HTTP error non-Inertia (mis. 403 dari Spatie) memicu event "invalid".
        function onInvalid(e) {
            const status = e.detail?.response?.status;

            if (!status || !STATUS_MODAL.includes(status)) {
                return;
            }

            // Di dev, biarkan halaman 500 default (Whoops/stacktrace) tetap tampil untuk debugging.
            if (status === 500 && import.meta.env.DEV) {
                return;
            }

            e.preventDefault();
            setErrorStatus(status);
        }

        // Fallback untuk error jaringan/parse (tanpa response HTTP yang bisa ditebak).
        function onException(e) {
            const status = e.detail?.exception?.response?.status;

            if (!status || !STATUS_MODAL.includes(status)) {
                return;
            }

            e.preventDefault();
            setErrorStatus(status);
        }

        document.addEventListener('inertia:invalid', onInvalid);
        document.addEventListener('inertia:exception', onException);
        return () => {
            document.removeEventListener('inertia:invalid', onInvalid);
            document.removeEventListener('inertia:exception', onException);
        };
    }, []);

    return (
        <>
            {children}
            {errorStatus && <ErrorModal status={errorStatus} onClose={() => setErrorStatus(null)} />}
        </>
    );
}