<?php

namespace WideBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use WideBundle\Entity\User;

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

}
