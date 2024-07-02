#!/bin/bash

# From https://github.com/ralish/bash-script-template
#
# A best practices Bash script template with many useful functions. This file
# sources in the bulk of the functions from the source.sh file which it expects
# to be in the same directory. Only those functions which are likely to need
# modification are present in this file. This is a great combination if you're
# writing several scripts! By pulling in the common functions you'll minimise
# code duplication, as well as ease any potential updates to shared functions.

# Enable xtrace if the DEBUG environment variable is set
if [[ ${DEBUG-} =~ ^1|yes|true$ ]]; then
    set -o xtrace       # Trace the execution of the script (debug)
fi

# Only enable these shell behaviours if we're not being sourced
# Approach via: https://stackoverflow.com/a/28776166/8787985
if ! (return 0 2> /dev/null); then
    # A better class of script...
    set -o errexit      # Exit on most errors (see the manual)
    set -o nounset      # Disallow expansion of unset variables
    set -o pipefail     # Use last non-zero exit code in a pipeline
fi

# Enable errtrace or the error trap handler will not work as expected
set -o errtrace         # Ensure the error trap handler is inherited

date

time drush migrate:import --group=legacy_taxonomies --continue-on-failure
time drush migrate:import --group=legacy_media --continue-on-failure
time drush migrate:import --group=legacy_paragraphs --continue-on-failure
time drush migrate:import --group=legacy_nodes --continue-on-failure
time drush migrate:import --group=legacy_documents --continue-on-failure
time drush migrate:import --group=legacy_basic_page --continue-on-failure
time drush migrate:import --group=legacy_page_news --continue-on-failure
time drush migrate:import --group=legacy_user_role --continue-on-failure
time drush migrate:import --group=legacy_menu --continue-on-failure
time drush migrate:import --group=legacy_files --continue-on-failure

date
