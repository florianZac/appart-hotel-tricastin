<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\FormSanitizerSubscriber;
use App\Service\SanitizerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvents;

class FormSanitizerSubscriberTest extends TestCase
{
    public function testSubscribesToPreSubmit(): void
    {
        $sanitizer = new SanitizerService();
        $subscriber = new FormSanitizerSubscriber($sanitizer);

        $events = FormSanitizerSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::PRE_SUBMIT, $events);
        $this->assertSame('onPreSubmit', $events[FormEvents::PRE_SUBMIT]);
    }
}
