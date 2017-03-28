<?php

namespace WideBundle\EventListener;

use WideBundle\Entity\User;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Class TeamResourceListener
 * Rejects users trying to access a team resource without having previously created/joined a team.
 *
 * @package WideBundle\EventListener
 */
class TeamResourceListener extends BaseListener
{
    /**
     * Changes the response of the current request, depending on whether the user belongs to a team or not.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        /** @var Request $request */
        $request = $event->getRequest();
        $requestPathInfo = $request->getPathInfo();

        if (!in_array($requestPathInfo, ['/utility/', '/editor']) && substr($requestPathInfo, 0, 10) != '/workspace') {
            return;
        }

        /** @var User $currentUser */
        $currentUser = $this->tokenStorage->getToken()->getUser();

        if ($currentUser->getTeam() === null) {
            $message = 'You need a team in order to complete this operation. Create a team and retry.';
            $event->setResponse($this->createEventResponse($request, $message));
        }
    }

}