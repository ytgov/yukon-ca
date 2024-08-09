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

echo "Rolling back menu..."
time $COMMAND migrate:rollback --continue-on-failure menu

echo "Rolling back yukon_migrate_files__public..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_files__public

echo "Rolling back yukon_user_roles..."
time $COMMAND migrate:rollback --continue-on-failure yukon_user_roles

echo "Rolling back menu_links..."
time $COMMAND migrate:rollback --continue-on-failure menu_links

echo "Rolling back yukon_migrate_media_folders..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_media_folders

echo "Rolling back yukon_migrate_document..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_document

echo "Rolling back yukon_migrate_menu_translation..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_menu_translation

echo "Rolling back yukon_migrate_images..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_images

echo "Rolling back yukon_migrate_menu_links_translation..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_menu_links_translation

echo "Rolling back yukon_migrate_category..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_category

echo "Rolling back yukon_migrate_department..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_department

echo "Rolling back yukon_migrate_teams..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_teams

echo "Rolling back yukon_migrate_documents_page..."
time $COMMAND migrate:rollback --continue-on-failure -vvv yukon_migrate_documents_page

echo "Rolling back yukon_migrate_documents_page_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_documents_page_translations

echo "Rolling back yukon_migrate_engagement_categories..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_engagement_categories

echo "Rolling back yukon_migrate_engagement..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_engagement

echo "Rolling back yukon_migrate_icon..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_icon

echo "Rolling back yukon_migrate_charts..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_charts

echo "Rolling back yukon_migrate_collapsable_field..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_collapsable_field

echo "Rolling back yukon_migrate_image_gallery..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_image_gallery

echo "Rolling back yukon_migrate_basic_page..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_basic_page

echo "Rolling back yukon_migrate_landing_page_level_2..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_landing_page_level_2

echo "Rolling back yukon_migrate_text..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_text

echo "Rolling back yukon_migrate_text_unformatted..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_text_unformatted

echo "Rolling back yukon_migrate_text_and_image..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_text_and_image

echo "Rolling back yukon_migrate_media_video..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_media_video

echo "Rolling back yukon_migrate_video..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_video

echo "Rolling back yukon_migrate_media_video_from_video_and_text..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_media_video_from_video_and_text

echo "Rolling back yukon_migrate_video_and_text..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_video_and_text

echo "Rolling back yukon_migrate_call_to_action..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_call_to_action

echo "Rolling back yukon_migrate_quote..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_quote

echo "Rolling back yukon_migrate_social_network..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_social_network

echo "Rolling back yukon_migrate_navigation_jump_point..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_navigation_jump_point

echo "Rolling back yukon_migrate_campaign_page..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_campaign_page

echo "Rolling back yukon_migrate_multi_step..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_multi_step

echo "Rolling back yukon_migrate_multi_step_page..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_multi_step_page

echo "Rolling back yukon_migrate_news_type..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_news_type

echo "Rolling back yukon_migrate_quick_facts..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_quick_facts

echo "Rolling back yukon_migrate_quotes..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_quotes

echo "Rolling back yukon_migrate_news..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_news

echo "Rolling back yukon_migrate_community..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_community

echo "Rolling back yukon_migrate_region..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_region

echo "Rolling back yukon_migrate_contact_person..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_contact_person

echo "Rolling back yukon_migrate_opening_and_closing_times..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_opening_and_closing_times

echo "Rolling back yukon_migrate_holiday_hours..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_holiday_hours

echo "Rolling back yukon_migrate_opening_times..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_opening_times

echo "Rolling back yukon_migrate_community_facilities_terms..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_community_facilities_terms

echo "Rolling back yukon_migrate_community_facilities..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_community_facilities

echo "Rolling back yukon_migrate_education_facilities_terms..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_education_facilities_terms

echo "Rolling back yukon_migrate_education_facilities..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_education_facilities

echo "Rolling back yukon_migrate_health_facilities_terms..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_health_facilities_terms

echo "Rolling back yukon_migrate_health_facilities..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_health_facilities

echo "Rolling back yukon_migrate_transactional_facility_type..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_transactional_facility_type

echo "Rolling back yukon_migrate_transactional_facilities..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_transactional_facilities

echo "Rolling back yukon_migrate_date_range_text..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_date_range_text

echo "Rolling back yukon_migrate_sanitary_facilities_terms..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_sanitary_facilities_terms

echo "Rolling back yukon_migrate_sanitary_facilities..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_sanitary_facilities

echo "Rolling back yukon_migrate_places..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_places

echo "Rolling back yukon_migrate_sub_heading..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_sub_heading

echo "Rolling back yukon_migrate_topics_page..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_topics_page

echo "Rolling back yukon_migrate_primary_content..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_primary_content

