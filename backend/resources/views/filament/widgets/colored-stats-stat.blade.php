@php
    use Filament\Support\Enums\IconPosition;
    use Filament\Support\Facades\FilamentView;

    $chartColor = $getChartColor() ?? 'gray';
    $color = $getColor() ?? 'gray';
    $descriptionColor = $getDescriptionColor() ?? 'gray';
    $descriptionIcon = $getDescriptionIcon();
    $descriptionIconPosition = $getDescriptionIconPosition();
    $url = $getUrl();
    $tag = $url ? 'a' : 'div';
    $dataChecksum = $generateDataChecksum();

    $labelStyles = match ($color) {
        'success' => 'color: #059669;',
        'warning' => 'color: #d97706;',
        'danger' => 'color: #dc2626;',
        'info' => 'color: #2563eb;',
        'primary' => 'color: rgb(var(--primary-600));',
        default => 'color: #6b7280;',
    };

    $colorClass = 'cstat-' . ($color ?? 'gray');
@endphp

<style>
    /* Dark mode overrides for colored stats */
    :root.dark .{{ $colorClass }}-value {
        color: #ffffff !important;
    }
    :root.dark .{{ $colorClass }}-label {
        {{ match ($color) {
            'success' => 'color: #34d399;',
            'warning' => 'color: #fbbf24;',
            'danger' => 'color: #f87171;',
            'info' => 'color: #60a5fa;',
            'primary' => 'color: rgb(var(--primary-400));',
            default => 'color: #9ca3af;',
        } }}
    }
</style>

<{!! $tag !!}
    @if ($url)
        {{ \Filament\Support\generate_href_html($url, $shouldOpenUrlInNewTab()) }}
    @endif
    {{
        $getExtraAttributeBag()
            ->class([
                'fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10',
            ])
    }}
>
    <div class="grid gap-y-2">
        <div class="flex items-center gap-x-2">
            @if ($icon = $getIcon())
                <x-filament::icon
                    :icon="$icon"
                    class="fi-wi-stats-overview-stat-icon h-5 w-5 text-gray-400 dark:text-gray-500"
                />
            @endif

            <span
                class="fi-wi-stats-overview-stat-label text-sm font-medium {{ $colorClass }}-label"
                style="{{ $labelStyles }}"
            >
                {{ $getLabel() }}
            </span>
        </div>

        <div
            class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight {{ $colorClass }}-value"
        >
            {{ $getValue() }}
        </div>

        @if ($description = $getDescription())
            <div class="flex items-center gap-x-1">
                @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::Before, 'before']))
                    <x-filament::icon
                        :icon="$descriptionIcon"
                        class="fi-wi-stats-overview-stat-description-icon h-5 w-5 text-gray-400 dark:text-gray-500"
                    />
                @endif

                <span class="fi-wi-stats-overview-stat-description text-sm text-gray-500 dark:text-gray-400">
                    {{ $description }}
                </span>

                @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::After, 'after']))
                    <x-filament::icon
                        :icon="$descriptionIcon"
                        class="fi-wi-stats-overview-stat-description-icon h-5 w-5 text-gray-400 dark:text-gray-500"
                    />
                @endif
            </div>
        @endif
    </div>

    @if ($chart = $getChart())
        <div x-data="{ statsOverviewStatChart: function () {} }">
            <div
                @if (FilamentView::hasSpaMode())
                    x-load="visible"
                @else
                    x-load
                @endif
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('stats-overview/stat/chart', 'filament/widgets') }}"
                x-data="statsOverviewStatChart({
                            dataChecksum: @js($dataChecksum),
                            labels: @js(array_keys($chart)),
                            values: @js(array_values($chart)),
                        })"
                class="fi-wi-stats-overview-stat-chart absolute inset-x-0 bottom-0 overflow-hidden rounded-b-xl"
            >
                <canvas x-ref="canvas" class="h-6"></canvas>
                <span x-ref="backgroundColorElement" class="text-gray-100 dark:text-gray-800"></span>
                <span x-ref="borderColorElement" class="text-gray-400"></span>
            </div>
        </div>
    @endif
</{!! $tag !!}>
