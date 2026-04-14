<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\SecurityHeadersSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityHeadersSubscriberTest extends TestCase
{
    private SecurityHeadersSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new SecurityHeadersSubscriber();
    }

    public function testSubscribesToResponseEvent(): void
    {
        $events = SecurityHeadersSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
    }

    public function testAddsSecurityHeaders(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response();

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->subscriber->onResponse($event);

        $headers = $response->headers;
        $this->assertTrue($headers->has('X-Frame-Options'));
        $this->assertSame('DENY', $headers->get('X-Frame-Options'));
        $this->assertTrue($headers->has('X-Content-Type-Options'));
        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        $this->assertTrue($headers->has('X-XSS-Protection'));
        $this->assertTrue($headers->has('Referrer-Policy'));
        $this->assertSame('strict-origin-when-cross-origin', $headers->get('Referrer-Policy'));
        $this->assertTrue($headers->has('Permissions-Policy'));
    }
}
