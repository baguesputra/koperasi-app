import { createContext, useContext, useEffect, useRef, useState } from 'react';

const PrivacyContext = createContext({
    isPrivacyActive: true,
    togglePrivacy: () => {},
    setPrivacy: () => {},
});

const INACTIVITY_TIMEOUT_MS = 15000; // 15 detik auto-lock jika privasi dimatikan sementara

export function PrivacyProvider({ children }) {
    // Default aktif (true) saat sesi baru dibuka
    const [isPrivacyActive, setIsPrivacyActive] = useState(() => {
        if (typeof window === 'undefined') return true;
        const stored = sessionStorage.getItem('privacy_mode');
        return stored !== null ? stored === '1' : true;
    });

    const timerInactivityRef = useRef(null);

    // Sinkronkan ke sessionStorage & pasang atribut data-privacy di elemen root (html)
    useEffect(() => {
        if (typeof window !== 'undefined') {
            sessionStorage.setItem('privacy_mode', isPrivacyActive ? '1' : '0');
            document.documentElement.setAttribute('data-privacy', isPrivacyActive ? 'active' : 'inactive');
        }
    }, [isPrivacyActive]);

    const setPrivacy = (val) => {
        setIsPrivacyActive(Boolean(val));
    };

    const togglePrivacy = () => {
        setIsPrivacyActive((prev) => !prev);
    };

    // Auto-lock saat berpindah tab atau meminimalkan browser (visibilitychange)
    useEffect(() => {
        if (typeof window === 'undefined') return undefined;

        const handleVisibilityChange = () => {
            if (document.visibilityState === 'hidden') {
                setIsPrivacyActive(true);
            }
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        };
    }, []);

    // Panic Key / Keyboard Shortcut: Ctrl + Shift + P
    useEffect(() => {
        if (typeof window === 'undefined') return undefined;

        const handleKeyDown = (e) => {
            // Tangkap Ctrl+Shift+P atau Cmd+Shift+P
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'p' || e.key === 'P')) {
                e.preventDefault();
                togglePrivacy();
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    // Inactivity Timer: jika privasi sengaja dimatikan, sistem otomatis menguncinya kembali setelah 15s
    useEffect(() => {
        if (typeof window === 'undefined') return undefined;

        if (isPrivacyActive) {
            if (timerInactivityRef.current) {
                clearTimeout(timerInactivityRef.current);
                timerInactivityRef.current = null;
            }
            return undefined;
        }

        const resetTimer = () => {
            if (timerInactivityRef.current) {
                clearTimeout(timerInactivityRef.current);
            }
            timerInactivityRef.current = setTimeout(() => {
                setIsPrivacyActive(true);
            }, INACTIVITY_TIMEOUT_MS);
        };

        resetTimer();

        const activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
        activityEvents.forEach((ev) => window.addEventListener(ev, resetTimer, { passive: true }));

        return () => {
            if (timerInactivityRef.current) {
                clearTimeout(timerInactivityRef.current);
            }
            activityEvents.forEach((ev) => window.removeEventListener(ev, resetTimer));
        };
    }, [isPrivacyActive]);

    return (
        <PrivacyContext.Provider value={{ isPrivacyActive, togglePrivacy, setPrivacy }}>
            {children}
        </PrivacyContext.Provider>
    );
}

export function usePrivacy() {
    return useContext(PrivacyContext);
}

