<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reseller Dashboard</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- KYC Banner --}}
            @if(auth()->user()->kyc_status !== 'approved')
                <div class="rounded-md bg-yellow-50 p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-800">
                                @if(auth()->user()->kyc_status === 'pending')
                                    Your KYC verification is under review.
                                @elseif(auth()->user()->kyc_status === 'rejected')
                                    Your KYC was rejected. <a href="{{ route('kyc.show') }}" class="underline">Please resubmit</a>.
                                @else
                                    Please <a href="{{ route('kyc.show') }}" class="underline">complete your KYC verification</a> to access all features.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Welcome Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium">Welcome, {{ auth()->user()->name }}</h3>
                    <p class="mt-1 text-sm text-gray-600">Role: Reseller | Billing: {{ ucfirst(auth()->user()->billing_type) }}</p>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Balance</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">${{ number_format(auth()->user()->balance, 2) }}</dd>
                </div>
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Max Channels</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ auth()->user()->max_channels }}</dd>
                </div>
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">KYC Status</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight {{ auth()->user()->kyc_status === 'approved' ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ ucfirst(auth()->user()->kyc_status) }}
                    </dd>
                </div>
            </div>

            {{-- Placeholder sections --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500">Client management, SIP accounts, rates, and billing features will be available in upcoming phases.</p>
            </div>
        </div>
    </div>
</x-app-layout>
