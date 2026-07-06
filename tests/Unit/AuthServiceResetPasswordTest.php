<?php

use App\Core\Service\ServiceReturn;
use App\Enums\TypeAuthenticate;
use App\Enums\UserOtpType;
use App\Models\User;
use App\Models\UserOtp;
use App\Repositories\AdminUserRepository;
use App\Repositories\UserDeviceRepository;
use App\Repositories\UserOtpRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Services\AuthService;
use App\Services\ConfigService;
use App\Services\MailService;
use App\Services\TwilioVerifyService;
use App\Services\ZaloService;
use Illuminate\Support\Facades\Hash;

uses(Tests\TestCase::class);

afterEach(function () {
    Mockery::close();
});

it('resets password when forgot-password otp has already been verified', function () {
    $userRepository = Mockery::mock(UserRepository::class);
    $userProfileRepository = Mockery::mock(UserProfileRepository::class);
    $walletRepository = Mockery::mock(WalletRepository::class);
    $userDeviceRepository = Mockery::mock(UserDeviceRepository::class);
    $configService = Mockery::mock(ConfigService::class);
    $zaloService = Mockery::mock(ZaloService::class);
    $twilioVerifyService = Mockery::mock(TwilioVerifyService::class);
    $mailService = Mockery::mock(MailService::class);
    $userOtpRepository = Mockery::mock(UserOtpRepository::class);
    $adminUserRepository = Mockery::mock(AdminUserRepository::class);

    $tokenQuery = Mockery::mock();
    $tokenQuery->shouldReceive('delete')->once();

    $deviceQuery = Mockery::mock();
    $deviceQuery->shouldReceive('where')->once()->with('user_id', 123)->andReturnSelf();
    $deviceQuery->shouldReceive('delete')->once();

    $user = Mockery::mock(new User([
        'email' => 'reset@example.com',
        'password' => Hash::make('Oldpassword1'),
        'is_active' => true,
    ]))->makePartial();
    $user->id = 123;
    $user->shouldReceive('save')->once()->andReturnTrue();
    $user->shouldReceive('tokens')->once()->andReturn($tokenQuery);

    $userRepository->shouldReceive('findByUserVerified')
        ->once()
        ->with('reset@example.com', TypeAuthenticate::EMAIL)
        ->andReturn($user);

    $userOtpRepository->shouldReceive('getLatestVerifiedOtp')
        ->once()
        ->with('reset@example.com', UserOtpType::FORGOT_PASSWORD, 30, TypeAuthenticate::EMAIL)
        ->andReturn(new UserOtp(['id' => 1]));

    $userOtpRepository->shouldReceive('deleteOtpHadVerified')
        ->once()
        ->with('reset@example.com', UserOtpType::FORGOT_PASSWORD, TypeAuthenticate::EMAIL);

    $userDeviceRepository->shouldReceive('query')
        ->once()
        ->andReturn($deviceQuery);

    $service = new class(
        $userRepository,
        $userProfileRepository,
        $walletRepository,
        $userDeviceRepository,
        $configService,
        $zaloService,
        $twilioVerifyService,
        $mailService,
        $userOtpRepository,
        $adminUserRepository,
    ) extends AuthService {
        protected function execute(
            callable $callback,
            bool $useTransaction = false,
            ?string $actionName = null,
            callable $catchCallback = null,
            bool $logServiceError = false,
            callable $afterCommitCallback = null
        ): ServiceReturn {
            $result = $callback();

            return $result instanceof ServiceReturn
                ? $result
                : ServiceReturn::success($result);
        }
    };

    $result = $service->resetPassword(
        username: 'reset@example.com',
        typeAuthenticate: TypeAuthenticate::EMAIL,
        password: 'Newpassword1',
    );

    expect($result->isSuccess())->toBeTrue();
    expect(Hash::check('Newpassword1', $user->password))->toBeTrue();
});
