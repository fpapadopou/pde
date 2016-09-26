<?php

namespace WideBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction()
    {
        return $this->render('WideBundle:Index:index.html.twig');
    }

    /**
     * @Route("/login", name="login")
     * @Method("POST")
     */
    public function loginAction()
    {
        return new JsonResponse(['success' => true, 'message' => 'User logged out.']);
    }

    /**
     * @Route("/logout", name="logout")
     * @Method("GET")
     */
    public function logoutAction()
    {
        return new JsonResponse(['success' => true, 'message' => 'User logged out.']);
    }
}
