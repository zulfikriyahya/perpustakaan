@php
    use Filament\Support\Enums\IconPosition;
    use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
    use Filament\Widgets\View\Components\StatsOverviewWidgetComponent\StatComponent\DescriptionComponent;
    use Filament\Widgets\View\Components\StatsOverviewWidgetComponent\StatComponent\StatsOverviewWidgetStatChartComponent;
    use Illuminate\Support\Str;

    $chartColor = $getChartColor() ?? 'gray';
    $descriptionColor = $getDescriptionColor() ?? 'gray';
    $descriptionIcon = $getDescriptionIcon();
    $descriptionIconPosition = $getDescriptionIconPosition();
    $url = $getUrl();
    $tag = $url ? 'a' : 'div';

    $exportFileName = Str::slug($getLabel() ?? 'stat');
@endphp

<{!! $tag !!}
    @if ($url)
        {{ \Filament\Support\generate_href_html($url, $shouldOpenUrlInNewTab()) }}
    @endif
    {{
        $getExtraAttributeBag()
            ->class([
                'fi-wi-stats-overview-stat',
            ])
    }}
>

    <div class="fi-wi-stats-overview-stat-content">
        <div class="fi-wi-stats-overview-stat-label-ctn">
            {{ \Filament\Support\generate_icon_html($getIcon())}}

            <span class="fi-wi-stats-overview-stat-label">
                {{ $getLabel() }}
            </span>
        </div>

        <div class="fi-wi-stats-overview-stat-value">
            {{ $getValue() }}
        </div>

        @if ($description = $getDescription())
            <div
                {{ (new FilamentComponentAttributeBag)->color(DescriptionComponent::class, $descriptionColor)->class(['fi-wi-stats-overview-stat-description']) }}
            >
                @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::Before, 'before']))
                    {{ \Filament\Support\generate_icon_html($descriptionIcon, attributes: (new \Filament\Support\View\ComponentAttributeBag)) }}
                @endif

                <span>{{ $description }}</span>

                @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::After, 'after']))
                    {{ \Filament\Support\generate_icon_html($descriptionIcon, attributes: (new \Filament\Support\View\ComponentAttributeBag)) }}
                @endif
            </div>
        @endif
    @if ($chart = $getChart())
        <div x-data="{ statsOverviewStatChart() {} }">
            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('stats-overview/stat/chart', 'filament/widgets') }}"
                wire:ignore
                x-data="statsOverviewStatChart({
                            key: @js($getKey(false)),
                            labels: @js(array_keys($chart)),
                            values: @js(array_values($chart)),
                        })"
                {{ (new FilamentComponentAttributeBag)->color(StatsOverviewWidgetStatChartComponent::class, $chartColor)->class(['fi-wi-stats-overview-stat-chart']) }}
            >
                <canvas x-ref="canvas" aria-hidden="true"></canvas>

                <span x-ref="backgroundColorElement" class="fi-wi-stats-overview-stat-chart-bg-color"></span>
                <span x-ref="borderColorElement" class="fi-wi-stats-overview-stat-chart-border-color"></span>
            </div>
        </div>

        {{-- Tombol download - hanya render kalau ada mini-chart --}}
        <div class="flex justify-end gap-x-1 pt-1">
            <button
    type="button"
    onclick="window.ChartExport.downloadImage(this.closest('.fi-wi-stats-overview-stat').querySelector('canvas'), '{{ $exportFileName }}')"
    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
>
    PNG
</button>
<span class="text-xs text-gray-300 dark:text-gray-600">|</span>
<button
    type="button"
    onclick="window.ChartExport.downloadPdf(this.closest('.fi-wi-stats-overview-stat').querySelector('canvas'), '{{ $exportFileName }}', @js(['widget' => \App\Filament\Widgets\PeminjamanStatsWidget::class, 'type' => 'stat', 'statLabel' => $getLabel()]))"
    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
>
    PDF
</button>
        </div>
    @endif
    </div>


</{!! $tag !!}>
