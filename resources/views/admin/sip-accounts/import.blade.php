<x-admin-layout>
    <x-slot name="header">Import SIP Accounts</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Import SIP Accounts</h2>
            <p class="page-subtitle">Bulk import SIP accounts from CSV file</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.sip-accounts.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Import Form --}}
        <div class="form-card">
            <div class="form-card-header">
                <h3 class="form-card-title">Upload CSV File</h3>
                <p class="form-card-subtitle">Select a CSV file to import SIP accounts</p>
            </div>
            <div class="form-card-body">
                <form method="POST" action="{{ route('admin.sip-accounts.import') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label for="csv_file" class="form-label">CSV File</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-400 transition-colors"
                             x-data="{ fileName: '' }"
                             @dragover.prevent="$el.classList.add('border-indigo-500', 'bg-indigo-50')"
                             @dragleave.prevent="$el.classList.remove('border-indigo-500', 'bg-indigo-50')"
                             @drop.prevent="$el.classList.remove('border-indigo-500', 'bg-indigo-50'); fileName = $event.dataTransfer.files[0]?.name || ''; $refs.fileInput.files = $event.dataTransfer.files">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="csv_file" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                                        <span>Upload a file</span>
                                        <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt" required class="sr-only" x-ref="fileInput" @change="fileName = $event.target.files[0]?.name || ''">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">CSV file up to 5MB</p>
                                <p x-show="fileName" x-text="fileName" class="text-sm font-medium text-indigo-600 mt-2"></p>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('csv_file')" class="mt-2" />
                    </div>

                    <div class="form-group">
                        <label for="mode" class="form-label">Import Mode</label>
                        <select id="mode" name="mode" required class="form-input">
                            <option value="add">Add Only - Skip existing usernames</option>
                            <option value="update">Update Only - Only update existing accounts</option>
                            <option value="add_update">Add & Update - Create new and update existing</option>
                        </select>
                        <p class="form-hint">Choose how to handle existing SIP accounts.</p>
                        <x-input-error :messages="$errors->get('mode')" class="mt-2" />
                    </div>

                    <div class="form-actions-compact">
                        <a href="{{ route('admin.sip-accounts.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Import SIP Accounts
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- CSV Format Guide --}}
        <div class="space-y-6">
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">CSV Format</h3>
                </div>
                <div class="detail-card-body">
                    <p class="text-sm text-gray-600 mb-4">Your CSV file must include the following columns:</p>

                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="badge badge-danger">Required</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">username</p>
                                <p class="text-xs text-gray-500">Unique SIP endpoint ID (alphanumeric, dashes, underscores)</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="badge badge-danger">Required</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">owner_email</p>
                                <p class="text-xs text-gray-500">Email of existing user (reseller or client)</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="badge badge-danger">Required</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">password</p>
                                <p class="text-xs text-gray-500">SIP password (min 6 characters for new accounts)</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="badge badge-danger">Required</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">auth_type</p>
                                <p class="text-xs text-gray-500">password, ip, or both</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="badge badge-danger">Required</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">caller_id_name</p>
                                <p class="text-xs text-gray-500">Outbound caller name</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="badge badge-danger">Required</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">caller_id_number</p>
                                <p class="text-xs text-gray-500">Outbound caller number</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="badge badge-danger">Required</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">max_channels</p>
                                <p class="text-xs text-gray-500">Maximum concurrent calls (1-100)</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="badge badge-danger">Required</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">codec_allow</p>
                                <p class="text-xs text-gray-500">Comma-separated codecs (e.g., ulaw,alaw,g729)</p>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="flex items-start gap-3">
                            <span class="badge badge-gray">Optional</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">allowed_ips</p>
                                <p class="text-xs text-gray-500">IP whitelist (required if auth_type is ip or both)</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="badge badge-gray">Optional</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">status</p>
                                <p class="text-xs text-gray-500">active, suspended, or disabled (default: active)</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="badge badge-gray">Optional</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">allow_p2p</p>
                                <p class="text-xs text-gray-500">Enable P2P calls: true/false or 1/0 (default: true)</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="badge badge-gray">Optional</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">allow_recording</p>
                                <p class="text-xs text-gray-500">Enable call recording: true/false or 1/0 (default: false)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Example CSV --}}
            <div class="detail-card">
                <div class="detail-card-header flex items-center justify-between">
                    <h3 class="detail-card-title">Example CSV</h3>
                    <button type="button" onclick="downloadExample()" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                        Download Template
                    </button>
                </div>
                <div class="detail-card-body">
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-xs text-gray-300 font-mono whitespace-pre">username,owner_email,password,auth_type,allowed_ips,caller_id_name,caller_id_number,max_channels,codec_allow,status
100001,client@example.com,SecurePass123!@#,password,,John Doe,+15551234567,2,ulaw,alaw,g729,active
100002,reseller@example.com,AnotherPass456$%^,both,192.168.1.100,Jane Smith,+15559876543,5,ulaw,alaw,active
100003,client2@example.com,ThirdPassword789&*,ip,10.0.0.0/24,Bob Wilson,+15555551234,3,g729,active</pre>
                    </div>
                </div>
            </div>

            {{-- Tips --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Tips</h3>
                </div>
                <div class="detail-card-body">
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Export existing SIP accounts first to see the correct format.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Users must exist in the system before importing their SIP accounts.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>New accounts are automatically provisioned to Asterisk after import.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Passwords are stored in plain text for SIP authentication. Use strong passwords.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function downloadExample() {
            const csv = `username,owner_email,password,auth_type,allowed_ips,caller_id_name,caller_id_number,max_channels,codec_allow,status
100001,client@example.com,SecurePass123!@#,password,,John Doe,+15551234567,2,"ulaw,alaw,g729",active
100002,reseller@example.com,AnotherPass456$%^,both,192.168.1.100,Jane Smith,+15559876543,5,"ulaw,alaw",active
100003,client2@example.com,ThirdPassword789&*,ip,10.0.0.0/24,Bob Wilson,+15555551234,3,g729,active`;

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'sip-accounts-template.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</x-admin-layout>
