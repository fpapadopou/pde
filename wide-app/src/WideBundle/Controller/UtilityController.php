<?php

namespace WideBundle\Controller;

use WideBundle\Entity\User;
use WideBundle\Entity\Team;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use WideBundle\Utility\UtilityHandler;

/**
 * Class UtilityController
 * Serves requests related to using any of the Flex/Bison/Gcc system tools.
 *
 * @package WideBundle\Controller
 * @Route("/utility")
 */
class UtilityController extends Controller implements SecureResourceInterface, TeamResourceInterface
{
    /**
     * All bison/flex/gcc/simulation requests are handled here.
     *
     * @Route("/", name="use_utility")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function utilityAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Team $team */
        $team = $user->getTeam();

        /** @var UtilityHandler $utilityHandler */
        $utilityHandler = $this->get('wide.utility.handler');
        $result = $utilityHandler->useUtility(
            $team->getTeamFolder(),
            $request->get('workspace'),
            $request->get('files'),
            $request->get('utility'),
            $request->get('input')
        );

        return new JsonResponse($result);
    }
}
