#!/bin/bash

usage () {
  echo "Usage: ./migrate_update.sh  (This uses ddev by default.)"
  echo "       ./migrate_update.sh ddev"
  echo "       ./migrate_update.sh local"
  echo "       ./migrate_update.sh terminus dev|test|live"
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

echo "Updating menu..."
time $COMMAND migrate:import --update --continue-on-failure menu

echo "Updating yukon_migrate_files__public..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_files__public

echo "Updating yukon_user_roles..."
time $COMMAND migrate:import --update --continue-on-failure yukon_user_roles

echo "Updating menu_links..."
time $COMMAND migrate:import --update --continue-on-failure menu_links

echo "Updating yukon_migrate_media_folders..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_media_folders

echo "Updating yukon_migrate_document..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_document

echo "Updating yukon_migrate_menu_translation..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_menu_translation

echo "Updating yukon_migrate_images..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_images

echo "Updating yukon_migrate_menu_links_translation..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_menu_links_translation

echo "Updating yukon_migrate_category..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_category

echo "Updating yukon_migrate_department..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_department

echo "Updating yukon_migrate_teams..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_teams

echo "Updating yukon_migrate_documents_page..."
time $COMMAND migrate:import --update --continue-on-failure -vvv yukon_migrate_documents_page

echo "Updating yukon_migrate_documents_page_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_documents_page_translations

echo "Updating yukon_migrate_engagement_categories..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_engagement_categories

echo "Updating yukon_migrate_engagement..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_engagement

echo "Updating yukon_migrate_icon..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_icon

echo "Updating yukon_migrate_charts..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_charts

echo "Updating yukon_migrate_collapsable_field..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_collapsable_field

echo "Updating yukon_migrate_image_gallery..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_image_gallery

echo "Updating yukon_migrate_basic_page..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_basic_page

echo "Updating yukon_migrate_landing_page_level_2..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_landing_page_level_2

echo "Updating yukon_migrate_text..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_text

echo "Updating yukon_migrate_text_unformatted..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_text_unformatted

echo "Updating yukon_migrate_text_and_image..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_text_and_image

echo "Updating yukon_migrate_media_video..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_media_video

echo "Updating yukon_migrate_video..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_video

echo "Updating yukon_migrate_media_video_from_video_and_text..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_media_video_from_video_and_text

echo "Updating yukon_migrate_video_and_text..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_video_and_text

echo "Updating yukon_migrate_call_to_action..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_call_to_action

echo "Updating yukon_migrate_quote..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_quote

echo "Updating yukon_migrate_social_network..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_social_network

echo "Updating yukon_migrate_navigation_jump_point..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_navigation_jump_point

echo "Updating yukon_migrate_campaign_page..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_campaign_page

echo "Updating yukon_migrate_multi_step..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_multi_step

echo "Updating yukon_migrate_multi_step_page..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_multi_step_page

echo "Updating yukon_migrate_news_type..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_news_type

echo "Updating yukon_migrate_quick_facts..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_quick_facts

echo "Updating yukon_migrate_quotes..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_quotes

echo "Updating yukon_migrate_news..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_news

echo "Updating yukon_migrate_community..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_community

echo "Updating yukon_migrate_region..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_region

echo "Updating yukon_migrate_contact_person..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_contact_person

echo "Updating yukon_migrate_opening_and_closing_times..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_opening_and_closing_times

echo "Updating yukon_migrate_holiday_hours..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_holiday_hours

echo "Updating yukon_migrate_opening_times..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_opening_times

echo "Updating yukon_migrate_community_facilities_terms..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_community_facilities_terms

echo "Updating yukon_migrate_community_facilities..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_community_facilities

echo "Updating yukon_migrate_education_facilities_terms..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_education_facilities_terms

echo "Updating yukon_migrate_education_facilities..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_education_facilities

echo "Updating yukon_migrate_health_facilities_terms..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_health_facilities_terms

echo "Updating yukon_migrate_health_facilities..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_health_facilities

echo "Updating yukon_migrate_transactional_facility_type..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_transactional_facility_type

echo "Updating yukon_migrate_transactional_facilities..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_transactional_facilities

echo "Updating yukon_migrate_date_range_text..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_date_range_text

echo "Updating yukon_migrate_sanitary_facilities_terms..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_sanitary_facilities_terms

echo "Updating yukon_migrate_sanitary_facilities..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_sanitary_facilities

echo "Updating yukon_migrate_places..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_places

echo "Updating yukon_migrate_sub_heading..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_sub_heading

echo "Updating yukon_migrate_topics_page..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_topics_page

echo "Updating yukon_migrate_primary_content..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_primary_content

echo "Updating yukon_migrate_secondary_content..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_secondary_content

echo "Updating yukon_migrate_landing_page..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_landing_page

echo "Updating yukon_migrate_landing_page_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_landing_page_translations

echo "Updating yukon_migrate_topics_page_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_topics_page_translations

echo "Updating yukon_migrate_related_content..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_related_content

echo "Updating yukon_migrate_section..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_section

echo "Updating yukon_migrate_department_section..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_department_section

echo "Updating yukon_migrate_top_tasks..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_top_tasks

echo "Updating yukon_migrate_department_nodes..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_department_nodes

echo "Updating yukon_migrate_home_page..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_home_page

echo "Updating yukon_migrate_blog_type..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_blog_type

echo "Updating yukon_migrate_subcategory..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_subcategory

echo "Updating yukon_migrate_blog..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_blog

echo "Updating yukon_migrate_blog_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_blog_translations

echo "Updating yukon_migrate_event..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_event

echo "Updating yukon_migrate_event_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_event_translations

echo "Updating yukon_migrate_campaign_page_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_campaign_page_translations

echo "Updating yukon_migrate_engagement_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_engagement_translations

echo "Updating yukon_migrate_home_page_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_home_page_translations

echo "Updating yukon_migrate_department_nodes_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_department_nodes_translations

echo "Updating yukon_migrate_places_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_places_translations

echo "Updating yukon_migrate_recreation_site_type..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_recreation_site_type

echo "Updating yukon_migrate_site_type..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_site_type

echo "Updating yukon_migrate_highway_names..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_highway_names

echo "Updating yukon_migrate_campground_directory_record..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_campground_directory_record

echo "Updating yukon_migrate_campground_directory_record_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_campground_directory_record_translations

echo "Updating yukon_migrate_basic_page_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_basic_page_translations

echo "Updating yukon_migrate_landing_page_level_2_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_landing_page_level_2_translations

echo "Updating yukon_migrate_in_page_alert..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_in_page_alert

echo "Updating yukon_migrate_multi_step_page_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_multi_step_page_translations

echo "Updating yukon_migrate_in_page_alert_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_in_page_alert_translations

echo "Updating yukon_migrate_quotes_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_quotes_translations

echo "Updating yukon_migrate_quick_facts_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_quick_facts_translations

echo "Updating yukon_migrate_news_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_news_translations

echo "Updating yukon_migrate_full_width_image..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_full_width_image

echo "Updating yukon_migrate_sanitary_facilities_terms_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_sanitary_facilities_terms_translations

echo "Updating yukon_migrate_media_folders_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_media_folders_translations

echo "Updating yukon_migrate_site_type_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_site_type_translations

echo "Updating yukon_migrate_category_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_category_translations

echo "Updating yukon_migrate_teams_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_teams_translations

echo "Updating yukon_migrate_blog_type_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_blog_type_translations

echo "Updating yukon_migrate_community_facilities_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_community_facilities_translations

echo "Updating yukon_migrate_highway_names_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_highway_names_translations

echo "Updating yukon_migrate_engagement_categories_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_engagement_categories_translations

echo "Updating yukon_migrate_education_facilities_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_education_facilities_translations

echo "Updating yukon_migrate_health_facilities_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_health_facilities_translations

echo "Updating yukon_migrate_news_type_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_news_type_translations

echo "Updating yukon_migrate_department_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_department_translations

echo "Updating yukon_migrate_transactional_facility_type_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_transactional_facility_type_translations

echo "Updating yukon_migrate_subcategory_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_subcategory_translations

echo "Updating yukon_migrate_community_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_community_translations

echo "Updating yukon_migrate_region_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_region_translations

echo "Updating yukon_migrate_recreation_site_type_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_recreation_site_type_translations

echo "Updating yukon_migrate_section_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_section_translations

echo "Updating yukon_migrate_audio..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_audio

echo "Updating yukon_migrate_document_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_document_translations

echo "Updating yukon_migrate_icon_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_icon_translations

echo "Updating yukon_migrate_images_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_images_translations

echo "Updating yukon_migrate_audio_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_audio_translations

echo "Updating yukon_migrate_media_video_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_media_video_translations

echo "Updating yukon_migrate_media_video_from_video_and_text_translations..."
time $COMMAND migrate:import --update --continue-on-failure yukon_migrate_media_video_from_video_and_text_translations


date
