@extends('templates/auth.base')

@section('title', 'Account Activation Pending')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-gray-900">
        <div class="max-w-md w-full bg-gray-800 rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <svg class="w-16 h-16 mx-auto text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-white mb-4">
                Account Activation Pending
            </h1>

            <p class="text-gray-300 mb-6">
                Your account is currently being reviewed and will be activated shortly.
            </p>

            <div class="bg-gray-700 rounded-lg p-6 mb-6">
                <p class="text-sm text-gray-400 mb-2">Time remaining until activation:</p>
                <div class="text-3xl font-mono font-bold text-yellow-400" id="countdown">
                    {{ $remainingMinutes }}m {{ $remainingSeconds }}s
                </div>
            </div>

            <p class="text-sm text-gray-400">
                This page will automatically refresh once your account is activated.
            </p>

            <div class="mt-6">
                <form action="{{ route('auth.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-gray-500 hover:text-gray-300 text-sm underline">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh countdown
        let remainingSeconds = {{ $remainingMinutes * 60 + $remainingSeconds }};
        
        function updateCountdown() {
            if (remainingSeconds <= 0) {
                window.location.reload();
                return;
            }
            
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            
            document.getElementById('countdown').textContent = minutes + 'm ' + seconds + 's';
            remainingSeconds--;
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        
        // Auto-refresh the page every 30 seconds to check if delay expired
        setInterval(function() {
            window.location.reload();
        }, 30000);
    </script>
@endsection
