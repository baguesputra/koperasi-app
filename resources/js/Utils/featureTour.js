import { useCallback, useEffect, useRef } from 'react';
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

export function useFeatureTour(name, userId, steps) {
    const tourRef = useRef(null);
    const stepsRef = useRef(steps);
    const key = `koperasi.feature-tour.${name}.${userId ?? 'guest'}`;

    stepsRef.current = steps;

    const start = useCallback(() => {
        tourRef.current?.destroy();
        if (!stepsRef.current.length) return;

        tourRef.current = driver({
            steps: stepsRef.current,
            skipMissingElement: true,
            showProgress: true,
            allowClose: true,
            overlayClickBehavior: 'close',
            nextBtnText: 'Lanjut',
            prevBtnText: 'Kembali',
            doneBtnText: 'Selesai',
            onDestroyStarted: () => {
                localStorage.setItem(key, '1');
                tourRef.current?.destroy();
            },
        });
        tourRef.current.drive();
    }, [key]);

    useEffect(() => {
        const eventName = `start-feature-tour:${name}`;
        const onRequest = () => start();
        window.addEventListener(eventName, onRequest);
        const timer = localStorage.getItem(key) ? null : window.setTimeout(start, 700);

        return () => {
            window.removeEventListener(eventName, onRequest);
            if (timer) window.clearTimeout(timer);
            tourRef.current?.destroy();
        };
    }, [name, key, start]);

    return start;
}
