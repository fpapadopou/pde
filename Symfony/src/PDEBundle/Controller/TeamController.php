<?php

namespace PDEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use PDEBundle\Entity\User;
use PDEBundle\Entity\Team;
use PDEBundle\Teams\TeamManager;

/**
 * Class TeamController
 * Handles teams creation, deletion and other operations.
 *
 * @package PDEBundle\Controller
 * @Route("/team")
 */
class TeamController extends Controller implements SecureResourceInterface, TeamOperationInterface
{
    /**
     * Creates a new team and adds the current user to it.
     *
     * @Route("/create", name="create_team")
     * @Method({"POST"})
     *
     * @return JsonResponse
     */
    public function createTeamAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var TeamManager $teamManager */
        $teamManager = $this->get('pde.teammanager');
        return new JsonResponse($teamManager->createTeam($user));
    }

    /**
     * Allows the current user to permanently delete a team and its workspaces.
     *
     * @Route("/delete", name="delete_team")
     * @Method({"POST"})
     *
     * @return JsonResponse
     */
    public function deleteTeamAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Team $team */
        $team = $user->getTeam();
        if ($team === null) {
            return new JsonResponse(['success' => false, 'error' => 'Your account has no team.']);
        }

        /** @var TeamManager $teamManager */
        $teamManager = $this->get('pde.teammanager');
        return new JsonResponse($teamManager->deleteTeam($team));
    }

    /**
     * Removes the current user from their team. If the user is the last member of the team, the team and all
     * of its workspaces are deleted from the application.
     *
     * @Route("/leave", name="leave_team")
     * @Method({"POST"})
     *
     * @return JsonResponse
     */
    public function leaveTeamAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var TeamManager $teamManager */
        $teamManager = $this->get('pde.teammanager');
        return new JsonResponse($teamManager->leaveTeam($user));
    }

    /**
     * Allows the current user to add a new member to their team. The new member is specified by their email.
     *
     * @Route("/add_member", name="add_member")
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addMemberAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var TeamManager $teamManager */
        $teamManager = $this->get('pde.teammanager');
        return new JsonResponse($teamManager->addMember($user, $request->get('email')));
    }

}