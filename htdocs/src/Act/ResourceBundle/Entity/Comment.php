<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Application\Sonata\UserBundle\Entity\User;

/**
 * Comment Entity
 * A comment can be added to a project by any user.
 *
 * @ORM\Table(name="comment")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class Comment
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var text $content the comment itself
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * @var datetime $created creation date
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var User $user user who owns this comment
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User", inversedBy="comments")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @var Project $project the project linked to this comment
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Project", inversedBy="comments")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=true)
     */
    private $project;

    /**
     * Get id
     * @codeCoverageIgnore
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     * @codeCoverageIgnore
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set the project
     * @codeCoverageIgnore
     * @param Project $project
     */
    public function setProject(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Get the project
     * @codeCoverageIgnore
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Returns the comment owner
     * @codeCoverageIgnore
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Define the comment owner
     * @codeCoverageIgnore
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Set content
     * @codeCoverageIgnore
     * @param text $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get content
     * @codeCoverageIgnore
     * @return text
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set created
     * @codeCoverageIgnore
     * @param datetime $created
     */
    public function setCreated(\Datetime $created)
    {
        $this->created = clone $created;
    }

    /**
     * Get created
     * @codeCoverageIgnore
     * @return datetime
     */
    public function getCreated()
    {
        return clone $this->created;
    }

    /**
     * Called by Doctrine when entity is first persisted
     * @codeCoverageIgnore
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
    }

    /**
     * Display as a string
     * @codeCoverageIgnore
     * @return string
     */
    public function __toString()
    {
        if (strlen($this->content) > 100) {
            return substring($this->content, 0, 97).'...';
        } else {
            return $this->content;
        }
    }
}
