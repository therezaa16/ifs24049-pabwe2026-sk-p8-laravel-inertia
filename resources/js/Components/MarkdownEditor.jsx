import React, { useEffect, useRef } from 'react';

/**
 * TOAST UI Editor (mode Markdown). Library dimuat secara dinamis supaya tidak
 * membebani bundle halaman publik.
 */
export default function MarkdownEditor({
    value = '',
    onChange,
    height = '420px',
    placeholder = 'Tulis artikel Anda dalam format Markdown...',
}) {
    const elRef = useRef(null);
    const instanceRef = useRef(null);
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;

    useEffect(() => {
        let cancelled = false;

        (async () => {
            const [{ default: Editor }] = await Promise.all([
                import('@toast-ui/editor'),
                import('@toast-ui/editor/toastui-editor.css'),
            ]);
            if (cancelled || !elRef.current) return;

            instanceRef.current = new Editor({
                el: elRef.current,
                height,
                initialEditType: 'markdown',
                previewStyle: 'tab',
                initialValue: value || '',
                placeholder,
                usageStatistics: false,
                events: {
                    change: () => onChangeRef.current?.(instanceRef.current.getMarkdown()),
                },
            });
        })();

        return () => {
            cancelled = true;
            instanceRef.current?.destroy();
            instanceRef.current = null;
        };
    }, []);

    useEffect(() => {
        const editor = instanceRef.current;
        if (editor && value !== undefined && value !== editor.getMarkdown()) {
            editor.setMarkdown(value || '', false);
        }
    }, [value]);

    return (
        <div className="border border-slate-300 rounded-xl overflow-hidden bg-white">
            <div ref={elRef} style={{ minHeight: height }} />
        </div>
    );
}
