import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Pagination from '../../Components/Pagination';
import AdminPostTable from '../../Components/AdminPostTable';
import AdminPostFilters from '../../Components/AdminPostFilters';
import { ArrowLeftIcon, DownloadIcon } from '../../Components/Icons';

export default function UserPostList({ targetUser, posts, categories, filters }) {
    return (
        <AppLayout>
            <Head title={`Artikel ${targetUser.name} - Admin`} />

            <div className="mx-auto max-w-6xl space-y-6">
                <Link href="/admin/users" className="inline-flex items-center gap-1.5 text-sm font-semibold text-purple-800 hover:underline">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke daftar pengguna
                </Link>

                <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-slate-900">Artikel {targetUser.name}</h1>
                        <p className="text-slate-700 mt-1">@{targetUser.username} &middot; {targetUser.email} &middot; mode read-only</p>
                    </div>
                    <a href={`/admin/users/${targetUser.id}/posts/export`} className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-emerald-900 bg-emerald-100 border border-emerald-400 hover:bg-emerald-200">
                        <DownloadIcon className="w-5 h-5" /> Ekspor ke Excel
                    </a>
                </div>

                <AdminPostFilters url={`/admin/users/${targetUser.id}/posts`} filters={filters} categories={categories} />
                <AdminPostTable posts={posts} showAuthor={false} caption={`Daftar artikel milik ${targetUser.name}`} />
                <Pagination links={posts.links} />
            </div>
        </AppLayout>
    );
}
