<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Supported application locales.
     *
     * @var list<string>
     */
    public const SUPPORTED_LOCALES = ['en', 'ar'];

    /**
     * Default fallback locale.
     */
    public const DEFAULT_LOCALE = 'en';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        App::setLocale($locale);

        $response = $next($request);

        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    /**
     * Determine preferred locale from query parameter or Accept-Language header.
     */
    private function determineLocale(Request $request): string
    {
        // 1. Explicit query parameter override
        $explicit = $request->query('lang') ?? $request->query('locale');
        if (is_string($explicit) && in_array(strtolower($explicit), self::SUPPORTED_LOCALES, true)) {
            return strtolower($explicit);
        }

        // 2. Accept-Language header
        $header = $request->header('Accept-Language');
        if ($header !== null && $header !== '') {
            $parsed = $this->parseAcceptLanguage($header);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return self::DEFAULT_LOCALE;
    }

    /**
     * Parse primary language from Accept-Language header (e.g. "ar-EG,ar;q=0.9,en;q=0.8").
     */
    private function parseAcceptLanguage(string $header): ?string
    {
        $languages = explode(',', $header);

        foreach ($languages as $part) {
            $langCode = trim(explode(';', $part)[0]);
            $primary = strtolower(explode('-', $langCode)[0]);

            if (in_array($primary, self::SUPPORTED_LOCALES, true)) {
                return $primary;
            }
        }

        return null;
    }
}
