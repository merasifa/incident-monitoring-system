<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-b from-indigo-50 to-white py-12 px-4">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-500 text-white shadow-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13h4v8H3zM10 3h4v18h-4zM17 9h4v12h-4z" />
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="text-xl font-bold text-indigo-700">OIMS</p>
                        <p class="text-xs text-slate-500">Operational Monitor</p>
                    </div>
                </a>
            </div>

            <div class="rounded-2xl bg-white p-8 shadow-lg">
                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <h2 class="text-2xl font-semibold text-slate-800 mb-4">Sign in to your account</h2>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-4">
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" class="mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" class="mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-between mb-6">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                            <span class="ms-2 text-sm text-slate-700">{{ __('Remember me') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="text-sm text-indigo-600 hover:underline" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                        @endif
                    </div>

                    <div>
                        <x-primary-button class="w-full py-3">
                            {{ __('Log in') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

            <p class="mt-4 text-center text-sm text-slate-500">Don't have an account? <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">Create one</a></p>
        </div>
    </div>
</x-guest-layout>
