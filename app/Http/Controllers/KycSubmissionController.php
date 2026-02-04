<?php

namespace App\Http\Controllers;

use App\Models\KycDocument;
use App\Models\KycProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class KycSubmissionController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        $profile = $user->kycProfile;
        $documents = $profile?->documents ?? collect();

        return view('kyc.show', compact('user', 'profile', 'documents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_type' => ['required', Rule::in(['individual', 'company'])],
            'full_name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'alt_phone' => ['nullable', 'string', 'max:20'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'id_type' => ['required', Rule::in(['national_id', 'passport', 'driving_license', 'business_license'])],
            'id_number' => ['required', 'string', 'max:50'],
            'id_expiry_date' => ['nullable', 'date', 'after:today'],
        ]);

        $user = auth()->user();

        $profile = KycProfile::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($validated, ['submitted_at' => now()])
        );

        $user->update(['kyc_status' => 'pending']);

        return redirect()->route('kyc.show')
            ->with('success', 'KYC profile submitted for review.');
    }

    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document_type' => ['required', Rule::in([
                'id_front', 'id_back', 'selfie', 'proof_of_address',
                'business_registration', 'tax_certificate', 'other',
            ])],
            'document' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        $user = auth()->user();
        $profile = $user->kycProfile;

        if (! $profile) {
            return back()->withErrors(['document' => 'Please submit your KYC profile first.']);
        }

        $path = $request->file('document')->store("kyc/{$user->id}", 'local');

        KycDocument::create([
            'kyc_profile_id' => $profile->id,
            'document_type' => $request->document_type,
            'file_path' => $path,
            'original_name' => $request->file('document')->getClientOriginalName(),
            'mime_type' => $request->file('document')->getMimeType(),
            'file_size' => $request->file('document')->getSize(),
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }
}
