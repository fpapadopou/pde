<?php

namespace WideBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use VBee\SettingBundle\Manager\SettingDoctrineManager;

/**
 * Class TeamOperationListener
 * @package WideBundle\EventListener
 */
class TeamOperationListener extends BaseListener
{
    /** @var bool $teamsEnabled */
    private $teamsEnabled;

    /**
     * TeamOperationListener constructor.
     * @param SettingDoctrineManager $settingsManager
     * @param TokenStorage $tokenStorage
     * @param Router $router
     */
    public function __construct(SettingDoctrineManager $settingsManager, TokenStorage $tokenStorage, Router $router)
    {
        parent::__construct($tokenStorage, $router);
        $this->teamsEnabled = $settingsManager->get('team_modifications_enabled');
    }

    /**
     * Rejects team operations if team modifications are not allowed by the application admin.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        /** @var Request $request */
        $request = $event->getRequest();
        $requestPathInfo = $request->getPathInfo();

        if (substr($requestPathInfo, 0, 5) != '/team') {
            return;
        }

        if ($this->teamsEnabled != 1) {
            $message = 'Team changes are not allowed at the moment.';
            $event->setResponse($this->createEventResponse($request, $message));
        }
    }
}