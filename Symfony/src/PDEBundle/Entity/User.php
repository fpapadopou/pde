<?php

namespace PDEBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User
 *
 * @ORM\Table(name="User")
 * @ORM\Entity(repositoryClass="PDEBundle\Repository\UserRepository")
 */
class User implements UserInterface
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
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=25, unique=true)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=50, unique=true)
     */
    private $email;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @var string
     *
     * @ORM\Column(name="roles", type="string", length=255)
     */
    private $roles;

    /**
     * One or more Users belong to one Team. The onDelete option is applied on a the database tables.
     * This enables set the `team` property of all team members (if any) to NULL when a team is deleted.
     *
     * @var Team
     *
     * @ORM\ManyToOne(targetEntity="Team", inversedBy="members")
     * @ORM\JoinColumn(name="team_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $team;

    /**
     * User constructor
     *
     * @return mixed
     */
    public function __construct()
    {
        $this->created = new \DateTime('now');
        return $this;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username.
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets a user's email address.
     *
     * @param string $email
     *
     * @return User
     * @throws \InvalidArgumentException
     */
    public function setEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address.');
        }
        $this->email = $email;

        return $this;
    }

    /**
     * Returns the user's email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get creation date
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * No password stored in the application database.
     *
     * @return null
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * Sets the roles a user has in the application context.
     *
     * @param array $roles
     */
    public function setRoles(array $roles = ['ROLE_USER'])
    {
        $this->roles = json_encode($roles);
    }

    /**
     * Returns a user's roles.
     *
     * @return mixed
     */
    public function getRoles()
    {
        return json_decode($this->roles, true);
    }

    /**
     * Not used. Must be implemented because it's part of the UserInterface.
     *
     * @return $this
     */
    public function eraseCredentials()
    {
        return $this;
    }

    /**
     * The users' passwords are not stored in the database, so no salt is used.
     *
     * @return null
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Sets a value for the `enabled` property of the User.
     *
     * @param $isEnabled
     */
    public function setEnabled($isEnabled)
    {

        $this->enabled = $isEnabled;
    }

    /**
     * Returns the 'enabled' property of the User.
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Sets a user's team.
     *
     * @param Team
     * @return User
     */
    public function setTeam(Team $team = null)
    {
        $this->team = $team;
        return $this;
    }

    /**
     * Returns the user's team or null if they belong to no team.
     *
     * @return null|Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Returns whether a user has administrative privileges  or not.
     *
     * @return bool
     */
    public function isAdmin()
    {
        $roles = $this->getRoles();
        if (in_array('ROLE_ADMIN', $roles)) {
            return true;
        }
        return false;
    }
}

