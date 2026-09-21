<?php

use Emaia\LaravelHotwire\Components\VideoEmbed;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\View\ViewException;

it('server-renders a supported URL without requiring Stimulus', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::video-embed
            url="https://youtu.be/dQw4w9WgXcQ"
            title="Product walkthrough"
            class="media-shell"
            data-controller="analytics"
        />
        BLADE);

    $xpath = new DOMXPath(dom($html));

    expect($xpath->query('//*[@data-slot="video-embed" and @data-controller="analytics" and contains(concat(" ", normalize-space(@class), " "), " media-shell ")]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-slot="video-embed" and contains(@style, "--video-embed-aspect-ratio: 16/9")]/iframe[@src="https://www.youtube.com/embed/dQw4w9WgXcQ" and @title="Product walkthrough" and @loading="lazy" and @data-slot="video-embed-frame" and @frameborder="0" and @allowfullscreen]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-controller and contains(concat(" ", normalize-space(@data-controller), " "), " oembed ")]')->count())->toBe(0)
        ->and($xpath->query('//oembed | //template')->count())->toBe(0);
});

it('supports eager loading, a custom ratio and YouTube privacy mode', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::video-embed
            url="https://www.youtube.com/watch?v=dQw4w9WgXcQ"
            ratio="4/3"
            loading="eager"
            privacy
            style="max-width: 60rem"
        />
        BLADE);

    $xpath = new DOMXPath(dom($html));

    expect($xpath->query('//*[@data-slot="video-embed" and contains(@style, "--video-embed-aspect-ratio: 4/3") and contains(@style, "max-width: 60rem")]/iframe[@src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ" and @loading="eager"]')->count())->toBe(1);
});

it('normalizes ratio whitespace before rendering it as CSS', function () {
    $view = $this->blade('<x-hw::video-embed url="https://vimeo.com/123456789" ratio=" 4 / 3 " />');

    $view->assertSee('style="--video-embed-aspect-ratio: 4/3;"', false);
});

it('rejects ratios that are not positive numbers or numeric fractions', function (string $ratio) {
    $this->blade('<x-hw::video-embed url="https://vimeo.com/123456789" :ratio="$ratio" />', ['ratio' => $ratio]);
})->with([
    'CSS declaration injection' => '16/9; color: red',
    'CSS function' => 'calc(16 / 9)',
    'zero numerator' => '0/9',
    'zero denominator' => '16/0',
])->throws(ViewException::class, 'Video Embed ratio must be a positive number or numeric fraction.');

it('server-renders Vimeo URLs', function () {
    $view = $this->blade('<x-hw::video-embed url="https://vimeo.com/123456789" />');

    $view->assertSee('src="https://player.vimeo.com/video/123456789"', false);
});

it('server-renders supported YouTube URL formats', function (string $url) {
    $view = $this->blade('<x-hw::video-embed :url="$url" />', ['url' => $url]);

    $view->assertSee('src="https://www.youtube.com/embed/dQw4w9WgXcQ"', false);
})->with([
    'watch' => 'https://www.youtube.com/watch?list=playlist&v=dQw4w9WgXcQ',
    'embed' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
    'shorts' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
]);

it('renders unsupported providers as safe links', function () {
    $html = (string) $this->blade('<x-hw::video-embed url="https://example.com/video" class="hero-link" aria-label="Open video" />');
    $xpath = new DOMXPath(dom($html));

    expect($xpath->query('//a[@data-slot="video-embed-link" and @href="https://example.com/video" and @target="_blank" and @rel="noopener noreferrer" and @aria-label="Open video" and contains(concat(" ", normalize-space(@class), " "), " hero-link ")]')->count())->toBe(1)
        ->and(trim($xpath->query('//a[@data-slot="video-embed-link"]')->item(0)->textContent))->toBe('https://example.com/video');
});

it('rejects URLs that are not absolute HTTP or HTTPS URLs', function (string $url) {
    $this->blade('<x-hw::video-embed :url="$url" />', ['url' => $url]);
})->with([
    'empty' => '',
    'relative' => '/video',
    'javascript' => 'javascript:alert(1)',
])->throws(ViewException::class, 'hw:video-embed requires an absolute HTTP or HTTPS `url`.');

it('rejects unsupported loading strategies', function () {
    $this->blade('<x-hw::video-embed url="https://vimeo.com/123456789" loading="instant" />');
})->throws(ViewException::class, 'Video Embed loading must be one of: lazy, eager. Got: instant');

it('registers the Video Embed family without assigning visual ownership to the OEmbed controller', function () {
    $registry = HotwireRegistry::make();
    $component = $registry->component('video-embed');

    expect(VideoEmbed::SLOTS)->toBe([
        'root' => ['name' => 'video-embed', 'kind' => 'visual'],
        'frame' => ['name' => 'video-embed-frame', 'kind' => 'visual'],
        'link' => ['name' => 'video-embed-link', 'kind' => 'visual'],
    ])->and($component)->not->toBeNull()
        ->and($component->class)->toBe(VideoEmbed::class)
        ->and($component->controllers)->toBe([])
        ->and($component->styling->slots)->toBe([
            'video-embed' => 'visual',
            'video-embed-frame' => 'visual',
            'video-embed-link' => 'visual',
        ])
        ->and($registry->controller('oembed')->styling->slots)->toBe([
            'oembed' => 'structural',
            'oembed-frame' => 'structural',
            'oembed-link' => 'structural',
        ]);
});
