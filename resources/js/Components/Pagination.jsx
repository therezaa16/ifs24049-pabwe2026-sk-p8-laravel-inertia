import React from 'react';
import { Link } from '@inertiajs/react';

const labelFor = (label) =>
    label
        .replace('&laquo; Previous', 'Sebelumnya')
        .replace('Next &raquo;', 'Berikutnya')
        .replace('pagination.previous', 'Sebelumnya')
        .replace('pagination.next', 'Berikutnya');

export default function Pagination({ links = [], className = '' }) {
    if (!links || links.length <= 3) return null;

    return (
        <nav aria-label="Navigasi halaman" className={`flex flex-wrap items-center justify-center gap-1.5 ${className}`}>
            {links.map((link, index) => {
                const label = labelFor(link.label);
                const base = 'min-w-10 h-10 px-3 inline-flex items-center justify-center rounded-lg text-sm font-semibold border transition';

                if (!link.url) {
                    return (
                        <span key={index} className={`${base} border-slate-200 bg-slate-100 text-slate-600`} aria-disabled="true">
                            {label}
                        </span>
                    );
                }

                return (
                    <Link
                        key={index}
                        href={link.url}
                        preserveScroll
                        aria-current={link.active ? 'page' : undefined}
                        className={`${base} ${
                            link.active
                                ? 'bg-indigo-700 text-white border-indigo-700'
                                : 'bg-white text-slate-700 border-slate-300 hover:bg-indigo-50 hover:border-indigo-300'
                        }`}
                    >
                        {label}
                    </Link>
                );
            })}
        </nav>
    );
}
