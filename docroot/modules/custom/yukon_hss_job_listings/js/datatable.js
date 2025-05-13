(function ($, window, Drupal) {
  Drupal.behaviors.yukon_hss_job_listings = {
    attach: function (context, settings) {
      if (context === document) {
        if (window.location.href.indexOf("/fr/") > -1) {
          currentLanguage = {
            "emptyTable": "The list of job postings is not available. [Fr]",
            "info": "_START_ to _END_ of _TOTAL_ jobs [Fr]",
            "infoEmpty": "",
            "infoFiltered": "(from _MAX_ total jobs) [Fr]",
            "loadingRecords": "",
            "search": "Search jobs [Fr]:",
            "zeroRecords": "No matching jobs were found. [Fr]"
          }
        } else {
          currentLanguage = {
            "emptyTable": "The list of job postings is not available.",
            "info": "_START_ to _END_ of _TOTAL_ jobs",
            "infoEmpty": "",
            "infoFiltered": "(from _MAX_ total jobs)",
            "loadingRecords": "",
            "search": "Search jobs:",
            "zeroRecords": "No matching jobs were found."
          }
        }

        new DataTable('#yukon-hss-hrsmart-job-listings', {
          language: currentLanguage,
          pageLength: 6,
          lengthChange: false,
          responsive: true,
          columnDefs: [
            // Highest priority: Job title
            { responsivePriority: 1, targets: 2 },
            // Second-highest priority: Req. #, Close date
            { responsivePriority: 2, targets: 1 },
            { responsivePriority: 2, targets: -1 }
          ]
        });
      }
    }
  };
})(jQuery, window, Drupal);
