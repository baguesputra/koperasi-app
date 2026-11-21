import { createContext, useContext, useEffect, useRef, useState, useCallback } from 'react';

const PrivacyContext = createContext({
    isPrivacyActive: true,
    togglePrivacy: () => {},
    setPrivacy: () => {},
    isScreenLocked: false,
    lockScreen: () => {},
    unlockScreen: () => {},
});

const INACTIVITY_PRIVACY_MS = 5 * 60 * 1000; // 5 menit (300.000 ms): Privasi blur otomatis aktif
const AFTER_BLUR_LOCK_MS = 3 * 60 * 1000;    // 3 menit (180.000 ms) setelah buram (manual/otomatis): Layar terkunci (Screen Lock)

export function PrivacyProvider({ children }) {
    // Default aktif (true) saat sesi baru dibuka
    const [isPrivacyActive, setIsPrivacyActive] = useState(() => {
        if (typeof window === 'undefined') return true;
        const stored = sessionStorage.getItem('privacy_mode');
        return stored !== null ? stored === '1' : true;
    });

    // Screen Lock state
    const [isScreenLocked, setIsScreenLocked] = useState(() => {
        if (typeof window === 'undefined') return false;
        return sessionStorage.getItem('screen_locked') === '1';
    });

    const isPrivacyActiveRef = useRef(isPrivacyActive);
    useEffect(() => {
        isPrivacyActiveRef.current = isPrivacyActive;
    }, [isPrivacyActive]);

    const isScreenLockedRef = useRef(isScreenLocked);
    useEffect(() => {
        isScreenLockedRef.current = isScreenLocked;
    }, [isScreenLocked]);

    const timerPrivacyRef = useRef(null);
    const timerLockRef = useRef(null);
    const broadcastRef = useRef(null);

    // Sinkronkan ke sessionStorage & pasang atribut data-privacy di elemen root (html)
    useEffect(() => {
        if (typeof window !== 'undefined') {
            sessionStorage.setItem('privacy_mode', isPrivacyActive ? '1' : '0');
            document.documentElement.setAttribute('data-privacy', isPrivacyActive ? 'active' : 'inactive');
        }
    }, [isPrivacyActive]);

    useEffect(() => {
        if (typeof window !== 'undefined') {
            sessionStorage.setItem('screen_locked', isScreenLocked ? '1' : '0');
        }
    }, [isScreenLocked]);

    const setPrivacy = useCallback((val) => {
        setIsPrivacyActive(Boolean(val));
    }, []);

    const togglePrivacy = useCallback(() => {
        setIsPrivacyActive((prev) => !prev);
    }, []);

    const lockScreen = useCallback(() => {
        setIsScreenLocked(true);
        setIsPrivacyActive(true);
        isScreenLockedRef.current = true;
        isPrivacyActiveRef.current = true;
        if (typeof window !== 'undefined') {
            sessionStorage.setItem('screen_locked', '1');
        }
        if (broadcastRef.current) {
            broadcastRef.current.postMessage({ type: 'LOCK' });
        }
    }, []);

    const unlockScreen = useCallback(() => {
        setIsScreenLocked(false);
        isScreenLockedRef.current = false;
        if (typeof window !== 'undefined') {
            sessionStorage.setItem('screen_locked', '0');
        }
        if (broadcastRef.current) {
            broadcastRef.current.postMessage({ type: 'UNLOCK' });
        }
    }, []);

    // Multi-tab synchronization via BroadcastChannel
    useEffect(() => {
        if (typeof window === 'undefined' || !('BroadcastChannel' in window)) return undefined;

        const channel = new BroadcastChannel('koperasi_screen_lock');
        broadcastRef.current = channel;

        channel.onmessage = (e) => {
            if (e.data?.type === 'LOCK') {
                setIsScreenLocked(true);
                setIsPrivacyActive(true);
                isScreenLockedRef.current = true;
                isPrivacyActiveRef.current = true;
                sessionStorage.setItem('screen_locked', '1');
            } else if (e.data?.type === 'UNLOCK') {
                setIsScreenLocked(false);
                isScreenLockedRef.current = false;
                sessionStorage.setItem('screen_locked', '0');
            }
        };

        return () => {
            channel.close();
            broadcastRef.current = null;
        };
    }, []);

    // Keyboard Shortcuts:
    // Ctrl + Shift + P: Toggle Mode Privasi
    // Ctrl + Shift + L: Langsung Kunci Layar (Screen Lock)
    useEffect(() => {
        if (typeof window === 'undefined') return undefined;

        const handleKeyDown = (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey) {
                if (e.key === 'p' || e.key === 'P') {
                    e.preventDefault();
                    togglePrivacy();
                } else if (e.key === 'l' || e.key === 'L') {
                    e.preventDefault();
                    lockScreen();
                }
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [togglePrivacy, lockScreen]);

    // Two-Stage State-Driven Timers:
    // 1. Saat tidak buram (isPrivacyActive === false):
    //    Hitung mundur 5 menit inaktivitas -> otomatis aktifkan buram (setIsPrivacyActive(true)).
    // 2. Setiap kali setelah buram (isPrivacyActive === true, baik diklik manual maupun otomatis setelah 5 menit):
    //    Hitung mundur 3 menit -> kunci halaman (lockScreen()).
    useEffect(() => {
        if (typeof window === 'undefined') return undefined;

        const clearTimers = () => {
            if (timerPrivacyRef.current) {
                clearTimeout(timerPrivacyRef.current);
                timerPrivacyRef.current = null;
            }
            if (timerLockRef.current) {
                clearTimeout(timerLockRef.current);
                timerLockRef.current = null;
            }
        };

        if (isScreenLocked) {
            clearTimers();
            return undefined;
        }

        const startStageTimer = () => {
            if (isScreenLockedRef.current) return;

            clearTimers();

            if (!isPrivacyActiveRef.current) {
                // Stage 1: Layar sedang terbuka -> tunggu 5 menit inaktivitas untuk buram otomatis
                timerPrivacyRef.current = setTimeout(() => {
                    setIsPrivacyActive(true);
                }, INACTIVITY_PRIVACY_MS);
            } else {
                // Stage 2: Layar sudah buram (manual atau otomatis) -> 3 menit kemudian kunci halaman
                timerLockRef.current = setTimeout(() => {
                    lockScreen();
                }, AFTER_BLUR_LOCK_MS);
            }
        };

        startStageTimer();

        const handleActivity = () => {
            // Saat layar terbuka (tidak buram), aktivitas pengguna mereset timer 5 menit menuju buram.
            // Saat layar sudah buram, timer 3 menit setelah buram tetap berjalan menuju kunci layar
            // (kecuali pengguna mematikan mode buram).
            if (!isPrivacyActiveRef.current && !isScreenLockedRef.current) {
                startStageTimer();
            }
        };

        const activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
        activityEvents.forEach((ev) => window.addEventListener(ev, handleActivity, { passive: true }));

        return () => {
            clearTimers();
            activityEvents.forEach((ev) => window.removeEventListener(ev, handleActivity));
        };
    }, [isPrivacyActive, isScreenLocked, lockScreen]);

    return (
        <PrivacyContext.Provider
            value={{
                isPrivacyActive,
                togglePrivacy,
                setPrivacy,
                isScreenLocked,
                lockScreen,
                unlockScreen,
            }}
        >
            {children}
        </PrivacyContext.Provider>
    );
}

export function usePrivacy() {
    return useContext(PrivacyContext);
}
