export function cn(...classes) {
    return classes.filter(Boolean).join(' ');
}

// Palet dikunci: emerald = aksi utama, amber/rose = status,
// sky/violet/teal = aksen data (dipakai Dashboard & badge lama).
const TONES = {
    emerald: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    amber: 'bg-amber-50 text-amber-800 border-amber-200',
    rose: 'bg-rose-50 text-rose-700 border-rose-200',
    sky: 'bg-sky-50 text-sky-700 border-sky-200',
    violet: 'bg-violet-50 text-violet-700 border-violet-200',
    slate: 'bg-slate-100 text-slate-700 border-slate-200',
    teal: 'bg-teal-50 text-teal-700 border-teal-200',
};

const DOT_TONES = {
    emerald: 'bg-emerald-500',
    amber: 'bg-amber-500',
    rose: 'bg-rose-500',
    sky: 'bg-sky-500',
    violet: 'bg-violet-500',
    slate: 'bg-slate-400',
    teal: 'bg-teal-500',
};

export function Badge({ tone = 'slate', dot = false, children, className = '' }) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold leading-5',
                TONES[tone] ?? TONES.slate,
                className,
            )}
        >
            {dot && (
                <span className={cn('h-1.5 w-1.5 rounded-full', DOT_TONES[tone] ?? DOT_TONES.slate)} aria-hidden="true" />
            )}
            {children}
        </span>
    );
}

export function Card({ title, subtitle, action, children, className = '', bodyClass = 'p-5', hover = false }) {
    return (
        <section
            className={cn(
                'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card transition-shadow duration-200',
                hover && 'hover:shadow-pop',
                className,
            )}
        >
            {(title || subtitle || action) && (
                <header className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                    <div className="min-w-0">
                        {title && <h2 className="truncate text-sm font-bold tracking-tight text-slate-800">{title}</h2>}
                        {subtitle && <p className="mt-0.5 text-xs text-slate-500">{subtitle}</p>}
                    </div>
                    {action && <div className="flex flex-wrap items-center gap-2">{action}</div>}
                </header>
            )}
            <div className={bodyClass}>{children}</div>
        </section>
    );
}

export function StatCard({ label, value, hint, trend, icon: Icon, tone = 'emerald' }) {
    const trendUp = typeof trend === 'string' ? trend.trim().startsWith('+') || trend.trim().startsWith('↑') : trend > 0;
    const trendDown =
        typeof trend === 'string' ? trend.trim().startsWith('-') || trend.trim().startsWith('↓') : trend < 0;

    return (
        <div className="group rounded-2xl border border-slate-200 bg-white p-4 shadow-card transition-all duration-200 hover:-translate-y-0.5 hover:shadow-pop">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate text-[11px] font-bold uppercase tracking-wider text-slate-500">{label}</p>
                    <p className="mt-1.5 text-2xl font-extrabold leading-none tracking-tight text-slate-900">{value}</p>
                    <div className="mt-1.5 flex flex-wrap items-center gap-2">
                        {hint && <p className="text-[11px] text-slate-500">{hint}</p>}
                        {trend !== undefined && trend !== null && trend !== '' && (
                            <span
                                className={cn(
                                    'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-bold tabular-nums',
                                    trendUp && 'bg-emerald-50 text-emerald-700',
                                    trendDown && 'bg-rose-50 text-rose-700',
                                    !trendUp && !trendDown && 'bg-slate-100 text-slate-600',
                                )}
                            >
                                {trend}
                            </span>
                        )}
                    </div>
                </div>
                {Icon && (
                    <span
                        className={cn(
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border transition-transform duration-200 group-hover:scale-105',
                            TONES[tone] ?? TONES.emerald,
                        )}
                    >
                        <Icon className="h-5 w-5" />
                    </span>
                )}
            </div>
        </div>
    );
}

export function PageHeader({ title, subtitle, children }) {
    return (
        <div className="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 className="text-xl font-extrabold tracking-tight text-slate-900">{title}</h1>
                {subtitle && <p className="mt-1 text-sm text-slate-500">{subtitle}</p>}
            </div>
            {children && <div className="flex flex-wrap items-center gap-2">{children}</div>}
        </div>
    );
}

