<?php

use App\Enums\ConfigName;
use App\Enums\WalletTransactionType;
use App\Models\WalletTransaction;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Services\ConfigService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\PayOsService;
use App\Services\WalletTransactionStatusService;
use App\Services\ZaloService;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);

afterEach(function () {
    Mockery::close();
});

function makePaymentService(?ConfigService $configService = null): PaymentService
{
    return new class(
        Mockery::mock(WalletRepository::class),
        Mockery::mock(WalletTransactionRepository::class),
        $configService ?? Mockery::mock(ConfigService::class),
        Mockery::mock(PayOsService::class),
        Mockery::mock(ZaloService::class),
        Mockery::mock(NotificationService::class),
        Mockery::mock(WalletTransactionStatusService::class),
    ) extends PaymentService {
        public function exposeResolveTransactionDetailKind(WalletTransaction $transaction): string
        {
            return $this->resolveTransactionDetailKind($transaction);
        }

        public function exposeResolveTransactionPaymentData(WalletTransaction $transaction): ?array
        {
            return $this->resolveTransactionPaymentData($transaction);
        }
    };
}

it('normalizes qr banking metadata into mobile transaction detail payload', function () {
    $service = makePaymentService();

    $transaction = new WalletTransaction([
        'type' => WalletTransactionType::DEPOSIT_QR_CODE->value,
        'money_amount' => '250000',
        'transaction_code' => 'QRBK123',
        'metadata' => json_encode([
            'data' => [
                'bin' => '970422',
                'accountNumber' => '0123456789',
                'accountName' => 'Massage Home',
                'amount' => 250000,
                'description' => 'QRBK123',
                'qrCode' => 'vietqr://sample',
            ],
        ]),
    ]);

    expect($service->exposeResolveTransactionDetailKind($transaction))->toBe('deposit_qr');

    $paymentData = $service->exposeResolveTransactionPaymentData($transaction);

    expect($paymentData)->not->toBeNull()
        ->and($paymentData['bin'])->toBe('970422')
        ->and($paymentData['bank_name'])->toBe('MBBank')
        ->and($paymentData['account_number'])->toBe('0123456789')
        ->and($paymentData['account_name'])->toBe('Massage Home')
        ->and((int) $paymentData['amount'])->toBe(250000)
        ->and($paymentData['description'])->toBe('QRBK123')
        ->and($paymentData['qr_code'])->toBe('vietqr://sample');
});

it('falls back to configured alipay qr data when old transaction metadata is missing', function () {
    Storage::fake('public');

    $configService = Mockery::mock(ConfigService::class);
    $configService->shouldReceive('getConfigValue')
        ->once()
        ->with(ConfigName::EXCHANGE_RATE_VND_CNY)
        ->andReturn('3500');
    $configService->shouldReceive('getConfigValue')
        ->once()
        ->with(ConfigName::SP_ALIPAY_QR_IMAGE)
        ->andReturn('payment/alipay.png');

    $service = makePaymentService($configService);

    $transaction = new WalletTransaction([
        'type' => WalletTransactionType::DEPOSIT_ALIPAY_PAY->value,
        'money_amount' => '350000',
        'transaction_code' => 'ALIPY123',
        'metadata' => null,
    ]);

    expect($service->exposeResolveTransactionDetailKind($transaction))->toBe('deposit_alipay');

    $paymentData = $service->exposeResolveTransactionPaymentData($transaction);

    expect($paymentData)->not->toBeNull()
        ->and($paymentData['description'])->toBe('ALIPY123')
        ->and($paymentData['amount'])->toBe('350000.00')
        ->and((float) $paymentData['amount_cny'])->toBe(100.0)
        ->and($paymentData['exchange_rate'])->toBe('3500')
        ->and($paymentData['qr_image'])->toContain('payment/alipay.png');
});
