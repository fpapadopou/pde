<?php

namespace PDEBundle\Controller;

use PDEBundle\Entity\User;
use PDEBundle\Entity\Team;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class WorkspaceController
 * Handles workspace list operation.
 *
 * @package PDEBundle\Controller
 */
class WorkspaceController extends BaseController implements SecureResourceInterface, TeamResourceInterface
{
    /**
     * Returns the current user's workspaces and their contents.
     *
     * @Route("/workspaces", name="get_wspsaces")
     * @Method("GET")
     *
     * @return JsonResponse
     */
    public function getWorkspacesAction()
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Team $team */
        $team = $user->getTeam();

        return new JsonResponse($this->getTeamWorkspaces($team));
    }

}
