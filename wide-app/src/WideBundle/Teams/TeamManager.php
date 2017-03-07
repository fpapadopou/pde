<?php

namespace WideBundle\Teams;

use Doctrine\ORM\EntityManager;
use WideBundle\FileSystemUtilities\StorageManager;
use Monolog\Logger;
use WideBundle\Entity\User;
use WideBundle\Entity\Team;

/**
 * Class TeamManager
 * Implements team-related operations.
 *
 * @package WideBundle\Teams
 */
class TeamManager
{
    /** @var EntityManager $entityManager */
    private $entityManager;

    /** @var StorageManager $storageManager */
    private  $storageManager;

    /** @var Logger $logger */
    private $logger;

    /**
     * TeamController constructor.
     * @param EntityManager $entityManager
     * @param StorageManager $storageManager
     * @param Logger $logger
     */
    public function __construct(EntityManager $entityManager, StorageManager $storageManager, Logger $logger)
    {
        $this->entityManager = $entityManager;
        $this->storageManager = $storageManager;
        $this->logger = $logger;
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
        $team = new Team();
        try {
            $team->addMember($user);
            $team->setTeamFolder($this->storageManager->createTeamSpace($user->getUsername()));
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
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
        if ($team->getMembersCount() > 1) {
            return [
                'success' => false,
                'error' => 'The rest of the members must leave the team before it can be deleted.'
            ];
        }
        if ($this->storageManager->deleteTeamSpace($team->getTeamFolder()) !== true) {
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
        $userToAdd = $this->entityManager->getRepository('WideBundle:User')
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