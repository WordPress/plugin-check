## Trialware / Locked Functionality Issues

A trialware issue occurs when functionality shipped in the plugin's own codebase is artificially restricted behind a license key, trial period, usage quota, "pro"/premium plan gate, or payment check, in violation of the WordPress.org guideline that plugins must be fully functional.

Using the case as a reference, check the code to determine if it genuinely locks bundled functionality or if it is a false positive.

Details:
- Genuinely flagged: code that checks a license/trial/quota/payment condition and then disables, hides, or short-circuits functionality that otherwise exists in the plugin's own files.
- A reference to a **separate, standalone premium plugin or add-on** sold by the same author (e.g. "Upgrade to Acme Pro" linking to a different plugin/product) is NOT trialware — that is a legitimate freemium business model, not locked bundled code.
- License-key checks used only to unlock **updates or support** (e.g. EDD/WooCommerce-style update-checker license activation) are NOT trialware, unless the same check also disables functionality already present in the submitted code.
- Checks against **external service API keys** (e.g. a third-party SaaS API key required to call that external service) are NOT trialware — the plugin isn't restricting its own bundled code, it's authenticating to an external system it doesn't control.
- Generic marketing copy, readme wording, or UI strings that merely mention "premium", "pro", or "trial" without any corresponding code path that disables bundled functionality are NOT trialware.
- Quota/limit checks tied to an **external resource** (API rate limits, storage quotas on a remote service) are NOT trialware; quota checks that disable the plugin's own local features once a count is reached ARE trialware.
- If the flagged code is inside test fixtures, examples, or clearly non-executed sample code, it is a false positive.
