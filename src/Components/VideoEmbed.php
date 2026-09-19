<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use InvalidArgumentException;

class VideoEmbed extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'video-embed', 'kind' => 'visual'],
        'frame' => ['name' => 'video-embed-frame', 'kind' => 'visual'],
        'link' => ['name' => 'video-embed-link', 'kind' => 'visual'],
    ];

    public ?string $embedUrl;

    /** Resolve a public media URL into a server-rendered embed or fallback link. */
    public function __construct(
        public string $url,
        public string $title = 'Embedded media',
        public string $ratio = '16/9',
        public string $loading = 'lazy',
        public bool $privacy = false,
    ) {
        $this->url = trim($url);
        $this->title = trim($title) !== '' ? trim($title) : 'Embedded media';
        $this->ratio = trim($ratio) !== '' ? trim($ratio) : '16/9';
        $this->loading = strtolower(trim($loading));

        if (! in_array($this->loading, ['lazy', 'eager'], true)) {
            throw new InvalidArgumentException(
                "Video Embed loading must be one of: lazy, eager. Got: {$loading}"
            );
        }

        if (! $this->isSafeUrl($this->url)) {
            throw new InvalidArgumentException('hw:video-embed requires an absolute HTTP or HTTPS `url`.');
        }

        $this->embedUrl = $this->resolveEmbedUrl();
    }

    public function render()
    {
        return view('hotwire::component-views.video-embed', [
            'slotName' => self::SLOTS['root']['name'],
            'frameSlotName' => self::SLOTS['frame']['name'],
            'linkSlotName' => self::SLOTS['link']['name'],
        ]);
    }

    private function isSafeUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function resolveEmbedUrl(): ?string
    {
        $host = strtolower((string) parse_url($this->url, PHP_URL_HOST));
        $path = (string) parse_url($this->url, PHP_URL_PATH);

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            $id = $this->youtubeId($path, (string) parse_url($this->url, PHP_URL_QUERY));
            $embedHost = $this->privacy ? 'www.youtube-nocookie.com' : 'www.youtube.com';

            return $id !== null ? "https://{$embedHost}/embed/{$id}" : null;
        }

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $id = $this->pathId($path, '~^/([\w-]+)(?:/|$)~');
            $embedHost = $this->privacy ? 'www.youtube-nocookie.com' : 'www.youtube.com';

            return $id !== null ? "https://{$embedHost}/embed/{$id}" : null;
        }

        if (in_array($host, ['vimeo.com', 'www.vimeo.com'], true)) {
            $id = $this->pathId($path, '~^/(\d+)(?:/|$)~');

            return $id !== null ? "https://player.vimeo.com/video/{$id}" : null;
        }

        return null;
    }

    private function youtubeId(string $path, string $query): ?string
    {
        if ($path === '/watch') {
            parse_str($query, $parameters);
            $id = $parameters['v'] ?? null;

            return is_string($id) && preg_match('/^[\w-]+$/', $id) === 1 ? $id : null;
        }

        return $this->pathId($path, '~^/(?:embed|shorts)/([\w-]+)(?:/|$)~');
    }

    private function pathId(string $path, string $pattern): ?string
    {
        return preg_match($pattern, $path, $matches) === 1 ? $matches[1] : null;
    }
}
