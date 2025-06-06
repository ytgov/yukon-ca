(function ($, window, Drupal) {
  Drupal.behaviors.yukon_hss_job_listings = {
    attach: function (context, settings) {
      if (context === document) {
        if (window.location.href.indexOf("/fr/") > -1) {
          currentLanguage = {
            "emptyTable": "La liste de postes n’est pas accessible. ",
            "info": "_START_ à _END_ de _TOTAL_ postes",
            "infoEmpty": "",
            "infoFiltered": "(maximum de _MAX_ postes affichés)",
            "loadingRecords": "",
            "search": "Rechercher un poste&nbsp;:",
            "zeroRecords": "Aucun poste ne correspond à la recherche."
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
