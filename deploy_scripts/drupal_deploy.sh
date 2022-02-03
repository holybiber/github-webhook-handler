#!/bin/bash
repo="/path/to/my-repo"
branch="main"

# The environment of a shell script called by the web server may differ from
# the environment when you log into your server with SSH. You may need to
# set some environment variables for your script to work in both cases
#export PATH=/other/necessary/path/bin:$PATH
#export HOME=/home/accountname
#env    # use this for debugging the environment

cd $repo

# git branch --show-current is more readable but only available since git 2.22.0
if [[ $(git symbolic-ref --short HEAD) != $branch ]]; then
    echo "WARNING: Not deploying to $repo - we're not on branch $branch as expected."
    exit 1
fi

if [[ -n $(git status --porcelain) ]]; then
    echo "WARNING: Not deploying to $repo - found uncommitted changes."
    exit 1
fi

if git status --porcelain --branch | grep -q ahead; then
    echo "WARNING: Not deploying to $repo - is ahead of its origin."
    exit 1
fi

git fetch

if git status --porcelain --branch | grep -q behind; then
    echo "INFO: Deploying to $repo"

    drush sset system.maintenance_mode 1

    drush cr	# cache rebuild

    drush sql-dump --result-file=auto --gzip

    git pull

    #drush cim	# configuration import

    # 2>&1 redirects STDERR to STDOUT so that you will see the complete output of the command
    composer install 2>&1	# install dependencies according to composer.lock
    drush updb 2>&1         # run database scheme upgrades if necessary

    # for localisation
    #drush locale:check 2>&1 >| /dev/null
    #drush locale:update

    drush cr 2>&1       	# cache rebuild again

    drush sset system.maintenance_mode 0

    #drush cex	# configuration export again (drush updb may have changed stuff -> just to be sure we want to have a backup)

    #if [[ -n $(git status --porcelain) ]]; then
    #    git add config
    #    git commit -m "config export after migrations"
    #    git push
    #fi
fi
