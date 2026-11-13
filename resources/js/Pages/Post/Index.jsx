import React, { useEffect, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Modal from '../../Components/Modal';
import KebabMenu from '../../Components/KebabMenu';
import Pagination from '../../Components/Pagination';
import PostFormModal from '../../Components/PostFormModal';
import {
    CheckCircleIcon, DocumentIcon, DownloadIcon, EyeIcon, ImageIcon, PencilIcon, PencilSquareIcon, PlusIcon,
    SearchIcon, SparklesIcon, Spinner, TrashIcon, UploadIcon,
} from '../../Components/Icons';

const selectClass = 'block w-full px-3.5 py-2.5 rounded-xl border border-slate-400 text-slate-900 bg-white focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600';

function StatCard({ label, value, Icon, tone }) {
    return (
        <div className="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 shadow-sm">
            <span className={`w-12 h-12 rounded-xl flex items-center justify-center ${tone}`}>
                <Icon className="w-6 h-6" />
            </span>
            <div>
                <p className="text-sm font-semibold text-slate-700">{label}</p>
                <p className="text-3xl font-extrabold text-slate-900">{value}</p>
            </div>
        </div>
    );
}

function StatusBadge({ published }) {
    return (
        <span className={`text-xs font-bold px-2.5 py-1 rounded-full border ${published ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-amber-100 text-amber-900 border-amber-300'}`}>
            {published ? 'Dipublikasikan' : 'Draf'}
        </span>
    );
}

export default function Index({ posts, categories, filters, stats }) {
    const { flash } = usePage().props;

    const [search, setSearch] = useState(filters.search || '');
    const [formOpen, setFormOpen] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [deleteBusy, setDeleteBusy] = useState(false);
    const [togglingId, setTogglingId] = useState(null);
    const [importing, setImporting] = useState(false);
    const importRef = useRef(null);
    const first = useRef(true);

    // Modal AI
    const [aiOpen, setAiOpen] = useState(false);
    const [aiPrompt, setAiPrompt] = useState('');
    const [aiCount, setAiCount] = useState(3);
    const [aiBusy, setAiBusy] = useState(false);
    const [aiSaving, setAiSaving] = useState(false);
    const [aiItems, setAiItems] = useState([]);
    const [aiErrors, setAiErrors] = useState({});

    const editing = posts.data.find((p) => p.id === editingId) || null;
    const countInvalid = !Number.isInteger(Number(aiCount)) || Number(aiCount) < 1 || Number(aiCount) > 5;

    const visit = (params) =>
        router.get('/dashboard/posts', params, { preserveState: true, preserveScroll: true, replace: true });

    useEffect(() => {
        if (first.current) {
            first.current = false;
            return undefined;
        }
        const t = setTimeout(() => visit({ search: search || undefined, status: filters.status || undefined, category: filters.category || undefined }), 350);
        return () => clearTimeout(t);
    }, [search]);

    const openCreate = () => {
        setEditingId(null);
        setFormOpen(true);
    };

    const openEdit = (post) => {
        setEditingId(post.id);
        setFormOpen(true);
    };

    const togglePublish = (post) => {
        setTogglingId(post.id);
        router.patch(`/dashboard/posts/${post.id}/publish`, {}, { preserveScroll: true, onFinish: () => setTogglingId(null) });
    };

    const confirmDelete = () => {
        setDeleteBusy(true);
        router.delete(`/dashboard/posts/${deleting.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setDeleteBusy(false);
                setDeleting(null);
            },
        });
    };

    const handleImport = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setImporting(true);
        router.post('/dashboard/posts/import', { importFile: file }, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                setImporting(false);
                if (importRef.current) importRef.current.value = '';
            },
        });
    };

    const generateAi = (e) => {
        e.preventDefault();
        if (countInvalid) return;
        setAiBusy(true);
        setAiErrors({});
        router.post('/dashboard/posts/ai-generate', { prompt: aiPrompt, count: Number(aiCount) }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => setAiItems(page.props.flash?.aiGeneratedItems || []),
            onError: (errors) => setAiErrors(errors),
            onFinish: () => setAiBusy(false),
        });
    };

    const saveAi = () => {
        setAiSaving(true);
        router.post('/dashboard/posts/ai-save', { items: aiItems }, {
            preserveScroll: true,
            onSuccess: () => {
                setAiItems([]);
                setAiPrompt('');
                setAiOpen(false);
            },
            onFinish: () => setAiSaving(false),
        });
    };

    return (
        <AppLayout>
            <Head title="Artikel Saya" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-slate-900">Artikel Saya</h1>
                        <p className="text-slate-700 mt-1">Kelola tulisan, status publikasi, dan foto sampul Anda.</p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <button type="button" onClick={openCreate} className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-white bg-indigo-700 hover:bg-indigo-800">
                            <PlusIcon className="w-5 h-5" /> Tulis Artikel
                        </button>
                        <button type="button" onClick={() => setAiOpen(true)} className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-white bg-violet-700 hover:bg-violet-800">
                            <SparklesIcon className="w-5 h-5" /> Generate AI
                        </button>
                        <a href="/dashboard/posts/export" className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-emerald-900 bg-emerald-100 border border-emerald-400 hover:bg-emerald-200">
                            <DownloadIcon className="w-5 h-5" /> Ekspor Excel
                        </a>
                        <input ref={importRef} type="file" accept=".xlsx,.xls,.csv" onChange={handleImport} className="sr-only" aria-label="Pilih berkas Excel untuk diimpor" tabIndex={-1} />
                        <button
                            type="button"
                            onClick={() => importRef.current?.click()}
                            disabled={importing}
                            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-slate-900 bg-white border border-slate-400 hover:bg-slate-50 disabled:opacity-60"
                        >
                            {importing ? <Spinner /> : <UploadIcon className="w-5 h-5" />} Impor Excel
                        </button>
                    </div>
                </div>

                <section aria-label="Ringkasan statistik artikel" className="grid gap-4 sm:grid-cols-3">
                    <StatCard label="Total Artikel" value={stats.total} Icon={DocumentIcon} tone="bg-indigo-100 text-indigo-800" />
                    <StatCard label="Total Selesai" value={stats.published} Icon={CheckCircleIcon} tone="bg-emerald-100 text-emerald-800" />
                    <StatCard label="Belum Selesai" value={stats.draft} Icon={PencilSquareIcon} tone="bg-amber-100 text-amber-900" />
                </section>

                <section aria-label="Filter artikel" className="bg-white rounded-2xl border border-slate-200 p-4 grid gap-4 md:grid-cols-[2fr_1fr_1fr]">
                    <div>
                        <label htmlFor="filter-search" className="block text-sm font-bold text-slate-900 mb-1.5">Cari artikel</label>
                        <div className="relative">
                            <SearchIcon className="w-5 h-5 text-slate-600 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                            <input id="filter-search" type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Judul atau isi artikel" className={`${selectClass} pl-11`} />
                        </div>
                    </div>
                    <div>
                        <label htmlFor="filter-status" className="block text-sm font-bold text-slate-900 mb-1.5">Status</label>
                        <select id="filter-status" value={filters.status} onChange={(e) => visit({ search: search || undefined, status: e.target.value || undefined, category: filters.category || undefined })} className={selectClass}>
                            <option value="">Semua status</option>
                            <option value="published">Dipublikasikan</option>
                            <option value="draft">Draf</option>
                        </select>
                    </div>
                    <div>
                        <label htmlFor="filter-category" className="block text-sm font-bold text-slate-900 mb-1.5">Kategori</label>
                        <select id="filter-category" value={filters.category} onChange={(e) => visit({ search: search || undefined, status: filters.status || undefined, category: e.target.value || undefined })} className={selectClass}>
                            <option value="">Semua kategori</option>
                            {categories.map((c) => (
                                <option key={c.value} value={c.value}>{c.value}</option>
                            ))}
                        </select>
                    </div>
                </section>

                <section aria-label="Daftar artikel">
                    {posts.data.length === 0 ? (
                        <div className="text-center py-16 bg-white rounded-2xl border border-dashed border-slate-300">
                            <p className="text-lg font-bold text-slate-900">Belum ada artikel</p>
                            <p className="mt-1 text-slate-700">Klik &ldquo;Tulis Artikel&rdquo; atau gunakan Generate AI untuk memulai.</p>
                        </div>
                    ) : (
                        <ul className="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-200 shadow-sm">
                            {posts.data.map((post) => (
                                <li key={post.id} className="flex items-center gap-4 p-4">
                                    <div className="hidden sm:flex w-20 h-14 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden items-center justify-center text-slate-600 shrink-0">
                                        {post.cover_url ? <img src={post.cover_url} alt="" width="80" height="56" loading="lazy" className="w-full h-full object-cover" /> : <ImageIcon className="w-6 h-6" />}
                                    </div>

                                    <div className="min-w-0 flex-1">
                                        <Link href={`/dashboard/posts/${post.id}`} className="font-bold text-slate-900 hover:text-indigo-800 hover:underline line-clamp-1">
                                            {post.title}
                                        </Link>
                                        <div className="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                            <span className={`text-xs font-bold px-2.5 py-1 rounded-full border ${post.category_badge}`}>{post.category}</span>
                                            <StatusBadge published={post.is_published} />
                                            <span className="text-xs text-slate-700">Diperbarui {post.updated_label}</span>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() => togglePublish(post)}
                                        disabled={togglingId === post.id}
                                        className={`hidden md:inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-semibold border disabled:opacity-60 ${
                                            post.is_published ? 'text-amber-900 border-amber-400 bg-amber-50 hover:bg-amber-100' : 'text-emerald-900 border-emerald-400 bg-emerald-50 hover:bg-emerald-100'
                                        }`}
                                    >
                                        {togglingId === post.id && <Spinner />}
                                        <span>{post.is_published ? 'Jadikan Draf' : 'Publikasikan'}</span>
                                    </button>

                                    <KebabMenu
                                        label={`Menu tindakan untuk ${post.title}`}
                                        items={[
                                            { label: 'Detail', icon: <EyeIcon className="w-4 h-4" />, href: `/dashboard/posts/${post.id}`, tone: 'primary' },
                                            { label: 'Ubah', icon: <PencilIcon className="w-4 h-4" />, onClick: () => openEdit(post), tone: 'primary' },
                                            { label: post.is_published ? 'Jadikan Draf' : 'Publikasikan', icon: <CheckCircleIcon className="w-4 h-4" />, onClick: () => togglePublish(post), tone: 'primary' },
                                            { label: 'Hapus', icon: <TrashIcon className="w-4 h-4" />, onClick: () => setDeleting(post), tone: 'danger' },
                                        ]}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                    <Pagination links={posts.links} className="mt-6" />
                </section>
            </div>

            <PostFormModal open={formOpen} onClose={() => setFormOpen(false)} post={editing} categories={categories} />

            {/* Konfirmasi hapus */}
            <Modal
                open={Boolean(deleting)}
                onClose={() => setDeleting(null)}
                title="Hapus Artikel"
                headerClass="bg-rose-700 text-white"
                footerClass="bg-slate-100 border-t border-slate-300"
                footer={
                    <>
                        <button type="button" onClick={() => setDeleting(null)} className="px-5 py-2.5 rounded-xl font-semibold text-slate-800 bg-white border border-slate-400 hover:bg-slate-50">Batal</button>
                        <button type="button" onClick={confirmDelete} disabled={deleteBusy} className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-white bg-rose-700 hover:bg-rose-800 disabled:opacity-60">
                            {deleteBusy && <Spinner />}
                            <span>{deleteBusy ? 'Menghapus...' : 'Hapus'}</span>
                        </button>
                    </>
                }
            >
                <p className="text-slate-800">
                    Anda yakin ingin menghapus artikel <strong>{deleting?.title}</strong>? Tindakan ini tidak dapat dibatalkan.
                </p>
            </Modal>

            {/* AI Generator */}
            <Modal
                open={aiOpen}
                onClose={() => setAiOpen(false)}
                title="Generate Artikel dengan AI"
                subtitle="Hasil disimpan sebagai draf sehingga dapat Anda tinjau terlebih dahulu"
                size="max-w-3xl"
                headerClass="bg-violet-700 text-white"
                footerClass="bg-slate-100 border-t border-slate-300"
                footer={
                    <>
                        <button type="button" onClick={() => setAiOpen(false)} className="px-5 py-2.5 rounded-xl font-semibold text-slate-800 bg-white border border-slate-400 hover:bg-slate-50">Tutup</button>
                        {aiItems.length > 0 && (
                            <button type="button" onClick={saveAi} disabled={aiSaving} className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-white bg-emerald-700 hover:bg-emerald-800 disabled:opacity-60">
                                {aiSaving && <Spinner />}
                                <span>{aiSaving ? 'Menyimpan...' : `Simpan ${aiItems.length} Artikel sebagai Draf`}</span>
                            </button>
                        )}
                    </>
                }
            >
                <form onSubmit={generateAi} className="space-y-4" noValidate>
                    <div>
                        <label htmlFor="ai-prompt" className="block text-sm font-bold text-slate-900 mb-1.5">Topik / instruksi artikel</label>
                        <textarea
                            id="ai-prompt"
                            rows={3}
                            value={aiPrompt}
                            onChange={(e) => setAiPrompt(e.target.value)}
                            className={selectClass}
                            placeholder="Contoh: Tips belajar Laravel untuk pemula"
                        />
                        {aiErrors.prompt && <p role="alert" className="mt-1 text-sm text-rose-700">{aiErrors.prompt}</p>}
                    </div>

                    <div className="flex flex-wrap items-end gap-4">
                        <div>
                            <label htmlFor="ai-count" className="block text-sm font-bold text-slate-900 mb-1.5">Jumlah artikel (1-5)</label>
                            <input
                                id="ai-count"
                                type="number"
                                min={1}
                                max={5}
                                step={1}
                                value={aiCount}
                                onChange={(e) => setAiCount(e.target.value)}
                                aria-invalid={countInvalid}
                                aria-describedby={countInvalid ? 'ai-count-error' : undefined}
                                className={`${selectClass} w-32`}
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={aiBusy || countInvalid || aiPrompt.trim().length < 3}
                            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-white bg-violet-700 hover:bg-violet-800 disabled:opacity-60"
                        >
                            {aiBusy ? <Spinner /> : <SparklesIcon className="w-5 h-5" />}
                            <span>{aiBusy ? 'Menghasilkan artikel...' : 'Generate'}</span>
                        </button>
                    </div>
                    {countInvalid && <p id="ai-count-error" role="alert" className="text-sm text-rose-700">Jumlah artikel harus berupa angka bulat antara 1 dan 5.</p>}
                    {aiErrors.count && <p role="alert" className="text-sm text-rose-700">{aiErrors.count}</p>}
                    {flash?.error && aiOpen && <p role="alert" className="text-sm text-rose-700">{flash.error}</p>}
                </form>

                {aiItems.length > 0 && (
                    <div className="mt-6">
                        <h3 className="text-sm font-bold text-slate-900 mb-2">Hasil generate ({aiItems.length})</h3>
                        <ul className="space-y-3">
                            {aiItems.map((item, index) => (
                                <li key={index} className="rounded-xl border border-slate-300 p-4 bg-slate-50">
                                    <p className="font-bold text-slate-900">{item.title}</p>
                                    <p className="text-xs font-semibold text-violet-800 mt-0.5">{item.category}</p>
                                    {item.excerpt && <p className="text-sm text-slate-800 mt-1.5">{item.excerpt}</p>}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </Modal>
        </AppLayout>
    );
}
