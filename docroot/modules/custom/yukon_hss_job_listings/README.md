# HSS Job Listings module

Machine name: `yukon_hss_job_listings`

The HSS Job Listings module renders structured job listing data from an external source
into an HTML table format for display within a content type or paragraph.
The [jQuery datatables library](https://datatables.net) is used to apply pagination,
sorting, and dynamic filtering to the rendered job listings table.

At present, only the Deltek HRSmart job listings are supported via a Telus/Deltek API
endpoint that responds with an XML payload.
This XML payload represents a set of job listings, filtered for certain health-related
categories taken from the complete list published on the
[YG jobs board](https://yukongovernment.hua.hrsmart.com/hr/ats/JobSearch/viewAll).

Three cooperating parts of the module are used to fetch, store, and render the job
listings:

1. A cron hook that fetches the job listings data from the API endpoint:
   `yukon_hss_job_listings.module:yukon_hss_job_listings_cron()`
2. A custom field type that stores the fetched data as text: `HRSmartJobListingsItem`
3. A custom field formatter that parses the fetched data into a Drupal render array:
   `HRSmartJobListingsFormatter`

Additionally, a Twig template fragment may be used to render the paragraph containing
the custom field.

## Requirements

None beyond the base yukon.ca D10 installation.

## Installation

1. Deploy the code, including the module under
   `docroot/modules/custom/yukon_hss_job_listings` and the paragraph Twig template
   `templates/paragraphs/paragraph--hrsmart-job-listings--default.html.twig` in the
   active theme.
2. Enable the module: `drush en yukon_hss_job_listings`
3. Configuration for the HRSmart Job Listings paragraph type and component fields is
   also included and can be loaded using `drush cim`.
   The configuration items that should be imported are:
   - `core.entity_form_display.paragraph.hrsmart_job_listings.default`
   - `core.entity_view_display.paragraph.hrsmart_job_listings.default`
   - `field.field.paragraph.hrsmart_job_listings.field_display_section_title`
   - `field.field.paragraph.hrsmart_job_listings.field_hrsmart_job_listings_data`
   - `field.field.paragraph.hrsmart_job_listings.field_section_content`
   - `field.field.paragraph.hrsmart_job_listings.field_title`
   - `field.storage.paragraph.field_hrsmart_job_listings_data`
   - `paragraphs.paragraphs_type.hrsmart_job_listings`

## Configuration

If configuration was loaded in step #3 of the Installation instructions, then skip ahead
to the "Enable the job listing paragraph type for the content type" step below.

### Add a new job listing paragraph type

1. Create a new paragraph type with label "HRSmart job listings" and description
   "Display job listings from the YG HRSmart job board filtered for HSS-related
   positions."
2. Add a re-used existing field, `field_title`.
   Keep the default settings.
3. Add a re-used existing field, `field_display_section_title`.
   Keep the default settings.
4. Add a re-used existing field, `field_section_content`.
   Keep the default settings.
5. Create a new Plain text field called "HRSmart job listings data" and choose the
   "HSS Job Listings data" option.
   Keep the default settings.
6. Change the form display so that only the Section title, Display section title,
   and Section Content fields are enabled and in that order.
7. Change the display so that only the Section title, Section Content,
   and HRSmart job listing data are enabled and in that order.
   Make sure that the labels for the three enabled fields are "hidden".

### Enable the job listing paragraph type for the content type

Instructions from this point forward are only necessary once it's time to display the
job listings on the website.

1. Navigate to the Manage fields tab for the content type that will display the job
   listings paragraph.
2. Edit the field that references paragraphs.
3. Under the Reference type section, make sure that "HRSmart job listings" is checked.

### Add the job listing paragraph to a page of content

1. Edit the page for that content type and add a new paragraph section by clicking the
   "Add HRSmart job listings".
2. Fill in the Section title and Section Content as appropriate and choose whether to
   display the section title or not.
3. View the page to verify that the section title and content appear.
   The job listings table will appear, but will only show the message:
   "The list of job postings is not available."

### Configure the remote sources and rendering locations

1. Navigate to the Configuration > Content authoring > HSS Job Listings and fill in the
   HRSmart API and destination content structure fields as described.
   The machine name fields will default to the names set above in the
   "Add a new job listing paragraph type" step above.
2. Specify the HRSmart API key in a Drupal settings file so that it won't be captured
   in configuration:
   `$config['yukon_hss_job_listings.settings']['hrsmart_xml_api_key'] = 'xxx...';`

### Run the Drupal cron job

If everything is correctly configured, the job listings table will appear as expected.
