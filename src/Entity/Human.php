<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\HumanRepository")
 */
class Human
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Groups({"list"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $lastname;


     /**
     * @ORM\Column(type="string", length=100, nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $organisation_name;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $organisation_definition;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $is_user;

    /**
     * @ORM\Column(type="integer", length=11, nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $user_id;

     /**
     * @ORM\Column(type="integer", length=11, nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $parent_human_id;

    
     /**
     * @ORM\Column(type="array")
     *
     * @Groups({"detail", "list"})
     */
    private $roles;


     /**
     * @ORM\Column(type="array", nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $appblocks;


    /**
     * @ORM\Column(type="string", length=100)
     *
     * @Groups({"detail", "list"})
     */
    private $mail;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $fullstreet;

    /**
     * @ORM\Column(type="integer", length=6, nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $postcode;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $town;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     *
     * @Groups({"detail", "list"})
     */
    private $country;

    
   
    public function getId()
    {
        return $this->id;
    }


    public function getFirstname()
    {
        return $this->firstname;
    }

    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }
    
    public function getLastname()
    {
        return $this->lastname;
    }

    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }
 
    public function getOrganisationName()
    {
        return $this->organisation_name;
    }

    public function setOrganisationName($organisation_name)
    {
        $this->organisation_name = $organisation_name;

        return $this;
    }

    public function getOrganisationDefinition()
    {
        return $this->organisation_definition;
    }

    public function setOrganisationDefinition($organisation_definition)
    {
        $this->organisation_definition = $organisation_definition;

        return $this;
    }

    public function getIsUser()
    {
        return $this->is_user;
    }

    public function setIsUser($is_user)
    {
        $this->is_user = $is_user;

        return $this;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getParentHumanId()
    {
        return $this->parent_human_id;
    }

    public function setParentHumanId($parent_human_id)
    {
        $this->parent_human_id = $parent_human_id;

        return $this;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    public function getAppblocks()
    {
        return $this->appblocks;
    }

    public function setAppblocks($appblocks)
    {
        $this->appblocks = $appblocks;

        return $this;
    }

    public function getMail()
    {
        return $this->mail;
    }

    public function setMail($mail)
    {
        $this->mail = $mail;

        return $this;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    public function getFullstreet()
    {
        return $this->fullstreet;
    }

    public function setFullstreet($fullstreet)
    {
        $this->fullstreet = $fullstreet;

        return $this;
    }

    public function getPostcode()
    {
        return $this->postcode;
    }

    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getTown()
    {
        return $this->town;
    }

    public function setTown($town)
    {
        $this->town = $town;

        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

}