echo "Rolling back yukon_migrate_secondary_content..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_secondary_content

echo "Rolling back yukon_migrate_landing_page..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_landing_page

echo "Rolling back yukon_migrate_landing_page_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_landing_page_translations

echo "Rolling back yukon_migrate_topics_page_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_topics_page_translations

echo "Rolling back yukon_migrate_related_content..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_related_content

echo "Rolling back yukon_migrate_section..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_section

echo "Rolling back yukon_migrate_department_section..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_department_section

echo "Rolling back yukon_migrate_top_tasks..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_top_tasks

echo "Rolling back yukon_migrate_department_nodes..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_department_nodes

echo "Rolling back yukon_migrate_home_page..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_home_page

echo "Rolling back yukon_migrate_blog_type..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_blog_type

echo "Rolling back yukon_migrate_subcategory..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_subcategory

echo "Rolling back yukon_migrate_blog..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_blog

echo "Rolling back yukon_migrate_blog_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_blog_translations

echo "Rolling back yukon_migrate_event..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_event

echo "Rolling back yukon_migrate_event_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_event_translations

echo "Rolling back yukon_migrate_campaign_page_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_campaign_page_translations

echo "Rolling back yukon_migrate_engagement_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_engagement_translations

echo "Rolling back yukon_migrate_home_page_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_home_page_translations

echo "Rolling back yukon_migrate_department_nodes_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_department_nodes_translations

echo "Rolling back yukon_migrate_places_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_places_translations

echo "Rolling back yukon_migrate_recreation_site_type..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_recreation_site_type

echo "Rolling back yukon_migrate_site_type..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_site_type

echo "Rolling back yukon_migrate_highway_names..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_highway_names

echo "Rolling back yukon_migrate_campground_directory_record..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_campground_directory_record

echo "Rolling back yukon_migrate_campground_directory_record_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_campground_directory_record_translations

echo "Rolling back yukon_migrate_basic_page_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_basic_page_translations

echo "Rolling back yukon_migrate_landing_page_level_2_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_landing_page_level_2_translations

echo "Rolling back yukon_migrate_in_page_alert..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_in_page_alert

echo "Rolling back yukon_migrate_multi_step_page_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_multi_step_page_translations

echo "Rolling back yukon_migrate_in_page_alert_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_in_page_alert_translations

echo "Rolling back yukon_migrate_quotes_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_quotes_translations

echo "Rolling back yukon_migrate_quick_facts_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_quick_facts_translations

echo "Rolling back yukon_migrate_news_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_news_translations

echo "Rolling back yukon_migrate_full_width_image..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_full_width_image

echo "Rolling back yukon_migrate_sanitary_facilities_terms_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_sanitary_facilities_terms_translations

echo "Rolling back yukon_migrate_media_folders_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_media_folders_translations

echo "Rolling back yukon_migrate_site_type_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_site_type_translations

echo "Rolling back yukon_migrate_category_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_category_translations

echo "Rolling back yukon_migrate_teams_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_teams_translations

echo "Rolling back yukon_migrate_blog_type_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_blog_type_translations

echo "Rolling back yukon_migrate_community_facilities_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_community_facilities_translations

echo "Rolling back yukon_migrate_highway_names_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_highway_names_translations

echo "Rolling back yukon_migrate_engagement_categories_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_engagement_categories_translations

echo "Rolling back yukon_migrate_education_facilities_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_education_facilities_translations

echo "Rolling back yukon_migrate_health_facilities_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_health_facilities_translations

echo "Rolling back yukon_migrate_news_type_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_news_type_translations

echo "Rolling back yukon_migrate_department_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_department_translations

echo "Rolling back yukon_migrate_transactional_facility_type_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_transactional_facility_type_translations

echo "Rolling back yukon_migrate_subcategory_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_subcategory_translations

echo "Rolling back yukon_migrate_community_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_community_translations

echo "Rolling back yukon_migrate_region_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_region_translations

echo "Rolling back yukon_migrate_recreation_site_type_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_recreation_site_type_translations

echo "Rolling back yukon_migrate_section_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_section_translations

echo "Rolling back yukon_migrate_audio..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_audio

echo "Rolling back yukon_migrate_document_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_document_translations

echo "Rolling back yukon_migrate_icon_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_icon_translations

echo "Rolling back yukon_migrate_images_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_images_translations

echo "Rolling back yukon_migrate_audio_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_audio_translations

echo "Rolling back yukon_migrate_media_video_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_media_video_translations

echo "Rolling back yukon_migrate_media_video_from_video_and_text_translations..."
time $COMMAND migrate:rollback --continue-on-failure yukon_migrate_media_video_from_video_and_text_translations

date
