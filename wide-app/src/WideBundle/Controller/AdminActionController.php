<?php

namespace WideBundle\Controller;

use WideBundle\Entity\User;
use WideBundle\Entity\Team;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use WideBundle\FileSystemHandler\FileHandler;
use WideBundle\Search\SearchManager;
use Knp\Component\Pager\Paginator;
use WideBundle\Utility\UtilityHandler;

/**
 * Class AdminActionController
 *
 * @package WideBundle\Controller
 * @Route("/admin")
 */
class AdminActionController extends BaseController implements SecureResourceInterface, AdminResourceInterface
{
    /**
     * Loads the editor with administrative privileges. An admin can load any team's content in the editor.
     *
     * @Route("/editor/{team}", name="admin_editor", requirements={"team": "\d+"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function editorAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Team $team */
        $team = $this->getTeam($request->get('team'));
        if ($team === null) {
            $this->addFlash('error', 'Team does not exist. Cannot load editor.');
            return $this->redirect($this->generateUrl('account_page'));
        }

        return $this->render(
            'WideBundle:Editor:editor.html.twig',
            ['username' => $user->getUsername(), 'team' => $team->getId()]
        );
    }

    /**
     * Returns the workspaces of the specified team. An admin has access to all users' workspaces.
     *
     * @Route("/workspaces/{team}", name="admin_get_wspaces", requirements={"team": "\d+"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWorkspacesAction(Request $request)
    {
        /** @var Team $team */
        $team = $this->getTeam($request->get('team'));
        if ($team === null) {
            return new JsonResponse(['success' => false, 'error' => 'Cannot find the specified team.']);
        }

        return new JsonResponse($this->getTeamWorkspaces($team));
    }

    /**
     * Allows an admin to download an zip archive with any team's workspace.
     *
     * @Route("/download/{team}", name="admin_download_wspace", requirements={"team": "\d+"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return Response
     */
    public function downloadAction(Request $request)
    {
        /** @var Team $team */
        $team = $this->getTeam($request->get('team'));
        if ($team === null) {
            $this->addFlash('error', 'Team does not exist. Cannot download workspace.');
            return $this->redirect($this->generateUrl('account_page'));
        }

        try {
            $zipFile = $this->getDownloadContent($team, $request->get('workspace'));
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), 500);
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-disposition', 'attachment; filename=' . $zipFile['name']);
        $response->headers->set('Content-Length', $zipFile['length']);
        $response->sendHeaders();
        $response->setContent($zipFile['content']);
        return $response;
    }

    /**
     * Allows an admin to use bison/flex/gcc or a parser within another user's workspace. No changes are saved in the
     * workspace.
     *
     * @Route("/utility/{team}", name="admin_utility", requirements={"team": "\d+"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function useUtility(Request $request)
    {
        /** @var Team $team */
        $team = $this->getTeam($request->get('team'));
        if ($team === null) {
            return new JsonResponse(['success' => false, 'error' => 'The specified team does not exist.']);
        }
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

    /**
     * Workspace creation/deletion/modification operations from the admin editor will be handled by this
     * dummy route. An admin will not be able to modify the contents of a workspace.
     *
     * @Route("/dummy", name="admin_dummy")
     * @Method({"GET","POST","PUT","DELETE"})
     *
     * @return JsonResponse
     */
    public function dummyAction()
    {
        $message = 'This function is not implemented. As an admin you can only create temporary files which will be deleted after this session.';
        return new JsonResponse(['success' => false, 'error' => $message]);
    }

    /**
     * Dummy file create action. The file is actually created only in the frontend, which is fine when an admin
     * is using the editor. This is the least invasive way to allow admins to create files when viewing a user's
     * workspace.
     *
     * @Route("/create-file", name="admin_create_file")
     * @Method({"POST"})
     *
     * @return JsonResponse
     */
    public function createFileAction(Request $request)
    {
        /** @var FileHandler $fileHandler */
        $fileHandler = $this->get('wide.file.handler');
        try {
            $fileHandler->validateFilename($request->get('filename'));
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'error' => $exception->getMessage()]);
        }
        $content = "This file has not been stored on the server. It will be lost once you close this tab. \n";
        $content .= "If you did not intend to create this file, just reload the current page.\n";
        return new JsonResponse(['success' => true, 'content' => $content]);
    }

    /**
     * Implements team search. Search criteria are optional - by default all teams are returned (paginated).
     *
     * @Route("/search", name="admin_search")
     * @Method({"GET"})
     *
     * @param Request $request
     * @return Response
     */
    public function searchAction(Request $request)
    {
        // Optional parameters fetched from querystring
        $email = $request->query->get('email', '');
        $date = $request->query->get('date', '');

        /** @var SearchManager $searchManager */
        $searchManager = $this->get('wide.search.manager');
        $searchResult = $searchManager->createTeamSearchQuery($email, $date);
        if ($searchResult['success'] !== true) {
            $this->addFlash('error', $searchResult['error']);
            return $this->redirect($this->generateUrl('account_page'));
        }

        /** @var Paginator $paginator */
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $searchResult['query'],
            $request->query->getInt('page', 1),
            10
        );

        /** @var User $user */
        $user = $this->getUser();
        return $this->render(
            'WideBundle:Search:search.html.twig',
                ['username' => $user->getUsername(),'pagination' => $pagination]
        );
    }

    /**
     * Returns the contents of the specified logfile.
     *
     * @Route("/log", name="admin_get_log")
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLogfileAction(Request $request)
    {
        return new JsonResponse(['success' => false, 'error' => 'Not implemented yet.']);
    }

    /**
     * Fetches a team from the database.
     *
     * @param $teamId
     * @return null|Team
     */
    private function getTeam($teamId)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        try {
            /** @var Team $team */
            $team = $entityManager->getRepository('WideBundle:Team')->find($teamId);
        } catch (\Exception $exception) {
            return null;
        }

        return $team;
    }
}
