<?php

namespace App\Services;

use App\Models\BookingFee;
use Illuminate\Support\Collection;

class FeeService
{
    public function getActiveFees(): Collection
    {
        return BookingFee::active()->orderBy('sort_order')->get();
    }

    public function calculateFees(int $subtotalCents): array
    {
        $fees = [];
        $totalFeesCents = 0;

        foreach ($this->getActiveFees() as $fee) {
            $amountCents = $fee->calculateFee($subtotalCents);
            $totalFeesCents += $amountCents;
            $fees[] = [
                'name' => $fee->name,
                'type' => $fee->type,
                'amount_cents' => $amountCents,
                'display' => $fee->displayValue(),
            ];
        }

        return [
            'fees' => $fees,
            'total_fees_cents' => $totalFeesCents,
            'subtotal_cents' => $subtotalCents,
            'grand_total_cents' => $subtotalCents + $totalFeesCents,
        ];
    }
}
