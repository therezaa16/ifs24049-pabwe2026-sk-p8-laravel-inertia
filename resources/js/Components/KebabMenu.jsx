import React, { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import { DotsIcon } from './Icons';

/**
 * Menu aksi vertical dots (⋮). Setiap item: { label, icon, href?, onClick?, tone: 'primary' | 'danger' }.
 */
export default function KebabMenu({ items = [], label = 'Menu tindakan' }) {
    const [open, setOpen] = useState(false);
    const wrapperRef = useRef(null);

    useEffect(() => {
        if (!open) return undefined;
        const onDocClick = (e) => {
            if (wrapperRef.current && !wrapperRef.current.contains(e.target)) setOpen(false);
        };
        const onKey = (e) => {
            if (e.key === 'Escape') setOpen(false);
        };
        document.addEventListener('mousedown', onDocClick);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('mousedown', onDocClick);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    const tones = {
        primary: 'text-indigo-700 hover:bg-indigo-50',
        danger: 'text-rose-700 hover:bg-rose-50',
    };

    return (
        <div ref={wrapperRef} className="relative">
            <button
                type="button"
                aria-label={label}
                aria-haspopup="menu"
                aria-expanded={open}
                onClick={() => setOpen((v) => !v)}
                className="p-2 rounded-lg text-slate-700 hover:bg-slate-100 border border-transparent hover:border-slate-200"
            >
                <DotsIcon />
            </button>

            {open && (
                <div role="menu" aria-label={label} className="absolute right-0 z-30 mt-1 w-44 rounded-xl bg-white border border-slate-200 shadow-xl py-1">
                    {items.map((item) => {
                        const className = `w-full flex items-center gap-2.5 px-4 py-2.5 text-sm font-semibold text-left ${tones[item.tone || 'primary']}`;
                        const content = (
                            <>
                                {item.icon}
                                <span>{item.label}</span>
                            </>
                        );

                        return item.href ? (
                            <Link key={item.label} role="menuitem" href={item.href} className={className} onClick={() => setOpen(false)}>
                                {content}
                            </Link>
                        ) : (
                            <button
                                key={item.label}
                                type="button"
                                role="menuitem"
                                className={className}
                                onClick={() => {
                                    setOpen(false);
                                    item.onClick?.();
                                }}
                            >
                                {content}
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
