<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingFee;

class PricingController extends Controller
{
    public function index()
    {
        return response()->json([
            'adult' => (float) (config('pricing.adult_price', 20000) / 100),
            'child' => (float) (config('pricing.child_price', 15000) / 100),
            'photo_upgrade' => (float) (config('pricing.photo_upgrade_price', 7500) / 100),
            'fees' => BookingFee::active()->orderBy('sort_order')->get()->map(fn ($f) => [
                'name' => $f->name,
                'type' => $f->type,
                'value' => (float) $f->value,
                'flat_value' => (float) $f->flat_value,
            ])->values()->all(),
        ]);
    }
}
