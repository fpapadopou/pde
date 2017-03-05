<?php

namespace WideBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Team
 * A group of users that own the same workspaces.
 *
 * @ORM\Table(name="Team")
 * @ORM\Entity(repositoryClass="WideBundle\Repository\TeamRepository")
 */
class Team
{
    /**
     * Teams are allowed to have up to 4 members.
     */
    const MAX_TEAM_MEMBER_COUNT = 4;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var string
     *
     * @ORM\Column(name="team_directory", type="string", length=255)
     */
    private $teamFolder;

    /**
     * A Team can have one or more members.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection $members
     *
     * @ORM\OneToMany(targetEntity="User", mappedBy="team")
     */
    private $members;

    /**
     * Team constructor.
     */
    public function __construct()
    {
        $this->created = new \DateTime('now');
        $this->members = new \Doctrine\Common\Collections\ArrayCollection();
        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns a team's creation date.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Sets the path where a team's workspaces will be stored.
     *
     * @param $path
     * @return $this
     */
    public function setTeamFolder($path)
    {
        if (!is_dir($path)) {
            throw  new \InvalidArgumentException('Invalid team path.');
        }

        $this->teamFolder = $path;
        return $this;
    }

    /**
     * Returns a team's folder path.
     *
     * @return string
     */
    public function getTeamFolder()
    {
        return $this->teamFolder;
    }

    /**
     * Returns the members of this team as an array.
     *
     * @return array
     */
    public function getMembers()
    {
        return $this->members->toArray();
    }

    /**
     * Returns the number of this team's members.
     *
     * @return int
     */
    public function getMembersCount()
    {
        return $this->members->count();
    }

    /**
     * Returns the team members' emails (if any).
     *
     * return array
     */
    public function getMembersEmails()
    {
        $addresses = [];
        /** @var User $user */
        foreach ($this->members as $user) {
            $addresses[] = $user->getEmail();
        }
        return $addresses;
    }

    /**
     * Adds a new member to this team.
     *
     * @param User $user
     * @return mixed
     * @throws \Exception;
     */
    public function addMember(User $user)
    {
        if ($this->members->count() == $this::MAX_TEAM_MEMBER_COUNT) {
            throw new \Exception('This team is full.');
        }

        if ($user->getTeam() !== null) {
            throw new \Exception('This user already has a team.');
        }

        $this->members->add($user);
        return $this;
    }

    /**
     * Removes a user from the team.
     *
     * @param User $user
     * @return mixed
     * @throws \Exception
     */
    public function removeMember(User $user)
    {
        if (!$this->members->contains($user)) {
            throw new \Exception('The specified user does not belong to this team.');
        }

        $this->members->removeElement($user);
        return $this;
    }
}

