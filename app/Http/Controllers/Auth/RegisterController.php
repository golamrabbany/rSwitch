<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\KycProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            // Account fields
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => 'required|string|max:20',
            'company_name' => 'nullable|string|max:255',

            // KYC fields
            'account_type' => 'required|in:individual,business',
            'full_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'id_type' => 'required|in:nid,passport,driving_license,trade_license',
            'id_number' => 'required|string|max:50',

            // Documents
            'id_front' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'id_back' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Find default parent (first super_admin)
        $superAdmin = User::where('role', 'super_admin')->orderBy('id')->first();

        return DB::transaction(function () use ($validated, $request, $superAdmin) {
            // Create user as client with pending KYC
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'],
                'company_name' => $validated['company_name'] ?? null,
                'role' => 'client',
                'parent_id' => $superAdmin?->id,
                'status' => 'active',
                'kyc_status' => 'pending',
                'billing_type' => 'prepaid',
                'balance' => 0,
                'credit_limit' => 0,
                'currency' => 'BDT',
                'max_channels' => 1,
            ]);

            // Create KYC profile
            $kyc = KycProfile::create([
                'user_id' => $user->id,
                'account_type' => $validated['account_type'],
                'full_name' => $validated['full_name'],
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => $validated['phone'],
                'address_line1' => $validated['address_line1'],
                'address_line2' => $validated['address_line2'] ?? null,
                'city' => $validated['city'],
                'state' => $validated['state'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'country' => $validated['country'],
                'id_type' => $validated['id_type'],
                'id_number' => $validated['id_number'],
                'submitted_at' => now(),
            ]);

            // Upload documents
            $docPath = 'kyc/' . $user->id;

            if ($request->hasFile('id_front')) {
                $file = $request->file('id_front');
                $path = $file->store($docPath, 'local');
                $kyc->documents()->create([
                    'document_type' => 'id_front',
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => 'uploaded',
                ]);
            }

            if ($request->hasFile('id_back')) {
                $file = $request->file('id_back');
                $path = $file->store($docPath, 'local');
                $kyc->documents()->create([
                    'document_type' => 'id_back',
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => 'uploaded',
                ]);
            }

            return redirect()->route('login')->with('status', 'Account created successfully! Your KYC is under review. You can log in once approved by admin.');
        });
    }
}
