<?php

namespace WideBundle\Controller\Security;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class SecurityController
 * Must be defined, even though it's empty, so that the login process can be initiated.
 *
 * @package WideBundle\Controller\Security
 */
class SecurityController extends Controller
{
    /**
     * The loginCheck action must be defined as an empty method, since all the login functionality
     * is handled by the GuardAuthenticator component.
     * @Route("/login_check", name="security_login_check")
     * @Method({"POST"})
     */
    public function loginCheckAction()
    {

    }
}