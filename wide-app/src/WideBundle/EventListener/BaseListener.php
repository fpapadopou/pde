<?php

namespace WideBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class BaseListener
 * @package WideBundle\EventListener
 */
class BaseListener
{
    /** @var TokenStorage $tokenStorage */
    protected $tokenStorage;
    /** @var Router $router */
    protected $router;

    /**
     * BaseListener constructor.
     *
     * @param TokenStorage $tokenStorage
     * @param Router $router
     */
    public function __construct(TokenStorage $tokenStorage, Router $router)
    {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    /**
     * Creates the Response object that will replace the default response of the object. If the request is an
     * ajax request from the application frontend, the response will be a JsonResponse, which can be handled by
     * the frontend. Otherwise, the user is redirected to the index page.
     *
     * @param Request $request
     * @param $message
     * @return JsonResponse|RedirectResponse
     */
    protected function createEventResponse(Request $request, $message)
    {
        if($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'error' => $message]);
        }

        $request->getSession()->getFlashBag()->add('error', $message);
        return new RedirectResponse($this->router->generate('index_page'));
    }

    /**
     * Gets the controller of the current event.
     *
     * @param FilterControllerEvent $event
     * @return mixed|null
     */
    protected function getEventController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        // Controllers must be in array format
        if (!is_array($controller)) {
            return null;
        }

        return $controller[0];
    }
}
