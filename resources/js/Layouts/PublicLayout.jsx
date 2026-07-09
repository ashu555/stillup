import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function PublicLayout({ children }) {
    return (
        <div className="min-h-screen bg-slate-50 text-slate-900">
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
                    <Link href="/" className="flex items-center gap-2">
                        <ApplicationLogo className="h-8 w-auto fill-current text-slate-800" />
                        <span className="text-sm font-semibold tracking-wide text-slate-700">
                            Stillup Status
                        </span>
                    </Link>
                </div>
            </header>
            <main>{children}</main>
        </div>
    );
}
