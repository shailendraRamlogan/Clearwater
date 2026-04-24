<?php

return [

    'adult_price' => (int) env('PRICING_ADULT', 20000),       // $200.00 in cents
    'child_price' => (int) env('PRICING_CHILD', 15000),       // $150.00 in cents
    'photo_upgrade_price' => (int) env('PRICING_PHOTO_UPGRADE', 7500), // $75.00 in cents

];
