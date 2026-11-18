<?php

declare(strict_types=1);

namespace HotelHub\Enums;

/**
 * RB06: statusi kalon vetëm përpara; anulimi lejohet vetëm nga pending ose confirmed.
 * I njëjti rregull zbatohet edhe nga trigger-i trg_reservations_bu në databazë.
 */
enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::CheckedIn, self::Cancelled],
            self::CheckedIn => [self::CheckedOut],
            self::CheckedOut, self::Cancelled => [],
        };
    }

    /**
     * Rezervimet që e zënë dhomën për datat e tyre (RB01).
     */
    public function blocksRoom(): bool
    {
        return $this !== self::Cancelled;
    }

    /**
     * Mund të marrë shërbime ose pagesa.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::CheckedIn], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Në pritje',
            self::Confirmed => 'Konfirmuar',
            self::CheckedIn => 'Brenda (check-in)',
            self::CheckedOut => 'Larguar (check-out)',
            self::Cancelled => 'Anuluar',
        };
    }
}
