( function ( pluginCheck ) {
	const checkItButton = document.getElementById( 'plugin-check__submit' );
	const resultsContainer = document.getElementById( 'plugin-check__results' );
	const spinner = document.getElementById( 'plugin-check__spinner' );
	const pluginsList = document.getElementById(
		'plugin-check__plugins-dropdown'
	);
	const categoriesList = document.querySelectorAll(
		'input[name=categories]'
	);
	const templates = {};

	// Return early if the elements cannot be found on the page.
	if (
		! checkItButton ||
		! pluginsList ||
		! resultsContainer ||
		! spinner ||
		! categoriesList.length
	) {
		console.error( 'Missing form elements on page' );
		return;
	}

	// Handle disabling the Check it button when a plugin is not selected.
	function canRunChecks() {
		if ( '' === pluginsList.value ) {
			checkItButton.disabled = true;
		} else {
			checkItButton.disabled = false;
		}
	}

	// Run on page load to test if dropdown is auto populated.
	canRunChecks();
	pluginsList.addEventListener( 'change', canRunChecks );

	function saveUserSettings() {
		const selectedCategories = [];

		// Assuming you have a list of category checkboxes, find the selected ones.
		categoriesList.forEach( function ( checkbox ) {
			if ( checkbox.checked ) {
				selectedCategories.push( checkbox.value );
			}
		} );

		// Join the selected category slugs with '__' and save it as a user setting.
		const settingValue = selectedCategories.join( '__' );
		window.setUserSetting(
			'plugin_check_category_preferences',
			settingValue
		);
	}

	// Attach the saveUserSettings function when a category checkbox is clicked.
	categoriesList.forEach( function ( checkbox ) {
		checkbox.addEventListener( 'change', saveUserSettings );
	} );

	// When the Check it button is clicked.
	checkItButton.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		resetResults();
		checkItButton.disabled = true;
		pluginsList.disabled = true;
		spinner.classList.add( 'is-active' );
		for ( let i = 0; i < categoriesList.length; i++ ) {
			categoriesList[ i ].disabled = true;
		}

		getChecksToRun()
			.then( setUpEnvironment )
			.then( runChecks )
			.then( cleanUpEnvironment )
			.then( ( data ) => {
				console.log( data.message );

				resetForm();
			} )
			.catch( ( error ) => {
				console.error( error );

				resetForm();
			} );
	} );

	/**
	 * Reset the results container.
	 *
	 * @since 1.0.0
	 */
	function resetResults() {
		// Empty the results container.
		resultsContainer.innerText = '';
	}

	/**
	 * Resets the form controls once checks have completed or failed.
	 *
	 * @since 1.0.0
	 */
	function resetForm() {
		spinner.classList.remove( 'is-active' );
		checkItButton.disabled = false;
		pluginsList.disabled = false;
		for ( let i = 0; i < categoriesList.length; i++ ) {
			categoriesList[ i ].disabled = false;
		}
	}

	/**
	 * Setup the runtime environment if needed.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} data Data object with props passed to form data.
	 */
	function setUpEnvironment( data ) {
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', pluginCheck.nonce );
		pluginCheckData.append( 'plugin', data.plugin );
		pluginCheckData.append(
			'action',
			pluginCheck.actionSetUpRuntimeEnvironment
		);

		for ( let i = 0; i < data.checks.length; i++ ) {
			pluginCheckData.append( 'checks[]', data.checks[ i ] );
		}

		return fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: pluginCheckData,
		} )
			.then( ( response ) => {
				return response.json();
			} )
			.then( handleDataErrors )
			.then( ( responseData ) => {
				if ( ! responseData.data || ! responseData.data.message ) {
					throw new Error( 'Response contains no data.' );
				}

				console.log( responseData.data.message );

				return responseData.data;
			} );
	}

	/**
	 * Cleanup the runtime environment.
	 *
	 * @since 1.0.0
	 *
	 * @return {Object} The response data.
	 */
	function cleanUpEnvironment() {
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', pluginCheck.nonce );
		pluginCheckData.append(
			'action',
			pluginCheck.actionCleanUpRuntimeEnvironment
		);

		return fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: pluginCheckData,
		} )
			.then( ( response ) => {
				return response.json();
			} )
			.then( handleDataErrors )
			.then( ( responseData ) => {
				if ( ! responseData.data || ! responseData.data.message ) {
					throw new Error( 'Response contains no data.' );
				}

				return responseData.data;
			} );
	}

	/**
	 * Get the Checks to run.
	 *
	 * @since 1.0.0
	 */
	function getChecksToRun() {
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', pluginCheck.nonce );
		pluginCheckData.append( 'plugin', pluginsList.value );
		pluginCheckData.append( 'action', pluginCheck.actionGetChecksToRun );

		for ( let i = 0; i < categoriesList.length; i++ ) {
			if ( categoriesList[ i ].checked ) {
				pluginCheckData.append(
					'categories[]',
					categoriesList[ i ].value
				);
			}
		}

		return fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: pluginCheckData,
		} )
			.then( ( response ) => {
				return response.json();
			} )
			.then( handleDataErrors )
			.then( ( responseData ) => {
				if (
					! responseData.data ||
					! responseData.data.plugin ||
					! responseData.data.checks
				) {
					throw new Error(
						'Plugin and Checks are missing from the response.'
					);
				}

				return responseData.data;
			} );
	}

	/**
	 * Run Checks.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} data The response data.
	 */
	async function runChecks( data ) {
		let isSuccessMessage = true;
		for ( let i = 0; i < data.checks.length; i++ ) {
			try {
				const results = await runCheck( data.plugin, data.checks[ i ] );
				const errorsLength = Object.values( results.errors ).length;
				const warningsLength = Object.values( results.warnings ).length;
				if (
					isSuccessMessage &&
					( errorsLength > 0 || warningsLength > 0 )
				) {
					isSuccessMessage = false;
				}
				renderResults( results );
			} catch ( e ) {
				// Ignore for now.
			}
		}

		renderResultsMessage( isSuccessMessage );
	}

	/**
	 * Renders result message.
	 *
	 * @since 1.0.0
	 *
	 * @param {boolean} isSuccessMessage Whether the message is a success message.
	 */
	function renderResultsMessage( isSuccessMessage ) {
		const messageType = isSuccessMessage ? 'success' : 'error';
		const messageText = isSuccessMessage
			? pluginCheck.successMessage
			: pluginCheck.errorMessage;

		resultsContainer.innerHTML =
			renderTemplate( 'plugin-check-results-complete', {
				type: messageType,
				message: messageText,
			} ) + resultsContainer.innerHTML;
	}

	/**
	 * Run a single check.
	 *
	 * @since 1.0.0
	 *
	 * @param {string} plugin The plugin to check.
	 * @param {string} check  The check to run.
	 * @return {Object} The check results.
	 */
	function runCheck( plugin, check ) {
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', pluginCheck.nonce );
		pluginCheckData.append( 'plugin', plugin );
		pluginCheckData.append( 'checks[]', check );
		pluginCheckData.append( 'action', pluginCheck.actionRunChecks );

		return fetch( ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: pluginCheckData,
		} )
			.then( ( response ) => {
				return response.json();
			} )
			.then( handleDataErrors )
			.then( ( responseData ) => {
				// If the response is successful and there is no message in the response.
				if ( ! responseData.data || ! responseData.data.message ) {
					throw new Error( 'Response contains no data' );
				}

				return responseData.data;
			} );
	}

	/**
	 * Handles any errors in the data returned from the response.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} data The response data.
	 * @return {Object} The response data.
	 */
	function handleDataErrors( data ) {
		if ( ! data ) {
			throw new Error( 'Response contains no data' );
		}

		if ( ! data.success ) {
			// If not successful and no message in the response.
			if ( ! data.data || ! data.data[ 0 ].message ) {
				throw new Error( 'Response contains no data' );
			}

			// If not successful and there is a message in the response.
			throw new Error( data.data[ 0 ].message );
		}

		return data;
	}

	/**
	 * Renders results for each check on the page.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} results The results object.
	 */
	function renderResults( results ) {
		const { errors, warnings } = results;
		// Render errors and warnings for files.
		for ( const file in errors ) {
			if ( warnings[ file ] ) {
				renderFileResults( file, errors[ file ], warnings[ file ] );
				delete warnings[ file ];
			} else {
				renderFileResults( file, errors[ file ], [] );
			}
		}

		// Render remaining files with only warnings.
		for ( const file in warnings ) {
			renderFileResults( file, [], warnings[ file ] );
		}
	}

	/**
	 * Renders the file results table.
	 *
	 * @since 1.0.0
	 *
	 * @param {string} file     The file name for the results.
	 * @param {Object} errors   The file errors.
	 * @param {Object} warnings The file warnings.
	 */
	function renderFileResults( file, errors, warnings ) {
		const index =
			Date.now().toString( 36 ) +
			Math.random().toString( 36 ).substr( 2 );

		// Check if any errors or warnings have links.
		const hasLinks =
			hasLinksInResults( errors ) || hasLinksInResults( warnings );

		// Render the file table.
		resultsContainer.innerHTML += renderTemplate(
			'plugin-check-results-table',
			{ file, index, hasLinks }
		);
		const resultsTable = document.getElementById(
			'plugin-check__results-body-' + index
		);

		// Render results to the table.
		renderResultRows( 'ERROR', errors, resultsTable, hasLinks );
		renderResultRows( 'WARNING', warnings, resultsTable, hasLinks );
	}

	/**
	 * Checks if there are any links in the results object.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} results The results object.
	 * @return {boolean} True if there are links, false otherwise.
	 */
	function hasLinksInResults( results ) {
		for ( const line in results ) {
			for ( const column in results[ line ] ) {
				for ( let i = 0; i < results[ line ][ column ].length; i++ ) {
					if ( results[ line ][ column ][ i ].link ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Renders a result row onto the file table.
	 *
	 * @since 1.0.0
	 *
	 * @param {string}  type     The result type. Either ERROR or WARNING.
	 * @param {Object}  results  The results object.
	 * @param {Object}  table    The HTML table to append a result row to.
	 * @param {boolean} hasLinks Whether any result has links.
	 */
	function renderResultRows( type, results, table, hasLinks ) {
		// Loop over each result by the line, column and messages.
		for ( const line in results ) {
			for ( const column in results[ line ] ) {
				for ( let i = 0; i < results[ line ][ column ].length; i++ ) {
					const message = results[ line ][ column ][ i ].message;
					const code = results[ line ][ column ][ i ].code;
					const link = results[ line ][ column ][ i ].link;

					table.innerHTML += renderTemplate(
						'plugin-check-results-row',
						{
							line,
							column,
							type,
							message,
							code,
							link,
							hasLinks,
						}
					);
				}
			}
		}
	}

	/**
	 * Renders the template with data.
	 *
	 * @since 1.0.0
	 *
	 * @param {string} templateSlug The template slug
	 * @param {Object} data         Template data.
	 * @return {string} Template HTML.
	 */
	function renderTemplate( templateSlug, data ) {
		if ( ! templates[ templateSlug ] ) {
			templates[ templateSlug ] = wp.template( templateSlug );
		}
		const template = templates[ templateSlug ];
		return template( data );
	}
} )( PLUGIN_CHECK ); /* global PLUGIN_CHECK */
