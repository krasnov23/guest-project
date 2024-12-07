<?php

namespace App\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DebugResponseListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();

        $executionTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];

        // Получаем использование памяти
        $memoryUsage = memory_get_usage();

        // Добавляем заголовки
        $response->headers->set('X-Debug-Time', $executionTime);
        $response->headers->set('X-Debug-Memory', $memoryUsage);
    }
}