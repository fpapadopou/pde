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
     * Using cascade persist option in order to persists both Team and User objects at once,
     * whenever a user is added to or removed from a team.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection $members
     *
     * @ORM\OneToMany(targetEntity="User", mappedBy="team", cascade={"persist"})
     */
    private $members;

    /**
     * @var int
     *
     * @ORM\Column(name="max_members", type="integer")
     */
    private $maxMemberCount;

    /**
     * Team constructor.
     */
    public function __construct($maxMembers)
    {
        $this->maxMemberCount = $maxMembers;
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
        if ($this->members->count() == $this->maxMemberCount) {
            throw new \Exception('This team is full.');
        }

        if ($user->getTeam() !== null) {
            throw new \Exception('This specified user already has a team.');
        }

        $this->members->add($user);
        $user->setTeam($this);
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
        $user->setTeam(null);
        return $this;
    }
}

