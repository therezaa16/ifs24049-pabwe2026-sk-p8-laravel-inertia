import React from 'react';
import { Link } from '@inertiajs/react';
import { EyeIcon } from './Icons';

/** Tabel artikel read-only untuk Admin (tanpa aksi ubah/hapus). */
export default function AdminPostTable({ posts, showAuthor = true, caption }) {
    return (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full text-left text-sm">
                    <caption className="sr-only">{caption}</caption>
                    <thead className="bg-slate-100 text-slate-800">
                        <tr>
                            <th scope="col" className="px-5 py-3 font-bold">Judul</th>
                            {showAuthor && <th scope="col" className="px-5 py-3 font-bold">Penulis</th>}
                            <th scope="col" className="px-5 py-3 font-bold">Kategori</th>
                            <th scope="col" className="px-5 py-3 font-bold">Status</th>
                            <th scope="col" className="px-5 py-3 font-bold">Diperbarui</th>
                            <th scope="col" className="px-5 py-3 font-bold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-200">
                        {posts.data.length === 0 && (
                            <tr>
                                <td colSpan={showAuthor ? 6 : 5} className="px-5 py-10 text-center text-slate-700">Tidak ada artikel yang cocok.</td>
                            </tr>
                        )}
                        {posts.data.map((post) => (
                            <tr key={post.id} className="hover:bg-slate-50">
                                <td className="px-5 py-4 font-bold text-slate-900 max-w-xs">
                                    <span className="line-clamp-2">{post.title}</span>
                                </td>
                                {showAuthor && <td className="px-5 py-4 text-slate-800">{post.author?.name}</td>}
                                <td className="px-5 py-4">
                                    <span className={`text-xs font-bold px-2.5 py-1 rounded-full border ${post.category_badge}`}>{post.category}</span>
                                </td>
                                <td className="px-5 py-4">
                                    <span className={`text-xs font-bold px-2.5 py-1 rounded-full border ${post.is_published ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-amber-100 text-amber-900 border-amber-300'}`}>
                                        {post.is_published ? 'Dipublikasikan' : 'Draf'}
                                    </span>
                                </td>
                                <td className="px-5 py-4 text-slate-800 whitespace-nowrap">{post.updated_label}</td>
                                <td className="px-5 py-4 text-right">
                                    <Link
                                        href={`/dashboard/posts/${post.id}`}
                                        aria-label={`Lihat detail ${post.title}`}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold text-purple-800 bg-purple-50 hover:bg-purple-100"
                                    >
                                        <EyeIcon className="w-4 h-4" /> Detail
                                    </Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
