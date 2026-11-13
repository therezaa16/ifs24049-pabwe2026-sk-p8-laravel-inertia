import React, { useEffect, useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    CloseIcon, DocumentIcon, HomeIcon, LoginIcon, LogoutIcon, MenuIcon, Spinner, UserIcon, UserPlusIcon, UsersIcon,
} from '../Components/Icons';

const APP_NAME = 'BlogVerse Pro';

const ACCENTS = {
    indigo: 'bg-indigo-50 text-indigo-800',
    purple: 'bg-purple-50 text-purple-800',
};

function Brand({ href = '/', light = false }) {
    return (
        <Link href={href} className="flex items-center gap-2.5">
            <span className="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-700 to-violet-600 text-white font-extrabold text-lg flex items-center justify-center shadow-sm" aria-hidden="true">
                B
            </span>
            <span className={`font-extrabold text-lg tracking-tight ${light ? 'text-white' : 'text-slate-900'}`}>{APP_NAME}</span>
        </Link>
    );
}

function Avatar({ user, size = 'w-9 h-9' }) {
    const [error, setError] = useState(false);
    useEffect(() => setError(false), [user?.profile_photo_path]);

    return (
        <span className={`${size} rounded-full overflow-hidden bg-indigo-700 text-white font-bold flex items-center justify-center shrink-0`} aria-hidden="true">
            {user.profile_photo_path && !error ? (
                <img
                    src={`/storage/${user.profile_photo_path}`}
                    alt=""
                    width="40"
                    height="40"
                    className="w-full h-full object-cover"
                    onError={() => setError(true)}
                />
            ) : (
                <span>{user.name?.charAt(0).toUpperCase()}</span>
            )}
        </span>
    );
}

function Feedback({ isProcessing, flash }) {
    const [dismissed, setDismissed] = useState(null);

    useEffect(() => setDismissed(null), [flash?.success, flash?.error]);

    return (
        <>
            {isProcessing && (
                <div className="fixed top-0 left-0 right-0 z-[70] h-1 bg-gradient-to-r from-indigo-700 via-violet-600 to-fuchsia-600" aria-hidden="true">
                    <div className="h-full w-full bg-white/40 animate-pulse" />
                </div>
            )}

            <div role="status" aria-live="polite">
                {isProcessing && (
                    <div className="fixed bottom-5 right-5 z-[70] bg-slate-900 text-white px-4 py-2.5 rounded-2xl shadow-xl flex items-center gap-2.5 text-xs font-semibold border border-slate-700 animate-pill-in">
                        <Spinner className="w-4 h-4 text-indigo-300" />
                        <span>Memproses tindakan...</span>
                    </div>
                )}
            </div>

            {flash?.success && dismissed !== 'success' && (
                <div role="status" className="mx-4 mt-4 sm:mx-6 lg:mx-8 bg-emerald-50 border border-emerald-300 text-emerald-900 px-4 py-3 rounded-xl flex items-center justify-between gap-3 text-sm">
                    <span>{flash.success}</span>
                    <button type="button" onClick={() => setDismissed('success')} aria-label="Tutup pesan" className="p-1 rounded hover:bg-emerald-100">
                        <CloseIcon className="w-4 h-4" />
                    </button>
                </div>
            )}
            {flash?.error && dismissed !== 'error' && (
                <div role="alert" className="mx-4 mt-4 sm:mx-6 lg:mx-8 bg-rose-50 border border-rose-300 text-rose-900 px-4 py-3 rounded-xl flex items-center justify-between gap-3 text-sm">
                    <span>{flash.error}</span>
                    <button type="button" onClick={() => setDismissed('error')} aria-label="Tutup pesan" className="p-1 rounded hover:bg-rose-100">
                        <CloseIcon className="w-4 h-4" />
                    </button>
                </div>
            )}
        </>
    );
}

function useProcessing() {
    const [isProcessing, setIsProcessing] = useState(false);

    useEffect(() => {
        const offStart = router.on('start', () => setIsProcessing(true));
        const offFinish = router.on('finish', () => setIsProcessing(false));
        return () => {
            offStart();
            offFinish();
        };
    }, []);

    return isProcessing;
}

const logout = () => router.post('/logout');

