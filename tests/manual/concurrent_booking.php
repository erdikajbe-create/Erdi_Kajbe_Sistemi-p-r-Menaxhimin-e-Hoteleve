<?php

declare(strict_types=1);

/**
 * Demonstrim i KJF08: N kërkesa paralele për të njëjtën dhomë dhe data.
 * Pritet: 1 × 201 dhe pjesa tjetër 409 ROOM_NOT_AVAILABLE.
 *
 * Serveri duhet të pranojë kërkesa paralele (Apache, ose në Linux/macOS:
 * PHP_CLI_SERVER_WORKERS=10 php -S localhost:8000 -t public).
 *
 *   php tests/manual/concurrent_booking.php http://localhost:8000 10
 */

$base = rtrim($argv[1] ?? 'http://localhost:8000', '/');
$count = (int) ($argv[2] ?? 10);
$checkIn = date('Y-m-d', strtotime('+90 days'));
$checkOut = date('Y-m-d', strtotime('+92 days'));

function call(string $method, string $url, ?array $body, string $cookieFile, string $csrf = ''): array
{
    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-CSRF-Token: ' . $csrf],
        CURLOPT_POSTFIELDS => $body === null ? null : json_encode($body),
    ]);
    $response = (string) curl_exec($handle);
    curl_close($handle);

    return json_decode($response, true) ?? [];
}

$sessions = [];
for ($i = 0; $i < $count; $i++) {
    $cookie = tempnam(sys_get_temp_dir(), 'hh');
    $csrf = call('GET', "$base/api/auth/csrf", null, $cookie)['data']['csrf_token'];
    $login = call('POST', "$base/api/auth/login", ['email' => 'recepsion@hotelhub.test', 'password' => 'password'], $cookie, $csrf);
    $sessions[] = [$cookie, $login['data']['csrf_token']];
}

$multi = curl_multi_init();
$handles = [];
foreach ($sessions as [$cookie, $csrf]) {
    $handle = curl_init("$base/api/reservations");
    curl_setopt_array($handle, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-CSRF-Token: ' . $csrf],
        CURLOPT_POSTFIELDS => json_encode(['room_id' => 5, 'guest_id' => 2, 'check_in' => $checkIn, 'check_out' => $checkOut, 'adults' => 1]),
    ]);
    curl_multi_add_handle($multi, $handle);
    $handles[] = $handle;
}
do {
    curl_multi_exec($multi, $running);
    curl_multi_select($multi);
} while ($running > 0);

$codes = array_map(static fn ($handle): int => curl_getinfo($handle, CURLINFO_RESPONSE_CODE), $handles);
$summary = array_count_values($codes);
ksort($summary);
foreach ($summary as $code => $times) {
    printf("HTTP %d × %d\n", $code, $times);
}
echo ($summary[201] ?? 0) === 1 ? "OK: vetëm një rezervim u ruajt.\n" : "GABIM: pritej saktësisht një 201.\n";
