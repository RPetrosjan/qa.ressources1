<?php

namespace Application\Sonata\UserBundle\Entity;

use Sonata\UserBundle\Entity\BaseUser as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Act\ResourceBundle\Entity\Resource;
use Act\ResourceBundle\Entity\Comment;
use Act\ResourceBundle\Entity\Team;
use Act\ResourceBundle\Entity\PreferedTeam;
use Act\ResourceBundle\Entity\PreferedProject;

/**
 * Classe décrivant un utilisateur
 * Hérite de l'utilisateur de FOSUserBundle pour la gestion des droits
 *
 * @ORM\Table(name="fos_user")
 * @ORM\Entity(repositoryClass="Application\Sonata\UserBundle\Entity\UserRepository")
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="emailCanonical", column=@ORM\Column(type="string", name="email_canonical", length=255, unique=false))
 * })
 **/
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string the slack username
     *
     * @ORM\Column(name="slack_user", type="string", length=50, nullable=TRUE)
     */
    protected $slackUser;

    /**
     * @var Resource $resource La ressource liée à cet utilisateur - peut être nulle
     *
     * @ORM\OneToOne(targetEntity="Act\ResourceBundle\Entity\Resource", mappedBy="user" )
     */
    protected $resource;

    /**
     * @var ArrayCollection $comments Commentaires postés par cet utilisateur
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\Comment", mappedBy="user")
     */
    protected $comments;

    /**
     * @var ArrayCollection $previsionalTeams les équipes pour lesquelles cet utilisateur
     * souhaite recevoir des notifications à interval régulier, en terme de remplissage.
     *
     * @ORM\ManyToMany(targetEntity="Act\ResourceBundle\Entity\Team", inversedBy="previsionalSubscribers")
     * @ORM\JoinTable(name="previsional_mail")
     */
    protected $previsionalTeams;

    /**
     * @var ArrayCollection $preferedTeams équipes préférées liées
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\PreferedTeam", mappedBy="user", cascade={"remove"})
     */
    protected $preferedTeams;

    /**
     * @var ArrayCollection $preferedProjects projets préférés d'utilisateurs
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\PreferedProject", mappedBy="user", cascade={"remove"})
     */
    protected $preferedProjects;

    /**
     * Construct a new User entity
     * Set default values
     */
    public function __construct()
    {
        parent::__construct();

        $this->comments = new ArrayCollection();
        $this->preferedTeams = new ArrayCollection();
        $this->preferedProjects = new ArrayCollection();

        $this->password = "see_on_ldap";
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
     * Get the slack username
     *
     * @return string
     */
    public function getSlackUser()
    {
        return $this->slackUser;
    }

    /**
     * Set the slack username
     */
    public function setSlackUser($slackUser = NULL)
    {
        $this->slackUser = $slackUser;
    }

    /**
     * Get the resource
     * @codeCoverageIgnore
     * @return Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set the resource, can be null
     * @codeCoverageIgnore
     * @param Resource $resource
     */
    public function setResource(Resource $resource = null)
    {
        $this->resource = $resource;
    }

    /**
     * Returns user comments
     * @codeCoverageIgnore
     * @return ArrayCollection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Adds a user comment
     * @codeCoverageIgnore
     * @param Comment $comment
     */
    public function addComment(Comment $comment)
    {
        $this->comments[] = $comment;
    }

    /**
     * Delete a comment
     * @codeCoverageIgnore
     * @param Comment $comment
     */
    public function removeComment(Comment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Returns teams subscriptions
     * @codeCoverageIgnore
     * @return ArrayCollection
     */
    public function getPrevisionalTeams()
    {
        return $this->previsionalTeams;
    }

    /**
     * Adds a team subscription
     * @codeCoverageIgnore
     * @param Team $team
     */
    public function addPrevisionalTeam(Team $team)
    {
        $this->previsionalTeams[] = $team;
    }

    /**
     * Delete a team subscription
     * @codeCoverageIgnore
     * @param Team $team
     */
    public function removePrevisionalTeam(Team $team)
    {
        $this->previsionalTeams->removeElement($team);
    }

    /**
     * Reset all users previsional team subscriptions.
     */
    public function resetPrevisionalTeams()
    {
        $this->previsionalTeams = new ArrayCollection();
    }

    /**
     * Returns the user prefered teams
     * @codeCoverageIgnore
     * @return ArrayCollection
     */
    public function getPreferedTeams()
    {
        return $this->preferedTeams;
    }

    /**
     * Adds a prefered team
     * @codeCoverageIgnore
     * @param PreferedTeam $preferedTeam
     */
    public function addPreferedTeam(PreferedTeam $preferedTeam)
    {
        $this->preferedTeams[] = $preferedTeam;
    }

    /**
     * Remove a prefered team
     * @codeCoverageIgnore
     * @param PreferedTeam $preferedTeam
     */
    public function removePreferedTeam(PreferedTeam $preferedTeam)
    {
        $this->preferedTeams->removeElement($preferedTeam);
    }

    /**
     * Returns the user prefered projects
     * @codeCoverageIgnore
     * @return ArrayCollection
     */
    public function getPreferedProjects()
    {
        return $this->preferedProjects;
    }

    /**
     * Adds a prefered project
     * @codeCoverageIgnore
     * @param PreferedProject $preferedProject
     */
    public function addPreferedProjects(PreferedProject $preferedProject)
    {
        $this->preferedProjects[] = $preferedProject;
    }

    /**
     * Remove a prefered project
     * @codeCoverageIgnore
     * @param PreferedProject $preferedProject
     */
    public function removePreferedProjects(PreferedProject $preferedProject)
    {
        $this->preferedProjects->removeElement($preferedProject);
    }

    /* End of getters and setters - Code below must be tested ! */

    /**
     * Checks if the user can maybe fit with
     * the given Resource, in term of name.
     *
     * @param Resource $resource
     *
     * @return bool
     */
    public function compare(Resource $resource)
    {
        $result = false;
        $code = null;

        if (strlen($this->username) >= 3) {
            // Take first letter, second letter, and last letter for code
            $code = strtoupper(substr($this->username, 0, 1).substr($this->username, 1, 1).substr($this->username, -1));
        } else {
            $code = strtoupper($this->username);
        }

        // Compare the user code with the resource code
        // Or compare the two first letters of the code
        if (strpos($resource->getNameShort(), $code) !== false) {
            $result = true;
        } elseif (strpos($resource->getNameShort(), substr($code, 0, 2)) !== false) {
            $result = true;
        }

        return $result;
    }

    /**
     * Checks if the current user has subscribed to
     * the given team, for the previsional email.
     *
     * @param Team $team
     *
     * @return bool
     */
    public function hasSubscribedTo(Team $team)
    {
        $res = false;

        if (count($this->previsionalTeams) > 0) {
            foreach ($this->previsionalTeams as $pt) {
                if ($pt->getId() == $team->getId()) {
                    $res = true;
                    break;
                }
            }
        }

        return $res;
    }
}
