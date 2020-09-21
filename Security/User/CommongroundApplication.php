<?php

// src/Security/User/WebserviceUser.php

namespace Conduction\CommonGroundBundle\Security\User;

use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CommongroundApplication implements UserInterface, EquatableInterface
{
    private $username;
    private $password;
    private $name;
    private $salt;
    private $roles;

    public function __construct(string $username = '', string $password = '', string $name = '', string $salt = null, array $roles = [], $locale = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->name = $name;
        $this->salt = $salt;
        $this->roles = $roles;
        $this->locale = $locale; // The language of this user
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this->name;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}
