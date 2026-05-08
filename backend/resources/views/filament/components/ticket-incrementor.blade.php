@props([
    'fieldPrefix' => 'formData.',
    'adultDefault' => 1,
    'childDefault' => 0,
])

<div class="cb-ticket-grid" x-data="{ adults: {{ $adultDefault }}, children: {{ $childDefault } }">
    <div class="cb-ticket-row">
        <div class="cb-ticket-info">
            <div class="cb-ticket-name">Adults</div>
            <div class="cb-ticket-price">$200.00 each</div>
        </div>
        <div class="cb-ticket-controls">
            <button type="button" x-on:click="adults = Math.max(1, adults - 1); @this.set('{{ $fieldPrefix }}adult_count', adults)">−</button>
            <div class="cb-ticket-val" x-text="adults"></div>
            <button type="button" x-on:click="adults = adults + 1; @this.set('{{ $fieldPrefix }}adult_count', adults)">+</button>
        </div>
    </div>
    <div class="cb-ticket-row">
        <div class="cb-ticket-info">
            <div class="cb-ticket-name">Children (under 12)</div>
            <div class="cb-ticket-price">$150.00 each</div>
        </div>
        <div class="cb-ticket-controls">
            <button type="button" x-on:click="children = Math.max(0, children - 1); @this.set('{{ $fieldPrefix }}child_count', children)">−</button>
            <div class="cb-ticket-val" x-text="children"></div>
            <button type="button" x-on:click="children = children + 1; @this.set('{{ $fieldPrefix }}child_count', children)">+</button>
        </div>
    </div>
</div>
