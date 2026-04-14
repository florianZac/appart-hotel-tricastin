<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\LocaleSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriberTest extends TestCase
{
    public function testSubscribesToRequestEvent(): void
    {
        $events = LocaleSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
    }

    public function testSessionLocaleIsApplied(): void
    {
        $subscriber = new LocaleSubscriber('fr');

        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $session->set('_locale', 'en');
        $request->setSession($session);
        // Simulate that a previous session exists
        $request->cookies->set($session->getName(), 'fake_id');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        $this->assertSame('en', $request->getLocale());
    }

    public function testDefaultLocaleWhenNoSessionLocale(): void
    {
        $subscriber = new LocaleSubscriber('fr');

        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $request->setSession($session);
        $request->cookies->set($session->getName(), 'fake_id');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        $this->assertSame('fr', $request->getLocale());
    }

    public function testSkipsWhenNoPreviousSession(): void
    {
        $subscriber = new LocaleSubscriber('fr');

        $request = new Request();
        // Pas de session → hasPreviousSession() = false
        $originalLocale = $request->getLocale();

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($event);

        // La locale ne doit pas changer
        $this->assertSame($originalLocale, $request->getLocale());
    }
}