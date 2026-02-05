<?php

namespace Tests\Feature\Invoice;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_invoice_pdf(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();

        $invoice = Invoice::factory()->create([
            'user_id' => $client->id,
        ]);

        $response = $this->actingAs($admin)->get("/admin/invoices/{$invoice->id}/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition');
        $this->assertStringContains($invoice->invoice_number, $response->headers->get('content-disposition'));
    }

    public function test_client_can_download_own_invoice_pdf(): void
    {
        $client = User::factory()->client()->create(['kyc_status' => 'approved']);

        $invoice = Invoice::factory()->create([
            'user_id' => $client->id,
        ]);

        $response = $this->actingAs($client)->get("/client/invoices/{$invoice->id}/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_client_cannot_download_other_users_invoice_pdf(): void
    {
        $client = User::factory()->client()->create(['kyc_status' => 'approved']);
        $otherClient = User::factory()->client()->create(['kyc_status' => 'approved']);

        $invoice = Invoice::factory()->create([
            'user_id' => $otherClient->id,
        ]);

        $response = $this->actingAs($client)->get("/client/invoices/{$invoice->id}/pdf");

        $response->assertForbidden();
    }

    public function test_pdf_contains_valid_content(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();

        $invoice = Invoice::factory()->create([
            'user_id' => $client->id,
            'call_charges' => 125.5000,
            'did_charges' => 10.0000,
            'tax_amount' => 0,
            'total_amount' => 135.5000,
        ]);

        $response = $this->actingAs($admin)->get("/admin/invoices/{$invoice->id}/pdf");

        $response->assertOk();
        // PDF content should be non-trivial (at least 1KB for a real PDF)
        $this->assertGreaterThan(1000, strlen($response->getContent()));
        // PDF starts with %PDF magic bytes
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
