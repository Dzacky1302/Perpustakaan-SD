import { Link } from '@inertiajs/react';
import { cn } from './ui';

export default function Pagination({ meta, links = [], className = '' }) {
    if (!meta || meta.total === 0) return null;

    const visible = links.filter((link) => !/&laquo;|&raquo;|Previous|Next/.test(link.label));

    return (
        <div className={cn('flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3', className)}>
            <p className="text-[11px] text-slate-500">
                Menampilkan <strong className="text-slate-700">{meta.from ?? 0}</strong>–
                <strong className="text-slate-700">{meta.to ?? 0}</strong> dari{' '}
                <strong className="text-slate-700">{meta.total}</strong> data
            </p>

            <nav className="flex flex-wrap items-center gap-1">
                {visible.map((link, index) => (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url ?? '#'}
                        preserveScroll
                        className={cn(
                            'rounded-lg px-3 py-1.5 text-[11px] font-semibold transition',
                            link.active
                                ? 'bg-emerald-600 text-white'
                                : link.url
                                  ? 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                  : 'cursor-not-allowed border border-slate-100 bg-slate-50 text-slate-300',
                        )}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ))}
            </nav>
        </div>
    );
}
