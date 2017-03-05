<?php

namespace WideBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User
 *
 * @ORM\Table(name="User")
 * @ORM\Entity(repositoryClass="WideBundle\Repository\UserRepository")
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
     * @ORM\Column(name="working_directory_path", type="string", length=255)
     */
    private $workingDirectoryPath;

    /**
     * @var string
     *
     * @ORM\Column(name="roles", type="string", length=255)
     */
    private $roles;

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
     * Creates a user's working directory, where their workspaces are stored.
     */
    public function setWorkingDirectory()
    {
        $this->workingDirectoryPath = md5($this->username);

        return $this;
    }

    /**
     * Returns a user's working directory.
     * @return string
     */
    public function getWorkingDirectory()
    {
        return $this->workingDirectoryPath;
    }

    /**
     * No password stored in the application database.
     * @return null
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * Sets the roles a user has in the application context.
     * @param array $roles
     */
    public function setRoles(array $roles = ['ROLE_USER'])
    {
        $this->roles = json_encode($roles);
    }

    /**
     * Returns a user's roles.
     * @return mixed
     */
    public function getRoles()
    {
        return json_decode($this->roles, true);
    }

    /**
     * Not used. Must be implemented because it's part of the UserInterface.
     * @return $this
     */
    public function eraseCredentials()
    {
        return $this;
    }

    /**
     * The users' passwords are not stored in the database, so no salt is used.
     * @return null
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Sets a value for the `enabled` property of the User.
     * @param $isEnabled
     */
    public function setEnabled($isEnabled)
    {

        $this->enabled = $isEnabled;
    }

    /**
     * Returns the 'enabled' property of the User.
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
}

