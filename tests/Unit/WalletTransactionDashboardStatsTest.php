<?php

use App\Enums\DateRangeDashboard;
use App\Enums\WalletTransactionType;
use App\Repositories\WalletTransactionRepository;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class);

it('recognizes revenue transactions without treating refunds as operation costs', function () {
    $range = DateRangeDashboard::ALL->getDateRange();
    $queries = DB::connection()->pretend(fn () => app(WalletTransactionRepository::class)
        ->getFinancialDashboardStats($range['from'], $range['to']));

    expect($queries[0]['query'])
        ->not->toContain('FROM service_bookings')
        ->and(WalletTransactionType::operationCostStatus())
        ->not->toContain(WalletTransactionType::REFUND->value)
        ->not->toContain(WalletTransactionType::REFUND_CUSTOMER_TRANSPORT->value)
        ->not->toContain(WalletTransactionType::PAYMENT_REFUND_KTV_FOR_BOOKING_CANCEL->value);
});
