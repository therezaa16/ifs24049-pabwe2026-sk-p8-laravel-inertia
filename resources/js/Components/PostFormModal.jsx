import React, { useEffect, useRef, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import Modal from './Modal';
import MarkdownEditor from './MarkdownEditor';
import { ImageIcon, Spinner, TrashIcon, UploadIcon } from './Icons';

const inputClass = 'block w-full px-3.5 py-2.5 rounded-xl border border-slate-400 text-slate-900 bg-white focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600';

function FieldError({ id, message }) {
    if (!message) return null;
    return (
        <p id={id} role="alert" className="mt-1 text-sm text-rose-700">
            {message}
        </p>
    );
}

/**
 * Modal Full Screen untuk Tambah / Ubah artikel.
 * Header berwarna indigo, footer berwarna abu-abu; tombol aksi di kanan footer.
 */
export default function PostFormModal({ open, onClose, post = null, categories = [] }) {
    const isEdit = Boolean(post);
    const fileRef = useRef(null);
    const [preview, setPreview] = useState(null);
    const [coverBusy, setCoverBusy] = useState(false);

    const form = useForm({
        title: '',
        category: 'Umum',
        excerpt: '',
        content: '',
        is_published: false,
        coverPhoto: null,
    });

    useEffect(() => {
        if (!open) return;
        form.clearErrors();
        form.setData({
            title: post?.title || '',
            category: post?.category || 'Umum',
            excerpt: post?.excerpt || '',
            content: post?.content || '',
            is_published: Boolean(post?.is_published),
            coverPhoto: null,
        });
        setPreview(null);
    }, [open, post?.id]);

    const submit = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => onClose() };

        if (isEdit) {
            form.put(`/dashboard/posts/${post.id}`, options);
        } else {
            form.post('/dashboard/posts', { ...options, forceFormData: true });
        }
    };

    const pickCover = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (isEdit) {
            setCoverBusy(true);
            router.post(`/dashboard/posts/${post.id}/cover`, { coverPhoto: file }, {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setCoverBusy(false),
            });
        } else {
            form.setData('coverPhoto', file);
            setPreview(URL.createObjectURL(file));
        }
        e.target.value = '';
    };

    const removeCover = () => {
        if (isEdit) {
            setCoverBusy(true);
            router.delete(`/dashboard/posts/${post.id}/cover`, {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setCoverBusy(false),
            });
        } else {
            form.setData('coverPhoto', null);
            setPreview(null);
        }
    };

    const coverSrc = isEdit ? post?.cover_url : preview;

    return (
        <Modal
            open={open}
            onClose={onClose}
            fullScreen
            title={isEdit ? 'Ubah Artikel' : 'Tulis Artikel Baru'}
            subtitle={isEdit ? post?.title : 'Isi detail artikel lalu simpan sebagai draf atau publikasikan'}
            headerClass="bg-indigo-700 text-white"
            footerClass="bg-slate-200 border-t border-slate-300"
            footer={
                <>
                    <button type="button" onClick={onClose} className="px-5 py-2.5 rounded-xl font-semibold text-slate-800 bg-white border border-slate-400 hover:bg-slate-50">
                        Batal
                    </button>
                    <button
                        type="submit"
                        form="post-form"
                        disabled={form.processing}
                        className="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl font-semibold text-white bg-indigo-700 hover:bg-indigo-800 disabled:opacity-60"
                    >
                        {form.processing && <Spinner />}
                        <span>{form.processing ? 'Menyimpan...' : 'Simpan'}</span>
                    </button>
                </>
            }
        >
            <form id="post-form" onSubmit={submit} className="mx-auto max-w-4xl space-y-6" noValidate>
                <div>
                    <label htmlFor="post-title" className="block text-sm font-bold text-slate-900 mb-1.5">Judul Artikel</label>
                    <input
                        id="post-title"
                        type="text"
                        value={form.data.title}
                        onChange={(e) => form.setData('title', e.target.value)}
                        aria-invalid={Boolean(form.errors.title)}
                        aria-describedby={form.errors.title ? 'post-title-error' : undefined}
                        className={inputClass}
                        placeholder="Judul yang menarik pembaca"
                        required
                    />
                    <FieldError id="post-title-error" message={form.errors.title} />
                </div>

                <div className="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label htmlFor="post-category" className="block text-sm font-bold text-slate-900 mb-1.5">Kategori</label>
                        <select id="post-category" value={form.data.category} onChange={(e) => form.setData('category', e.target.value)} className={inputClass}>
                            {categories.map((c) => (
                                <option key={c.value} value={c.value}>{c.label}</option>
                            ))}
                        </select>
                        <FieldError message={form.errors.category} />
                    </div>

                    <div className="flex items-end">
                        <label htmlFor="post-published" className="flex items-center gap-3 py-2.5 text-sm font-semibold text-slate-900">
                            <input
                                id="post-published"
                                type="checkbox"
                                checked={form.data.is_published}
                                onChange={(e) => form.setData('is_published', e.target.checked)}
                                className="w-5 h-5 rounded border-slate-500 text-indigo-700 focus:ring-indigo-600"
                            />
                            <span>Publikasikan ke halaman publik</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label htmlFor="post-excerpt" className="block text-sm font-bold text-slate-900 mb-1.5">
                        Ringkasan <span className="font-normal text-slate-700">(1-2 kalimat pemikat pembaca)</span>
                    </label>
                    <textarea
                        id="post-excerpt"
                        rows={3}
                        maxLength={500}
                        value={form.data.excerpt}
                        onChange={(e) => form.setData('excerpt', e.target.value)}
                        className={inputClass}
                    />
                    <FieldError message={form.errors.excerpt} />
                </div>

                <fieldset>
                    <legend className="block text-sm font-bold text-slate-900 mb-1.5">Foto Sampul</legend>
                    <div className="flex flex-wrap items-center gap-4">
                        <div className="w-44 aspect-[16/9] rounded-xl border border-slate-300 bg-slate-100 overflow-hidden flex items-center justify-center text-slate-600">
                            {coverSrc ? <img src={coverSrc} alt="Pratinjau foto sampul" className="w-full h-full object-cover" /> : <ImageIcon className="w-8 h-8" />}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <input ref={fileRef} id="post-cover" type="file" accept="image/*" onChange={pickCover} className="sr-only" aria-label="Pilih foto sampul" />
                            <button
                                type="button"
                                onClick={() => fileRef.current?.click()}
                                disabled={coverBusy}
                                className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-indigo-800 border border-indigo-400 hover:bg-indigo-50 disabled:opacity-60"
                            >
                                {coverBusy ? <Spinner /> : <UploadIcon className="w-4 h-4" />}
                                <span>{coverSrc ? 'Ganti Foto' : 'Unggah Foto'}</span>
                            </button>
                            {coverSrc && (
                                <button
                                    type="button"
                                    onClick={removeCover}
                                    disabled={coverBusy}
                                    className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-rose-700 border border-rose-400 hover:bg-rose-50 disabled:opacity-60"
                                >
                                    <TrashIcon className="w-4 h-4" />
                                    <span>Hapus Foto</span>
                                </button>
                            )}
                        </div>
                    </div>
                    <FieldError message={form.errors.coverPhoto} />
                </fieldset>

                <div>
                    <span id="post-content-label" className="block text-sm font-bold text-slate-900 mb-1.5">Isi Artikel (Markdown)</span>
                    <MarkdownEditor value={form.data.content} onChange={(md) => form.setData('content', md)} height="420px" />
                    <FieldError message={form.errors.content} />
                </div>
            </form>
        </Modal>
    );
}
