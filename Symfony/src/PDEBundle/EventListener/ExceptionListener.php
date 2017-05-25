<?php

namespace PDEBundle\EventListener;

use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class ExceptionListener
 * Redirects exceptions to an application page, instead of letting Symfony handle them.
 *
 * @package PDEBundle\EventListener
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
    public function onKernelException(GetResponseEvent $event)
    {
        /** @var Request $request */
        $request = $event->getRequest();
        $exception = $event->getException();
        // If it's just a `page not found` exception, report it to the user.
        if (is_a($exception, '\Symfony\Component\HttpKernel\Exception\NotFoundHttpException')) {
            $message = 'The resource you tried to access does not exist.';
            $event->setResponse($this->createEventResponse($request, $message));
            return;
        }

        // Same applies to custom ApplicationControlException exceptions.
        if (is_a($exception, 'PDEBundle\Exception\ApplicationControlException')) {
            $response = $this->createEventResponse($request, $exception->getMessage());
            /*
             * Setting status code 200 in an exception response is in fact against the standard HTTP flow.
             * However, the custom exceptions caught here are not really errors, so in the application context
             * it looks like an acceptable option.
             */
            $response->headers->set('X-Status-Code', 200);
            $event->setResponse($response);
            return;
        }

        // Otherwise, log the cause of the issue and return the predefined response.
        $this->logger->addError('An exception occurred - ' . $event->getException()->getMessage());
    }
}
