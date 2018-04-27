<?php

namespace App\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="App\CoreBundle\Repository\UserRepository")
 */
class User extends BaseUser
{


    /**
    * @ORM\Column(name="id", type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;


    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    //private $id;


    /**
     * Get id
     *
     * @return int
     */
    /*public function getId()
    {
        return $this->id;
    }*/
}

