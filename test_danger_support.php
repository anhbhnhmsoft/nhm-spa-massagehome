// 1. Setup Data
$phoneKtv = '09' . rand(10000000, 99999999);
$ktv = \App\Models\User::create([
'name' => 'Test KTV',
'phone' => $phoneKtv,
'password' => bcrypt('password'),
'role' => \App\Enums\UserRole::KTV,
]);
$address = \App\Models\UserAddress::create([
'user_id' => $ktv->id,
'address' => '123 Test St',
'latitude' => 10.123,
'longitude' => 106.123,
'is_primary' => true,
]);

$phoneCustomer = '08' . rand(10000000, 99999999);
$customer = \App\Models\User::create([
'name' => 'Test Customer',
'phone' => $phoneCustomer,
'password' => bcrypt('password'),
'role' => \App\Enums\UserRole::CUSTOMER,
]);

$booking = \App\Models\ServiceBooking::create([
'user_id' => $customer->id,
'ktv_user_id' => $ktv->id,
'status' => \App\Enums\BookingStatus::ONGOING,
'start_time' => now()->subMinutes(30),
'duration' => 60,
'price' => 100000,
]);

// 2. Create Danger Support (Empty location)
echo "Creating DangerSupport...\n";
$danger = \App\Models\DangerSupport::create([
'user_id' => $ktv->id,
'content' => 'Test Danger',
]);

// 3. Verify
echo "Verifying...\n";
$danger->refresh();

if ($danger->status === \App\Enums\DangerSupportStatus::PENDING) {
echo "PASS: Status defaulted to PENDING.\n";
} else {
echo "FAIL: Status is " . ($danger->status->value ?? $danger->status) . "\n";
}

if ($danger->latitude == 10.123 && $danger->longitude == 106.123) {
echo "PASS: Location defaulted to Primary Address.\n";
} else {
echo "FAIL: Location is {$danger->latitude}, {$danger->longitude}\n";
}

if ($danger->booking_id == $booking->id) {
echo "PASS: Linked to Ongoing Booking.\n";
} else {
echo "FAIL: Booking ID is {$danger->booking_id}, expected {$booking->id}\n";
}

// Clean up
$danger->delete();
$booking->delete();
$address->delete();
$ktv->delete();
$customer->delete();