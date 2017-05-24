<?php

namespace WideBundle\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class RegistrationException
 * Thrown when a user cannot be registered to the system. Since the registration is part of the user authentication
 * an AuthenticationException will stop the process and redirect the user to the index page.
 *
 * @package WideBundle\Exception
 */
class RegistrationException extends AuthenticationException
{

}