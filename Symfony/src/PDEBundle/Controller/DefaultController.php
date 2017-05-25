<?php

namespace PDEBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use PDEBundle\Entity\User;

/**
 * Class DefaultController
 * @package PDEBundle\Controller
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

        if (is_a($user, 'PDEBundle\Entity\User')) {
            /** @var Router $router */
            $router = $this->get('router');
            return new RedirectResponse($router->generate('account_page'));
        }

        // Since the login form is rendered in the index page, all data from the last failed login attempt (if any),
        // such as the login error, should be rendered along with the page.
        /** @var \Symfony\Component\Security\Http\Authentication\AuthenticationUtils $helper */
        $helper = $this->get('security.authentication_utils');

        return $this->render(
            'PDEBundle:Index:index.html.twig',
            [
                // last username entered by the user (if any)
                'last_username' => $helper->getLastUsername(),
                // last authentication error (if any)
                'last_error' => $helper->getLastAuthenticationError()
            ]
        );
    }

    /**
     * Renders the help page. Publicly accessible page.
     *
     * @Route("/help", name="help_page")
     * @Method({"GET"})
     */
    public function helpAction()
    {
        $parameters = [];
        /** @var User $user */
        $user = $this->getUser();

        if (is_a($user, 'PDEBundle\Entity\User')) {
            $parameters['username'] = $user->getUsername();
        }
        return $this->render('PDEBundle:Help:help.html.twig', $parameters);
    }
}
