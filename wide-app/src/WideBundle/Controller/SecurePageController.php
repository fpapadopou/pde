<?php

namespace WideBundle\Controller;

use WideBundle\Entity\User;
use WideBundle\Entity\Team;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use VBee\SettingBundle\Entity\Setting;
use VBee\SettingBundle\Manager\SettingDoctrineManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class SecurePageController
 * @package WideBundle\Controller
 */
class SecurePageController extends Controller implements SecureResourceInterface
{
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