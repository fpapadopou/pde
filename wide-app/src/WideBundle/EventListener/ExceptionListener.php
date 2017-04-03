<?php

namespace WideBundle\EventListener;

use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class ExceptionListener
 * Redirects exceptions to an application page, instead of letting Symfony handle them.
 *
 * @package WideBundle\EventListener
 */
class ExceptionListener extends BaseListener
{
    /** @var Logger $logger */
    private $logger;

    /**
     * ExceptionListener constructor.
     *
     * @param TokenStorage $tokenStorage
     * @param Router $router
     * @param Logger $logger
     */
    public function __construct(TokenStorage $tokenStorage, Router $router, Logger $logger)
    {
        parent::__construct($tokenStorage, $router);
        $this->logger = $logger;
    }

    /**
     * Checks for exceptions, redirects requests and logs errors, if necessary.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        /** @var Request $request */
        $request = $event->getRequest();
        // If it's just a `page not found` exception, report it to the user.
        if (is_a($event->getException(), '\Symfony\Component\HttpKernel\Exception\NotFoundHttpException')) {
            $message = 'The resource you tried to access does not exist.';
            $event->setResponse($this->createEventResponse($request, $message));
            return;
        }

        // Otherwise, report a generic message and log the real cause of the exception.
        $this->logger->addError('An exception occurred - ' . $event->getException()->getMessage());
        $message = 'An error occurred. Try again.';
        $event->setResponse($this->createEventResponse($request, $message));
    }
}