const BUTTON_VARIANTS = {
    primary:
        'bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 hover:shadow focus-visible:ring-emerald-500 active:bg-emerald-800',
    secondary:
        'bg-white text-slate-700 border border-slate-300 shadow-sm hover:bg-slate-50 hover:border-slate-400 focus-visible:ring-slate-400 active:bg-slate-100',
    danger: 'bg-rose-600 text-white shadow-sm hover:bg-rose-700 focus-visible:ring-rose-500 active:bg-rose-800',
    warn: 'bg-amber-500 text-white shadow-sm hover:bg-amber-600 focus-visible:ring-amber-400 active:bg-amber-700',
    ghost: 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:ring-slate-300',
};

const BUTTON_SIZES = {
    xs: 'px-2.5 py-1.5 text-[11px]',
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-3.5 py-2 text-xs',
    lg: 'px-4 py-2.5 text-sm',
};

export function Button({ variant = 'primary', size = 'md', loading = false, className = '', children, ...props }) {
    return (
        <button
            {...props}
            disabled={loading || props.disabled}
            className={cn(
                'inline-flex items-center justify-center gap-1.5 rounded-lg font-bold transition-all duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50',
                BUTTON_VARIANTS[variant] ?? BUTTON_VARIANTS.primary,
                BUTTON_SIZES[size] ?? BUTTON_SIZES.md,
                className,
            )}
        >
            {loading && (
                <svg className="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path
                        className="opacity-90"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                </svg>
            )}
            {children}
        </button>
    );
}

export function Field({ label, hint, error, required = false, children, className = '' }) {
    return (
        <label className={cn('block', className)}>
            {label && (
                <span className="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">
                    {label}
                    {required && (
                        <span className="ml-0.5 text-rose-500" aria-hidden="true">
                            *
                        </span>
                    )}
                </span>
            )}
            {children}
            {hint && !error && <span className="mt-1 block text-[11px] text-slate-400">{hint}</span>}
            {error && (
                <span className="mt-1 flex items-center gap-1 text-[11px] font-semibold text-rose-600" role="alert">
                    <svg className="h-3 w-3 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path
                            fillRule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clipRule="evenodd"
                        />
                    </svg>
                    {error}
                </span>
            )}
        </label>
    );
}

const INPUT_BASE =
    'block w-full rounded-lg border text-sm shadow-sm transition-colors duration-150 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400';

export function Input({ invalid = false, className = '', ...props }) {
    return (
        <input
            {...props}
            aria-invalid={invalid || undefined}
            className={cn(INPUT_BASE, invalid ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/30' : 'border-slate-300', className)}
        />
    );
}

export function Select({ invalid = false, className = '', children, ...props }) {
    return (
        <select
            {...props}
            aria-invalid={invalid || undefined}
            className={cn(INPUT_BASE, invalid ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/30' : 'border-slate-300', className)}
        >
            {children}
        </select>
    );
}

export function EmptyState({ title, description, icon: Icon, action, compact = false }) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50/60 text-center',
                compact ? 'px-4 py-6' : 'px-6 py-10',
            )}
        >
            {Icon && (
                <span className="mb-3 flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 shadow-sm">
                    <Icon className="h-5 w-5" />
                </span>
            )}
            <p className="text-sm font-bold text-slate-700">{title}</p>
            {description && <p className="mt-1 max-w-md text-xs leading-relaxed text-slate-500">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}

export function Skeleton({ className = '' }) {
    return <div aria-hidden="true" className={cn('animate-pulse rounded-lg bg-slate-200/70', className)} />;
}

export function StatSkeleton() {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <Skeleton className="h-3 w-24" />
            <Skeleton className="mt-2 h-7 w-20" />
            <Skeleton className="mt-2 h-3 w-32" />
        </div>
    );
}

export function TableSkeleton({ rows = 5, cols = 4 }) {
    return (
        <div className="space-y-2 p-5" aria-hidden="true">
            {Array.from({ length: rows }).map((_, i) => (
                <div key={i} className="flex gap-2">
                    {Array.from({ length: cols }).map((_, j) => (
                        <Skeleton key={j} className="h-8 flex-1" />
                    ))}
                </div>
            ))}
        </div>
    );
}

/* Wrapper tabel responsif: scroll horizontal di HP + header sticky. */
export function TableWrapper({ children, className = '' }) {
    return <div className={cn('table-sticky-head max-h-[70vh] overflow-auto rounded-b-2xl', className)}>{children}</div>;
}

