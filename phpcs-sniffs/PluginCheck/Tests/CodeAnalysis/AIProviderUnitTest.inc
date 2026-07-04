<?php

/* testOpenAiInSingleQuotedString */
$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', $args );

/* testAnthropicInSingleQuotedString */
$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', $args );

/* testGeminiInDoubleQuotedString */
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent";

/* testGrokInSingleQuotedString */
$endpoint = 'https://api.x.ai/v1/chat/completions';

/* testMistralInSingleQuotedString */
$endpoint = 'https://api.mistral.ai/v1/chat/completions';

/* testCohereAiInSingleQuotedString */
$endpoint = 'https://api.cohere.ai/v1/generate';

/* testCohereComInSingleQuotedString */
$endpoint = 'https://api.cohere.com/v2/chat';

/* testGroqInSingleQuotedString */
$endpoint = 'https://api.groq.com/openai/v1/chat/completions';

/* testPerplexityInSingleQuotedString */
$endpoint = 'https://api.perplexity.ai/chat/completions';

/* testDeepSeekInSingleQuotedString */
$endpoint = 'https://api.deepseek.com/chat/completions';

/* testOpenRouterInSingleQuotedString */
$endpoint = 'https://openrouter.ai/api/v1/chat/completions';

/* testHttpSchemeIsMatched */
$endpoint = 'http://api.openai.com/v1/models';

/* testProviderInHeredoc */
$body = <<<EOD
POST https://api.openai.com/v1/embeddings
EOD;

/* testProviderInNowdoc */
$body = <<<'NOWDOC'
See https://api.anthropic.com/v1/messages
NOWDOC;

/*
 * Negative cases below: these must NOT be flagged.
 */

/* testProviderMentionedInComment */
// We deliberately avoid https://api.openai.com and use the WordPress AI Client instead.

/* testProviderInDocComment */
/**
 * Historically this called https://api.anthropic.com directly.
 *
 * @link https://api.x.ai
 */
function legacy_notes() {
}

/* testBareHostWithoutSchemeNotMatched */
$host = 'api.openai.com';

/* testUnrelatedGoogleApiNotMatched */
$endpoint = 'https://www.googleapis.com/oauth2/v3/userinfo';

/* testUnrelatedUrlNotMatched */
$endpoint = 'https://example.com/v1/chat/completions';

/* testWordPressAiClientUsageNotMatched */
$text = wp_ai_client_prompt( 'Summarize this post.' )->generate_text();
