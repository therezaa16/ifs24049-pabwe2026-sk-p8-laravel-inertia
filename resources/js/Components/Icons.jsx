import React from 'react';

const base = { fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round', 'aria-hidden': 'true', focusable: 'false' };

function Svg({ className = 'w-5 h-5', children }) {
    return (
        <svg className={`${className} shrink-0`} viewBox="0 0 24 24" {...base}>
            {children}
        </svg>
    );
}

export const EyeIcon = (p) => (
    <Svg {...p}>
        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z" />
        <circle cx="12" cy="12" r="3" />
    </Svg>
);
export const PencilIcon = (p) => (
    <Svg {...p}>
        <path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z" />
    </Svg>
);
export const TrashIcon = (p) => (
    <Svg {...p}>
        <path d="M3 6h18M8 6V4h8v2m-9 0l1 14h8l1-14M10 11v5M14 11v5" />
    </Svg>
);
export const DotsIcon = (p) => (
    <Svg {...p}>
        <circle cx="12" cy="5" r="1.2" fill="currentColor" />
        <circle cx="12" cy="12" r="1.2" fill="currentColor" />
        <circle cx="12" cy="19" r="1.2" fill="currentColor" />
    </Svg>
);
export const SearchIcon = (p) => (
    <Svg {...p}>
        <circle cx="11" cy="11" r="7" />
        <path d="M21 21l-4.3-4.3" />
    </Svg>
);
export const LoginIcon = (p) => (
    <Svg {...p}>
        <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3" />
    </Svg>
);
export const UserPlusIcon = (p) => (
    <Svg {...p}>
        <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM19 8v6M22 11h-6" />
    </Svg>
);
export const LogoutIcon = (p) => (
    <Svg {...p}>
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
    </Svg>
);
export const PlusIcon = (p) => (
    <Svg {...p}>
        <path d="M12 5v14M5 12h14" />
    </Svg>
);
export const SparklesIcon = (p) => (
    <Svg {...p}>
        <path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3zM19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z" />
    </Svg>
);
export const DownloadIcon = (p) => (
    <Svg {...p}>
        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" />
    </Svg>
);
export const UploadIcon = (p) => (
    <Svg {...p}>
        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" />
    </Svg>
);
export const DocumentIcon = (p) => (
    <Svg {...p}>
        <path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-5-5zM14 3v5h5M9 13h6M9 17h6" />
    </Svg>
);
export const CheckCircleIcon = (p) => (
    <Svg {...p}>
        <circle cx="12" cy="12" r="9" />
        <path d="M8 12l3 3 5-6" />
    </Svg>
);
export const PencilSquareIcon = (p) => (
    <Svg {...p}>
        <path d="M11 4H5a2 2 0 00-2 2v13a2 2 0 002 2h13a2 2 0 002-2v-6M18.5 2.5a2.1 2.1 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
    </Svg>
);
export const MenuIcon = (p) => (
    <Svg {...p}>
        <path d="M4 6h16M4 12h16M4 18h16" />
    </Svg>
);
export const CloseIcon = (p) => (
    <Svg {...p}>
        <path d="M6 6l12 12M18 6L6 18" />
    </Svg>
);
export const ArrowLeftIcon = (p) => (
    <Svg {...p}>
        <path d="M19 12H5M12 19l-7-7 7-7" />
    </Svg>
);
export const ClockIcon = (p) => (
    <Svg {...p}>
        <circle cx="12" cy="12" r="9" />
        <path d="M12 7v5l3 2" />
    </Svg>
);
export const HomeIcon = (p) => (
    <Svg {...p}>
        <path d="M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10" />
    </Svg>
);
export const UsersIcon = (p) => (
    <Svg {...p}>
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8" />
    </Svg>
);
export const UserIcon = (p) => (
    <Svg {...p}>
        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" />
    </Svg>
);
export const ImageIcon = (p) => (
    <Svg {...p}>
        <rect x="3" y="3" width="18" height="18" rx="2" />
        <circle cx="9" cy="9" r="2" />
        <path d="M21 15l-5-5L5 21" />
    </Svg>
);
export const Spinner = ({ className = 'w-4 h-4' }) => (
    <svg className={`animate-spin ${className} shrink-0`} fill="none" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
    </svg>
);
