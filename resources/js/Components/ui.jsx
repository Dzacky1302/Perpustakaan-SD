export function cn(...classes) {
    return classes.filter(Boolean).join(' ');
}

const TONES = {
    emerald: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    amber: 'bg-amber-50 text-amber-800 border-amber-200',
    rose: 'bg-rose-50 text-rose-700 border-rose-200',
    sky: 'bg-sky-50 text-sky-700 border-sky-200',
    violet: 'bg-violet-50 text-violet-700 border-violet-200',
    slate: 'bg-slate-100 text-slate-700 border-slate-200',
    teal: 'bg-teal-50 text-teal-700 border-teal-200',
};

export function Badge({ tone = 'slate', children, className = '' }) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-semibold',
                TONES[tone] ?? TONES.slate,
                className,
            )}
        >
            {children}
        </span>
    );
}

export function Card({ title, subtitle, action, children, className = '', bodyClass = 'p-5' }) {
    return (
        <section
            className={cn(
                'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm',
                className,
            )}
        >
            {(title || action) && (
                <header className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                    <div>
                        {title && <h2 className="text-sm font-bold text-slate-800">{title}</h2>}
                        {subtitle && <p className="text-xs text-slate-500">{subtitle}</p>}
                    </div>
                    {action}
                </header>
            )}
            <div className={bodyClass}>{children}</div>
        </section>
    );
}

export function StatCard({ label, value, hint, icon: Icon, tone = 'emerald' }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-[11px] font-bold uppercase tracking-wide text-slate-500">{label}</p>
                    <p className="mt-1.5 text-2xl font-extrabold leading-none text-slate-900">{value}</p>
                    {hint && <p className="mt-1.5 text-[11px] text-slate-500">{hint}</p>}
                </div>
                {Icon && (
                    <span
                        className={cn(
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border',
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
    primary: 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500',
    secondary: 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 focus:ring-slate-400',
    danger: 'bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-500',
    warn: 'bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-400',
    ghost: 'bg-transparent text-slate-600 hover:bg-slate-100 focus:ring-slate-300',
};

export function Button({ variant = 'primary', className = '', children, ...props }) {
    return (
        <button
            {...props}
            className={cn(
                'inline-flex items-center justify-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-bold transition focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50',
                BUTTON_VARIANTS[variant] ?? BUTTON_VARIANTS.primary,
                className,
            )}
        >
            {children}
        </button>
    );
}

export function Field({ label, hint, error, children, className = '' }) {
    return (
        <label className={cn('block', className)}>
            {label && (
                <span className="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">
                    {label}
                </span>
            )}
            {children}
            {hint && !error && <span className="mt-1 block text-[11px] text-slate-400">{hint}</span>}
            {error && <span className="mt-1 block text-[11px] font-semibold text-rose-600">{error}</span>}
        </label>
    );
}

export function Input({ className = '', ...props }) {
    return (
        <input
            {...props}
            className={cn(
                'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500',
                className,
            )}
        />
    );
}

export function Select({ className = '', children, ...props }) {
    return (
        <select
            {...props}
            className={cn(
                'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500',
                className,
            )}
        >
            {children}
        </select>
    );
}

export function EmptyState({ title, description, icon: Icon, action }) {
    return (
        <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50/60 px-6 py-10 text-center">
            {Icon && (
                <span className="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                    <Icon className="h-5 w-5" />
                </span>
            )}
            <p className="text-sm font-bold text-slate-700">{title}</p>
            {description && <p className="mt-1 max-w-md text-xs text-slate-500">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}

