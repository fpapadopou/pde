<?php

namespace PDEBundle\Controller;

use PDEBundle\Entity\User;
use PDEBundle\Entity\Team;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PDEBundle\CommandExecution\CommandManager;

/**
 * Class CommandController
 * Serves requests related to using any of the Flex/Bison/Gcc system tools.
 *
 * @package PDEBundle\Controller
 * @Route("/command")
 */
class CommandController extends Controller implements SecureResourceInterface, TeamResourceInterface
{
    /**
     * All bison/flex/gcc/simulation requests are handled here.
     *
     * @Route("/", name="run_command")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function commandAction(Request $request)
    {
        /** @var CommandManager $commandManager */
        $commandManager = $this->get('pde.command.manager');
        $result = $commandManager->runCommand(
            $request->get('files'),
            $request->get('options'),
            $request->get('utility'),
            $request->get('input')
        );

        return new JsonResponse($result);
    }
}
