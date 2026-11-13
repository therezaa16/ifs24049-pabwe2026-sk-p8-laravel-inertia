import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import MarkdownViewer from '../../Components/MarkdownViewer';
import { ArrowLeftIcon, ClockIcon, LoginIcon, UserPlusIcon } from '../../Components/Icons';

export default function PostShow({ post, related }) {
    const description = post.excerpt || `Baca artikel "${post.title}" di BlogVerse Pro.`;

    return (
        <AppLayout variant="public">
            <Head title={post.title}>
                <meta name="description" content={description} />
                <meta property="og:title" content={post.title} />
                <meta property="og:description" content={description} />
                <meta property="og:type" content="article" />
            </Head>

            <article className="mx-auto max-w-3xl px-4 sm:px-6 pt-8">
                <Link href="/articles" className="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-800 hover:underline">
                    <ArrowLeftIcon className="w-4 h-4" /> Kembali ke daftar artikel
                </Link>

                <header className="mt-6">
                    <span className={`inline-block text-xs font-bold px-2.5 py-1 rounded-full border ${post.category_badge}`}>{post.category_label}</span>
                    <h1 className="mt-4 text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">{post.title}</h1>
                    {post.excerpt && <p className="mt-3 text-lg text-slate-700">{post.excerpt}</p>}
                    <p className="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-700">
                        <span className="font-semibold text-slate-900">{post.author?.name}</span>
                        <span aria-hidden="true">&middot;</span>
                        <time dateTime={post.published_at}>{post.published_label}</time>
                        <span aria-hidden="true">&middot;</span>
                        <span className="inline-flex items-center gap-1">
                            <ClockIcon className="w-4 h-4" /> {post.reading_minutes} menit baca
                        </span>
                    </p>
                </header>

                {post.cover_url && (
                    <img
                        src={post.cover_url}
                        alt={`Sampul artikel: ${post.title}`}
                        width="1200"
                        height="630"
                        fetchpriority="high"
                        className="mt-8 w-full aspect-[1200/630] object-cover rounded-2xl border border-slate-200"
                    />
                )}

                <div className="mt-8">
                    <MarkdownViewer content={post.content} />
                </div>
            </article>

            <section aria-labelledby="ajakan-komunitas" className="mx-auto max-w-3xl px-4 sm:px-6 mt-14">
                <div className="rounded-2xl bg-gradient-to-br from-indigo-800 to-violet-700 text-white p-6 sm:p-8">
                    <h2 id="ajakan-komunitas" className="text-xl sm:text-2xl font-extrabold">Bergabung dengan komunitas penulis</h2>
                    <p className="mt-2 text-indigo-50">Masuk untuk mengelola artikel Anda, atau daftar gratis dan terbitkan tulisan pertama Anda hari ini.</p>
                    <div className="mt-5 flex flex-wrap gap-3">
                        <Link href="/login" className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white text-indigo-800 font-bold hover:bg-indigo-50">
                            <LoginIcon className="w-5 h-5" /> Halaman Login
                        </Link>
                        <Link href="/register" className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-950 text-white font-bold border border-indigo-300/40 hover:bg-black/60">
                            <UserPlusIcon className="w-5 h-5" /> Halaman Register
                        </Link>
                    </div>
                </div>
            </section>

            {related?.length > 0 && (
                <section aria-labelledby="artikel-terkait" className="mx-auto max-w-3xl px-4 sm:px-6 mt-12">
                    <h2 id="artikel-terkait" className="text-xl font-extrabold text-slate-900">Artikel Terkait</h2>
                    <ul className="mt-4 divide-y divide-slate-200 border-y border-slate-200">
                        {related.map((item) => (
                            <li key={item.id} className="py-4">
                                <Link href={`/articles/${item.slug}`} className="font-bold text-slate-900 hover:text-indigo-800 hover:underline">
                                    {item.title}
                                </Link>
                                <p className="text-sm text-slate-700 mt-1">
                                    {item.author?.name} &middot; {item.published_label}
                                </p>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </AppLayout>
    );
}
