<?php

namespace App\EventSubscriber;

use App\Controller\Pool\TokenAuthenticatedController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TokenSubscriber implements EventSubscriberInterface
{
    private $token;

    public function __construct(ContainerBagInterface $params)
    {
        $this->token = $params->get('app.worker.token');
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // when a controller class defines multiple action methods, the controller
        // is returned as [$controllerInstance, 'methodName']
        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof TokenAuthenticatedController) {
            $token = $event->getRequest()->headers->get('Worker-Token');

            if (empty($token) || $token !== $this->token) {
                throw new AccessDeniedHttpException('This action needs a valid token!');
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }
}
