<?php

use App\Http\Middleware\DetectAppVersion;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

test('detect app version middleware stores normalized app metadata', function () {
    $request = Request::create('/', 'GET', server: [
        'HTTP_X_APP_PLATFORM' => 'iOS',
        'HTTP_X_APP_VERSION' => '1.2',
    ]);

    (new DetectAppVersion())->handle($request, fn () => new Response());

    expect($request->attributes->get('app_platform'))->toBe('ios')
        ->and($request->attributes->get('app_version'))->toBe('1.2.0')
        ->and($request->attributes->get('is_known_app_version'))->toBeTrue();
});

test('detect app version middleware treats invalid headers as unknown', function () {
    $request = Request::create('/', 'GET', server: [
        'HTTP_X_APP_PLATFORM' => 'web',
        'HTTP_X_APP_VERSION' => 'invalid',
    ]);

    (new DetectAppVersion())->handle($request, fn () => new Response());

    expect($request->attributes->get('app_platform'))->toBeNull()
        ->and($request->attributes->get('app_version'))->toBeNull()
        ->and($request->attributes->get('is_known_app_version'))->toBeFalse();
});
