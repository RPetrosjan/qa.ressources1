<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrate from old Production database to the new 1.4 version one.
 */
class Version1_4 extends AbstractMigration implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Up - put the database to the latest version
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Create new tables
        $this->addSql('CREATE TABLE fos_user_user_group (user_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_B3C77447A76ED395 (user_id), INDEX IDX_B3C77447FE54D947 (group_id), PRIMARY KEY(user_id, group_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fos_user_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', UNIQUE INDEX UNIQ_583D1F3E5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE team_project (id INT AUTO_INCREMENT NOT NULL, team_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_D0CAA1D9296CD8AE (team_id), INDEX IDX_D0CAA1D9A76ED395 (user_id), UNIQUE INDEX team_user_projects (user_id, team_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE team_project_item (project_id INT NOT NULL, team_project_id INT NOT NULL, INDEX IDX_2E0CE0C4166D1F9C (project_id), INDEX IDX_2E0CE0C428B46D59 (team_project_id), PRIMARY KEY(project_id, team_project_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE simulated_assignment (id INT AUTO_INCREMENT NOT NULL, event VARCHAR(255) NOT NULL, serialized LONGTEXT NOT NULL, created BIGINT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');

        // Drop tables
        $this->addSql('DROP TABLE ext_translations');
        $this->addSql('DROP TABLE project_comment');
        $this->addSql('DROP TABLE task_comment');

        // Rename tables
        $this->addSql('RENAME TABLE ressource TO resource');

        // Drop indexes
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C8DB60186');
        $this->addSql('DROP INDEX IDX_9474526C8DB60186 ON comment');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61FFC6CD52A');
        $this->addSql('DROP INDEX IDX_C4E0A61FFC6CD52A ON team');
        $this->addSql('DROP INDEX UNIQ_957A6479A0D96FBF ON fos_user');
        $this->addSql('ALTER TABLE project_cpt DROP FOREIGN KEY FK_5FFCBA5BFC6CD52A');
        $this->addSql('DROP INDEX IDX_5FFCBA5BFC6CD52A ON project_cpt');
        $this->addSql('ALTER TABLE project_cpt DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_939F4544A76ED395');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_939F4544296CD8AE');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_939F454464D218E');
        $this->addSql('DROP INDEX uniq_939f45445e237e06 ON resource');
        $this->addSql('DROP INDEX uniq_939f4544410ec2e7 ON resource');
        $this->addSql('DROP INDEX idx_939f4544296cd8ae ON resource');
        $this->addSql('DROP INDEX uniq_939f4544a76ed395 ON resource');
        $this->addSql('DROP INDEX idx_939f454464d218e ON resource');
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BAFC6CD52A');
        $this->addSql('DROP INDEX IDX_30C544BAFC6CD52A ON assignment');
        $this->addSql('DROP INDEX assignment_day_ress_proj ON assignment');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEFC6CD52A');
        $this->addSql('DROP INDEX IDX_2FB3D0EEFC6CD52A ON project');

        // Alterations
        $this->addSql('ALTER TABLE link CHANGE type type INT NOT NULL');
        $this->addSql('ALTER TABLE client ADD contact_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE bank_holiday DROP duration');
        $this->addSql('ALTER TABLE fos_user ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, ADD date_of_birth DATETIME DEFAULT NULL, ADD firstname VARCHAR(64) DEFAULT NULL, ADD lastname VARCHAR(64) DEFAULT NULL, ADD website VARCHAR(64) DEFAULT NULL, ADD biography VARCHAR(1000) DEFAULT NULL, ADD gender VARCHAR(1) DEFAULT NULL, ADD timezone VARCHAR(64) DEFAULT NULL, ADD phone VARCHAR(64) DEFAULT NULL, ADD facebook_uid VARCHAR(255) DEFAULT NULL, ADD facebook_name VARCHAR(255) DEFAULT NULL, ADD facebook_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD twitter_uid VARCHAR(255) DEFAULT NULL, ADD twitter_name VARCHAR(255) DEFAULT NULL, ADD twitter_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD gplus_uid VARCHAR(255) DEFAULT NULL, ADD gplus_name VARCHAR(255) DEFAULT NULL, ADD gplus_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD token VARCHAR(255) DEFAULT NULL, ADD two_step_code VARCHAR(255) DEFAULT NULL, CHANGE locale locale VARCHAR(8) DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD is_presale_gt70 TINYINT(1) NOT NULL, ADD is_presale_lt70 TINYINT(1) NOT NULL, ADD is_signed TINYINT(1) NOT NULL, ADD is_holiday TINYINT(1) NOT NULL, ADD is_internal TINYINT(1) NOT NULL, ADD is_research TINYINT(1) NOT NULL, CHANGE ressource_id resource_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE team CHANGE ressource_id resource_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fos_user_user_group ADD CONSTRAINT FK_B3C77447A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fos_user_user_group ADD CONSTRAINT FK_B3C77447FE54D947 FOREIGN KEY (group_id) REFERENCES fos_user_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_cpt CHANGE ressource_id resource_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE assignment ADD comment_user_id INT DEFAULT NULL, ADD created DATETIME NOT NULL, ADD updated DATETIME DEFAULT NULL, ADD comment_text LONGTEXT DEFAULT NULL, ADD comment_date DATETIME DEFAULT NULL, CHANGE ressource_id resource_id INT NOT NULL');
        $this->addSql('ALTER TABLE comment DROP task_id');

        // Recreate indexes
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('CREATE INDEX IDX_C4E0A61F89329D25 ON team (resource_id)');
        $this->addSql('ALTER TABLE project_cpt ADD CONSTRAINT FK_5FFCBA5B89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('CREATE INDEX IDX_5FFCBA5B89329D25 ON project_cpt (resource_id)');
        $this->addSql('CREATE UNIQUE INDEX cpt_project_team ON project_cpt (project_id, team_id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BC91F4165E237E06 ON resource (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BC91F416410EC2E7 ON resource (name_short)');
        $this->addSql('CREATE INDEX IDX_BC91F416296CD8AE ON resource (team_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BC91F416A76ED395 ON resource (user_id)');
        $this->addSql('CREATE INDEX IDX_BC91F41664D218E ON resource (location_id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_939F4544296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_939F454464D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_939F4544A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)');
        $this->addSql('ALTER TABLE team_project ADD CONSTRAINT FK_D0CAA1D9296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE team_project ADD CONSTRAINT FK_D0CAA1D9A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)');
        $this->addSql('ALTER TABLE team_project_item ADD CONSTRAINT FK_2E0CE0C4166D1F9C FOREIGN KEY (project_id) REFERENCES team_project (id)');
        $this->addSql('ALTER TABLE team_project_item ADD CONSTRAINT FK_2E0CE0C428B46D59 FOREIGN KEY (team_project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BA89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BA541DB185 FOREIGN KEY (comment_user_id) REFERENCES fos_user (id)');
        $this->addSql('CREATE INDEX IDX_30C544BA89329D25 ON assignment (resource_id)');
        $this->addSql('CREATE INDEX IDX_30C544BA541DB185 ON assignment (comment_user_id)');
        $this->addSql('CREATE UNIQUE INDEX assignment_day_ress_proj ON assignment (day, resource_id, project_id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE89329D25 ON project (resource_id)');
        $this->addSql('ALTER TABLE project_cpt ADD id INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (id)');
    }

    /**
     * Down - not supported here
     * @param Schema $schema
     */
    public function down(Schema $schema) {
        $this->throwIrreversibleMigrationException('Not supported');
    }

    /**
     * After update, create some groups.
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $groupManager = $this->container->get('fos_user.group_manager');

        // Create the super administrator group
        $superAdminGroup = $groupManager->createGroup('Super Administrateurs');
        $superAdminGroup->addRole('ROLE_SUPER_ADMIN');
        $groupManager->updateGroup($superAdminGroup);

        // Create the administrator group
        $adminGroup = $groupManager->createGroup('Administrateurs');
        $adminGroup->addRole('ROLE_ADMIN');
        $groupManager->updateGroup($adminGroup);

        // Create the users group
        $userGroup = $groupManager->createGroup('Utilisateurs');
        $userGroup->addRole('ROLE_USER');
        $groupManager->updateGroup($userGroup);

        // Put all users in the user group
        $em = $this->container->get('doctrine')->getManager();
        $users = $em->getRepository('ApplicationSonataUserBundle:User')->findAll();
        foreach ($users as $user) {
            $user->addGroup($userGroup);
        }

        // Adds the user 'ressources.admin' into the superadmin group
        $admin = $em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(array('username' => 'ressources.admin'));
        if ($admin) {
            $admin->addGroup($superAdminGroup);
        }

        $em->flush();
    }
}
