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
use WideBundle\Search\SearchManager;
use Knp\Component\Pager\Paginator;

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
     *
     * @Route("/utility/{team}", name="admin_utility", requirements={"team": "\d+"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function useUtility(Request $request)
    {
        return new JsonResponse(['success' => false, 'error' => 'Not implemented yet.']);
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
        return new JsonResponse(['success' => false, 'error' => 'Admins cannot modify user content.']);
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

        return $this->render('WideBundle:AdminPanel:search.html.twig', ['pagination' => $pagination]);
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
