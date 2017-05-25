<?php

namespace PDEBundle\Registration;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use PDEBundle\Entity\User;
use VBee\SettingBundle\Manager\SettingDoctrineManager;
use PDEBundle\Exception\RegistrationException;

/**
 * Class RegistrationManager
 * Handles users' registration
 * @package PDEBundle\Registration
 */
class RegistrationManager
{
    /** @var EntityManager $entityManager */
    private $entityManager;

    /** @var Logger $logger */
    private $logger;

    /** @var bool $registrationsEnabled */
    private $registrationsEnabled;

    /**
     * RegistrationManager constructor.
     * @param EntityManager $entityManager
     * @param Logger $logger
     */
    public function __construct(EntityManager $entityManager, Logger $logger, SettingDoctrineManager $settingsManager)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->registrationsEnabled = true;
        if ($settingsManager->get('registrations_enabled') != 1) {
            $this->registrationsEnabled = false;
        }
    }

    /**
     * Creates a User object based on the provided credentials. The user will be persisted to the database
     * during a latter step.
     * @param $credentials
     * @return bool|User
     * @throws \ErrorException
     */
    public function createUser($credentials)
    {
        if ($this->registrationsEnabled !== true) {
            throw new RegistrationException('Registrations are disabled. Cannot create account.');
        }

        // Create the user and set its properties
        $user = new User();
        // If any of the properties is invalid, the EntityManager will throw an exception
        try {
            $user->setUsername($credentials['username']);
            $user->setEmail($credentials['email']);
            $user->setEnabled(false);
            $user->setRoles(); // default roles used
        } catch (\Exception $exception) {
            $this->logger->addError('User creation failed - ' . $exception->getMessage());
            return false;
        }

        return $user;
    }

    /**
     * Finalizes an account, flushing the user object to the database.
     * @param User $user
     * @return bool
     */
    public function persistUser(User $user)
    {
        // Enable the user before moving on
        $user->setEnabled(true);
        // Flush the user's data to the db
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return true;
    }
}