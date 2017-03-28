<?php

namespace WideBundle\EventListener;

use WideBundle\Entity\User;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Class RequestListener
 * Rejects non-authenticated users trying to access any resource of the application.
 *
 * @package WideBundle\EventListener
 */
class AuthListener extends BaseListener
{
    /**
     * Changes the response of the current request, depending on whether the user is logged in or not.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        /** @var Request $request */
        $request = $event->getRequest();
        $requestPathInfo = $request->getRequestUri();
        $ignoredRoutes = [
            $this->router->generate('index_page'),
            $this->router->generate('security_login_check'),
            $this->router->generate('logout')
        ];

        if (in_array($requestPathInfo, $ignoredRoutes)) {
            return;
        }

        /** @var TokenInterface $token */
        $token = $this->tokenStorage->getToken();
        $message = 'Session expired. You need to log into your account in order to access this resource.';
        if ($token === null || !is_object($token->getUser())) {
            $event->setResponse($this->createEventResponse($request, $message));
            return;
        }
    }

}