import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Pagination from '../../Components/Pagination';
import AdminPostTable from '../../Components/AdminPostTable';
import AdminPostFilters from '../../Components/AdminPostFilters';
import { DownloadIcon } from '../../Components/Icons';

export default function PostList({ posts, categories, filters, stats }) {
    return (
        <AppLayout>
            <Head title="Monitoring Semua Artikel - Admin" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-slate-900">Semua Artikel</h1>
                        <p className="text-slate-700 mt-1">Pantau seluruh artikel pengguna (mode read-only).</p>
                    </div>
                    <a href="/admin/posts/export" className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-emerald-900 bg-emerald-100 border border-emerald-400 hover:bg-emerald-200">
                        <DownloadIcon className="w-5 h-5" /> Ekspor Semua ke Excel
                    </a>
                </div>

                <dl className="grid gap-4 sm:grid-cols-3">
                    {[['Total Artikel', stats.total], ['Dipublikasikan', stats.published], ['Draf', stats.draft]].map(([label, value]) => (
                        <div key={label} className="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                            <dt className="text-sm font-semibold text-slate-700">{label}</dt>
                            <dd className="text-3xl font-extrabold text-slate-900">{value}</dd>
                        </div>
                    ))}
                </dl>

                <AdminPostFilters url="/admin/posts" filters={filters} categories={categories} />
                <AdminPostTable posts={posts} caption="Daftar seluruh artikel sistem" />
                <Pagination links={posts.links} />
            </div>
        </AppLayout>
    );
}
