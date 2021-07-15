#!/bin/bash

if ! command -v drupal-check &> /dev/null
then
    echo "drupal-check command not found, please install it globally https://github.com/mglaman/drupal-check#installation"
    exit 1
fi

# Clone repo in /tmp folder if needed
echo "### Cloning the code, you know like Dolly ###"
if [ -d "/tmp/$1" ]
then
	cd /tmp/$1
	git checkout --quiet master
else
	cd /tmp
	git clone --quiet git@git.dropsolid.com:project/$1.git
	cd $1
fi

# Go back to master and run drupal-check
echo "### Checking for deprecated code ###"
git checkout --quiet master
drupal-check docroot/modules/custom &> /tmp/$1-check.txt
if [ -z "`sed -n 's/\[OK\]//p' /tmp/$1-check.txt`" ]
then
        echo "Drupal check result: `sed -n 's/\[ERROR\]//p' /tmp/$1-check.txt | xargs`, see /tmp/$1-check.txt"
else
        echo "Could not find any deprecated code in custom modules, you may still want to check /tmp/$1-check.txt"
fi

# Complexity
echo "### Assessing complexity  ###"
CUSTOM_MODULES=`find docroot/modules/custom -type f -name *.info.yml | wc -l | xargs`
LINES_CODE=`find docroot/modules/custom -type f \( -name "*.php" -o -name "*.module" \) -print0 | xargs -0 cat | wc -l | xargs`
echo "There are $CUSTOM_MODULES custom modules and $LINES_CODE lines of custom code."

# Get 10th commit.
echo "### Looking for the original Drupal version ###"
REV=`git log --reverse --format=%H | head -n 10 | tail -n 1`
git checkout --quiet $REV

# Extract version
DRUPAL_PHP="docroot/core/lib/Drupal.php"
if [ ! -f "$DRUPAL_PHP" ]
then
	DRUPAL_PHP="web/core/lib/Drupal.php"
fi
VERSION=`sed -n "s/  const VERSION = '//p" $DRUPAL_PHP | sed -n "s/';//p"`
echo "Orginal Drupal version: $VERSION"

# Go back to master to avoid confusion
git checkout --quiet master
