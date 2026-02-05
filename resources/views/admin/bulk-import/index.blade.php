<x-admin-layout>
    <x-slot name="header">Bulk Import</x-slot>

    <div class="max-w-3xl space-y-6">

        @if (session('import_errors') && count(session('import_errors')) > 0)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-yellow-800 mb-2">Import Warnings ({{ count(session('import_errors')) }})</h4>
                <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1 max-h-48 overflow-y-auto">
                    @foreach (session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Users Import --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-2">Import Users</h3>
            <p class="text-sm text-gray-500 mb-4">
                Upload a CSV file with columns: <code class="text-xs bg-gray-100 px-1 rounded">name, email, password, role, billing_type, balance, rate_group_id, parent_id</code>
            </p>
            <form method="POST" action="{{ route('admin.bulk-import.users') }}" enctype="multipart/form-data" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label for="csv_users" class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
                    <input type="file" id="csv_users" name="csv_file" accept=".csv,.txt" required
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Import Users
                </button>
            </form>
            <div class="mt-3">
                <details class="text-xs text-gray-500">
                    <summary class="cursor-pointer hover:text-gray-700">CSV Example</summary>
                    <pre class="mt-2 bg-gray-50 p-3 rounded text-xs overflow-x-auto">name,email,password,role,billing_type,balance,rate_group_id,parent_id
John Doe,john@example.com,SecurePass123,client,prepaid,100.00,1,2
Jane Smith,jane@example.com,,reseller,postpaid,0,1,</pre>
                </details>
            </div>
        </div>

        {{-- SIP Accounts Import --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-2">Import SIP Accounts</h3>
            <p class="text-sm text-gray-500 mb-4">
                Upload a CSV file with columns: <code class="text-xs bg-gray-100 px-1 rounded">username, password, user_id, auth_type, allowed_ips, caller_id_name, caller_id_number, max_channels</code>
            </p>
            <form method="POST" action="{{ route('admin.bulk-import.sip-accounts') }}" enctype="multipart/form-data" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label for="csv_sip" class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
                    <input type="file" id="csv_sip" name="csv_file" accept=".csv,.txt" required
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Import SIP Accounts
                </button>
            </form>
            <div class="mt-3">
                <details class="text-xs text-gray-500">
                    <summary class="cursor-pointer hover:text-gray-700">CSV Example</summary>
                    <pre class="mt-2 bg-gray-50 p-3 rounded text-xs overflow-x-auto">username,password,user_id,auth_type,allowed_ips,caller_id_name,caller_id_number,max_channels
1001,,3,password,,John Doe,+18005551234,5
1002,MyPass!2024,3,ip,192.168.1.100,,,10</pre>
                </details>
            </div>
        </div>

        {{-- DIDs Import --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-2">Import DIDs</h3>
            <p class="text-sm text-gray-500 mb-4">
                Upload a CSV file with columns: <code class="text-xs bg-gray-100 px-1 rounded">number, provider, trunk_id, assigned_to_user_id, destination_type, destination_id, destination_number, monthly_cost, monthly_price</code>
            </p>
            <form method="POST" action="{{ route('admin.bulk-import.dids') }}" enctype="multipart/form-data" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label for="csv_dids" class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
                    <input type="file" id="csv_dids" name="csv_file" accept=".csv,.txt" required
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Import DIDs
                </button>
            </form>
            <div class="mt-3">
                <details class="text-xs text-gray-500">
                    <summary class="cursor-pointer hover:text-gray-700">CSV Example</summary>
                    <pre class="mt-2 bg-gray-50 p-3 rounded text-xs overflow-x-auto">number,provider,trunk_id,assigned_to_user_id,destination_type,destination_id,destination_number,monthly_cost,monthly_price
+18005551234,Telnyx,1,3,sip_account,1,,1.50,3.00
+18005551235,Telnyx,1,,external,,+442071234567,1.50,3.00</pre>
                </details>
            </div>
        </div>
    </div>
</x-admin-layout>
