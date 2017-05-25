<?php

namespace PDEBundle\EventListener;

use PDEBundle\Entity\User;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use PDEBundle\Controller\TeamResourceInterface;
use PDEBundle\Exception\ApplicationControlException;

/**
 * Class TeamResourceListener
 * Rejects users trying to access a team resource without having previously created/joined a team.
 *
 * @package PDEBundle\EventListener
 */
class TeamResourceListener extends BaseListener
{
    /**
     * Ensures that only users that are members of a team can access team resource.
     *
     * @param FilterControllerEvent $event
     * @throws ApplicationControlException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $this->getEventController($event);
        if (!($controller instanceof TeamResourceInterface)) {
            return;
        }

        /** @var User $currentUser */
        $currentUser = $this->tokenStorage->getToken()->getUser();

        if ($currentUser->getTeam() === null) {
            $message = 'You need a team in order to complete this operation. Create a team and retry.';
            throw new ApplicationControlException($message);
        }
    }

}