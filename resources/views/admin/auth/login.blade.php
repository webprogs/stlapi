<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - STL Lottery Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#0D5C3D] to-gray-900 px-4">
    <div class="w-full max-w-md">
        <div class="bg-gray-800 rounded-xl shadow-xl p-8 border border-gray-700">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#D4AF37]">STL Lottery</h1>
                <p class="text-gray-400 mt-2">Admin Dashboard</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-500/20 border border-red-500 rounded-lg text-red-400 text-sm">
                    @foreach ($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent"
                           placeholder="admin@lottery.local"
                           required
                           autofocus>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent"
                           placeholder="Enter your password"
                           required>
                </div>

                <div class="flex items-center">
                    <input type="checkbox"
                           id="remember"
                           name="remember"
                           class="h-4 w-4 text-[#D4AF37] focus:ring-[#D4AF37] border-gray-600 rounded bg-gray-700">
                    <label for="remember" class="ml-2 block text-sm text-gray-300">Remember me</label>
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 bg-[#D4AF37] hover:bg-[#F6D365] text-gray-900 font-semibold rounded-lg transition-colors">
                    Sign In
                </button>
            </form>

            <p class="mt-6 text-center text-xs text-gray-500">
                Default: admin@lottery.local / admin123
            </p>
        </div>
    </div>
</body>
</html>
