import { createContext, useContext, useEffect, useRef, useState, useCallback } from 'react';
import { router } from '@inertiajs/react';

const PrivacyContext = createContext({
    isPrivacyActive: true,
    togglePrivacy: () => {},
    setPrivacy: () => {},
    isScreenLocked: false,
    lockScreen: () => {},
    unlockScreen: () => {},
});

const INACTIVITY_PRIVACY_MS = 5 * 60 * 1000;      // 5 menit (300.000 ms) idle layar terbuka Pengurus -> buram otomatis
const AFTER_BLUR_LOCK_MS = 3 * 60 * 1000;         // 3 menit (180.000 ms) idle saat layar buram Pengurus -> kunci layar
const PORTAL_INACTIVITY_LOCK_MS = 5 * 60 * 1000;  // 5 menit (300.000 ms) idle di Portal Anggota -> kunci layar
const ACTIVITY_THROTTLE_MS = 1000;                // Throttle reset timer per 1 detik agar hemat CPU
const BROADCAST_ACTIVITY_THROTTLE_MS = 5000;      // Sinkronisasi aktivitas antar-tab setiap 5 detik

function getPathname() {
    return typeof window !== 'undefined' ? window.location.pathname : '/';
}

function isPortalPath(pathname) {
    return pathname === '/portal' || pathname.startsWith('/portal/');
}

function isPublicPath(pathname) {
    return (
        pathname === '/' ||
        pathname.startsWith('/login') ||
        pathname.startsWith('/register') ||
        pathname.startsWith('/forgot-password') ||
        pathname.startsWith('/reset-password') ||
        pathname.startsWith('/auth/') ||
        pathname.startsWith('/verifikasi')
    );
}

export function PrivacyProvider({ children }) {
    const [pathname, setPathname] = useState(getPathname);

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
    const startStageTimerRef = useRef(null);
    const lastActivityResetRef = useRef(0);
    const lastActivityBroadcastRef = useRef(0);

    // Pantau perpindahan rute SPA Inertia agar aturan timer Portal vs Pengurus selalu akurat
    useEffect(() => {
        if (typeof window === 'undefined') return undefined;

        const syncPathname = () => setPathname(getPathname());
        const removeNavigate = router.on('navigate', syncPathname);
        window.addEventListener('popstate', syncPathname);

        return () => {
            if (typeof removeNavigate === 'function') {
                removeNavigate();
            } else if (typeof router.off === 'function') {
                router.off('navigate', syncPathname);
            }
            window.removeEventListener('popstate', syncPathname);
        };
    }, []);

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
            } else if (e.data?.type === 'ACTIVITY') {
                if (!isScreenLockedRef.current) {
                    startStageTimerRef.current?.();
                }
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

    // Timer Inaktivitas & Screen Lock:
    // 1. Di halaman publik (login, verifikasi, dll.): timer nonaktif.
    // 2. Di Portal Anggota (/portal/*): hitung mundur inaktivitas penuh 5 menit menuju PIN Screen Lock,
    //    selalu di-reset setiap aktivitas pengguna (mousemove, keydown, click, scroll, touchstart).
    // 3. Di halaman Pengurus:
    //    - Saat layar terbuka (isPrivacyActive === false): 5 menit inaktivitas -> buram otomatis.
    //    - Saat layar buram (isPrivacyActive === true): 3 menit inaktivitas -> kunci halaman (lockScreen()),
    //      juga selalu di-reset saat pengguna aktif sehingga tidak mengunci di tengah pekerjaan.
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

        if (isScreenLocked || isPublicPath(pathname)) {
            clearTimers();
            startStageTimerRef.current = null;
            return undefined;
        }

        const startStageTimer = () => {
            if (isScreenLockedRef.current) return;

            clearTimers();

            if (isPortalPath(pathname)) {
                timerLockRef.current = setTimeout(() => {
                    lockScreen();
                }, PORTAL_INACTIVITY_LOCK_MS);
                return;
            }

            if (!isPrivacyActiveRef.current) {
                // Stage 1 (Pengurus): Layar sedang terbuka -> tunggu 5 menit inaktivitas untuk buram otomatis
                timerPrivacyRef.current = setTimeout(() => {
                    setIsPrivacyActive(true);
                }, INACTIVITY_PRIVACY_MS);
            } else {
                // Stage 2 (Pengurus): Layar sudah buram -> 3 menit inaktivitas kunci halaman
                timerLockRef.current = setTimeout(() => {
                    lockScreen();
                }, AFTER_BLUR_LOCK_MS);
            }
        };

        startStageTimerRef.current = startStageTimer;
        lastActivityResetRef.current = Date.now();
        startStageTimer();

        const handleActivity = () => {
            if (isScreenLockedRef.current) return;

            const now = Date.now();
            if (now - lastActivityResetRef.current >= ACTIVITY_THROTTLE_MS) {
                lastActivityResetRef.current = now;
                startStageTimer();
            }

            if (broadcastRef.current && now - lastActivityBroadcastRef.current >= BROADCAST_ACTIVITY_THROTTLE_MS) {
                lastActivityBroadcastRef.current = now;
                broadcastRef.current.postMessage({ type: 'ACTIVITY' });
            }
        };

        const activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
        activityEvents.forEach((ev) => window.addEventListener(ev, handleActivity, { passive: true }));

        return () => {
            clearTimers();
            startStageTimerRef.current = null;
            activityEvents.forEach((ev) => window.removeEventListener(ev, handleActivity));
        };
    }, [pathname, isPrivacyActive, isScreenLocked, lockScreen]);

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
