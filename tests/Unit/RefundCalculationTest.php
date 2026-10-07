<?php

use App\Core\Helper\CalculatePrice;

it('refunds the full customer payment when no amount was refunded before', function () {
    expect(CalculatePrice::remainingRefundAmount(661000, 0))->toBe(661000.0);
});

it('refunds only the balance after a partial transportation refund', function () {
    expect(CalculatePrice::remainingRefundAmount(661000, 69000))->toBe(592000.0);
});

it('refunds the amount paid after a coupon discount', function () {
    $customerPaidTotal = CalculatePrice::totalBookingPrice(
        price: 650000,
        priceDiscount: 100000,
        priceTransportation: 0,
    );

    expect(CalculatePrice::remainingRefundAmount($customerPaidTotal, 0))->toBe(550000.0);
});

it('caps an admin refund at the amount still refundable', function () {
    expect(CalculatePrice::remainingRefundAmount(661000, 69000, 700000))->toBe(592000.0);
});

it('does not refund more when cancellation is retried after a full refund', function () {
    expect(CalculatePrice::remainingRefundAmount(661000, 661000))->toBe(0.0);
});

it('honors an admin decision not to refund the customer', function () {
    expect(CalculatePrice::remainingRefundAmount(661000, 69000, 0))->toBe(0.0);
});
