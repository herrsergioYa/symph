<?php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Response;

#[AsEventListener('kernel.request')]
class RequestListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        if(!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $session = $request->getSession();

        // Пример: логирование, инициализация или проверка
        // logger, timezone, или IP-фильтр, например
        if ($request->getClientIp() === '192.168.0.13') {
            // Пример: запретить доступ
            $event->setResponse(new Response('Access denied', 403));
        }
    }
}
