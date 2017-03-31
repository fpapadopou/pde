<?php

namespace WideBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use VBee\SettingBundle\Entity\Setting;
use VBee\SettingBundle\Manager\SettingDoctrineManager;
use WideBundle\Entity\User;
use WideBundle\Entity\Team;

/**
 * Class DefaultController
 * @package WideBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * Renders the landing page of the application. If a user previously tried to
     * log in but failed, the login error will be rendered too.
     *
     * @Route("/", name="index_page")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        if (is_a($user, 'WideBundle\Entity\User')) {
            /** @var Router $router */
            $router = $this->get('router');
            return new RedirectResponse($router->generate('account_page'));
        }

        // Since the login form is rendered in the index page, all data from the last failed login attempt (if any),
        // such as the login error, should be rendered along with the page.
        /** @var \Symfony\Component\Security\Http\Authentication\AuthenticationUtils $helper */
        $helper = $this->get('security.authentication_utils');

        return $this->render(
            'WideBundle:Index:index.html.twig',
            [
                // last username entered by the user (if any)
                'last_username' => $helper->getLastUsername(),
                // last authentication error (if any)
                'last_error' => $helper->getLastAuthenticationError()
            ]
        );
    }

    /**
     * Renders the user account page.
     *
     * @Route("/account", name="account_page")
     * @Method({"GET"})
     *
     * @return Response
     */
    public function accountPageAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Team $Team */
        $team = $user->getTeam();
        $hasTeam = false;
        if ($team !== null) {
            $hasTeam = true;
        }

        return $this->render(
            'WideBundle:Accounts:account.html.twig',
            [
                'username' => $user->getUsername(),
                'has_team' => $hasTeam,
                'email' => $user->getEmail(),
                'team' => $user->getTeam()->getMembersEmails(),
                'deadline' => 'April 1st 2017' // TODO Add configurable setting
            ]
        );
    }

    /**
     * Renders the editor of the application.
     *
     * @Route("/editor", name="editor")
     * @Method("GET")
     *
     * @return Response
     */
    public function editorAction()
    {
        /** @var User $user */
        $user = $this->getUser();
        // A user must have a team before they can use the editor.
        if ($user->getTeam() === null) {
            return $this->redirect($this->generateUrl('account_page'));
        }

        return $this->render(
            'WideBundle:Editor:editor.html.twig',
            ['username' => $user->getUsername()]
        );
    }

    /**
     * Renders the admin panel, where an admin can modify some application settings.
     *
     * @Route("/admin-panel", name="admin_panel")
     * @Method("GET")
     *
     * @return Response
     */
    public function adminPanelAction()
    {
        /** @var User $user */
        $user = $this->getUser();
        // Reject non admin users.
        if (!$user->isAdmin()) {
            $this->addFlash('error', 'You are not allowed to access this page.');
            return $this->redirect($this->generateUrl('account_page'));
        }

        /** @var SettingDoctrineManager $settingsManager */
        $settingsManager = $this->get('vbee.manager.setting');
        $settingsEntities = $settingsManager->all();
        $settings = [];
        foreach ($settingsEntities as $setting) {
            /** @var Setting $setting */
            $settings[] = [
                'name' => $setting->getName(),
                'value' => $setting->getValue(),
                'description' => $setting->getDescription(),
                'type' => $setting->getType()
            ];
        }

        return $this->render(
            'WideBundle:AdminPanel:admin_panel.html.twig',
            ['username' => $user->getUsername(), 'settings' => $settings]
        );
    }
}
