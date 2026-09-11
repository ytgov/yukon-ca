# D11 upgrade: testing checklist for translators

A quick checklist covering translation-related editing after the site upgrade. No technical background needed.

**How to test:** log in with your normal account and follow the steps below. If something looks different, throws an error, or doesn't work as described, note the page you were on and what happened, and pass that along.

**Roles:** Translator (and anyone else who edits French content).

## 1. Editing a translated multi-step page

- Open the French translation of a multi-step page that has nested "chart" items (example: the page titled "The application process").
- **Expected:** you should **not** see "Add"/"Remove" buttons for the nested chart items while editing the French translation. This is correct, intended behavior — not a bug. (Previously those buttons briefly appeared but were never functional here.)
- The existing chart items should still display and edit correctly in both English and French.

## 2. Workbench dashboard French text (optional)

- View the Workbench dashboard (My Workbench, recent content, etc.) in French.
- **Expected:** the text should read as normal, correct French — not corrupted or showing raw code.
- This particular French wording was newly written as part of the upgrade rather than recovered from an existing translation, so if you spot anything you'd phrase differently, feel free to flag it — this is a nice-to-have wording check, not a pass/fail test.
