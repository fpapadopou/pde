<?php

namespace WideBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use WideBundle\Setting\SettingHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class SettingController
 * Updates configurable settings (only available to admins).
 *
 * @package WideBundle\Controller
 * @Route("/setting")
 */
class SettingController extends Controller
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
        $settingHandler = $this->get('wide.setting.handler');

        return new JsonResponse($settingHandler->updateSetting($setting, $value));
    }

}
