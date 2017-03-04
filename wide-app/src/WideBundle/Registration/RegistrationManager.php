<?php

namespace WideBundle\Registration;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use WideBundle\Entity\User;
use WideBundle\FileSystemUtilities\StorageManager;

/**
 * Class RegistrationManager
 * Handles users' registration
 * @package WideBundle\Registration
 */
class RegistrationManager
{
    /** @var EntityManager $entityManager */
    private $entityManager;

    /** @var Logger $logger */
    private $logger;

    /** @var StorageManager $storageManager */
    private $storageManager;

    /**
     * RegistrationManager constructor.
     * @param EntityManager $entityManager
     * @param Logger $logger
     * @param StorageManager $storageManager
     */
    public function __construct(EntityManager $entityManager, Logger $logger, StorageManager $storageManager)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->storageManager = $storageManager;
    }

    /**
     * Creates a User object based on the provided credentials. The user will be persisted to the database
     * during a latter step.
     * @param $credentials
     * @return bool|User
     */
    public function createUser($credentials)
    {
        // Create the user and set its properties
        $user = new User();
        // If any of the properties is invalid, the EntityManager will throw an exception
        try {
            $user->setUsername($credentials['username']);
            $user->setEmail($credentials['email']);
            $user->setEnabled(false);
            $user->setRoles(); // default roles used
            $user->setWorkingDirectory(); // the directory is set internally
        } catch (\Exception $exception) {
            $this->logger->addError('User creation failed - ' . $exception->getMessage());
            return false;
        }

        return $user;
    }

    /**
     * Finalizes an account, creating its directory and flushing the user object to the database.
     * @param User $user
     * @return bool
     */
    public function persistUser(User $user)
    {
        // Enable the user before moving on
        $user->setEnabled(true);

        // Create a dedicated directory for the user
        $directory = $user->getWorkingDirectory();
        if ($this->storageManager->createUserDirectory($directory) === false) {
            $this->logger->addError('Failed to create user directory for ' . $user->getUsername());
            return false;
        }

        // Flush the user's data to the db
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return true;
    }
}