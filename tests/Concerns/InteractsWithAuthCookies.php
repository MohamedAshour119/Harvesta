<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Cookie;

trait InteractsWithAuthCookies
{
    protected function refreshCookieFrom(TestResponse $response): Cookie
    {
        foreach ($response->baseResponse->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'refresh_token') {
                return $cookie;
            }
        }

        $this->fail('The refresh_token cookie was not attached to the response.');
    }

    protected function refreshCookieValueFrom(TestResponse $response): string
    {
        return $this->refreshCookieFrom($response)->getValue();
    }

    protected function assertRefreshCookieIsHttpOnly(TestResponse $response): Cookie
    {
        $cookie = $this->refreshCookieFrom($response);

        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', strtolower((string) $cookie->getSameSite()));
        $this->assertNotEmpty($cookie->getValue());

        return $cookie;
    }

    protected function assertRefreshCookieIsCleared(TestResponse $response): Cookie
    {
        $cookie = $this->refreshCookieFrom($response);

        $this->assertTrue($cookie->isCleared());

        return $cookie;
    }
}
