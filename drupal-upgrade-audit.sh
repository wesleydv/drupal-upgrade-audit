#!/bin/bash

if ! command -v drupal-check &> /dev/null
then
    echo "drupal-check command not found, please install it globally https://github.com/mglaman/drupal-check#installation"
    exit 1
fi

# Clone repo in /tmp folder if needed
if [ -d "/tmp/$1" ]
then
	echo "### You already have the code, fancy, fetching and checking out master ###"
	cd /tmp/$1
	git fetch --quiet
	git checkout --quiet master
else
	echo "### Cloning the code, you know like Dolly ###"
	cd /tmp
	git clone --quiet git@git.dropsolid.com:project/$1.git
	cd $1
fi

# Look for commit where core/lib/Drupal.php was added
echo "### Looking for the original Drupal version ###"
REV=`git log --format=%H --diff-filter=A -- *lib/Drupal.php | head -n 1`

# Extract version
DRUPAL_PHP_PATH=`git ls-tree --name-only -r $REV | grep lib/Drupal.php`
VERSION=`git show $REV:docroot/core/lib/Drupal.php | sed -n "s/  const VERSION = '//p" | sed -n "s/';//p"`
echo "Orginal Drupal version: $VERSION"

# Complexity
echo "### Assessing complexity  ###"
CUSTOM_MODULES=`find docroot/modules/custom -type f -name *.info.yml | wc -l | xargs`
LINES_CODE=`find docroot/modules/custom -type f \( -name "*.php" -o -name "*.module" \) -print0 | xargs -0 cat | wc -l | xargs`
echo "There are $CUSTOM_MODULES custom modules and $LINES_CODE lines of custom code."

# Go back to master and run drupal-check
echo "### Looking for deprecated code ###"
git checkout --quiet master
drupal-check docroot/modules/custom &> /tmp/$1-check.txt
if [ -z "`sed -n 's/\[OK\]//p' /tmp/$1-check.txt`" ]
then
        echo "Drupal check result: `sed -n 's/\[ERROR\]//p' /tmp/$1-check.txt | xargs`, see /tmp/$1-check.txt"
else
        echo "Could not find any deprecated code in custom modules, you may still want to check /tmp/$1-check.txt"
fi
