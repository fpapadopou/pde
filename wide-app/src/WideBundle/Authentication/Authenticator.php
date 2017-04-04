<?php

namespace WideBundle\Authentication;

use Symfony\Component\Security\Guard\GuardAuthenticatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\Security;
use VBee\SettingBundle\Manager\SettingDoctrineManager;
use WideBundle\Entity\User;
use WideBundle\Registration\RegistrationManager;

/**
 * Class Authenticator
 *
 * Implements methods necessary for user authentication. That is the
 * class can handle login and "registration" requests. Registration is not a discrete action,
 * but occurs when a user successfully logs in for the first time.
 *
 * @package WideBundle\Authentication
 */
class Authenticator implements GuardAuthenticatorInterface
{
    /** @var  WebmailAuthenticator $webmailAuthenticator */
    private $webmailAuthenticator;

    /** @var  Router $router */
    private $router;

    /** @var RegistrationManager $registrationManager */
    private $registrationManager;

    /** The domain of the email addresses used during authentication */
    private $webmailDomain;

    /** @var bool $registrationAllowed */
    private $registrationAllowed;

    /**
     * Authenticator constructor.
     * @param WebmailAuthenticator $webmailAuthenticator
     * @param Router $router
     * @param RegistrationManager $registrationManager
     * @param SettingDoctrineManager $settingsManager
     */
    public function __construct(
        WebmailAuthenticator $webmailAuthenticator,
        Router $router,
        RegistrationManager $registrationManager,
        SettingDoctrineManager $settingsManager
    )
    {
        $this->webmailAuthenticator = $webmailAuthenticator;
        $this->router = $router;
        $this->registrationManager = $registrationManager;
        $this->webmailDomain = $settingsManager->get('webmail_domain');
        $this->registrationAllowed = true;
        if ($settingsManager->get('registrations_enabled') != 1) {
            $this->registrationAllowed = false;
        }
    }

    /**
     * Method called when a user tries to access a secure page without being logged in.
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        // Redirect to homepage
        $target = $this->router->generate('index_page');
        return new RedirectResponse($target);
    }

    /**
     * Handles the credentials submitted to the login form and passes them to the next method.
     * @param Request $request
     * @return array|null
     */
    public function getCredentials(Request $request)
    {
        $loginCheckRoute = $this->router->generate('security_login_check');
        // TODO: Should `getPathInfo` or `getRequestUri` be used? `getRequestUri` matched the env too (app_dev)
        if ($request->getRequestUri() != $loginCheckRoute) {
            return null;
        }

        // Get the user's credentials from the form
        $email = $request->request->get('_email');
        $password = $request->request->get('_password');

        $username = str_replace('@' . $this->webmailDomain, '', $email);
        // Store the last used username. Will be used in case an error occurs
        $request->getSession()->set(Security::LAST_USERNAME, $username);
        return [
            'username' => $username,
            'email' => $email,
            'password' => $password
        ];
    }

    /**
     * This method is intended to retrieve the user from the database by their username and then pass the User
     * object to the next method. Since no database is used for authentication, the method either returns
     * an existing user or attempts to create a new one.
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return UserInterface
     * @throws \ErrorException|AuthenticationException
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            // If the user exists, fetch the respective object from the database
            return $userProvider->loadUserByUsername($credentials['username']);
        } catch (\Exception $exception) {
            if ($this->registrationAllowed !== true) {
                throw new \ErrorException('Registrations are disabled. Cannot create account.');
            }
            // The user is trying to authenticate for the first time
            $newUser = $this->registrationManager->createUser($credentials);
            if ($newUser === false) {
                throw new AuthenticationException('Failed to create your account. Try again.');
            }
            return $newUser;
        }
    }

    /**
     * Makes sure the provided password matches the user's email. If the user is new, finalize the account creation.
     * @param mixed $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        // If the authentication fails, an exception is thrown
        $this->webmailValidation($credentials['email'], $credentials['password']);

        // If the user has just been created, finalize the process
        /** @var User $user */
        if ($user->getEnabled() === false && $this->registrationManager->persistUser($user) === false) {
            throw new AuthenticationException('Failed to create your account. Try again.');
        }

        return true;
    }

    /**
     * When the authentication is completed, a new token is created containing the user's data.
     * @param UserInterface $user
     * @param string $providerKey
     * @return PostAuthenticationGuardToken
     */
    public function createAuthenticatedToken(UserInterface $user, $providerKey)
    {
        return new PostAuthenticationGuardToken(
            $user,
            $providerKey,
            $user->getRoles()
        );
    }

    /**
     * Authentication failure handler.
     * @param Request $request
     * @param AuthenticationException $exception
     * @return RedirectResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception->getMessage());
        // If the authentication process fails, redirect the user to the index page.
        $target = $this->router->generate('index_page');
        return new RedirectResponse($target);
    }

    /**
     * Authentication success handler.
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // If the authentication process is successful, redirect the user to the user's account page.
        $target = $this->router->generate('account_page');
        return new RedirectResponse($target);
    }

    /**
     * No "remember me" option is used in this app
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * Does the actual authentication with the user's mailbox.
     * @param $email
     * @param $password
     * @return bool
     * @throws AuthenticationException
     */
    private function webmailValidation($email, $password)
    {
        if ($this->webmailAuthenticator->validateCredentials($email, $password) !== true) {
            throw new AuthenticationException('Check your email & password.');
        }

        return true;
    }
}