import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import MarkdownViewer from '../../Components/MarkdownViewer';
import PostFormModal from '../../Components/PostFormModal';
import { ArrowLeftIcon, ClockIcon, EyeIcon, PencilIcon, Spinner } from '../../Components/Icons';

export default function Show({ post, categories }) {
    const { auth } = usePage().props;
    const isOwner = auth.user.id === post.author?.id;
    const [formOpen, setFormOpen] = useState(false);
    const [busy, setBusy] = useState(false);

    const backHref = isOwner ? '/dashboard/posts' : `/admin/users/${post.author?.id}/posts`;

    const toggle = () => {
        setBusy(true);
        router.patch(`/dashboard/posts/${post.id}/publish`, {}, { preserveScroll: true, onFinish: () => setBusy(false) });
    };

    return (
        <AppLayout>
            <Head title={post.title} />

            <div className="mx-auto max-w-4xl space-y-6">
                <Link href={backHref} className="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-800 hover:underline">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali
                </Link>

                <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    {post.cover_url && (
                        <img src={post.cover_url} alt={`Sampul artikel: ${post.title}`} width="1200" height="630" className="w-full aspect-[1200/630] object-cover" />
                    )}

                    <div className="p-6 sm:p-8">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className={`text-xs font-bold px-2.5 py-1 rounded-full border ${post.category_badge}`}>{post.category_label}</span>
                            <span className={`text-xs font-bold px-2.5 py-1 rounded-full border ${post.is_published ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-amber-100 text-amber-900 border-amber-300'}`}>
                                {post.is_published ? 'Dipublikasikan' : 'Draf'}
                            </span>
                        </div>

                        <h1 className="mt-4 text-3xl font-extrabold text-slate-900 leading-tight">{post.title}</h1>
                        {post.excerpt && <p className="mt-2 text-lg text-slate-700">{post.excerpt}</p>}

                        <p className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-700">
                            <span>Penulis: <strong className="text-slate-900">{post.author?.name}</strong></span>
                            <span>Dibuat {post.created_label}</span>
                            <span>Diperbarui {post.updated_label}</span>
                            <span className="inline-flex items-center gap-1"><ClockIcon className="w-4 h-4" /> {post.reading_minutes} menit baca</span>
                        </p>

                        <div className="mt-5 flex flex-wrap gap-2">
                            {post.is_published && (
                                <Link href={`/articles/${post.slug}`} className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-indigo-800 border border-indigo-400 hover:bg-indigo-50">
                                    <EyeIcon className="w-4 h-4" /> Lihat di halaman publik
                                </Link>
                            )}
                            {isOwner && (
                                <>
                                    <button type="button" onClick={() => setFormOpen(true)} className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-indigo-700 hover:bg-indigo-800">
                                        <PencilIcon className="w-4 h-4" /> Ubah Artikel
                                    </button>
                                    <button type="button" onClick={toggle} disabled={busy} className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-slate-900 border border-slate-400 hover:bg-slate-50 disabled:opacity-60">
                                        {busy && <Spinner />}
                                        <span>{post.is_published ? 'Jadikan Draf' : 'Publikasikan'}</span>
                                    </button>
                                </>
                            )}
                        </div>

                        <hr className="my-6 border-slate-200" />

                        <MarkdownViewer content={post.content} />
                    </div>
                </div>
            </div>

            {isOwner && <PostFormModal open={formOpen} onClose={() => setFormOpen(false)} post={post} categories={categories} />}
        </AppLayout>
    );
}
