<?php
declare(strict_types=1);

namespace Mvc4us\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CorsUtils
{
    public static function isCors(Request $request): bool
    {
        return $request->headers->has('Origin');
    }

    public static function isPreflight(Request $request): bool
    {
        return $request->getMethod() === 'OPTIONS' && $request->headers->has('Access-Control-Request-Method');
    }

    public static function handlePreflight(Response $response, Request $request): void
    {
        $response->setStatusCode(Response::HTTP_NO_CONTENT);

        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
        $response->headers->set('Vary', ['Accept-Encoding', 'Origin']);
        $response->headers->set(
            'Access-Control-Allow-Methods',
            $request->headers->get('Access-Control-Request-Method')
        );
        $response->headers->set(
            'Access-Control-Allow-Headers',
            $request->headers->get('Access-Control-Request-Headers')
        );
    }

    public static function handleCors(Response $response, Request $request): void
    {
        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
        $response->headers->set('Vary', ['Accept-Encoding', 'Origin']);
    }
}
