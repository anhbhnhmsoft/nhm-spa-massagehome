<?php

use App\Core\Service\ServiceReturn;
use App\Services\ZaloService;

uses(Tests\TestCase::class);

beforeEach(function () {
    config()->set('services.internal_api.secret', 'bridge-secret');
});

it('rejects requests without the internal secret', function () {
    $this->getJson('/api/integration/zalo/access-token')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthorized');
});

it('returns the zalo access token for authorized internal requests', function () {
    $zaloService = Mockery::mock(ZaloService::class);
    $zaloService->shouldReceive('getAccessTokenForOA')
        ->once()
        ->andReturn(ServiceReturn::success('zalo-access-token'));

    $this->app->instance(ZaloService::class, $zaloService);

    $this->withHeaders([
        'X-Internal-Secret' => 'bridge-secret',
    ])->getJson('/api/integration/zalo/access-token')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.access_token', 'zalo-access-token');
});

it('returns a server error when zalo token retrieval fails', function () {
    $zaloService = Mockery::mock(ZaloService::class);
    $zaloService->shouldReceive('getAccessTokenForOA')
        ->once()
        ->andReturn(ServiceReturn::error('Unable to get access token'));

    $this->app->instance(ZaloService::class, $zaloService);

    $this->withHeaders([
        'X-Internal-Secret' => 'bridge-secret',
    ])->getJson('/api/integration/zalo/access-token')
        ->assertStatus(500)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unable to get access token');
});
