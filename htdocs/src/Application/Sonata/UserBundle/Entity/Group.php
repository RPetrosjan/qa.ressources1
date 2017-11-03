<?php

namespace Application\Sonata\UserBundle\Entity;

use Sonata\UserBundle\Entity\BaseGroup as BaseGroup;
use Doctrine\ORM\Mapping as ORM;

/**
 * Classe décrivant un groupe d'utilisateur
 *
 * @ORM\Table(name="fos_user_group")
 * @ORM\Entity(repositoryClass="Application\Sonata\UserBundle\Entity\GroupRepository")
 */
class Group extends BaseGroup
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Get id
     *
     * @codeCoverageIgnore
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }
}
