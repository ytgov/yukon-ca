(function ($, Drupal, drupalSettings, once) {
  if (typeof $.jstree === 'undefined') {
    $(once('media-directories-no-jstree', '.browser--listing')).append(Drupal.t('Could not load jsTree!') + ' <a href="' + Drupal.url('admin/reports/status') + '" target="_blank">' + Drupal.t('Check status page') + '</a>');
    return;
  }
  Drupal.MediaBrowser.tree = function () {
    $(Drupal.MediaBrowser.treeSelector).jstree({
      plugins: ['dnd', 'wholerow', 'contextmenu', 'sort'],
      multiple: false,
      core: {
        check_callback: Drupal.MediaBrowser.treeCheckCallback,
        data: {
          url: Drupal.MediaBrowser.getUrl('directory.tree'),
          data: function data(node) {
            return {
              'id': node.id
            };
          }
        },
        themes: {
          ellipsis: true
        }
      },
      sort: function (a, b) {
        var weight_a = this.get_node(a).original.weight;
        var weight_b = this.get_node(b).original.weight;
        if (weight_a !== weight_b) {
          return this.get_node(a).original.weight > this.get_node(b).original.weight ? 1 : -1;
        } else {
          return this.get_text(a) > this.get_text(b) ? 1 : -1;
        }
      },
      dnd: {
        copy: false,
        is_draggable: function is_draggable(nodes) {
          // Root node is not draggable.
          var is_root = nodes[0].id === 'dir-root';
          return !is_root;
        }
      },
      contextmenu: {
        items: Drupal.MediaBrowser.treeContextMenu
      }
    })
    .on('changed.jstree', function (e, data) {
      if (data.action === 'select_node') {
        var doLoadContent = false;
        if (drupalSettings.media_directories.selection_mode != 'keep') {
          if (!Drupal.MediaBrowser.keepSelectionOnChange && Drupal.MediaBrowser.getSelectedMids().length > 0) {
            var $warningDialog = $('<div>' + Drupal.theme('mediaDirectoriesDeSelectionWarningChangeDirectoryModal') + '</div>').appendTo('body');
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
                  var directory_id = data.node.a_attr["data-tid"];
                  Drupal.MediaBrowser.loadDirectoryContent(directory_id);
                  Drupal.MediaBrowser.activeDirectory = directory_id;

                  if (drupalSettings.media_directories.selection_mode != 'keep') {
                    if (!Drupal.MediaBrowser.keepSelectionOnChange) {
                      // Clear selection from global storage.
                      Drupal.MediaBrowser.clearMediaSelection();
                    }
                  }

                  Drupal.MediaBrowser.keepSelectionOnChange = false;
                  $(this).dialog('close');
                }
              }]
            }).showModal();
          }
          else {
            doLoadContent = true;
          }
        }
        else {
          doLoadContent = true;
        }

        if (doLoadContent) {
          var directory_id = data.node.a_attr["data-tid"];
          Drupal.MediaBrowser.loadDirectoryContent(directory_id);
          Drupal.MediaBrowser.activeDirectory = directory_id;
          Drupal.MediaBrowser.keepSelectionOnChange = false;
        }
      }
    })
    .on('loaded.jstree', function () {
      Drupal.MediaBrowser.loadDirectoryContent(-1);

      // Clear selection from global storage.
      Drupal.MediaBrowser.clearMediaSelection(false);
    })
    .on('rename_node.jstree', function (event, data) {
      var directory_id = $('#' + data.node.a_attr['id']).data('tid');
      Drupal.MediaBrowser.renameDirectory(directory_id, data.text);
    })
    .on('move_node.jstree', function (event, data) {
      var move_directory_id = data.node.a_attr['data-tid'];
      var target_directory_id = data.parent === '#' ? -1 : $('#' + data.parent + '_anchor').data('tid');
      Drupal.MediaBrowser.moveDirectoryToDirectory(move_directory_id, target_directory_id);
    });
  };

  /**
   * Global event bindings.
   */
  Drupal.MediaBrowser.globalBindings = function () {
    $(once('media-browser-dnd-move', document)).each(function () {
      // Drag&Drop indicators for media dnd operation.
      $(this).on('dnd_move.vakata', function (e, data) {
        // Only work on media: folders drag&drop are handled from jsTree events directly.
        if (data.data.handleDndStopMedia) {
          // Check if the tree instance is dnd enabled.
          var jsTree = $(Drupal.MediaBrowser.treeSelector).jstree(true);
          if (jsTree && jsTree._data && jsTree._data.dnd) {
            // Check if hovering a folder.
            ref = jsTree.settings.dnd.large_drop_target ? $(data.event.target).closest('.jstree-node').children('.jstree-anchor') : $(data.event.target).closest('.jstree-anchor');
            if (ref && ref.length && ref.parent().is('.jstree-closed, .jstree-open, .jstree-leaf')) {
              if (data.data.currentDirectory !== data.event.target.dataset.tid) {
                data.helper.find('.jstree-icon').first().removeClass('jstree-er').addClass('jstree-ok');
              } else {
                data.helper.find('.jstree-icon').removeClass('jstree-ok').addClass('jstree-er');
              }
            }
          }
        }
      });

      // Drag and Drop event bind for external sources.
      $(this).on('dnd_stop.vakata', function (e, data) {
        var targetDirectory = data.event.target.dataset.tid;
        // Only work on media: folders drag&drop are handled from jsTree events directly.
        if (data.data.handleDndStopMedia && data.data.currentDirectory !== targetDirectory) {
          var media_items = [];
          for (var i = 0; i < data.data.nodes.length; i++) {
            media_items.push(data.data.nodes[i].id);
          }
          Drupal.MediaBrowser.moveMediaToDirectory(media_items, targetDirectory);
        }
      });
    });
  };

  /**
   * Load directory content.
   */
  Drupal.MediaBrowser.loadDirectoryContent = function (directory_id) {
    var arguments = {
      directory_id: directory_id,
      target_bundles: Drupal.MediaBrowser.targetBundles,
      media_name: Drupal.MediaBrowser.name
    };
    if (Drupal.MediaBrowser.searchString) {
      arguments['media_name'] = Drupal.MediaBrowser.searchString;
    }
    var ajaxSettings = {
      url: Drupal.MediaBrowser.getUrl('directory.content'),
      submit: arguments
    };

    // Lock the UI.
    Drupal.MediaBrowser.startLoader();
    Drupal.ajax(ajaxSettings).execute().done(function () {
      Drupal.MediaBrowser.activeDirectory = directory_id;
      Drupal.MediaBrowser.media.init($('.browser--listing'));

      // Unlock the UI.
      Drupal.MediaBrowser.stopLoader();

      // Set previous selection, if there is any.
      $.each(Drupal.MediaBrowser.getSelectedMids(), function (key, value) {
        var $element = Drupal.MediaBrowser.getMediaElement(value);
        if ($element.length > 0) {
          $element.addClass('selected');
          $('input[type="checkbox"]', $element).prop('checked', true);
        }
      });
      Drupal.MediaBrowser.toolbar.selectionChanged();
    });
  };

  /**
   * Move media item(s) into directory.
   */
  Drupal.MediaBrowser.moveMediaToDirectory = function (media_items, directory_id) {
    if (media_items && directory_id) {
      Drupal.MediaBrowser.startLoader();
      var ajaxSettings = {
        url: Drupal.MediaBrowser.getUrl('media.move'),
        submit: {
          directory_id: directory_id,
          media_items: media_items,
          target_bundles: Drupal.MediaBrowser.targetBundles
        }
      };
      Drupal.ajax(ajaxSettings).execute().done(function () {
        Drupal.MediaBrowser.stopLoader();
      });

      // User is moving media to new directory, we need to clean selection.
      Drupal.MediaBrowser.clearMediaSelection();
    } else {
      console.log('Parameters missing!');
    }
  };

  /**
   * Move directory into directory.
   *
   * @param move_directory_id
   * @param directory_id
   */
  Drupal.MediaBrowser.moveDirectoryToDirectory = function (move_directory_id, directory_id) {
    if (move_directory_id && directory_id) {
      var ajaxSettings = {
        url: Drupal.MediaBrowser.getUrl('directory.move'),
        submit: {
          directory_id: directory_id,
          move_directory_id: move_directory_id,
          target_bundles: Drupal.MediaBrowser.targetBundles
        }
      };
      Drupal.ajax(ajaxSettings).execute();
    } else {
      console.log('Parameters missing!');
    }
  };

  /**
   * Rename directory.
   *
   * @param directory_id
   * @param new_name
   */
  Drupal.MediaBrowser.renameDirectory = function (directory_id, new_name) {
    var ajaxSettings = {
      url: Drupal.MediaBrowser.getUrl('directory.rename'),
      submit: {
        directory_id: directory_id,
        directory_new_name: new_name
      }
    };
    Drupal.ajax(ajaxSettings).execute();
  };

  /**
   * Delete directory.
   *
   * @param directory_id
   */
  Drupal.MediaBrowser.deleteDirectory = function (directory_id) {
    if (directory_id === -1 || directory_id === '') {
      console.log('Parameter missing!');
      return;
    }

    var ajaxSettings = {
      url: Drupal.MediaBrowser.getUrl('directory.delete'),
      submit: {
        directory_id: directory_id,
        target_bundles: Drupal.MediaBrowser.targetBundles
      }
    };
    Drupal.ajax(ajaxSettings).execute();
  };

  /**
   * Callback for checking if operation is allowed.
   *
   * @param operation
   * @param node
   * @param node_parent
   * @param node_position
   * @param more
   * @returns {boolean}
   */
  Drupal.MediaBrowser.treeCheckCallback = function (operation, node, node_parent, node_position, more) {
    // Media item is foreign and we don't allow any modifications.
    if (more && more.is_foreign) {
      return false;
    }

    if (operation === 'move_node') {
      // While dragging, don't allow dropping between nodes.
      if(more.dnd === true && more.pos === 'i') {
        if (node.parent !== node_parent.id) {
            return true;
        }
      }
      // Handle the last event aka "drop".
      else if (more.core === true) {
        if (node.parent !== node_parent.id) {
            return true;
        }
      }
    }
    // Create/rename and edit operations are allowed.
    else if (operation === 'create_node' || operation === 'rename_node' || operation === 'edit') {
      return true;
    }

    return false;
  };

  /**
   * Callback for context menu items.
   *
   * @param node
   * @returns {{rename: {_disabled: boolean, action: rename.action, label: *},
   *   new_directory: {action: new_directory.action, label: *}, delete:
   *   {_disabled: boolean, action: delete.action, label: *}}}
   */
  Drupal.MediaBrowser.treeContextMenu = function (node) {
    // Root node cannot be deleted or renamed.
    var is_root = node.id === 'dir-root';

    // Elements with children cannot be deleted!
    var has_children = node.children.length > 0;

    var menu_items = {};
    if (drupalSettings.media_directories.vocabulary_permissions['create']) {
      menu_items.new_directory = {
        label: Drupal.t('New folder'),
        action: function action(data) {
          var inst = $.jstree.reference(data.reference),
          obj = inst.get_node(data.reference);
          $.post({
            url: Drupal.MediaBrowser.getUrl('directory.add'),
            data: {
              action: 'create_directory',
              parent_id: obj.a_attr['data-tid'],
              name: Drupal.t('New folder')
            },
            success: function success(data) {
              if (data.hasOwnProperty('id')) {
                inst.create_node(obj, data, "last", function (new_node) {
                  try {
                    inst.edit(new_node);
                  } catch (ex) {
                    setTimeout(function () {
                      inst.edit(new_node);
                    }, 0);
                  }
                });
              }
            }
          });
        }
      };
    }

    if (drupalSettings.media_directories.vocabulary_permissions['update']) {
      menu_items.rename = {
        label: Drupal.t('Rename'),
        _disabled: is_root,
        action: function action(data) {
          var inst = $.jstree.reference(data.reference),
          obj = inst.get_node(data.reference);
          inst.edit(obj);
        }
      };
    }

    if (drupalSettings.media_directories.term_translation_enabled &&
        drupalSettings.media_directories.vocabulary_permissions['translate']) {
      menu_items.show_translations = {
        label: Drupal.t('Show translations in new tab'),
        _disabled: is_root,
        action: function action(data) {
          window.open('/taxonomy/term/' +$(data.reference).data('tid') + '/translations');
        }
      };
    }

    if (drupalSettings.media_directories.vocabulary_permissions['delete']) {
      menu_items.delete = {
        label: Drupal.t('Delete'),
        _disabled: is_root || has_children,
        action: function action(node) {
          Drupal.MediaBrowser.deleteDirectory($(node.reference).data('tid'));
        }
      };
    }

    return menu_items;
  };
})(jQuery, Drupal, drupalSettings, once);
