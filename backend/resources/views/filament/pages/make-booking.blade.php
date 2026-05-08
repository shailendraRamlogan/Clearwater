<x-filament-panels::page>
@push('styles')
<style>
/* Step icons */
.fi-forms-wizard-step-icon { width: 2.75rem; height: 2.75rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; background: #1f2937; color: #6b7280; }
.fi-forms-wizard-step-active .fi-forms-wizard-step-icon, .fi-forms-wizard-step-current .fi-forms-wizard-step-icon { background: #0f766e; color: white; box-shadow: 0 0 0 4px rgba(15,118,110,0.2); }
.fi-forms-wizard-step-completed .fi-forms-wizard-step-icon { background: #0f766e; color: white; }
.fi-forms-wizard-step-label { font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: none; letter-spacing: 0; margin-top: 0.25rem; }
.fi-forms-wizard-step-active .fi-forms-wizard-step-label, .fi-forms-wizard-step-current .fi-forms-wizard-step-label, .fi-forms-wizard-step-completed .fi-forms-wizard-step-label { color: #5eead4; }
/* Card grids */
.cb-card-grid > div { position: relative; }
.cb-card-grid > div > label { display: flex !important; flex-direction: column; align-items: flex-start; padding: 1rem 1.25rem; border: 2px solid #1f2937; border-radius: 0.75rem; background: #0a0d14; cursor: pointer; transition: all 0.2s ease; gap: 0.375rem; min-height: 70px; justify-content: center; position: relative; color: #e5e7eb; }
.cb-card-grid > div > label:hover { border-color: #5eead4; background: rgba(15,118,110,0.06); }
.cb-card-grid > div > label > input[type="radio"],
.cb-card-grid > div > label > input[type="checkbox"] { position: absolute; opacity: 0; pointer-events: none; }
.cb-card-grid > div > label svg { display: none !important; }
.cb-card-grid > div:has(input:checked) > label { border-color: #5eead4; background: rgba(15,118,110,0.1); box-shadow: 0 0 0 1px #5eead4; }
.cb-card-grid > div:has(input:checked) > label::after { content: "\2713"; position: absolute; top: 0.625rem; right: 0.75rem; width: 1.5rem; height: 1.5rem; border-radius: 50%; background: #0f766e; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; line-height: 1; }
.cb-card-grid--type > div > label { min-height: 90px; padding: 1.25rem 1.5rem; }
.cb-card-grid--type > div > label .grid > span:first-child { font-size: 1rem; font-weight: 700; color: #f3f4f6; }
.cb-card-grid--type > div > label .grid > p { font-size: 0.8125rem; color: #9ca3af; line-height: 1.4; margin-top: 0.125rem; }
.cb-card-grid--timeslot > div > label { min-height: 80px; }
.cb-card-grid--timeslot > div > label .grid > span:first-child { font-size: 0.9375rem; font-weight: 700; color: #f3f4f6; }
.cb-card-grid--timeslot > div > label .grid > p { font-size: 0.75rem; color: #9ca3af; margin-top: 0.125rem; line-height: 1.5; }
/* Addon cards */
.cb-card-grid--addon .fi-forms-checkbox-list-grid { grid-template-columns: repeat(3, 1fr) !important; }
.cb-card-grid--addon .break-inside-avoid { max-width: 24rem; }
.cb-card-grid--addon > div > label { min-height: 60px; flex-direction: row; justify-content: space-between; align-items: center; }
.cb-card-grid--addon > div > label .grid { flex: 1; display: flex; justify-content: space-between; align-items: center; width: 100%; }
.cb-card-grid--addon > div > label .grid > span:first-child { font-size: 0.9375rem; font-weight: 600; color: #f3f4f6; }
.cb-card-grid--addon > div > label .grid > p { font-size: 0.875rem; font-weight: 700; color: #5eead4; margin: 0; }
/* Form inputs */
.fi-forms-wizard-content input[type="text"], .fi-forms-wizard-content input[type="email"], .fi-forms-wizard-content input[type="tel"], .fi-forms-wizard-content input[type="date"], .fi-forms-wizard-content textarea, .fi-forms-wizard-content select { background: #0a0d14 !important; border: 1px solid #1f2937 !important; border-radius: 0.75rem !important; color: #e5e7eb !important; transition: all 0.15s ease; }
.fi-forms-wizard-content input:focus, .fi-forms-wizard-content textarea:focus { border-color: #5eead4 !important; box-shadow: 0 0 0 2px rgba(94,234,212,0.1) !important; }
.fi-forms-wizard-content label, .fi-forms-wizard-content .fi-forms-component-label { color: #9ca3af !important; }
.fi-forms-wizard-content .fi-helper-text { color: #6b7280 !important; }
.fi-forms-component-placeholder { color: #d1d5db !important; }
/* Summary */
.cb-wizard .whitespace-pre-wrap { font-family: DM Sans, system-ui, sans-serif; font-size: 0.8125rem; line-height: 1.7; color: #d1d5db; }
.cb-wizard .fi-btn { border-radius: 0.75rem !important; font-weight: 600; }
@media (max-width: 639px) { .cb-card-grid--type, .cb-card-grid--timeslot { grid-template-columns: 1fr !important; } .cb-card-grid--addon .fi-forms-checkbox-list-grid { grid-template-columns: 1fr !important; } }
</style>
@endpush

{{ $this->form }}

</x-filament-panels::page>
