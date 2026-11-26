<?php

namespace Emaia\LaravelHotwire\Http\Middleware;

use Closure;
use Composer\InstalledVersions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stand in for the origin cache adapter when Spatie ResponseCache is missing or unsupported.
 *
 * @internal
 */
final class BypassResponseCache
{
    private const string PACKAGE = 'spatie/laravel-responsecache';

    private static bool $warned = false;

    public function __construct(private ?string $installed = null)
    {
        $this->installed ??= self::installedVersion();
    }

    /** Check a normalized Composer version, since other releases may write before terminate() or serve hits differently. */
    public static function supports(?string $version): bool
    {
        return $version !== null && preg_match('/\A8\.7\.[0-9]+(?:\.0)?\z/', $version) === 1;
    }

    /** Read the installed Spatie ResponseCache version, normalized by Composer. */
    public static function installedVersion(): ?string
    {
        return InstalledVersions::isInstalled(self::PACKAGE) ? InstalledVersions::getVersion(self::PACKAGE) : null;
    }

    /** Render the route uncached so a dependency update degrades to no origin cache instead of a 500. */
    public function handle(Request $request, Closure $next): Response
    {
        if (! self::$warned) {
            self::$warned = true;
            Log::warning('Hotwire served hotwire.cache.spatie routes without origin caching: it requires Spatie ResponseCache 8.7.x.', [
                'installed' => $this->installed,
            ]);
        }

        return $next($request);
    }
}
