import { forwardRef, useLayoutEffect, useRef } from 'react';
import TextField from './TextField';
import {
    bersihkanNominalKetikan,
    bersihkanNominalTempel,
    formatRibuan,
    hitungPosisiKursorRibuan,
} from '@/Utils/formatCurrency';

/**
 * Input nominal Rupiah dengan pemisah ribuan bertitik (id-ID) otomatis saat mengetik,
 * penjagaan posisi kursor saat edit di tengah angka, smart paste (",00" aman),
 * dan nilai state yang tetap berupa digit bersih tanpa titik.
 */
const CurrencyField = forwardRef(function CurrencyField(
    {
        value = '',
        onValueChange,
        onChange,
        onKeyDown,
        onPaste,
        prefix = 'Rp',
        suffix = null,
        size = 'md',
        className = '',
        wrapperClassName = '',
        invalid = false,
        disabled = false,
        min: _min,
        max: _max,
        step: _step,
        type: _type,
        ...props
    },
    forwardedRef,
) {
    const innerRef = useRef(null);
    const pendingCaretRef = useRef(null);
    const displayValue = formatRibuan(value);

    function setRefs(el) {
        innerRef.current = el;
        if (typeof forwardedRef === 'function') {
            forwardedRef(el);
        } else if (forwardedRef) {
            forwardedRef.current = el;
        }
    }

    useLayoutEffect(() => {
        if (pendingCaretRef.current === null) {
            return;
        }
        const el = innerRef.current;
        const targetDigits = pendingCaretRef.current;
        pendingCaretRef.current = null;

        if (el && document.activeElement === el) {
            const pos = hitungPosisiKursorRibuan(displayValue, targetDigits);
            el.setSelectionRange(pos, pos);
        }
    }, [displayValue]);

    function emitCleanValue(clean, event) {
        onValueChange?.(clean);
        if (onChange) {
            const name = props.name ?? event?.target?.name ?? '';
            const targetProxy = { ...(event?.target ?? {}), value: clean, name };
            const currentTargetProxy = { ...(event?.currentTarget ?? {}), value: clean, name };
            onChange({
                ...event,
                target: targetProxy,
                currentTarget: currentTargetProxy,
            });
        }
    }

    function handleChange(e) {
        const raw = e.target.value;
        const caret = e.target.selectionStart ?? raw.length;
        const rawDigitsBefore = raw.slice(0, caret).replace(/\D/g, '').length;
        const totalRawDigits = raw.replace(/\D/g, '').length;
        const clean = bersihkanNominalKetikan(raw);

        let nextDigits = rawDigitsBefore;
        if (clean.length < totalRawDigits && !raw.replace(/\D/g, '').startsWith(clean)) {
            const trimmedLeadingZeros = totalRawDigits - clean.length;
            nextDigits = Math.max(clean === '0' ? 1 : 0, rawDigitsBefore - trimmedLeadingZeros);
        } else if (nextDigits > clean.length) {
            nextDigits = clean.length;
        }

        pendingCaretRef.current = nextDigits;
        emitCleanValue(clean, e);
    }

    function handleKeyDown(e) {
        const el = e.currentTarget;
        const start = el.selectionStart ?? 0;
        const end = el.selectionEnd ?? 0;

        if (start === end) {
            if (e.key === 'Backspace' && start > 0 && displayValue[start - 1] === '.') {
                e.preventDefault();
                const removedRaw = displayValue.slice(0, start - 2) + displayValue.slice(start - 1);
                const digitsBefore = Math.max(0, displayValue.slice(0, start - 1).replace(/\D/g, '').length - 1);
                const clean = bersihkanNominalKetikan(removedRaw);
                pendingCaretRef.current = clean === '0' && digitsBefore === 0 ? 1 : digitsBefore;
                emitCleanValue(clean, e);
                return;
            }

            if (e.key === 'Delete' && start < displayValue.length && displayValue[start] === '.') {
                e.preventDefault();
                const removedRaw = displayValue.slice(0, start) + displayValue.slice(start + 2);
                const digitsBefore = displayValue.slice(0, start).replace(/\D/g, '').length;
                const clean = bersihkanNominalKetikan(removedRaw);
                pendingCaretRef.current = Math.min(digitsBefore, clean.length);
                emitCleanValue(clean, e);
                return;
            }
        }

        onKeyDown?.(e);
    }

    function handlePaste(e) {
        const pastedText = e.clipboardData?.getData('text');
        if (typeof pastedText !== 'string') {
            onPaste?.(e);
            return;
        }

        e.preventDefault();
        const el = e.currentTarget;
        const start = el.selectionStart ?? displayValue.length;
        const end = el.selectionEnd ?? displayValue.length;

        const beforeDigits = displayValue.slice(0, start).replace(/\D/g, '');
        const afterDigits = displayValue.slice(end).replace(/\D/g, '');
        const pastedDigits = bersihkanNominalTempel(pastedText);
        const combinedClean = bersihkanNominalKetikan(`${beforeDigits}${pastedDigits}${afterDigits}`);

        const targetDigits = beforeDigits.length + pastedDigits.length;
        pendingCaretRef.current = Math.min(targetDigits, combinedClean.length);
        emitCleanValue(combinedClean, e);
        onPaste?.(e);
    }

    return (
        <TextField
            ref={setRefs}
            type="text"
            inputMode="numeric"
            autoComplete="off"
            size={size}
            prefix={prefix}
            suffix={suffix}
            invalid={invalid}
            disabled={disabled}
            value={displayValue}
            onChange={handleChange}
            onKeyDown={handleKeyDown}
            onPaste={handlePaste}
            wrapperClassName={wrapperClassName}
            className={`tabular-nums ${className}`}
            {...props}
        />
    );
});

export default CurrencyField;