/* ------------------------------------------------------------------ */
/* Layout publik: Sticky Top Navbar, area baca lebar penuh, footer     */
/* ------------------------------------------------------------------ */
function PublicShell({ children, flash, user, url, isProcessing }) {
    const path = url.split('?')[0];
    const navLink = (href, label, active) => (
        <Link
            href={href}
            aria-current={active ? 'page' : undefined}
            className={`px-3 py-2 rounded-lg text-sm font-semibold ${active ? 'bg-indigo-50 text-indigo-800' : 'text-slate-700 hover:bg-slate-100'}`}
        >
            {label}
        </Link>
    );

    return (
        <div className="min-h-screen flex flex-col bg-white">
            <a href="#konten" className="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[80] focus:bg-white focus:text-indigo-800 focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg">
                Lewati ke konten utama
            </a>

            <Feedback isProcessing={isProcessing} flash={{}} />

            <header className="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                    <Brand />

                    <nav aria-label="Navigasi utama" className="flex flex-wrap items-center gap-1.5">
                        {navLink('/', 'Beranda', path === '/')}
                        {navLink('/articles', 'Artikel', path.startsWith('/articles'))}

                        {user ? (
                            <>
                                <Link
                                    href={user.role === 'admin' ? '/admin/users' : '/dashboard/posts'}
                                    className="ml-1 inline-flex items-center gap-2 pl-1.5 pr-3.5 py-1.5 rounded-full bg-indigo-700 text-white text-sm font-semibold hover:bg-indigo-800"
                                >
                                    <Avatar user={user} size="w-7 h-7" />
                                    <span>{user.role === 'admin' ? 'Panel Admin' : 'Dashboard Penulis'}</span>
                                </Link>
                                <button type="button" onClick={logout} className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold text-rose-700 hover:bg-rose-50">
                                    <LogoutIcon className="w-4 h-4" />
                                    <span>Keluar</span>
                                </button>
                            </>
                        ) : (
                            <>
                                <Link href="/login" className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-semibold text-indigo-800 border border-indigo-300 hover:bg-indigo-50">
                                    <LoginIcon className="w-4 h-4" />
                                    <span>Masuk</span>
                                </Link>
                                <Link href="/register" className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-700 hover:bg-indigo-800">
                                    <UserPlusIcon className="w-4 h-4" />
                                    <span>Daftar</span>
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            <main id="konten" className="flex-1">
                {flash?.success && (
                    <div role="status" className="mx-auto max-w-6xl mt-4 px-4 sm:px-6">
                        <p className="bg-emerald-50 border border-emerald-300 text-emerald-900 px-4 py-3 rounded-xl text-sm">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div role="alert" className="mx-auto max-w-6xl mt-4 px-4 sm:px-6">
                        <p className="bg-rose-50 border border-rose-300 text-rose-900 px-4 py-3 rounded-xl text-sm">{flash.error}</p>
                    </div>
                )}
                {children}
            </main>

            <footer className="bg-slate-900 text-slate-300 mt-16">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 py-10 grid gap-8 sm:grid-cols-2 items-start">
                    <div>
                        <Brand light />
                        <p className="mt-3 text-sm text-slate-300 max-w-sm">
                            Platform publikasi artikel tentang teknologi, pendidikan, gaya hidup, bisnis, dan opini. Baca gratis, tulis kapan saja.
                        </p>
                    </div>
                    <nav aria-label="Navigasi footer" className="sm:justify-self-end">
                        <ul className="flex flex-wrap gap-x-6 gap-y-2 text-sm font-semibold">
                            <li><Link href="/" className="text-white underline-offset-4 hover:underline">Beranda Publik</Link></li>
                            <li><Link href="/login" className="text-white underline-offset-4 hover:underline">Halaman Login</Link></li>
                            <li><Link href="/register" className="text-white underline-offset-4 hover:underline">Halaman Register</Link></li>
                        </ul>
                    </nav>
                </div>
                <div className="border-t border-slate-700 py-4 text-center text-xs text-slate-300">
                    &copy; {new Date().getFullYear()} {APP_NAME}. Dibangun dengan Laravel, Inertia.js &amp; React.
                </div>
            </footer>
        </div>
    );
}

/* ------------------------------------------------------------------ */
/* Layout dashboard (Penulis & Admin) dengan sidebar                   */
/* ------------------------------------------------------------------ */
function DashboardShell({ children, flash, user, url, isProcessing }) {
    const [menuOpen, setMenuOpen] = useState(false);
    const path = url.split('?')[0];

    useEffect(() => setMenuOpen(false), [url]);

    const item = (href, label, Icon, active, accent = 'indigo') => (
        <Link
            href={href}
            aria-current={active ? 'page' : undefined}
            className={`flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold ${
                active ? ACCENTS[accent] : 'text-slate-700 hover:bg-slate-100'
            }`}
        >
            <Icon className="w-5 h-5" />
            <span>{label}</span>
        </Link>
    );

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col md:flex-row">
            <a href="#konten" className="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[80] focus:bg-white focus:text-indigo-800 focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg">
                Lewati ke konten utama
            </a>

            <header className="md:hidden bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between sticky top-0 z-30">
                <Brand href={user?.role === 'admin' ? '/admin/users' : '/dashboard/posts'} />
                <button
                    type="button"
                    onClick={() => setMenuOpen((v) => !v)}
                    aria-label={menuOpen ? 'Tutup navigasi' : 'Buka navigasi'}
                    aria-expanded={menuOpen}
                    aria-controls="sidebar-nav"
                    className="p-2 text-slate-700 rounded-lg hover:bg-slate-100"
                >
                    {menuOpen ? <CloseIcon className="w-6 h-6" /> : <MenuIcon className="w-6 h-6" />}
                </button>
            </header>

            <aside
                id="sidebar-nav"
                className={`fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 md:sticky md:top-0 md:h-screen md:translate-x-0 ${
                    menuOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="p-5 border-b border-slate-100 hidden md:block">
                    <Brand href={user?.role === 'admin' ? '/admin/users' : '/dashboard/posts'} />
                </div>

                <div className="p-4 m-4 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-3 mt-16 md:mt-4">
                    <Avatar user={user} size="w-10 h-10" />
                    <div className="min-w-0 flex-1">
                        <p className="text-sm font-bold text-slate-900 truncate">{user.name}</p>
                        <p className="text-xs text-slate-600 truncate">@{user.username}</p>
                        <span className={`inline-block text-[11px] font-bold px-2 py-0.5 mt-1 rounded-full ${user.role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-slate-200 text-slate-800'}`}>
                            {user.role?.toUpperCase()}
                        </span>
                    </div>
                </div>

                <nav aria-label="Navigasi dashboard" className="flex-1 px-4 space-y-1.5 overflow-y-auto">
                    {user.role !== 'admin' && item('/dashboard/posts', 'Artikel Saya', DocumentIcon, path.startsWith('/dashboard/posts'))}
                    {item('/', 'Lihat Blog Publik', HomeIcon, false)}
                    {item('/profile', 'Profil Saya', UserIcon, path.startsWith('/profile'))}

                    {user.role === 'admin' && (
                        <div className="pt-4 mt-4 border-t border-slate-200 space-y-1.5">
                            <p className="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-2">Admin Area</p>
                            {item('/admin/users', 'Manajemen User', UsersIcon, path.startsWith('/admin/users'), 'purple')}
                            {item('/admin/posts', 'Semua Artikel', DocumentIcon, path.startsWith('/admin/posts'), 'purple')}
                        </div>
                    )}
                </nav>

                <div className="p-4 border-t border-slate-100">
                    <button type="button" onClick={logout} className="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-rose-700 hover:bg-rose-50">
                        <LogoutIcon className="w-5 h-5" />
                        <span>Keluar</span>
                    </button>
                </div>
            </aside>

            {menuOpen && <div onClick={() => setMenuOpen(false)} className="fixed inset-0 bg-slate-900/40 z-30 md:hidden" aria-hidden="true" />}

            <div className="flex-1 flex flex-col min-w-0">
                <Feedback isProcessing={isProcessing} flash={flash} />
                <main id="konten" className="p-4 sm:p-6 lg:p-8 flex-1">
                    {children}
                </main>
            </div>
        </div>
    );
}

/**
 * Layout utama aplikasi.
 *  - variant="public"  : Sticky Top Navbar + footer editorial (tanpa sidebar)
 *  - variant="app"     : sidebar untuk Dashboard Penulis & Panel Admin
 */
export default function AppLayout({ children, variant = 'app' }) {
    const { auth, flash } = usePage().props;
    const { url } = usePage();
    const user = auth?.user;
    const isProcessing = useProcessing();

    if (variant === 'public' || !user) {
        return (
            <PublicShell flash={flash} user={user} url={url} isProcessing={isProcessing}>
                {children}
            </PublicShell>
        );
    }

    return (
        <DashboardShell flash={flash} user={user} url={url} isProcessing={isProcessing}>
            {children}
        </DashboardShell>
    );
}
