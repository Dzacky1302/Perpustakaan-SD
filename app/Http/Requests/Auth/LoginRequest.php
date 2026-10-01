<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), $this->decaySeconds());
            RateLimiter::hit($this->ipThrottleKey(), $this->decaySeconds());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->ipThrottleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * Dua batas diperiksa: per email+IP (seseorang yang membidik satu akun)
     * dan per IP saja (seseorang yang mengganti email tiap percobaan).
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        foreach ([$this->throttleKey() => $this->maxAttempts(), $this->ipThrottleKey() => $this->maxAttemptsPerIp()] as $key => $limit) {
            if (! RateLimiter::tooManyAttempts($key, $limit)) {
                continue;
            }

            event(new Lockout($this));

            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return 'login:'.Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /**
     * Kunci khusus per IP, dipisah dari throttleKey() di atas.
     *
     * Tanpa kunci ini, penyerang cukup mengganti email pada setiap
     * percobaan untuk selalu mendapat hitungan baru, sehingga batas
     * per-akun tidak pernah tercapai.
     */
    public function ipThrottleKey(): string
    {
        return 'login-ip:'.$this->ip();
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('perpustakaan.login.max_attempts', 5));
    }

    public function maxAttemptsPerIp(): int
    {
        // Sengaja tidak memakai max() terhadap maxAttempts(): batas per IP
        // adalah pengaturan mandiri, dan bisa saja sengaja dibuat lebih kecil
        // atau lebih besar dari batas per akun.
        return max(1, (int) config('perpustakaan.login.max_attempts_per_ip', 20));
    }

    public function decaySeconds(): int
    {
        return max(1, (int) config('perpustakaan.login.decay_seconds', 60));
    }
}
