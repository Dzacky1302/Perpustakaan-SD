<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'role' => $request->user()?->role ?? 'admin',
                'can_manage' => $request->user()?->isAdmin() ?? false,
                'role_label' => $request->user()?->roleLabel() ?? 'Pustakawan',
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'school' => [
                'name' => config('perpustakaan.school.name'),
                'office' => config('perpustakaan.school.office'),
                'npsn' => config('perpustakaan.school.npsn'),
                'address' => config('perpustakaan.school.address'),
                'library' => config('perpustakaan.library_name'),
                'academic_year' => config('perpustakaan.current_academic_year'),
                'headmaster' => config('perpustakaan.headmaster.name'),
                'librarian' => config('perpustakaan.librarian.name'),
                'loan_days' => config('perpustakaan.loan_days'),
            ],
        ];
    }
}
