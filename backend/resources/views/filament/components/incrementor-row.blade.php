@props([
    'field' => '',
    'min' => 0,
    'value' => 0,
    'label' => '',
    'price' => '',
])

<div
    class="cb-inc-wrap"
    x-data="cbInc(document.currentScript.closest('.cb-inc-wrap'))"
    x-init="init()"
    data-cb-field="{{ $field }}"
    data-cb-min="{{ $min }}"
    data-cb-val="{{ $value }}"
>
    <div class="cb-inc-label">
        <div class="cb-inc-name">{{ $label }}</div>
        <div class="cb-inc-price">{{ $price }}</div>
    </div>
    <div class="cb-inc-btns">
        <button type="button" x-on:click="dec()" class="cb-inc-minus">&#8722;</button>
        <div class="cb-inc-val" x-text="count"></div>
        <button type="button" x-on:click="inc()" class="cb-inc-plus">+</button>
    </div>
</div>
