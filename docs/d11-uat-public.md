# D11 upgrade: testing checklist for general site visitors

A quick checklist for anyone checking the public-facing site after the upgrade. **No login needed** — just browse these pages like any visitor would.

**How to test:** visit each page below and confirm you see the "expected result." If something looks different, throws an error, or doesn't work as described, note the page URL and what happened, and pass that along.

## 1. Page feedback block

- Visit <https://yukon.cms-uat.yukon.ca/decide-you-ride-drive-sober> — **expected:** no "was this page helpful" feedback box near the bottom.
- Visit <https://yukon.cms-uat.yukon.ca/news/omicron-cases-increasing-quickly> — **expected:** the feedback box does show up.

## 2. Page title display

- Visit each of these pages — **expected:** no page title heading shown at the top of the content area:
  - <https://yukon.cms-uat.yukon.ca/>
  - <https://yukon.cms-uat.yukon.ca/engagements>
  - <https://yukon.cms-uat.yukon.ca/documents>
  - <https://yukon.cms-uat.yukon.ca/decide-you-ride-drive-sober>
  - <https://yukon.cms-uat.yukon.ca/blogs/digital-information-and-services>
- Visit <https://yukon.cms-uat.yukon.ca/news/omicron-cases-increasing-quickly> — **expected:** the page title does show normally here.

## 3. Feedback form submission

- Submit the page feedback form from <https://yukon.cms-uat.yukon.ca/news/omicron-cases-increasing-quickly> — **expected:** submits normally, no odd leftover placeholder text anywhere.
- Submit it again from <https://yukon.cms-uat.yukon.ca/news/omicron-cases-increasing-quickly?ref=test> — **expected:** same, submits cleanly.

## 4. Icons on pages

- Visit each of these pages — **expected:** icons show up correctly, no error message anywhere on the page:
  - <https://yukon.cms-uat.yukon.ca/decide-you-ride-drive-sober>
  - <https://yukon.cms-uat.yukon.ca/winter-driving-road-safety-awareness>
  - <https://yukon.cms-uat.yukon.ca/40-assets>
  - <https://yukon.cms-uat.yukon.ca/yukon-grown>
  - <https://yukon.cms-uat.yukon.ca/restoring-shakwak-corridor>

## 5. Older list-style content

- Visit each of these pages — **expected:** loads normally, lists and formatting display correctly:
  - <https://yukon.cms-uat.yukon.ca/yukon-health-care-and-social-services-bursary>
  - <https://yukon.cms-uat.yukon.ca/health-and-wellness/care-services/request-coverage-services-not-included-yukon-health-insurance-plan>

## 6. Broken link handling

- Visit <https://yukon.cms-uat.yukon.ca/taxonomy/term/999999999> (an intentionally invalid page).
- **Expected:** a normal "page not found" message — not a server error page.

## 7. Mining Investment Yukon page

- Visit <https://yukon.cms-uat.yukon.ca/doing-business/funding-and-supports-business/mining-investment-yukon>.
- **Expected:** the page loads normally, and the Twitter/X link near the bottom of the content works and points somewhere sensible.
