<?php

namespace PDEBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use PDEBundle\Entity\User;

class LoadUserData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * Loads the actual user data.
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = new User();
        $user->setUsername('admin');
        $user->setEmail('admin@domain.com');
        $user->setEnabled(1);
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        $this->addReference('admin', $user);
        $manager->persist($user);

        $user = new User();
        $user->setUsername('first_user');
        $user->setEmail('first_user@domain.com');
        $user->setEnabled(1);
        $user->setRoles();

        $this->addReference('first_user', $user);
        $manager->persist($user);

        $user = new User();
        $user->setUsername('sec_user');
        $user->setEmail('sec_user@domain.com');
        $user->setEnabled(1);
        $user->setRoles();

        $this->addReference('sec_user', $user);
        $manager->persist($user);

        $user = new User();
        $user->setUsername('user');
        $user->setEmail('user@domain.com');
        $user->setEnabled(1);
        $user->setRoles();

        $this->addReference('user', $user);
        $manager->persist($user);

        $manager->flush();
    }

    /**
     * Returns the order of this specific fixture class.
     * The lower the number, the sooner the data will be loaded.
     * In this case, the  user data must be loaded first.
     */
    public function getOrder()
    {
        return 1;
    }
}