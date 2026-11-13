import React, { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Pagination from '../../Components/Pagination';
import { ClockIcon, ImageIcon, LoginIcon, SearchIcon, UserPlusIcon } from '../../Components/Icons';

function ArticleCard({ post }) {
    return (
        <li>
            <article className="h-full flex flex-col bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-lg transition">
                <Link href={`/articles/${post.slug}`} tabIndex={-1} aria-hidden="true" className="block aspect-[16/9] bg-gradient-to-br from-indigo-100 to-violet-100">
                    {post.cover_url ? (
                        <img src={post.cover_url} alt="" width="640" height="360" loading="lazy" decoding="async" className="w-full h-full object-cover" />
                    ) : (
                        <span className="w-full h-full flex items-center justify-center text-indigo-700">
                            <ImageIcon className="w-10 h-10" />
                        </span>
                    )}
                </Link>
                <div className="flex-1 flex flex-col p-5">
                    <span className={`self-start text-xs font-bold px-2.5 py-1 rounded-full border ${post.category_badge}`}>{post.category}</span>
                    <h3 className="mt-3 text-lg font-bold text-slate-900 leading-snug">
                        <Link href={`/articles/${post.slug}`} className="hover:text-indigo-800 hover:underline underline-offset-2">
                            {post.title}
                        </Link>
                    </h3>
                    {post.excerpt && <p className="mt-2 text-sm text-slate-700 line-clamp-3">{post.excerpt}</p>}
                    <div className="mt-auto pt-4 flex items-center justify-between gap-3 text-xs text-slate-600">
                        <span className="truncate">
                            {post.author?.name} &middot; <time dateTime={post.published_at}>{post.published_label}</time>
                        </span>
                        <span className="inline-flex items-center gap-1 shrink-0">
                            <ClockIcon className="w-3.5 h-3.5" /> {post.reading_minutes} mnt
                        </span>
                    </div>
                </div>
            </article>
        </li>
    );
}

export default function PostIndex({ posts, categories, filters, isHome }) {
    const [search, setSearch] = useState(filters?.search || '');
    const first = useRef(true);
    const category = filters?.category || '';

    const visit = (params) =>
        router.get('/articles', params, { preserveState: true, preserveScroll: true, replace: true });

    // Pencarian dengan debounce agar tidak membanjiri server.
    useEffect(() => {
        if (first.current) {
            first.current = false;
            return undefined;
        }
        const t = setTimeout(() => visit({ search: search || undefined, category: category || undefined }), 350);
        return () => clearTimeout(t);
    }, [search]);

    const chip = (value, label) => {
        const active = category === value;
        return (
            <li key={value || 'all'}>
                <button
                    type="button"
                    aria-pressed={active}
                    onClick={() => visit({ search: search || undefined, category: value || undefined })}
                    className={`px-4 py-2 rounded-full text-sm font-semibold border transition ${
                        active ? 'bg-indigo-700 text-white border-indigo-700' : 'bg-white text-slate-700 border-slate-300 hover:bg-indigo-50'
                    }`}
                >
                    {label}
                </button>
            </li>
        );
    };

    return (
        <AppLayout variant="public">
            <Head title={isHome ? 'Beranda' : 'Daftar Artikel'}>
                <meta
                    name="description"
                    content="Jelajahi artikel terbaru tentang teknologi, pendidikan, gaya hidup, bisnis, dan opini di BlogVerse Pro. Baca gratis tanpa perlu login."
                />
            </Head>

            {isHome && (
                <section aria-labelledby="hero-title" className="bg-gradient-to-br from-indigo-800 via-indigo-700 to-violet-700 text-white">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 py-14 sm:py-20">
                        <p className="text-sm font-bold uppercase tracking-widest text-indigo-100">Platform Blog Multi-Tingkat</p>
                        <h1 id="hero-title" className="mt-3 text-3xl sm:text-5xl font-extrabold leading-tight max-w-3xl">
                            Bacalah gagasan baru, tulislah gagasanmu sendiri.
                        </h1>
                        <p className="mt-4 text-base sm:text-lg text-indigo-50 max-w-2xl">
                            BlogVerse Pro mengumpulkan artikel teknologi, pendidikan, gaya hidup, bisnis, dan opini. Baca gratis tanpa akun, atau daftar sebagai penulis untuk mulai menerbitkan.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link href="/login" className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-indigo-800 font-bold hover:bg-indigo-50">
                                <LoginIcon className="w-5 h-5" /> Masuk
                            </Link>
                            <Link href="/register" className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-indigo-950 text-white font-bold border border-indigo-300/40 hover:bg-black/60">
                                <UserPlusIcon className="w-5 h-5" /> Daftar Penulis
                            </Link>
                        </div>
                    </div>
                </section>
            )}

            <div className="mx-auto max-w-6xl px-4 sm:px-6 py-10">
                {isHome ? (
                    <h2 className="text-2xl sm:text-3xl font-extrabold text-slate-900">Artikel Terbaru</h2>
                ) : (
                    <h1 className="text-2xl sm:text-3xl font-extrabold text-slate-900">Semua Artikel</h1>
                )}

                <form role="search" onSubmit={(e) => { e.preventDefault(); visit({ search: search || undefined, category: category || undefined }); }} className="mt-6">
                    <label htmlFor="cari-artikel" className="block text-sm font-semibold text-slate-800 mb-1.5">
                        Cari artikel berdasarkan judul atau isi
                    </label>
                    <div className="relative">
                        <SearchIcon className="w-5 h-5 text-slate-600 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input
                            id="cari-artikel"
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Contoh: Laravel, produktivitas..."
                            className="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-400 text-slate-900 placeholder:text-slate-600 focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600"
                        />
                    </div>
                </form>

                <nav aria-label="Filter kategori" className="mt-5">
                    <ul className="flex flex-wrap gap-2">
                        {chip('', 'Semua')}
                        {categories.map((c) => chip(c.value, c.value))}
                    </ul>
                </nav>

                <div className="mt-8" aria-live="polite">
                    {posts.data.length === 0 ? (
                        <div className="text-center py-16 bg-slate-50 rounded-2xl border border-dashed border-slate-300">
                            <p className="text-lg font-bold text-slate-900">Belum ada artikel yang cocok</p>
                            <p className="mt-1 text-slate-700">Coba kata kunci atau kategori lain.</p>
                        </div>
                    ) : (
                        <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {posts.data.map((post) => (
                                <ArticleCard key={post.id} post={post} />
                            ))}
                        </ul>
                    )}
                </div>

                <Pagination links={posts.links} className="mt-10" />
            </div>
        </AppLayout>
    );
}
