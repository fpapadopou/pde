<?php

namespace PDEBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use PDEBundle\Entity\Team;
use PDEBundle\Entity\User;

class LoadTeamData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * Loads team data.
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var User $first_user */
        $first_user = $this->getReference('first_user');
        $team = new Team(3);
        $team->setTeamFolder('/tmp');
        $team->addMember($first_user);

        $this->addReference('team_one', $team);
        $manager->persist($team);
        $manager->flush();
    }

    /**
     * Returns the order of this specific fixture class.
     * Team data is loaded right after user data has been loaded.
     */
    public function getOrder()
    {
        return 2;
    }
}