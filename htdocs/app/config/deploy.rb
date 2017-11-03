set :application, "Act&Ressources"
set :deploy_to,   "/var/www/erp.actency.fr/qa.ressources/htdocs"
set :domain,      "qa.ressources.actency.fr"
set :user,        "qaressources"

set :repository,  "https://github.com/Actency/act-ressources"
set :scm,         :git
set :deploy_via,  :remote_cache

role :web,        domain
role :app,        domain, :primary => true

set :use_sudo,      false
set :keep_releases, 3

set :shared_files,        ["app/config/parameters.yml"]
set :shared_children,     [app_path + "/logs", web_path + "/uploads", "vendor"]
set :use_composer,        true
set :assets_install,      true
set :dump_assetic_assets, true

logger.level = Logger::MAX_LEVEL