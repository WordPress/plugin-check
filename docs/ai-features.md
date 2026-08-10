[Back to overview](./README.md)

# AI-Powered Features & Configuration

Plugin Check (PCP) includes AI-powered capabilities to help plugin developers with code review, false-positive detection, naming evaluation, and trademark compliance. These features work with the WordPress AI connectors available in WordPress 7.0 and newer.

---

## 1. Prerequisites

Before enabling AI features, ensure your environment meets these requirements:

* **WordPress 7.0 or higher** — Plugin Check uses the AI APIs introduced in WordPress 7.0. Older versions will not have AI features available.
* **PHP 7.4 or higher** — Standard requirement for Plugin Check.
* **AI support enabled** — Your site must have AI support enabled in WordPress. If AI support has been disabled by a plugin or custom configuration, AI features will be unavailable.

---

## 2. Installing & Activating AI Connectors

Plugin Check communicates with AI providers through the WordPress AI Client system.

1. **Ensure WordPress 7.0+** is installed with AI connector support enabled.
2. **Supported AI Providers** include:
   * **OpenAI** — e.g., GPT-4o, GPT-4o-mini
   * **Anthropic** — e.g., Claude 4.6 Opus
   * **Google Gemini** — e.g., Gemini 3.1 Pro, Gemini 3.5 Flash
   * **Other providers** registered with the WordPress AI Client system.

---

## 3. Connecting an AI Provider

API credentials are managed centrally in WordPress — not inside Plugin Check.

1. Navigate to **Settings > Connectors** in your WordPress admin dashboard.
2. Select your preferred AI provider (e.g., OpenAI, Anthropic, or Google).
3. Enter your API credentials and complete the connection setup.
4. Once connected and active, Plugin Check will automatically discover the provider and its available models.

---

## 4. Selecting an AI Model

Plugin Check automatically discovers available models and filters them to include only those that support text generation. Audio-only or speech models are excluded automatically.

You can configure model preferences in three places:

### WP Admin: Settings Page
1. Go to **Settings > Plugin Check**.
2. Under the **AI Code Review** section, use the **AI Model** dropdown to choose from connected providers and models.
3. Selecting **Default (auto)** lets WordPress use its default text model.
4. *Tip*: Larger, code-capable models (such as Claude 4.6 Opus or Gemini 3.1 Pro) provide the best accuracy for code analysis.

### WP Admin: Namer Tool
1. Go to **Tools > Plugin Check Namer**.
2. Use the **AI model** dropdown to choose a specific model or select **Automatic (recommended)**.

### WP-CLI
Pass the `--ai-model` flag when running checks from the command line:
```bash
wp plugin check my-plugin-slug --ai --ai-model=openai::gpt-4o
wp plugin check my-plugin-slug --ai --ai-model=anthropic::claude-3-5-sonnet-20241022
```

---

## 5. AI Analysis & False-Positive Detection

Static code analysis can sometimes flag safe code as an issue (false positives). Enabling AI Analysis lets an AI model review flagged code in context to determine if each finding is a genuine issue or a false positive.

### How It Works
* **WP-CLI**: Pass `--ai` when running `wp plugin check <slug>`.
* **WP Admin**: Enable the AI Analysis option in the check runner.
* **Process**:
  1. Plugin Check runs all static and runtime checks as normal.
  2. Flagged issues are grouped by category and sent to the AI model along with surrounding code context.
  3. The AI determines whether each finding is a **genuine issue** or a **false positive**, with a brief explanation.
  4. False positives are annotated in the results, helping you focus on real problems.

---

## 6. Severity Thresholds

To save API usage and ensure critical issues are never dismissed by AI, Plugin Check enforces severity thresholds.

Configure under **Settings > Plugin Check** (Severity Threshold section):
* **Errors Threshold**: Default **7** (range: 1–10).
* **Warnings Threshold**: Default **6** (range: 1–10).

### How It Works
* Only issues with severity **below** the threshold are sent to the AI for review.
* Issues **at or above** the threshold are considered high-severity and always reported directly — they bypass AI review entirely.
* *Why?* Low-severity issues have higher false-positive rates and benefit most from AI review. High-severity issues should always be reviewed manually.

---

## 7. What Information is Sent to the AI Provider

Only the minimum data required for analysis is transmitted to your configured AI provider.

### During Code Review
* Guidance prompts instructing the AI to act as a WordPress code reviewer.
* The check code, file path, line number, and the error/warning message.
* Up to **10 lines before and after** the flagged line from the specific file.

### During Namer Tool Evaluation
* The plugin display name entered in the evaluation form.
* The optional author/brand name (if provided).

### Privacy Guarantee
* **No database credentials**, config keys, or auth salts are ever collected or sent.
* **No unrelated code** — only the small code window around each flagged issue is transmitted. Other files, directories, or user data on your site are never sent.

---

## 8. Behavior Without an AI Connector

If no AI provider is connected, Plugin Check works normally without AI features:

* **Settings Page**: The AI Model dropdown is disabled with a message to configure an AI connector first.
* **Namer Tool**: An error notice appears with a button to configure AI connectors, and form submission is disabled.
* **During Check Execution**: If `--ai` is passed but no connector is available, the scan completes normally and outputs all check results without AI annotations.

---

## 9. Troubleshooting

If AI features aren't working, check the following:

1. **WordPress version** — Confirm you're running WordPress 7.0 or higher.
2. **AI support** — Ensure no plugin or custom code has disabled AI support on your site.
3. **Connectors** — Verify at least one AI provider is connected and active under **Settings > Connectors**.
4. **Missing models** — Make sure the connected provider offers text generation models. Audio-only models are filtered out automatically.
5. **API errors** — If analysis fails with an API error, verify your API keys under **Settings > Connectors** and confirm your server can make outbound HTTPS requests.

---

## 10. Limitations

* **Batching**: To manage API costs and latency, Plugin Check processes up to 12 issues per API request and up to 24 issues per check category. Additional issues beyond these limits are reported with their original static severity.
* **Supported Review Types**: Plugin Check includes specialized AI review prompts for:
  * Late Escaping
  * Nonce Verification
  * Input Sanitization & Validation
  * Direct Database Queries & Prepared SQL
  * Code Obfuscation
  * Plugin Updater Integrations
  * All other check types use a comprehensive generic review prompt.
