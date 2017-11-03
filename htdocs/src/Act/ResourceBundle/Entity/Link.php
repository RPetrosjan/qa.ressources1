<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Link Entity
 * A link can be a file or an URL and can be added to a project.
 *
 * @ORM\Table(name="link")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Link
{
    const LINK_URL = 1;
    const LINK_FILE = 2;
    const LINK_URL_INTERNAL = 3;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $name the link name
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=45)
     */
    private $name;

    /**
     * @var string $url the link URL
     *
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;

    /**
     * @var integer $type the type of link
     *
     * @ORM\Column(name="type", type="integer")
     */
    private $type;

    /**
     * @var Project $project the project linked to this link
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Project", inversedBy="links")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    private $project;

    /**
     * Used for file uploads
     * @Assert\File(maxSize="6000000")
     */
    private $file;

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
     * Set the project
     * @codeCoverageIgnore
     * @param Project $project
     */
    public function setProject(Project $project)
    {
        $this->project = $project;
    }

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
     * Set name
     * @codeCoverageIgnore
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     * @codeCoverageIgnore
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set url
     * @codeCoverageIgnore
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get url
     * @codeCoverageIgnore
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the link type
     * @codeCoverageIgnore
     * @param $type
     * @throws \Exception
     */
    public function setType($type)
    {
        if ($type != self::LINK_URL && $type != self::LINK_FILE) {
            throw new \Exception('Only URL and File types are allowed');
        } else {
            $this->type = $type;
        }
    }

    /**
     * Get type
     * @codeCoverageIgnore
     * @return string
     */
    public function getType()
    {
        if ($this->type == self::LINK_URL && !$this->isExternalLink()) {
            return self::LINK_URL_INTERNAL;
        } else {
            return $this->type;
        }
    }

    /**
     * Executed by Doctrine before persist and before update
     * @codeCoverageIgnore
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        // If type = File and the file is not null
        if ($this->type == self::LINK_FILE && null !== $this->file) {
            // Generate unique name for the file
            $this->url = uniqid().'.'.$this->file->guessExtension();
        }
    }

    /**
     * Executed by Doctrine after persist and after update
     * @codeCoverageIgnore
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null === $this->file) {
            return;
        }

        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->file->move($this->getUploadRootDir(), $this->url);

        unset($this->file);
    }

    /**
     * Executed by Doctrine after deletion
     * @codeCoverageIgnore
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        // Delete the file
        if ($file = $this->getAbsolutePath()) {
            @unlink($file);
        }
    }

    /**
     * Get the absolute path to the file
     * @codeCoverageIgnore
     * @return null|string
     */
    public function getAbsolutePath()
    {
        return null === $this->url ? null : $this->getUploadRootDir().'/'.$this->url;
    }

    /**
     * Get the web path to the file
     * @codeCoverageIgnore
     * @return null|string
     */
    public function getWebPath()
    {
        return null === $this->url ? null : '/'.$this->getUploadDir().'/'.$this->url;
    }

    /**
     * Get the absolute path to upload directory
     * @codeCoverageIgnore
     * @return string
     */
    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    /**
     * Get the upload directory path
     * @codeCoverageIgnore
     * @return string
     */
    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw when displaying uploaded doc/image in the view.
        return 'uploads/links';
    }

    /**
     * Get the path to the file
     * @codeCoverageIgnore
     * @return string
     */
    public function getPath()
    {
        if ($this->type == self::LINK_URL) {
            if(!$this->isExternalLink())

                return 'file:///'.$this->url;
            else
                return $this->url;
        } else {
            return $this->getWebPath();
        }
    }

    /**
     * Returns true or false if the link is external or not
     * @codeCoverageIgnore
     * @return boolean
     */
    public function isExternalLink()
    {
        if ($this->type == self::LINK_URL && strncmp($this->url, 'http', 4) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the file
     * @codeCoverageIgnore
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set the file
     * @codeCoverageIgnore
     * @param $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Display as a string
     * @codeCoverageIgnore
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
