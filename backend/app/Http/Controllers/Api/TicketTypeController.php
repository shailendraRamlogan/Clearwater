<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketType;

class TicketTypeController extends Controller
{
    public function __invoke()
    {
        return response()->json(
            TicketType::active()
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'description' => $t->description,
                    'price_cents' => $t->price_cents,
                    'sort_order' => $t->sort_order,
                    'features' => $t->features->map(fn ($f) => [
                        'icon' => $f->icon,
                        'label' => $f->label,
                        'sort_order' => $f->sort_order,
                    ])->values(),
                ])
        );
    }
}
