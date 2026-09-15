# D11 upgrade: testing checklist for content editors

This is a quick checklist to confirm content editing still works normally after the site upgrade. It's meant for anyone who creates, edits, or moderates content — no technical background needed.

**How to test:** log in with your normal account and follow the steps below. For each item, confirm you see the "expected result." If something looks different, throws an error, or doesn't work as described, note the page you were on and what happened, and pass that along.

**Roles:** Editor, Publisher, Writer, Blog Author, Site Administrator.

## 1. Content moderation status

- Create or edit content of one of these types: Campground Directory Record, Department, Directory Records/Places, Event, or News.
- **Expected:** the moderation status field (Draft / Needs Review / Published, etc.) appears normally, and you can move content through your usual workflow.

## 2. Icons on navigation/jump-point content

- Open one of these existing pages for editing:
  - <https://yukon.cms-uat.yukon.ca/decide-you-ride-drive-sober>
  - <https://yukon.cms-uat.yukon.ca/winter-driving-road-safety-awareness>
  - <https://yukon.cms-uat.yukon.ca/40-assets>
  - <https://yukon.cms-uat.yukon.ca/yukon-grown>
  - <https://yukon.cms-uat.yukon.ca/restoring-shakwak-corridor>
- **Expected:** the icons on any "jump point"/navigation sections show up correctly in the editing preview, with no error message.
- Also try adding or changing an icon on any jump-point section (new or existing content) — the icon picker should work normally.

## 3. Workbench dashboard

- Visit your Workbench dashboard (My Workbench, recent content, etc.).
- If you also work with the French version of the site, check the same dashboard in French.
- **Expected:** headings and messages show normal, readable text — not the word "Array" or anything garbled.

## 4. Rich text editor

- Create or edit a piece of content with a body field (e.g. a News item) and try:
  - Adding a bulleted or numbered list.
  - Using one of the heading style options in the toolbar.
- **Expected:** the toolbar loads normally, formatting applies as expected, and the content saves correctly.

## 5. Permissions (Site Administrators only)

- Go to **People → Permissions**.
- **Expected:** none of these roles have "Rebuild content access permissions" checked: Blog Author, Editor, Publisher, Site Administrator, Translator, Writer.
