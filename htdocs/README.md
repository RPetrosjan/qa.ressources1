Act&Ressources
========================

Act&Ressources est une application utilisée en interne pour réaliser les affectations des différentes ressources de l'entreprise.

PROD : http://ressources.actency.fr/
QA : http://qa.ressources.actency.fr/

Compte admin:
- ressources.admin
- Actency13*
(Utiliser le simulateur de profil dans la navigation)

1) Généralités
----------------------------------

### Prérequis techniques :

- Vérifier la configuration du serveur avec php app/check.php, corriger les erreurs et warnings.
- Extensions PHP à installer : intl, ldap
- Autres outils à installer : Java, GIT, phpunit

### Si problèmes de droits sur app/cache ou app/logs :

- sudo apt-get install acl
- Paramétrer les ACL comme expliqué ici : http://symfony.com/doc/2.7/setup/file_permissions.html

### Pour installer l'application :

- Créez un compte GitHub et demandez l'autorisation d'accéder au dépôt GitHub.
- Télécharger les sources du dépôt GIT sur GitHub.
- Télécharger et installer composer : http://getcomposer.org/
- Lancer l'installation des librairies avec composer : composer install
- Vérifier les droits sur les répertoires app/cache et app/logs, si besoin, voir rubrique ci-dessus
- Vérifier que tout est bien configuré avec la commande : php app/check.php
- Paramétrer les configurations de l'application dans le fichier app/parameters.yml
- Créer la base de données avec la commande : php app/console doctrine:database:create
- Importer la BDD de prod dans la base créée via phpmyadmin ou autre
- Lancer les migrations si nécessaire : php app/console doctrine:migrations:migrate
- S'assurer que la BDD est à jour : php app/console doctrine:schema:update --force
- Installer les assets : php app/console assets:install --symlink
- Initialiser les fichiers CSS/JS : php app/console assetic:dump --env=prod et php app/console assetic:dump
- Pour configurer votre vhost, attention de bien faire pointer celui-ci sur le dossier /web.
- Lancer les tests pour s'assurer que tout à bien fonctionné comme prévu (Voir partie "Tests" ci-dessous).

### FAQ :

- Les icônes et images ne s'affichent pas, que faire?

Vérifiez dans votre `parameters.yml` que l'entrée `host` est bien renseignée sur votre installation locale, comme par exemple `host: ressources.loc`.

- Je n'arrive pas à me connecter au LDAP, que faire?

Vérifiez dans votre `parameters.yml` que l'entrée `ldap_server` est bien renseignée sur une IP correcte. En local, il faut une IP et non un nom de domaine.

### Workflow GIT :

Voici le workflow à suivre lors du développement sur le projet, ainsi que la convention de nommage.
- Lorsqu'un développeur commence à travailler sur un ticket, il créé une branche à partir de develop nommée feature/XXXX avec XXXX le numéro du ticket dans redmine.
- Il commit ensuite toutes ses modifications sur cette branche.
- Une fois son ticket terminé, il lance les tests unitaires et vérifie le bon fonctionnement.
- Si les tests passent, il soumet un Pull Request sur Github.
- Le chef de projet technique s'occupe de faire une review du PR et de le merger si tout est validé.
- Si le PR n'est pas validé, le développeur retourne sur sa branche et continue ses développements.

2) Interface d'administration
----------------------------------

L'interface se base sur SonataAdminBundle.

### Commandes :

1. Si modification des ROLES, ACL, ou création d'une nouvelle classe ADMIN.

    php app/console sonata:admin:setup-acl

2. Générer les ACL d'objets existants.

    php app/console sonata:admin:generate-object-acl

Pour gérer les droits, il suffit d'ajouter les utilisateurs à un groupe disposant de rôles définis, ou bien d'ajouter le rôle directement à l'utilisateur.
Par exemple le rôle ROLE_SONATA_USER_ADMIN_USER_GUEST permet de voir la liste des utilisateurs.

3) CRON tasks
----------------------------------

1. Envoi des emails de remplissage prévisionnel.
Mettre en place le CRON sur la commande "act:resources:mail:previsional:send"

2. Envoi des emails de notification.
Mettre en place le CRON sur la commande "act:resources:mail:assignment:send"

4) Déploiement
----------------------------------

Optimiser l'autoloader pour la prod :

    composer dump-autoload --optimize

Initialiser les ACL pour sonata : 

    php app/console sonata:admin:setup-acl
    php app/console sonata:admin:generate-object-acl


5) Tests
----------------------------------

Une batterie de tests fonctionnels et unitaires servent à assurer le bon fonctionnement de l'application au fur et à mesure des développements.
Veuillez installer PHPUnit préalablement : https://phpunit.de/manual/current/en/installation.html
Pour les lancer, il suffit d'executer la commande suivante :

    phpunit -c app/phpunit.xml.dist src
    (Sans génération de couverture de code)

    phpunit -c app/phpunit.xml src
    (Avec génération de couverture de code)

Chaque nouvelle fonctionnalité ajoutée doit être testée de manière complète.
