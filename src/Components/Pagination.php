<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\FrameTarget;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\UrlWindow;
use Illuminate\View\Component;

class Pagination extends Component
{
    public const string DEFAULT_ROOT_MARGIN = '300px';

    public const float DEFAULT_THRESHOLD = 1.0;

    public const string DEFAULT_LOAD_MORE_LABEL = 'Load more';

    public const string DEFAULT_LOADING_LABEL = 'Loading more';

    public const string DEFAULT_LOADED_LABEL = 'More results loaded';

    public const string DEFAULT_ERROR_LABEL = 'Loading failed';

    public function __construct(
        public PaginatorContract|CursorPaginatorContract|null $paginator = null,
        public string $label = 'Pagination',
        public string|object|bool|null $frame = null,
        public ?string $previousLabel = 'Previous',
        public ?string $nextLabel = 'Next',
        public string $ellipsisLabel = 'More pages',
        public string $display = 'full',
        public bool $turboStream = false,
        public bool $incremental = false,
        public bool $infinite = false,
        public string|bool|null $loadMoreLabel = self::DEFAULT_LOAD_MORE_LABEL,
        public string|bool|null $loadingLabel = self::DEFAULT_LOADING_LABEL,
        public ?string $appendTo = null,
        public ?string $scrollTo = null,
        public string $rootMargin = self::DEFAULT_ROOT_MARGIN,
        public float $threshold = self::DEFAULT_THRESHOLD,
        public ?Htmlable $stimulus = null,
        public string $previousAriaLabel = 'Go to previous page',
        public string $nextAriaLabel = 'Go to next page',
        public string $loadedLabel = self::DEFAULT_LOADED_LABEL,
        public string $errorLabel = self::DEFAULT_ERROR_LABEL,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
    }

    public function render()
    {
        return view('hotwire::component-views.pagination', [
            'links' => $this->normalizedLinks(),
        ]);
    }

    public function shouldRender(): bool
    {
        if ($this->paginator !== null && $this->usesIncrementalLoading()) {
            return is_string($this->paginator->nextPageUrl());
        }

        return $this->paginator === null || $this->paginator->hasPages();
    }

    /**
     * @return array<int, array{type: string, label: string|null, url: string|null, active: bool, disabled: bool, size?: string}>
     */
    public function normalizedLinks(): array
    {
        if ($this->paginator === null) {
            return [];
        }

        $display = $this->resolvedDisplay();
        $links = [];

        if ($this->usesIncrementalLoading()) {
            $next = $this->controlLink('next', $this->paginator->nextPageUrl(), $this->nextControlLabel($display), $display);

            return $next['disabled'] ? [] : [$next];
        }

        if ($this->showsControls($display)) {
            $links[] = $this->controlLink('previous', $this->paginator->previousPageUrl(), $this->previousLabel, $display);
        }

        if ($this->paginator instanceof LengthAwarePaginatorContract && in_array($display, ['full', 'numbers'], true)) {
            array_push($links, ...$this->numberedLinks($this->paginator));
        }

        if ($this->showsControls($display)) {
            $links[] = $this->controlLink('next', $this->paginator->nextPageUrl(), $this->nextControlLabel($display), $display);
        }

        return $links;
    }

    private function resolvedDisplay(): string
    {
        $display = in_array($this->display, ['full', 'numbers', 'controls', 'icons'], true)
            ? $this->display
            : 'full';

        if (! $this->paginator instanceof LengthAwarePaginatorContract && in_array($display, ['full', 'numbers'], true)) {
            return 'controls';
        }

        return $display;
    }

    private function showsControls(string $display): bool
    {
        return in_array($display, ['full', 'controls', 'icons'], true);
    }

    /**
     * @return array<int, array{type: string, label: string, url: string|null, active: bool, disabled: bool}>
     */
    private function numberedLinks(LengthAwarePaginatorContract $paginator): array
    {
        $links = [];
        $window = UrlWindow::make($paginator);

        $elements = array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);

        foreach ($elements as $element) {
            if (is_string($element)) {
                $links[] = [
                    'type' => 'ellipsis',
                    'label' => $this->ellipsisLabel,
                    'url' => null,
                    'active' => false,
                    'disabled' => true,
                ];

                continue;
            }

            foreach ($element as $page => $url) {
                $active = $paginator->currentPage() === (int) $page;

                $links[] = [
                    'type' => 'page',
                    'label' => (string) $page,
                    'url' => is_string($url) ? $url : null,
                    'active' => $active,
                    'disabled' => ! is_string($url) && ! $active,
                ];
            }
        }

        return $links;
    }

    /**
     * @return array{type: string, label: string|null, url: string|null, active: bool, disabled: bool, size: string}
     */
    private function controlLink(string $type, ?string $url, ?string $label, string $display): array
    {
        $label = $display === 'icons' ? null : $label;
        $hasLabel = $label !== null && $label !== '';

        return [
            'type' => $type,
            'label' => $label,
            'url' => $url,
            'active' => false,
            'disabled' => ! is_string($url),
            'size' => $hasLabel ? 'default' : 'icon',
        ];
    }

    private function nextControlLabel(string $display): ?string
    {
        if (! $this->usesIncrementalLoading()) {
            return $this->nextLabel;
        }

        return $display === 'icons' ? null : $this->loadMoreLabelValue();
    }

    public function usesIncrementalLoading(): bool
    {
        return $this->incremental || $this->infinite;
    }

    public function loadingLabelValue(): string
    {
        return $this->normalizedOptionalLabel($this->loadingLabel, self::DEFAULT_LOADING_LABEL);
    }

    public function loadMoreAriaLabel(): string
    {
        $label = $this->loadMoreLabelValue();

        return $label === '' ? $this->nextAriaLabel : $label;
    }

    private function loadMoreLabelValue(): string
    {
        return $this->normalizedOptionalLabel($this->loadMoreLabel, self::DEFAULT_LOAD_MORE_LABEL);
    }

    private function normalizedOptionalLabel(string|bool|null $label, string $default): string
    {
        return match ($label) {
            true => $default,
            false, null => '',
            default => $label,
        };
    }
}
