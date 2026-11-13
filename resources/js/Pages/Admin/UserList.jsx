import React, { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Pagination from '../../Components/Pagination';
import { DocumentIcon, DownloadIcon, SearchIcon } from '../../Components/Icons';

export default function UserList({ users, filters }) {
    const [search, setSearch] = useState(filters?.search || '');
    const first = useRef(true);

    useEffect(() => {
        if (first.current) {
            first.current = false;
            return undefined;
        }
        const t = setTimeout(() => router.get('/admin/users', { search: search || undefined }, { preserveState: true, replace: true }), 350);
        return () => clearTimeout(t);
    }, [search]);

    return (
        <AppLayout>
            <Head title="Manajemen Pengguna - Admin" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-slate-900">Manajemen Pengguna</h1>
                        <p className="text-slate-700 mt-1">Daftar akun terdaftar beserta jumlah artikel masing-masing.</p>
                    </div>
                    <Link href="/admin/posts" className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-purple-900 bg-purple-100 border border-purple-400 hover:bg-purple-200">
                        <DocumentIcon className="w-5 h-5" /> Monitoring Semua Artikel
                    </Link>
                </div>

                <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                    <label htmlFor="user-search" className="block text-sm font-bold text-slate-900 mb-1.5">Cari pengguna</label>
                    <div className="relative">
                        <SearchIcon className="w-5 h-5 text-slate-600 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            id="user-search"
                            type="search"
                            placeholder="Nama, username, atau email"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-400 text-slate-900 focus:ring-2 focus:ring-purple-600 focus:border-purple-600"
                        />
                    </div>
                </div>

                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <caption className="sr-only">Daftar pengguna terdaftar</caption>
                            <thead className="bg-slate-100 text-slate-800">
                                <tr>
                                    <th scope="col" className="px-5 py-3 font-bold">Pengguna</th>
                                    <th scope="col" className="px-5 py-3 font-bold">Email</th>
                                    <th scope="col" className="px-5 py-3 font-bold">Role</th>
                                    <th scope="col" className="px-5 py-3 font-bold text-center">Jumlah Artikel</th>
                                    <th scope="col" className="px-5 py-3 font-bold">Terdaftar</th>
                                    <th scope="col" className="px-5 py-3 font-bold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {users.data.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-5 py-10 text-center text-slate-700">Tidak ada pengguna yang cocok.</td>
                                    </tr>
                                )}
                                {users.data.map((user) => (
                                    <tr key={user.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-4">
                                            <div className="flex items-center gap-3">
                                                <span aria-hidden="true" className="w-9 h-9 rounded-full bg-purple-100 text-purple-900 font-bold flex items-center justify-center shrink-0">
                                                    {user.name?.charAt(0).toUpperCase()}
                                                </span>
                                                <div>
                                                    <p className="font-bold text-slate-900">{user.name}</p>
                                                    <p className="text-slate-700">@{user.username}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-5 py-4 text-slate-800">{user.email}</td>
                                        <td className="px-5 py-4">
                                            <span className={`inline-block px-2.5 py-0.5 rounded-full font-bold uppercase text-xs ${user.role === 'admin' ? 'bg-purple-100 text-purple-900' : 'bg-slate-200 text-slate-900'}`}>
                                                {user.role}
                                            </span>
                                        </td>
                                        <td className="px-5 py-4 text-center font-bold text-slate-900">{user.posts_count ?? 0}</td>
                                        <td className="px-5 py-4 text-slate-800 whitespace-nowrap">
                                            {user.created_at ? new Date(user.created_at).toLocaleDateString('id-ID') : '-'}
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={`/admin/users/${user.id}/posts`}
                                                    aria-label={`Lihat artikel ${user.name}`}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold text-purple-800 bg-purple-50 hover:bg-purple-100"
                                                >
                                                    <DocumentIcon className="w-4 h-4" /> Artikel
                                                </Link>
                                                <a
                                                    href={`/admin/users/${user.id}/posts/export`}
                                                    aria-label={`Ekspor artikel ${user.name} ke Excel`}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold text-emerald-900 bg-emerald-50 hover:bg-emerald-100"
                                                >
                                                    <DownloadIcon className="w-4 h-4" /> Excel
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                <Pagination links={users.links} />
            </div>
        </AppLayout>
    );
}
