<?php

namespace WideBundle\EventListener;

use VBee\SettingBundle\Manager\SettingDoctrineManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use WideBundle\Exception\ApplicationControlException;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use WideBundle\Controller\EditableResourceInterface;

/**
 * Class EditableResourceListener
 * Rejects workspace/file creation and modification operations when they are disabled by the
 * application's configuration.
 *
 * @package WideBundle\EventListener
 */
class EditableResourceListener extends BaseListener
{
    /** @var int $editsEnabled */
    private $editsEnabled;

    /**
     * EditableResourceListener constructor.
     * @param SettingDoctrineManager $settingsManager
     * @param TokenStorage $tokenStorage
     * @param Router $router
     */
    public function __construct(SettingDoctrineManager $settingsManager, TokenStorage $tokenStorage, Router $router)
    {
        parent::__construct($tokenStorage, $router);
        $this->editsEnabled = $settingsManager->get('edit_operations_enabled');
    }

    /**
     * Rejects workspace/file operations if the corresponding setting is active.
     *
     * @param FilterControllerEvent $event
     * @throws ApplicationControlException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $this->getEventController($event);
        if (!($controller instanceof EditableResourceInterface)) {
            return;
        }

        if ($this->editsEnabled != 1) {
            $message = 'Workspace and file modifications are disabled.';
            throw new ApplicationControlException($message);
        }
    }
}