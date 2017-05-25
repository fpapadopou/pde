<?php

namespace PDEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PDEBundle\Setting\SettingHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class SettingController
 * Updates configurable settings (only available to admins).
 *
 * @package PDEBundle\Controller
 * @Route("/setting")
 */
class SettingController extends Controller implements SecureResourceInterface, AdminResourceInterface
{
    /**
     * Updates a specified setting.
     *
     * @Route("/update", name="update_setting")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSettingAction(Request $request)
    {
        $setting = $request->get('setting');
        $value = $request->get('value');

        /** @var SettingHandler $settingHandler */
        $settingHandler = $this->get('pde.setting.handler');

        return new JsonResponse($settingHandler->updateSetting($setting, $value));
    }

}
