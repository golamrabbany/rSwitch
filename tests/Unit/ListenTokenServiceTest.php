<?php

namespace Tests\Unit;

use App\Services\ListenTokenService;
use Tests\TestCase;

class ListenTokenServiceTest extends TestCase
{
    public function test_mint_then_verify_round_trip(): void
    {
        $svc = new ListenTokenService('secret-xyz');
        $token = $svc->mint(linkedId: '100.1', uid: 15192, ttlSeconds: 30);

        $claims = $svc->verify($token);
        $this->assertSame('100.1', $claims['lid']);
        $this->assertSame(15192, $claims['uid']);
        $this->assertSame('super_admin', $claims['role']);
    }

    public function test_tampered_token_fails_verify(): void
    {
        $svc = new ListenTokenService('secret-xyz');
        $token = $svc->mint(linkedId: '100.1', uid: 1, ttlSeconds: 30);

        $this->assertNull($svc->verify($token . 'x'));
    }

    public function test_token_has_two_dot_separated_parts(): void
    {
        $svc = new ListenTokenService('secret-xyz');
        $token = $svc->mint(linkedId: 'a', uid: 1, ttlSeconds: 30);
        $this->assertCount(2, explode('.', $token));
    }

    public function test_mint_throws_when_secret_is_empty(): void
    {
        $svc = new ListenTokenService('');
        $this->expectException(\RuntimeException::class);
        $svc->mint(linkedId: 'a', uid: 1, ttlSeconds: 30);
    }
}
