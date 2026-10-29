<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceFingerprintTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_cookie_and_registration_are_created_automatically(): void
    {
        $response = $this->get('/liff/tracking');

        $response->assertOk();
        $response->assertCookie('flow_device_token');

        $deviceToken = $response->getCookie('flow_device_token')->getValue();
        $this->assertNotEmpty($deviceToken);

        $this->assertDatabaseHas('registered_devices', [
            'device_token' => $deviceToken,
        ]);
    }
}
