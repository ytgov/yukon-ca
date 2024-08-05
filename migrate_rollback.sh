#!/opt/homebrew/bin/bash

usage () {
  echo "Usage: ./migrate_rollback.sh  (This uses ddev by default.)"
  echo "       ./migrate_rollback.sh ddev"
  echo "       ./migrate_rollback.sh local"
  echo "       ./migrate_rollback.sh terminus dev|test|live"
  exit 1
}

if [ "$#" -gt 2 ]
then
  usage
fi

case "$1" in
  ddev)
    COMMAND="ddev drush"
    ;;
  local)
    COMMAND="drush"
    ;;
  terminus)
    if [ "$#" -ne 2 ]
    then
      usage
    fi

    COMMAND="terminus drush yukon-drupal-10.$2 --"
    ;;
  *)
    if [ "$#" -eq 0 ]
    then
      COMMAND="ddev drush"
    else
      usage
    fi
    ;;
esac

date

time $COMMAND migrate:rollback --group=legacy_taxonomies --continue-on-failure
time $COMMAND migrate:rollback --group=legacy_media --continue-on-failure
time $COMMAND migrate:rollback --group=legacy_paragraphs --continue-on-failure
time $COMMAND migrate:rollback --group=legacy_nodes --continue-on-failure
time $COMMAND migrate:rollback --group=legacy_documents --continue-on-failure
time $COMMAND migrate:rollback --group=legacy_basic_page --continue-on-failure
time $COMMAND migrate:rollback --group=legacy_page_news --continue-on-failure
time $COMMAND migrate:rollback --group=legacy_user_role --continue-on-failure
time $COMMAND migrate:rollback --group=legacy_menu --continue-on-failure
time $COMMAND migrate:rollback --group=legacy_files --continue-on-failure

date
