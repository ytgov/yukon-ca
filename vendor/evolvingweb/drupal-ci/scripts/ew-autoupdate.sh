#!/bin/bash

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

# Check for ew-autoupdate.json in project root.
if [[ ! -f "${SCRIPT_DIR}/../../../../ew-autoupdate.json" ]]
then
  echo "No autoupdate configured. Exiting..."
  exit 0
fi

BASE_BRANCH=`cat ${SCRIPT_DIR}/../../../../ew-autoupdate.json | python2 -c "import sys, json; print json.load(sys.stdin)['baseBranch']"`
DRUSH_ALIAS=`cat ${SCRIPT_DIR}/../../../../ew-autoupdate.json | python2 -c "import sys, json; print json.load(sys.stdin)['drushAlias']"`
EXPORT_CONFIG=`cat ${SCRIPT_DIR}/../../../../ew-autoupdate.json | python2 -c "import sys, json; print json.load(sys.stdin)['exportConfig']"`
SKIP_TABLES=`cat ${SCRIPT_DIR}/../../../../ew-autoupdate.json | python2 -c "import sys, json; print json.load(sys.stdin)['skipTables']"`

cd ${SCRIPT_DIR}/../../../../
TO_UPDATE=`./vendor/bin/drush ${DRUSH_ALIAS} pm:security --field=name 2> /dev/null  | grep drupal/ | grep -v notice | grep -v Warning`
COMPOSER="php -d memory_limit=-1 /usr/local/bin/composer"


if [[ ! -z "$TO_UPDATE" ]];
then
  DRUSH="./vendor/bin/drush"
  BRANCH_NAME=`date +%Y%m%d`-updates
  git fetch
  $DRUSH status
  git checkout ${BASE_BRANCH}
  git checkout -b ${BRANCH_NAME} || git checkout ${BRANCH_NAME}
  $DRUSH status
  $DRUSH sql-sync --source-dump=/tmp/dump.sql.gz --target-dump=/tmp/dump.sql.gz --skip-tables-list="${SKIP_TABLES}" @dev @self -y
  $DRUSH status
  $DRUSH cr
  $DRUSH cim -y
  # @todo Should parse TO_UPDATE to single line?
  $COMPOSER update ${TO_UPDATE} --no-interaction
  $DRUSH status
  git add composer.lock
  git config user.email "ci@evolvingweb.ca"
  git config user.name "CI Bot"
  git commit -m "refs #000: Automatic Drupal security updates." -m "${TO_UPDATE}"
  if [[ ! -z "$EXPORT_CONFIG" ]];
  then
    $DRUSH updb -y
    $DRUSH cex -y
    git add config/default -A
    git commit -m "refs #000: Add config changes from automated updates." -m "${TO_UPDATE}"
  fi
  git push origin ${BRANCH_NAME}
  git status
  echo 'Updated'
else
  echo 'Nothing to update'
  exit 0
fi
