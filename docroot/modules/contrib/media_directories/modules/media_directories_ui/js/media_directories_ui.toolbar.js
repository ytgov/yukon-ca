(function ($, Drupal, drupalSettings, debounce) {
  /**
   * Media Browser toolbar functionality.
   *
   * @type {{init: Drupal.MediaBrowser.toolbar.init, buttons: {submit: (*|jQuery|HTMLElement), media_edit: (*|jQuery|HTMLElement), media_delete: (*|jQuery|HTMLElement), media_add: (*|jQuery|HTMLElement)}, selectionChanged: Drupal.MediaBrowser.toolbar.selectionChanged}}
   */
  Drupal.MediaBrowser.toolbar = {
    buttons: {
      media_add: $('#browser-add-media'),
      media_edit: $('#browser-edit-media'),
      media_translate: $('#browser-translate-media'),
      media_delete: $('#browser-delete-media'),
      submit: $('#edit-submit')
    },
    inputs: {
      media_name_filter: $('#browser-filter-name'),
    },
    moveFocusFromNameFilter: false,
    init: function init() {

      // Filter input
      Drupal.MediaBrowser.searchString = this.inputs.media_name_filter.val();
      this.inputs.media_name_filter.on('focus', function (e) {
        // Closing the dialog re-focuses the input, so make sure to skip that.
        if (Drupal.MediaBrowser.toolbar.moveFocusFromNameFilter) {
          Drupal.MediaBrowser.toolbar.moveFocusFromNameFilter = false;
          $('#edit-submit').focus();
        }
        else {
          if (drupalSettings.media_directories.selection_mode != 'keep') {
            if (Drupal.MediaBrowser.getSelectedMids().length > 0) {
              var $warningDialog = $('<div>' + Drupal.theme('mediaDirectoriesDeSelectionWarningSearchModal') + '</div>').appendTo('body');
              Drupal.dialog($warningDialog, {
                title: Drupal.t('Clear selection?'),
                buttons: [{
                  text: Drupal.t('Cancel'),
                  click: function click() {
                    Drupal.MediaBrowser.toolbar.moveFocusFromNameFilter = true;
                    $(this).dialog('close');
                  }
                }, {
                  text: Drupal.t('OK'),
                  click: function click() {
                    Drupal.MediaBrowser.clearMediaSelection(false);
                    Drupal.MediaBrowser.toolbar.selectionChanged();
                    $(this).dialog('close');
                  }
                }]
              }).showModal();
            }
          }
        }
      });
      this.inputs.media_name_filter.on('keyup', debounce(Drupal.MediaBrowser.toolbar.filterMediaBrowserByName, 400));

      // Add new media button.
      this.buttons.media_add.on('click', function (e) {
        e.preventDefault();
        var submitAjax = false;
        if (drupalSettings.media_directories.selection_mode != 'keep') {
          if (Drupal.MediaBrowser.getSelectedMids().length > 0) {
            var $warningDialog = $('<div>' + Drupal.theme('mediaDirectoriesDeSelectionWarningAddMedia') + '</div>').appendTo('body');
            Drupal.dialog($warningDialog, {
              title: Drupal.t('Clear selection?'),
              buttons: [{
                text: Drupal.t('Cancel'),
                click: function click() {
                  $(this).dialog('close');
                }
              }, {
                text: Drupal.t('OK'),
                click: function click() {
                  var ajaxSettings = {
                    url: Drupal.MediaBrowser.getUrl('media.add'),
                    submit: {
                      active_directory: Drupal.MediaBrowser.activeDirectory,
                      target_bundles: Drupal.MediaBrowser.targetBundles,
                      cardinality: drupalSettings.media_directories.cardinality,
                      selection_mode: drupalSettings.media_directories.selection_mode
                    }
                  };
                  Drupal.ajax(ajaxSettings).execute();
                  $(this).dialog('close');
                }
              }]
            }).showModal();
          }
          else {
            submitAjax = true;
          }
        }
        else {
          submitAjax = true;
        }
        if (submitAjax) {
          var ajaxSettings = {
            url: Drupal.MediaBrowser.getUrl('media.add'),
            submit: {
              active_directory: Drupal.MediaBrowser.activeDirectory,
              target_bundles: Drupal.MediaBrowser.targetBundles,
              cardinality: drupalSettings.media_directories.cardinality,
              selection_mode: drupalSettings.media_directories.selection_mode
            }
          };
          Drupal.ajax(ajaxSettings).execute();
        }
      });

      // Edit media button.
      this.buttons.media_edit.on('click', function (e) {
        e.preventDefault();

        if ($(this).hasClass('is-disabled')) {
          return;
        }

        var ajaxSettings = {
          url: Drupal.MediaBrowser.getUrl('media.edit'),
          submit: {
            active_directory: Drupal.MediaBrowser.activeDirectory,
            media_items: Drupal.MediaBrowser.getSelectedMids()
          }
        };
        Drupal.ajax(ajaxSettings).execute();
      });

      // Translate media button.
      this.buttons.media_translate.on('click', function (e) {
        e.preventDefault();

        if ($(this).hasClass('is-disabled')) {
          return;
        }

        var mids = Drupal.MediaBrowser.getSelectedMids();
        window.open(drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix + 'media/' + mids[0] + '/edit/translations');
      });

      // Delete media button.
      this.buttons.media_delete.on('click', function (e) {
        e.preventDefault();

        if ($(this).hasClass('is-disabled')) {
          return;
        }

        var mids = [];
        $.each(Drupal.MediaBrowser.getSelectedMids(), function (key, value) {
          mids.push(value);
        });

        var ajaxSettings = {
          url: Drupal.MediaBrowser.getUrl('media.delete'),
          submit: {
            media_items: mids
          }
        };

        Drupal.ajax(ajaxSettings).execute();
      });

      // Set initial button states.
      this.selectionChanged();
    },

    /**
     * Set correct states for toolbar buttons.
     */
    selectionChanged: function selectionChanged() {
      var selected = Drupal.MediaBrowser.getSelectedMids();
      var remaining = Drupal.MediaBrowser.remainingItems;
      var status_text = null;

      if (selected.length === 1) {
        this.buttons.media_edit.removeClass('is-disabled');
        var $element = Drupal.MediaBrowser.getSelectedElements();

        if ($element.length > 0) {
          $element = $element[0];
          var show_translation_button = false;
          $.each(drupalSettings.media_directories.media_translation_enabled, function (type, status) {
            if ($element.hasClass('media-type--' + type)) {
              show_translation_button = true;
            }
          });

          if (show_translation_button) {
            this.buttons.media_translate.removeClass('is-disabled');
          }
        }

        this.buttons.media_delete.removeClass('is-disabled');
        this.buttons.submit.removeAttr('disabled');
      } else if (selected.length === 0) {
        this.buttons.media_edit.addClass('is-disabled');
        this.buttons.media_translate.addClass('is-disabled');
        this.buttons.media_delete.addClass('is-disabled');
        this.buttons.submit.attr('disabled', 'disabled');
      } else {
        this.buttons.media_edit.removeClass('is-disabled');
        this.buttons.media_translate.addClass('is-disabled');
        this.buttons.media_delete.removeClass('is-disabled');
        this.buttons.submit.removeAttr('disabled');
      }

      if (Drupal.MediaBrowser.cardinality === -1) {
        status_text = Drupal.formatPlural(selected.length, '1 item selected', '@count items selected');
      } else {
        status_text = Drupal.formatPlural(remaining, '@selected of 1 remaining item selected', '@selected of @count remaining items selected', {
          '@selected': selected.length
        });
      }

      $('.browser--footer .browser-status').text(status_text);
    },

    /**
     * Filter media items in current folder by name.
     */
    filterMediaBrowserByName: function filterMediaBrowserByName(searchTerm) {
      if (searchTerm !== undefined && typeof searchTerm === 'string') {
        // Otherwise searchTerm will be a browser event.
        Drupal.MediaBrowser.toolbar.inputs.media_name_filter.val(searchTerm);
      }

      // Handle enter key up event here.
      if (typeof searchTerm === 'object' && searchTerm && searchTerm.keyCode === 13) {
        searchTerm.preventDefault();
        return false;
      }

      Drupal.MediaBrowser.searchString = Drupal.MediaBrowser.toolbar.inputs.media_name_filter.val();
      Drupal.MediaBrowser.loadDirectoryContent(Drupal.MediaBrowser.activeDirectory);
    }
  };

  Drupal.theme.mediaDirectoriesDeSelectionWarningSearchModal = function () {
    return '<p>' + Drupal.t('Your current selection will be cleared when you start searching.') + '</p><small class="description">' + Drupal.t('Media directories browser is in reset selection mode, as the entity browser does not support the selection of in-existent  items.') + '</small>';
  };

  Drupal.theme.mediaDirectoriesDeSelectionWarningChangeDirectoryModal = function () {
    return '<p>' + Drupal.t('Your current selection will be cleared when you change the directory.') + '</p><small class="description">' + Drupal.t('Media directories browser is in reset selection mode, as the entity browser does not support the selection of in-existent  items.') + '</small>';
  };

  Drupal.theme.mediaDirectoriesDeSelectionWarningAddMedia = function () {
    return '<p>' + Drupal.t('Your current selection will be cleared when you add new media.') + '</p><small class="description">' + Drupal.t('Media directories browser is in reset selection mode, as the entity browser does not support the selection of in-existent  items.') + '</small>';
  };
})(jQuery, Drupal, drupalSettings, Drupal.debounce);
