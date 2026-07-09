import { useState } from 'react';

export default function CopyButton({ value, label = 'Copy' }) {
    const [copied, setCopied] = useState(false);

    const copyWithFallback = (text) => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(textarea);
        return ok;
    };

    const copy = async () => {
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(value);
            } else if (!copyWithFallback(value)) {
                throw new Error('Copy failed');
            }
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        } catch {
            try {
                if (copyWithFallback(value)) {
                    setCopied(true);
                    setTimeout(() => setCopied(false), 1500);
                    return;
                }
            } catch {
                // ignore
            }
            setCopied(false);
        }
    };

    return (
        <button
            type="button"
            onClick={copy}
            className="rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50"
        >
            {copied ? 'Copied' : label}
        </button>
    );
}
