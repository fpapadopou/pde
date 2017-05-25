<?php

namespace PDEBundle\EventListener;

use PDEBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use PDEBundle\Controller\AdminResourceInterface;
use PDEBundle\Controller\SecureResourceInterface;
use PDEBundle\Exception\ApplicationControlException;

/**
 * Class RequestListener
 * Rejects non-authenticated users trying to access any resource of the application.
 *
 * @package PDEBundle\EventListener
 */
class AuthListener extends BaseListener
{
    /**
     * Changes the response of the current request, depending on whether the user is logged in or not.
     *
     * @param FilterControllerEvent $event
     * @throws ApplicationControlException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $this->getEventController($event);
        // User needs to be logged in
        if (!($controller instanceof SecureResourceInterface)) {
            return;
        }
        /** @var TokenInterface $token */
        $token = $this->tokenStorage->getToken();
        $message = 'Session expired. You need to log into your account in order to access this resource.';
        if ($token === null || !is_object($token->getUser())) {
            throw new ApplicationControlException($message);
        }

        // Admin access control
        if (!($controller instanceof AdminResourceInterface)) {
            return;
        }
        /** @var User $user */
        $user = $token->getUser();
        if (!$user->isAdmin()) {
            $message = 'You need administrative privileges in order to access this resource.';
            throw new ApplicationControlException($message);
        }
    }

}
