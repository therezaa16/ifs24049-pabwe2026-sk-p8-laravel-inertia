import React, { useEffect, useRef } from 'react';
import { CloseIcon } from './Icons';

/**
 * Dialog aksesibel. `fullScreen` membuat modal memenuhi layar dengan header & footer
 * berwarna kontras; tombol aksi diletakkan di sisi kanan footer.
 */
export default function Modal({
    open,
    onClose,
    title,
    subtitle,
    children,
    footer,
    fullScreen = false,
    headerClass = 'bg-indigo-700 text-white',
    footerClass = 'bg-slate-100 border-t border-slate-300',
    size = 'max-w-lg',
}) {
    const dialogRef = useRef(null);
    const titleId = useRef(`modal-${Math.random().toString(36).slice(2, 9)}`).current;

    useEffect(() => {
        if (!open) return undefined;

        const previouslyFocused = document.activeElement;
        const onKey = (e) => {
            if (e.key === 'Escape') onClose?.();
        };
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        dialogRef.current?.focus();

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
            previouslyFocused?.focus?.();
        };
    }, [open]);

    if (!open) return null;

    return (
        <div className={`fixed inset-0 z-[60] flex ${fullScreen ? '' : 'items-center justify-center p-4'} bg-slate-900/60`}>
            <div
                ref={dialogRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                tabIndex={-1}
                className={`flex flex-col bg-white shadow-2xl outline-none ${
                    fullScreen ? 'w-full h-full' : `w-full ${size} max-h-[90vh] rounded-2xl overflow-hidden`
                }`}
            >
                <div className={`flex items-center justify-between gap-4 px-5 py-4 sm:px-8 ${headerClass}`}>
                    <div className="min-w-0">
                        <h2 id={titleId} className="text-lg sm:text-xl font-bold truncate">
                            {title}
                        </h2>
                        {subtitle && <p className="text-sm opacity-90 truncate">{subtitle}</p>}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Tutup dialog"
                        className="p-2 rounded-lg hover:bg-white/20 focus-visible:outline-white"
                    >
                        <CloseIcon />
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto px-5 py-5 sm:px-8 sm:py-6">{children}</div>

                {footer && <div className={`flex flex-wrap items-center justify-end gap-3 px-5 py-4 sm:px-8 ${footerClass}`}>{footer}</div>}
            </div>
        </div>
    );
}
