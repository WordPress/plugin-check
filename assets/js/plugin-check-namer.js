/**
 * Plugin Check Namer tool.
 */

/* global pluginCheckNamer */

( function () {
	'use strict';

	function setText( el, text ) {
		if ( ! el ) {
			return;
		}
		el.textContent = text;
	}

	function setHtml( el, html ) {
		if ( ! el ) {
			return;
		}
		el.innerHTML = html;
	}

	function escapeHtml( text ) {
		const div = document.createElement( 'div' );
		div.textContent = text;
		return div.innerHTML;
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		const form = document.getElementById( 'plugin-check-namer-form' );
		const input = document.getElementById( 'plugin_check_namer_input' );

		if ( ! form || ! input || ! window.pluginCheckNamer ) {
			return;
		}

		const authorInput = document.getElementById(
			'plugin_check_namer_author'
		);
		const modelSelect = document.getElementById(
			'plugin_check_namer_model'
		);

		const submitBtn = document.getElementById(
			'plugin-check-namer-submit'
		);
		const spinner = document.getElementById( 'plugin-check-namer-spinner' );

		const resultWrap = document.getElementById(
			'plugin-check-namer-result'
		);
		const verdictEl = document.getElementById(
			'plugin-check-namer-verdict'
		);
		const explainEl = document.getElementById(
			'plugin-check-namer-explanation'
		);
		const verdictContainer = document.getElementById(
			'plugin-check-namer-verdict-container'
		);
		const confusionPluginsDiv = document.getElementById(
			'plugin-check-namer-confusion-plugins'
		);
		const confusionPluginsList = document.getElementById(
			'plugin-check-namer-confusion-plugins-list'
		);
		const confusionOthersDiv = document.getElementById(
			'plugin-check-namer-confusion-others'
		);
		const confusionOthersList = document.getElementById(
			'plugin-check-namer-confusion-others-list'
		);
		const timingDiv = document.getElementById(
			'plugin-check-namer-timing'
		);
		const timingValue = document.getElementById(
			'plugin-check-namer-timing-value'
		);
		const tokensDiv = document.getElementById(
			'plugin-check-namer-tokens'
		);
		const tokensValue = document.getElementById(
			'plugin-check-namer-tokens-value'
		);
		const errorDiv = document.getElementById( 'plugin-check-namer-error' );
		const errorEl = errorDiv ? errorDiv.querySelector( 'p' ) : null;

		function setLoading( isLoading ) {
			if ( spinner ) {
				spinner.classList.toggle( 'is-active', isLoading );
			}
			submitBtn.disabled = isLoading;
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			const name = ( input.value || '' ).trim();
			if ( ! name ) {
				setText( errorEl, pluginCheckNamer.messages.missingName );
				if ( errorEl ) {
					errorEl.classList.remove( 'plugin-check-namer-hidden' );
				}
				return;
			}

			// Clear previous results.
			if ( resultWrap ) {
				resultWrap.classList.add( 'plugin-check-namer-hidden' );
			}
			if ( verdictEl ) {
				setText( verdictEl, '' );
			}
			if ( explainEl ) {
				setHtml( explainEl, '' );
			}
			if ( errorDiv ) {
				errorDiv.classList.add( 'plugin-check-namer-hidden' );
			}
			if ( errorEl ) {
				setText( errorEl, '' );
			}
			if ( confusionPluginsDiv ) {
				confusionPluginsDiv.classList.add(
					'plugin-check-namer-hidden'
				);
			}
			if ( confusionPluginsList ) {
				confusionPluginsList.innerHTML = '';
			}
			if ( confusionOthersDiv ) {
				confusionOthersDiv.classList.add( 'plugin-check-namer-hidden' );
			}
			if ( confusionOthersList ) {
				confusionOthersList.innerHTML = '';
			}
			if ( timingDiv ) {
				timingDiv.classList.add( 'plugin-check-namer-hidden' );
			}
			if ( timingValue ) {
				setText( timingValue, '' );
			}
			if ( tokensDiv ) {
				tokensDiv.classList.add( 'plugin-check-namer-hidden' );
			}
			if ( tokensValue ) {
				setText( tokensValue, '' );
			}
			if ( verdictContainer ) {
				verdictContainer.classList.add( 'plugin-check-namer-hidden' );
			}

			// Record start time.
			const startTime = Date.now();

			setLoading( true );

			const formData = new FormData();
			formData.append( 'action', 'plugin_check_namer_analyze' );
			formData.append( 'nonce', pluginCheckNamer.nonce );
			formData.append( 'plugin_name', name );
			if ( authorInput ) {
				formData.append(
					'author_name',
					( authorInput.value || '' ).trim()
				);
			}
			if ( modelSelect ) {
				formData.append(
					'model_preference',
					( modelSelect.value || '' ).trim()
				);
			}

			fetch( pluginCheckNamer.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( payload ) {
					if ( ! payload || ! payload.success ) {
						throw new Error(
							payload && payload.data && payload.data.message
								? payload.data.message
								: pluginCheckNamer.messages.genericError
						);
					}

					setText( verdictEl, payload.data.verdict || '' );
					setHtml( explainEl, payload.data.explanation || '' );

					// Set border color based on verdict.
					if ( verdictContainer ) {
						const verdict = (
							payload.data.verdict || ''
						).toLowerCase();
						let borderColor = '#2271b1'; // Default blue.

						if ( verdict.indexOf( 'disallowed' ) !== -1 ) {
							borderColor = '#d63638'; // Red for disallowed.
						} else if (
							verdict.indexOf( 'good' ) !== -1 ||
							verdict.indexOf( 'low' ) !== -1 ||
							verdict.indexOf( 'no issues' ) !== -1
						) {
							borderColor = '#00a32a'; // Green for good.
						} else if (
							verdict.indexOf( 'generally allowable' ) !== -1 ||
							verdict.indexOf( 'allowable' ) !== -1
						) {
							borderColor = '#2271b1'; // Blue for generally allowable.
						} else if (
							verdict.indexOf( 'review' ) !== -1 ||
							verdict.indexOf( 'medium' ) !== -1 ||
							verdict.indexOf( 'issues found' ) !== -1
						) {
							borderColor = '#dba617'; // Yellow/orange for needs review.
						} else if (
							verdict.indexOf( 'problematic' ) !== -1 ||
							verdict.indexOf( 'high' ) !== -1
						) {
							borderColor = '#d63638'; // Red for problematic.
						}

						verdictContainer.style.borderLeftColor = borderColor;
						verdictContainer.classList.remove(
							'plugin-check-namer-hidden'
						);
					}

					// Calculate and display elapsed time.
					if ( timingDiv && timingValue ) {
						const endTime = Date.now();
						const elapsedSeconds = Math.round(
							( endTime - startTime ) / 1000
						);
						timingValue.textContent =
							elapsedSeconds + ' ' + 'seconds';
						timingDiv.classList.remove(
							'plugin-check-namer-hidden'
						);
					}

					// Display token usage if available.
					if (
						tokensDiv &&
						tokensValue &&
						payload.data.token_usage
					) {
						const tokenUsage = payload.data.token_usage;
						let tokensText = '';

						// Add AI provider and model info if available.
						if ( payload.data.ai_info ) {
							const parts = [];
							if ( payload.data.ai_info.provider ) {
								parts.push( payload.data.ai_info.provider );
							}
							if ( payload.data.ai_info.model ) {
								parts.push( payload.data.ai_info.model );
							}
							if ( parts.length ) {
								tokensText = parts.join( ' / ' ) + ' - ';
							}
						}

						const totalTokens =
							tokenUsage.total_tokens ||
							( tokenUsage.prompt_tokens &&
							tokenUsage.completion_tokens
								? tokenUsage.prompt_tokens +
								  tokenUsage.completion_tokens
								: null );

						if ( totalTokens ) {
							tokensText += totalTokens + ' total';
						}

						// Add breakdown with prompt and completion tokens.
						if (
							tokenUsage.prompt_tokens &&
							tokenUsage.completion_tokens
						) {
							tokensText +=
								' (' +
								tokenUsage.prompt_tokens +
								' prompt + ' +
								tokenUsage.completion_tokens +
								' completion)';
						}

						// Add similar name query tokens if available.
						if (
							tokenUsage.similar_name &&
							tokenUsage.similar_name.total_tokens
						) {
							tokensText +=
								' [Similar name query: ' +
								tokenUsage.similar_name.total_tokens +
								' tokens]';
						}

						tokensValue.textContent = tokensText;
						tokensDiv.classList.remove(
							'plugin-check-namer-hidden'
						);
					}

					// Display confusion_existing_plugins if available.
					if (
						confusionPluginsDiv &&
						confusionPluginsList &&
						payload.data.confusion_existing_plugins &&
						payload.data.confusion_existing_plugins.length > 0
					) {
						confusionPluginsList.innerHTML = '';
						payload.data.confusion_existing_plugins.forEach(
							function ( plugin ) {
								const div = document.createElement( 'div' );
								div.className =
									'plugin-check-namer-confusion-item';
								div.innerHTML =
									'<strong>' +
									escapeHtml( plugin.name || '' ) +
									'</strong>' +
									( plugin.active_installations
										? ' <span class="plugin-check-namer-confusion-meta">(' +
										  escapeHtml(
												plugin.active_installations
										  ) +
										  ' ' +
										  'active installs' +
										  ')</span>'
										: '' ) +
									( plugin.owner_username
										? ' <span class="plugin-check-namer-confusion-meta"> - ' +
										  escapeHtml( plugin.owner_username ) +
										  '</span>'
										: '' ) +
									'<br>' +
									'<span class="plugin-check-namer-confusion-text">' +
									escapeHtml( plugin.explanation || '' ) +
									'</span>' +
									( plugin.link
										? '<br><a href="' +
										  escapeHtml( plugin.link ) +
										  '" target="_blank" rel="noopener">' +
										  escapeHtml( plugin.link ) +
										  '</a>'
										: '' );
								confusionPluginsList.appendChild( div );
							}
						);
						confusionPluginsDiv.classList.remove(
							'plugin-check-namer-hidden'
						);
					} else if ( confusionPluginsDiv ) {
						confusionPluginsDiv.classList.add(
							'plugin-check-namer-hidden'
						);
					}

					// Display confusion_existing_others if available.
					if (
						confusionOthersDiv &&
						confusionOthersList &&
						payload.data.confusion_existing_others &&
						payload.data.confusion_existing_others.length > 0
					) {
						confusionOthersList.innerHTML = '';
						payload.data.confusion_existing_others.forEach(
							function ( item ) {
								const div = document.createElement( 'div' );
								div.className =
									'plugin-check-namer-confusion-item plugin-check-namer-confusion-item-others';
								div.innerHTML =
									'<strong>' +
									escapeHtml( item.name || '' ) +
									'</strong>' +
									'<br>' +
									'<span class="plugin-check-namer-confusion-text">' +
									escapeHtml( item.explanation || '' ) +
									'</span>' +
									( item.link
										? '<br><a href="' +
										  escapeHtml( item.link ) +
										  '" target="_blank" rel="noopener">' +
										  escapeHtml( item.link ) +
										  '</a>'
										: '' );
								confusionOthersList.appendChild( div );
							}
						);
						confusionOthersDiv.classList.remove(
							'plugin-check-namer-hidden'
						);
					} else if ( confusionOthersDiv ) {
						confusionOthersDiv.classList.add(
							'plugin-check-namer-hidden'
						);
					}

					if ( resultWrap ) {
						resultWrap.classList.remove(
							'plugin-check-namer-hidden'
						);
					}
				} )
				.catch( function ( err ) {
					setText(
						errorEl,
						err && err.message
							? err.message
							: pluginCheckNamer.messages.genericError
					);

					if ( errorDiv ) {
						errorDiv.classList.remove(
							'plugin-check-namer-hidden'
						);
					}
				} )
				.finally( function () {
					setLoading( false );
				} );
		} );
	} );
} )();
