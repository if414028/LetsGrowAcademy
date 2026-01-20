<x-guest-layout>
    <div class="w-full max-w-md">
        <div class="bg-white/90 backdrop-blur rounded-2xl shadow-xl ring-1 ring-black/5 px-8 py-8">
            <div class="flex flex-col items-center text-center">
                <x-application-logo class="h-32 md:h-40 mx-auto mb-6" />

                <h1 class="mt-5 text-3xl font-bold tracking-tight text-gray-900">Let's Grow Academy</h1>
                <p class="mt-1 text-sm text-gray-500">Sign in to your dashboard</p>
            </div>

            {{-- session status / errors --}}
            <x-auth-session-status class="mt-6" :status="session('status')" />
            <x-input-error :messages="$errors->get('email')" class="mt-4" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input
                        id="email"
                        class="mt-1 block w-full rounded-xl"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="your@email.com"
                    />
                </div>

                <div>
                    <x-input-label for="password" value="Password" />

                    <div class="relative mt-1">
                        <x-text-input
                            id="password"
                            class="block w-full pr-10"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                        />

                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700"
                        >
                            {{-- eye icon --}}
                            <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                                        -1.274 4.057-5.064 7-9.542 7
                                        -4.477 0-8.268-2.943-9.542-7z" />
                            </svg>

                            {{-- eye off icon --}}
                            <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 hidden" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19
                                        c-4.478 0-8.268-2.943-9.543-7
                                        a9.956 9.956 0 012.658-4.568M6.223 6.223
                                        A9.953 9.953 0 0112 5
                                        c4.478 0 8.268 2.943 9.543 7
                                        a9.97 9.97 0 01-4.043 5.197M15 12
                                        a3 3 0 00-3-3M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                        <span class="ms-2 text-sm text-gray-600">Remember me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-sm text-blue-600 hover:text-blue-700 hover:underline" href="{{ route('password.request') }}">
                            Forgot password?
                        </a>
                    @endif
                </div>

                <button
                    type="submit"
                    class="w-full inline-flex justify-center items-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Sign In
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeOpen.classList.add('hidden');
            eyeClosed.classList.remove('hidden');
        } else {
            passwordInput.type = 'password';
            eyeOpen.classList.remove('hidden');
            eyeClosed.classList.add('hidden');
        }
    }
</script>

