<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class LoginForm extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    // ڕێساکانی دروستی (Validation Rules)
    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    // (ئارەزوومەندانە) بۆ ڕێگریکردن لە هێرشی Brute Force
    protected function ensureIsNotRateLimited()
    {
        $throttleKey = Str::lower($this->email) . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw new \Exception(__('auth.throttle', [
                'seconds' => RateLimiter::availableIn($throttleKey),
            ]));
        }
    }

    public function login()
    {
        // 1. دروستیی زانیارییەکان بپشکنە
        $this->validate();

        // 2. (ئارەزوومەندانە) کۆنترۆڵی هێرشی Brute Force [citation:10]
        try {
            $this->ensureIsNotRateLimited();
        } catch (\Exception $e) {
            $this->addError('email', $e->getMessage());
            return;
        }

        // 3. هەوڵی چوونەژوورەوە بدە
        if (Auth::attempt([
            'email' => $this->email,
            'password' => $this->password
        ], $this->remember)) {

            // سەرکەوتوو بوو: پاککردنەوەی تۆمارەکانی ڕێگریکردن
            RateLimiter::clear(Str::lower($this->email) . '|' . request()->ip());

            // ڕێخستنی سێشن و ڕەوانەکردن بۆ داشبۆرد
            session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // 4. سەرکەوتوو نەبوو: تۆمارکردنی هەوڵێکی شکست خواردوو
        RateLimiter::hit(Str::lower($this->email) . '|' . request()->ip(), 60);

        // نیشاندانی هەڵە
        $this->addError('email', __('auth.failed'));
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
