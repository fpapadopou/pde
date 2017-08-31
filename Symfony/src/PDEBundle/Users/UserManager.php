<?php

namespace PDEBundle\Users;

use Doctrine\ORM\EntityManager;
use PDEBundle\Entity\User;

/**
 * Class UserManager
 * User management related operations.
 *
 * @package PDEBundle\Teams
 */
class UserManager
{
    /** @var EntityManager $entityManager **/
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Deletes users from the system database. The users must be provided in an array.
     *
     * @param array $users
     * @return array
     */
    public function deleteUsers($users)
    {
        try {
            foreach ($users as $user) {
                $this->entityManager->remove($user);
            }
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => 'Failed to delete one or more users ' . $exception->getMessage()];
        }
        return ['success' => true];
    }
}