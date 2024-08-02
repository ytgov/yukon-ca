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

$COMMAND mr yukon_migrate_files__public
$COMMAND mr yukon_migrate_documents_page_translations
$COMMAND mr yukon_migrate_engagement
$COMMAND mr yukon_migrate_landing_page_translations
$COMMAND mr yukon_migrate_basic_page
$COMMAND mr yukon_migrate_topics_page_translations
$COMMAND mr yukon_migrate_department_nodes
$COMMAND mr yukon_migrate_home_page
$COMMAND mr yukon_migrate_blog_translations
$COMMAND mr yukon_migrate_blog
$COMMAND mr yukon_migrate_event_translations
$COMMAND mr yukon_migrate_campaign_page_translations
$COMMAND mr yukon_migrate_engagement_translations
$COMMAND mr yukon_migrate_documents_page
$COMMAND mr yukon_migrate_landing_page_level_2
$COMMAND mr yukon_migrate_multi_step_page
$COMMAND mr yukon_migrate_home_page_translations
$COMMAND mr yukon_migrate_department_nodes_translations
$COMMAND mr yukon_migrate_places_translations
$COMMAND mr yukon_migrate_event
$COMMAND mr yukon_migrate_campground_directory_record_translations
$COMMAND mr yukon_migrate_campaign_page
$COMMAND mr yukon_migrate_basic_page_translations
$COMMAND mr yukon_migrate_landing_page
$COMMAND mr yukon_migrate_landing_page_level_2_translations
$COMMAND mr yukon_migrate_in_page_alert
$COMMAND mr yukon_migrate_multi_step_page_translations
$COMMAND mr yukon_migrate_campground_directory_record
$COMMAND mr yukon_migrate_places
$COMMAND mr yukon_migrate_in_page_alert_translations
$COMMAND mr yukon_migrate_news
$COMMAND mr yukon_migrate_topics_page
$COMMAND mr yukon_migrate_news_translations
$COMMAND mr yukon_user_roles
$COMMAND mr menu_links
$COMMAND mr yukon_migrate_menu_translation
$COMMAND mr menu
$COMMAND mr yukon_migrate_menu_links_translation
$COMMAND mr yukon_migrate_text
$COMMAND mr yukon_migrate_full_width_image
$COMMAND mr yukon_migrate_text_unformatted
$COMMAND mr yukon_migrate_video
$COMMAND mr yukon_migrate_quick_facts_translations
$COMMAND mr yukon_migrate_quote
$COMMAND mr yukon_migrate_opening_times
$COMMAND mr yukon_migrate_health_facilities
$COMMAND mr yukon_migrate_charts
$COMMAND mr yukon_migrate_date_range_text
$COMMAND mr yukon_migrate_community_facilities
$COMMAND mr yukon_migrate_video_and_text
$COMMAND mr yukon_migrate_transactional_facilities
$COMMAND mr yukon_migrate_call_to_action
$COMMAND mr yukon_migrate_contact_person
$COMMAND mr yukon_migrate_navigation_jump_point
$COMMAND mr yukon_migrate_text_and_image
$COMMAND mr yukon_migrate_secondary_content
$COMMAND mr yukon_migrate_related_content
$COMMAND mr yukon_migrate_top_tasks
$COMMAND mr yukon_migrate_sub_heading
$COMMAND mr yukon_migrate_collapsable_field
$COMMAND mr yukon_migrate_opening_and_closing_times
$COMMAND mr yukon_migrate_multi_step
$COMMAND mr yukon_migrate_quick_facts
$COMMAND mr yukon_migrate_image_gallery
$COMMAND mr yukon_migrate_sanitary_facilities
$COMMAND mr yukon_migrate_holiday_hours
$COMMAND mr yukon_migrate_department_section
$COMMAND mr yukon_migrate_quotes
$COMMAND mr yukon_migrate_social_network
$COMMAND mr yukon_migrate_primary_content
$COMMAND mr yukon_migrate_quotes_translations
$COMMAND mr yukon_migrate_education_facilities
$COMMAND mr yukon_migrate_blog_type
$COMMAND mr yukon_migrate_health_facilities_terms
$COMMAND mr yukon_migrate_teams
$COMMAND mr yukon_migrate_region
$COMMAND mr yukon_migrate_transactional_facility_type
$COMMAND mr yukon_migrate_category
$COMMAND mr yukon_migrate_recreation_site_type
$COMMAND mr yukon_migrate_highway_names
$COMMAND mr yukon_migrate_sanitary_facilities_terms
$COMMAND mr yukon_migrate_education_facilities_terms
$COMMAND mr yukon_migrate_news_type
$COMMAND mr yukon_migrate_section
$COMMAND mr yukon_migrate_community
$COMMAND mr yukon_migrate_department
$COMMAND mr yukon_migrate_site_type
$COMMAND mr yukon_migrate_media_folders
$COMMAND mr yukon_migrate_community_facilities_terms
$COMMAND mr yukon_migrate_subcategory
$COMMAND mr yukon_migrate_engagement_categories
$COMMAND mr yukon_migrate_audio
$COMMAND mr yukon_migrate_document_translations
$COMMAND mr yukon_migrate_icon_translations
$COMMAND mr yukon_migrate_media_video
$COMMAND mr yukon_migrate_document
$COMMAND mr yukon_migrate_images_translations
$COMMAND mr yukon_migrate_audio_translations
$COMMAND mr yukon_migrate_images
$COMMAND mr yukon_migrate_media_video_translations
$COMMAND mr yukon_migrate_media_video_from_video_and_text_translations
$COMMAND mr yukon_migrate_icon
$COMMAND mr yukon_migrate_media_video_from_video_and_text
