(function ($, Drupal, once) {

  'use strict';

  var source = '//cdnjs.cloudflare.com/ajax/libs/ace/1.11.2/ace.min.js';

  Drupal.behaviors.yamlEditor = {
    attach: function () {
      var initEditor = function () {
        once('yaml-editor', $('textarea[data-yaml-editor]')).forEach(function (item) {
          var $textarea = $(item);
          var $editDiv = $('<div>').insertBefore($textarea);

          $textarea.addClass('visually-hidden');

          // Init ace editor.
          var editor = ace.edit($editDiv[0]);
          editor.getSession().setValue($textarea.val());
          editor.getSession().setTabSize(2);
          editor.setOptions({
            minLines: 3,
            maxLines: 20
          });

          // Update Drupal textarea value.
          editor.getSession().on('change', function () {
            $textarea.val(editor.getSession().getValue());
          });
        });
      };

      // Check if Ace editor is already available and load it from source cdn otherwise.
      if (typeof ace !== 'undefined') {
        initEditor();
      }
      else {
        $.getScript(source, initEditor);
      }
    }
  }
})(jQuery, Drupal, once);
