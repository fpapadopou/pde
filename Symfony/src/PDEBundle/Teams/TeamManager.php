<?php

namespace PDEBundle\Teams;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use PDEBundle\Entity\User;
use PDEBundle\Entity\Team;
use PDEBundle\FileSystemHandler\DirectoryHandler;
use VBee\SettingBundle\Manager\SettingDoctrineManager;

/**
 * Class TeamManager
 * Implements team-related operations. Handles teams space allocation and de-allocation.
 *
 * @package PDEBundle\Teams
 */
class TeamManager
{
    /** @var EntityManager $entityManager */
    private $entityManager;

    /** @var DirectoryHandler $directoryHandler */
    private $directoryHandler;

    /** @var Logger $logger */
    private $logger;

    /** @var string $storageRoot */
    private $storageRoot;

    /** @var int $maxTeamMembers */
    private $maxTeamMembers;

    /**
     * TeamController constructor.
     *
     * @param EntityManager $entityManager
     * @param DirectoryHandler $directoryHandler
     * @param Logger $logger
     * @param SettingDoctrineManager $settingsManager
     * @throws \ErrorException
     */
    public function __construct(
        EntityManager $entityManager,
        DirectoryHandler $directoryHandler,
        Logger $logger,
        SettingDoctrineManager $settingsManager
    )
    {
        $this->entityManager = $entityManager;
        $this->directoryHandler = $directoryHandler;
        $this->logger = $logger;
        $storageRoot = $settingsManager->get('storage_root');
        // Create the directory if it does not already exist
        if (!is_dir($storageRoot) && !mkdir($storageRoot, 0755)) {
            $this->logger->addCritical('Failed to create storage for team spaces.');
            throw new \ErrorException('Failed to create application file system.');
        }
        $this->storageRoot = $storageRoot;
        $this->maxTeamMembers = $settingsManager->get('team_members');
    }

    /**
     * Creates a new team and sets the provided user as a member to the new team.
     *
     * @param User $user
     * @return array
     */
    public function createTeam(User $user)
    {
        if ($user->getTeam() !== null) {
            return ['success' => false, 'error' => 'You already have a team.'];
        }

        /** @var Team $team */
        $team = new Team($this->maxTeamMembers);
        try {
            $team->addMember($user);
            $folderName = md5($user->getUsername()) . rand(10000, 99999);
            $this->directoryHandler->createDirectory($this->storageRoot, $folderName, false);
            $team->setTeamFolder($this->storageRoot . DIRECTORY_SEPARATOR . $folderName);
        } catch (\Exception $exception) {
            $this->logger->addError('Team space creation failed - ' . $exception->getMessage());
            return ['success' => false, 'error' => 'Team creation failed. Try again.'];
        }

        $this->entityManager->persist($team);
        $this->entityManager->flush();

        return ['success' => true];
    }

    /**
     * Performs the actual deletion of a team. Removes the team from the database and all of its files from the
     * file system.
     *
     * @param Team $team
     * @return array
     */
    public function deleteTeam(Team $team)
    {
        try {
            $this->directoryHandler->deleteDirectory($team->getTeamFolder());
        } catch (\Exception $exception) {
            $this->logger->addError(
                'Failed to delete directory of team ' . $team->getId() . ' with error: ' . $exception->getMessage()
            );
            return ['success' => false, 'error' => 'Failed to delete team. Try again.'];
        }

        $this->entityManager->remove($team);
        $this->entityManager->flush();

        return ['success' => true];
    }

    /**
     * Removes the provided user from their team (if any). If no team members are left in the team after the operation,
     * the team is deleted.
     *
     * @param User $user
     * @return array
     */
    public function leaveTeam(User $user)
    {
        /** @var Team $team */
        $team = $user->getTeam();
        if ($team === null) {
            return ['success' => false, 'error' => 'Your account has no team.'];
        }

        $team->removeMember($user);
        if ($team->getMembersCount() == 0) {
            return $this->deleteTeam($team);
        }
        $this->entityManager->persist($team);
        $this->entityManager->flush();

        return ['success' => true];
    }

    /**
     * Adds a new member to the provided user's team. The new user is retrieved by their email address.
     *
     * @param User $user
     * @param $emailAddress
     * @return array
     */
    public function addMember(User $user, $emailAddress)
    {
        /** @var Team $team */
        $team = $user->getTeam();
        if ($team === null) {
            return ['success' => false, 'error' => 'Your account has no team.'];
        }

        /** @var User $userToAdd */
        $userToAdd = $this->entityManager->getRepository('PDEBundle:User')
            ->findOneBy(['email' => $emailAddress, 'enabled' => 1]);
        if ($userToAdd === null) {
            return ['success' => false, 'error' => 'Can\'t find the specified user.'];
        }

        try {
            $team->addMember($userToAdd);
            $this->entityManager->persist($team);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        return ['success' => true];
    }
}