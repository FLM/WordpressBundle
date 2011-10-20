<?php

/**
 * Contains the WordpressUser class, part of the Symfony WordPress Bundle
 *
 * @package    Hypebeast\WordpressBundle
 * @subpackage Entity
 * @author     Miquel Rodríguez Telep / Michael Rodríguez-Torrent <mike@themikecam.com>
 */

namespace Hypebeast\WordpressBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Hypebeast\WordpressBundle\Utilities\RoleUtilities;

/**
 * A wrapper of WordPress's WP_User class which implements Symfony's UserInterface
 *
 * @package    Hypebeast\WordpressBundle
 * @subpackage Entity
 * @author     Miquel Rodríguez Telep / Michael Rodríguez-Torrent <mike@themikecam.com>
 */
class WordpressUser extends \WP_User implements UserInterface
{
    
    public function getRoles()
    {
        return RoleUtilities::normalise_role_names($this->roles);
    }

    public function getPassword()
    {
        return $this->user_pass;
    }

    /**
     * Not implemented. The salt is embedded in the hashed password string, but we shouldn't ever 
     * need to extract it ourselves.
     */
    public function getSalt()
    {
        
    }

    public function getUsername()
    {
        return $this->user_login;
    }

    /**
     * This object doesn't store any sensitive data
     */
    public function eraseCredentials()
    {
        
    }

    public function equals(UserInterface $user)
    {
        return ($user instanceof WordpressUser)
                and ($this->ID === $user->ID)
                and ($this->getUsername() === $user->getUsername())
                and ($this->getPassword() === $user->getPassword());
    }

}