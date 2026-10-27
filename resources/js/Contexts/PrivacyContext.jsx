import { createContext, useContext, useEffect, useRef, useState, useCallback } from 'react';

const PrivacyContext = createContext({
    isPrivacyActive: true,
    togglePrivacy: () => {},
    setPrivacy: () => {},
    isScreenLocked: false,
    lockScreen: () => {},
    unlockScreen: () => {},
});

const INACTIVITY_PRIVACY_MS = 30000; // 30 detik: Privasi blur otomatis aktif
const INACTIVITY_LOCK_MS = 60000;    // 60 detik total: Layar terkunci (Screen Lock)

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

    // Inactivity Timers:
    // Stage 1 (15 detik): Mode Privasi Aktif (jika sengaja dimatikan)
    // Stage 2 (30 detik total): Screen Lock Aktif
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

        const resetTimers = () => {
            // Jangan reset jika layar sudah terkunci
            if (isScreenLockedRef.current) return;

            clearTimers();

            // Timer Tier 1: Privacy Mode Blur pada 15 detik
            timerPrivacyRef.current = setTimeout(() => {
                setIsPrivacyActive(true);
            }, INACTIVITY_PRIVACY_MS);

            // Timer Tier 2: Hard Screen Lock pada 30 detik
            timerLockRef.current = setTimeout(() => {
                lockScreen();
            }, INACTIVITY_LOCK_MS);
        };

        resetTimers();

        const activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
        activityEvents.forEach((ev) => window.addEventListener(ev, resetTimers, { passive: true }));

        return () => {
            clearTimers();
            activityEvents.forEach((ev) => window.removeEventListener(ev, resetTimers));
        };
    }, [lockScreen]);

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
