import React, { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { SearchIcon } from './Icons';

const field = 'block w-full px-3.5 py-2.5 rounded-xl border border-slate-400 text-slate-900 bg-white focus:ring-2 focus:ring-purple-600 focus:border-purple-600';

export default function AdminPostFilters({ url, filters, categories }) {
    const [search, setSearch] = useState(filters.search || '');
    const first = useRef(true);

    const visit = (params) => router.get(url, params, { preserveState: true, preserveScroll: true, replace: true });

    useEffect(() => {
        if (first.current) {
            first.current = false;
            return undefined;
        }
        const t = setTimeout(() => visit({ search: search || undefined, status: filters.status || undefined, category: filters.category || undefined }), 350);
        return () => clearTimeout(t);
    }, [search]);

    return (
        <section aria-label="Filter artikel" className="bg-white rounded-2xl border border-slate-200 p-4 grid gap-4 md:grid-cols-[2fr_1fr_1fr]">
            <div>
                <label htmlFor="admin-search" className="block text-sm font-bold text-slate-900 mb-1.5">Cari artikel</label>
                <div className="relative">
                    <SearchIcon className="w-5 h-5 text-slate-600 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input id="admin-search" type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Judul atau isi artikel" className={`${field} pl-11`} />
                </div>
            </div>
            <div>
                <label htmlFor="admin-status" className="block text-sm font-bold text-slate-900 mb-1.5">Status</label>
                <select id="admin-status" value={filters.status} onChange={(e) => visit({ search: search || undefined, status: e.target.value || undefined, category: filters.category || undefined })} className={field}>
                    <option value="">Semua status</option>
                    <option value="published">Dipublikasikan</option>
                    <option value="draft">Draf</option>
                </select>
            </div>
            <div>
                <label htmlFor="admin-category" className="block text-sm font-bold text-slate-900 mb-1.5">Kategori</label>
                <select id="admin-category" value={filters.category} onChange={(e) => visit({ search: search || undefined, status: filters.status || undefined, category: e.target.value || undefined })} className={field}>
                    <option value="">Semua kategori</option>
                    {categories.map((c) => (
                        <option key={c.value} value={c.value}>{c.value}</option>
                    ))}
                </select>
            </div>
        </section>
    );
}
