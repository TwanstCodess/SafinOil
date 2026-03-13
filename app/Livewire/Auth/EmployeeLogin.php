<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class EmployeeLogin extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

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
        $this->validate();

        try {
            $this->ensureIsNotRateLimited();
        } catch (\Exception $e) {
            $this->addError('email', $e->getMessage());
            return;
        }

        if (Auth::guard('employee')->attempt([
            'email' => $this->email,
            'password' => $this->password
        ], $this->remember)) {

            RateLimiter::clear(Str::lower($this->email) . '|' . request()->ip());
            session()->regenerate();

            return redirect()->intended(route('employee.dashboard'));
        }

        RateLimiter::hit(Str::lower($this->email) . '|' . request()->ip(), 60);
        $this->addError('email', __('auth.failed'));
    }

    public function render()
    {
        return view('livewire.auth.employee-login')->layout('layouts.employee-guest');
    }
}
