<?php

declare(strict_types=1);

namespace Siroko\Order\Domain;

/**
 * Resultado de un intento de cobro (OK/KO). Value object inmutable.
 *
 * En caso de fallo, el motivo viaja con el resultado (no un bool pelado), para poder
 * registrarlo o exponerlo. En caso de éxito puede llevar una referencia de transacción.
 */
final class PaymentResult
{
    private function __construct(
        private readonly bool $successful,
        private readonly ?string $reference,
        private readonly ?string $reason,
    ) {
    }

    public static function succeeded(?string $reference = null): self
    {
        return new self(true, $reference, null);
    }

    public static function failed(string $reason): self
    {
        return new self(false, null, $reason);
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function reference(): ?string
    {
        return $this->reference;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }
}
