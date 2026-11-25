<?php

declare(strict_types=1);

namespace HotelHub\Services;

use HotelHub\Core\Money;
use HotelHub\Core\StayRules;
use HotelHub\Enums\PaymentMethod;
use HotelHub\Repositories\ReservationRepository;

/**
 * KF19: fatura e rezervimit — netët, shërbimet, pagesat dhe bilanci.
 */
final readonly class InvoiceService
{
    public function __construct(private ReservationRepository $reservations)
    {
    }

    /**
     * @param array<string, mixed> $reservation rreshti nga ReservationRepository::find()
     * @return array<string, mixed>
     */
    public function forReservation(array $reservation): array
    {
        $id = (int) $reservation['id'];
        $nights = StayRules::nights($reservation['check_in'], $reservation['check_out']);
        $nightsTotal = Money::normalize((string) $reservation['total_amount']);

        $services = array_map(static fn (array $line): array => [
            'id' => (int) $line['id'],
            'name' => $line['name'],
            'quantity' => (int) $line['quantity'],
            'unit_price' => Money::normalize((string) $line['unit_price']),
            'line_total' => Money::normalize((string) $line['line_total']),
            'added_at' => $line['added_at'],
        ], $this->reservations->services($id));

        $payments = array_map(static fn (array $payment): array => [
            'id' => (int) $payment['id'],
            'amount' => Money::normalize((string) $payment['amount']),
            'method' => $payment['method'],
            'method_label' => PaymentMethod::tryFrom($payment['method'])?->label() ?? $payment['method'],
            'reference' => $payment['reference'],
            'paid_at' => $payment['paid_at'],
            'received_by' => $payment['received_by_name'],
        ], $this->reservations->payments($id));

        $servicesTotal = Money::add(...array_column($services, 'line_total'));
        $paidTotal = Money::add(...array_column($payments, 'amount'));
        $grandTotal = Money::add($nightsTotal, $servicesTotal);

        return [
            'reservation_id' => $id,
            'code' => $reservation['code'],
            'nights' => $nights,
            'price_per_night' => $nights > 0 ? bcdiv($nightsTotal, (string) $nights, 2) : $nightsTotal,
            'nights_total' => $nightsTotal,
            'services' => $services,
            'services_total' => $servicesTotal,
            'payments' => $payments,
            'grand_total' => $grandTotal,
            'paid_total' => $paidTotal,
            'balance' => Money::sub($grandTotal, $paidTotal),
        ];
    }
}
