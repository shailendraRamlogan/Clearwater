<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Addon;

class AddonController extends Controller
{
    public function index()
    {
        return response()->json(
            Addon::active()
                ->orderBy("sort_order")
                ->get()
                ->map(fn ($a) => [
                    "id" => $a->id,
                    "title" => $a->title,
                    "description" => $a->description,
                    "price_cents" => $a->price_cents,
                    "price_dollars" => $a->price_dollars,
                    "is_active" => $a->is_active,
                    "sort_order" => $a->sort_order,
                    "max_quantity" => $a->max_quantity,
                    "icon_name" => $a->icon_name,
                ])
        );
    }

    public function privateTourAddons()
    {
        $addons = Addon::active()
            ->forPrivateTours()
            ->orderBy("sort_order")
            ->get()
            ->map(fn ($a) => [
                "id" => $a->id,
                "title" => $a->title,
                "description" => $a->description,
                "icon_name" => $a->icon_name,
            ]);

        return response()->json(['addons' => $addons]);
    }
}
