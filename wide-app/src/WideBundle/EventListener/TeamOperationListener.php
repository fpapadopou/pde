<?php

namespace WideBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use VBee\SettingBundle\Manager\SettingDoctrineManager;
use WideBundle\Exception\ApplicationControlException;
use WideBundle\Controller\TeamOperationInterface;

/**
 * Class TeamOperationListener
 * @package WideBundle\EventListener
 */
class TeamOperationListener extends BaseListener
{
    /** @var int $teamsEnabled */
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
     * @param FilterControllerEvent $event
     * @throws ApplicationControlException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $this->getEventController($event);
        if (!($controller instanceof TeamOperationInterface)) {
            return;
        }

        if ($this->teamsEnabled != 1) {
            $message = 'Team changes are not allowed at the moment.';
            throw new ApplicationControlException($message);
        }
    }
}