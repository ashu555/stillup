import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';

export default function FlashMessage() {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(false);
    const [message, setMessage] = useState(null);
    const [type, setType] = useState('success');

    useEffect(() => {
        if (flash?.success) {
            setMessage(flash.success);
            setType('success');
            setVisible(true);
        } else if (flash?.error) {
            setMessage(flash.error);
            setType('error');
            setVisible(true);
        } else {
            return;
        }

        const timer = setTimeout(() => setVisible(false), 4000);
        return () => clearTimeout(timer);
    }, [flash?.success, flash?.error]);

    if (!visible || !message) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed inset-x-0 top-4 z-50 flex justify-center px-4">
            <div
                className={`pointer-events-auto rounded-md px-4 py-3 text-sm shadow-lg ${
                    type === 'success'
                        ? 'bg-green-700 text-white'
                        : 'bg-red-700 text-white'
                }`}
            >
                {message}
            </div>
        </div>
    );
}
