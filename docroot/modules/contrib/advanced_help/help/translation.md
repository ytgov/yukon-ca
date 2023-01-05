To translate a help-file indexed by **Advanced help**, first create a
directory `translations/help/language` in the project's root
directory, where *language* is the language code that appears on the
*Languages* page in the administrative UI.

Then, copy the `.help.yml` file and all the `.html`, `.md` and `.txt`
files from the help directory into this.  If you need to alter an
image to use it in a translation, you may also put the altered image
there.

In the topics section, the `.help.yml` file only needs to keep the
topic names (unaltered) and titles (translated). If there is a `name`
or `index name` setting in the 'advanced help settings' portion, that
should be retained. Any retained settings should be translated. The
rest of the data in the `.help.yml` file may be discarded or ignored.

Each `.html`, `.md` and `.txt` file should then be translated in
place.

When translating a `.html` file, you will find that the
`&path&` keyword (used for images and links) will lead to the
original directory. If you must translate items that are linked, such
as images containing text, use `&trans_path&` instead, which
will lead to the translated directory. This will allow you to pick and
choose which linked items, if any, will be translated.

If a topic is not translated, the default (untranslated) version will
be shown instead.

## Translating Advanced help's help files

If you want to help with the translation of **Advanced help** help
texts for a particular language, look for an issue named named
“Translation to XXX” (where “XXX” is the language you want to
translate the help texts to) in the issue queue for [Advanced
help][1].  If such an issue does not exist, please can create it.
Choose *Category* “Task”, *Status* “Needs review” and *Component*
“Documentation”.  Upload translated files as an attachment (change the
file type from `.html` to `.txt` to be allowed to upload).

Uploaded translations will be included in the next version if reviewed
and approved by other users (i.e. gets to status “RTBC”).

[1]: https://www.drupal.org/project/issues/advanced_help