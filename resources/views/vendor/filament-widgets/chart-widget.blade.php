@php
    use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
    use Filament\Widgets\View\Components\ChartWidgetComponent;
    use Illuminate\Contracts\Support\Htmlable;
    use Illuminate\Support\Str;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $filters = $this->getFilters();
    $isCollapsible = $this->isCollapsible();
    $type = $this->getType();
    $maxHeight = $this->getMaxHeight();
    $hasMaxHeight = filled($maxHeight) && $maxHeight !== '100%';
    $isEmpty = $this->isEmpty();

    $chartAccessibleLabel = trim(implode('. ', array_filter([
        $heading instanceof Htmlable ? strip_tags($heading->toHtml()) : $heading,
        $description instanceof Htmlable ? strip_tags($description->toHtml()) : $description,
    ], fn ($value): bool => filled($value))));

    // Nama file download - dari heading widget (Fitur: download grafik).
    $exportFileName = Str::slug($heading instanceof Htmlable ? strip_tags($heading->toHtml()) : ($heading ?? 'grafik'));
@endphp


<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section
        :description="$description"
        :heading="$heading"
        :collapsible="$isCollapsible"
    >
        <x-slot name="afterHeader">
    <div class="flex items-center gap-x-2 ml-auto">
        @if ($filters)
            <x-filament::input.wrapper
                inline-prefix
                wire:target="filter"
                class="fi-wi-chart-filter"
            >
                <x-filament::input.select
                    :aria-label="__('filament-widgets::chart.filter.label')"
                    inline-prefix
                    wire:model.live="filter"
                >
                    @foreach ($filters as $value => $label)
                        <option value="{{ $value }}">
                            {{ $label }}
                        </option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        @endif

        @if (method_exists($this, 'getFiltersSchema'))
            <x-filament::dropdown
                placement="bottom-end"
                shift
                width="xs"
                class="fi-wi-chart-filter"
            >
                <x-slot name="trigger">
                    {{ $this->getFiltersTriggerAction() }}
                </x-slot>

                <div class="fi-wi-chart-filter-content">
                    {{ $this->getFiltersSchema() }}

                    @if (method_exists($this, 'hasDeferredFilters') && $this->hasDeferredFilters())
                        <div class="fi-wi-chart-filter-content-actions-ctn">
                            {{ $this->getFiltersApplyAction() }}
                            {{ $this->getFiltersResetAction() }}
                        </div>
                    @endif
                </div>
            </x-filament::dropdown>
        @endif

        @unless ($isEmpty)
            <button
                type="button"
                onclick="window.ChartExport.downloadImage(this.closest('.fi-wi-chart').querySelector('canvas'), '{{ $exportFileName }}')"
                class="fi-btn fi-btn-size-xs inline-flex items-center gap-1 rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-white/5"
            >
                PNG
            </button>
            <button
                type="button"
                onclick="window.ChartExport.downloadPdf(this.closest('.fi-wi-chart').querySelector('canvas'), '{{ $exportFileName }}', @js(['widget' => get_class($this), 'type' => 'chart']))"
                class="fi-btn fi-btn-size-xs inline-flex items-center gap-1 rounded-lg border border-gray-300 px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-white/5"
            >
                PDF
            </button>
        @endunless
    </div>
</x-slot>

        <div
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}="updateChartData"
            @endif
            @if ($isEmpty)
                style="display: none"
            @endif
        >
            {{-- Toolbar download grafik (Fitur: download PNG/PDF, client-side) --}}
                        <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                data-chart-type="{{ $type }}"
                x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            options: @js($this->getOptions()),
                            type: @js($type),
                        })"
                {{
                    (new FilamentComponentAttributeBag)
                        ->color(ChartWidgetComponent::class, $color)
                        ->class([
                            'fi-wi-chart-frame',
                            'fi-wi-chart-canvas-ctn',
                            'fi-wi-chart-frame-no-aspect-ratio' => $hasMaxHeight,
                        ])
                }}
            >
                <canvas
                    x-ref="canvas"
                    @if (filled($chartAccessibleLabel))
                        role="img"
                        aria-label="{{ $chartAccessibleLabel }}"
                    @endif
                    @style([
                        'width: 100%',
                        'height: 100%; max-height: 100%' => ! $hasMaxHeight,
                        ('max-height: ' . e($maxHeight)) => $hasMaxHeight,
                    ])
                ></canvas>

                <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
            </div>
        </div>

        @if ($isEmpty)
            @if ($emptyState = $this->getEmptyState())
                {{ $emptyState }}
            @else
                <div
                    @class([
                        'fi-wi-chart-frame',
                        'fi-wi-chart-frame-no-aspect-ratio' => $hasMaxHeight,
                    ])
                    @style([
                        ('min-height: ' . e($maxHeight)) => $hasMaxHeight,
                    ])
                >
                    <x-filament::empty-state
                        :contained="false"
                        :description="$this->getEmptyStateDescription()"
                        :heading="$this->getEmptyStateHeading()"
                        :icon="$this->getEmptyStateIcon()"
                        icon-color="gray"
                    >
                        @if ($emptyStateActions = $this->getEmptyStateActions())
                            <x-slot name="footer">
                                <x-filament::actions
                                    :actions="$emptyStateActions"
                                    alignment="center"
                                />
                            </x-slot>
                        @endif
                    </x-filament::empty-state>
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
