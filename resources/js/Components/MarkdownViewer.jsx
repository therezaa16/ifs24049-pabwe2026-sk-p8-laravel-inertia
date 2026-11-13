import React, { useEffect, useRef } from 'react';

/**
 * TOAST UI Viewer (hanya render Markdown, tanpa editor) dimuat secara dinamis.
 */
export default function MarkdownViewer({ content = '' }) {
    const elRef = useRef(null);
    const instanceRef = useRef(null);

    useEffect(() => {
        let cancelled = false;

        (async () => {
            const [{ default: Viewer }] = await Promise.all([
                import('@toast-ui/editor/viewer'),
                import('@toast-ui/editor/toastui-editor-viewer.css'),
            ]);
            if (cancelled || !elRef.current) return;

            instanceRef.current = new Viewer({
                el: elRef.current,
                initialValue: content || '',
                usageStatistics: false,
            });
        })();

        return () => {
            cancelled = true;
            instanceRef.current?.destroy();
            instanceRef.current = null;
        };
    }, []);

    useEffect(() => {
        instanceRef.current?.setMarkdown(content || '');
    }, [content]);

    return (
        <div className="article-body">
            <div ref={elRef} />
        </div>
    );
}
