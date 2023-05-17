( function ( pluginCheck ) {
	const checkItButton = document.getElementById( 'plugin-check__submit' );
	const resultsContainer = document.getElementById( 'plugin-check__results' );
	const spinner = document.getElementById( 'plugin-check__spinner' );
	const pluginsList = document.getElementById(
		'plugin-check__plugins-dropdown'
	);
	const templates = {};

	// Return early if the elements cannot be found on the page.
	if ( ! checkItButton || ! pluginsList || ! resultsContainer || ! spinner ) {
		console.error( 'Missing form elements on page' );
		return;
	}

	checkItButton.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		resetResults();
		checkItButton.disabled = true;
		spinner.classList.add( 'is-active' );

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
	 * @since n.e.x.t
	 */
	function resetResults() {
		// Empty the results container.
		resultsContainer.innerText = '';
	}

	/**
	 * Resets the form controls once checks have completed or failed.
	 *
	 * @since n.e.x.t
	 */
	function resetForm() {
		spinner.classList.remove( 'is-active' );
		checkItButton.disabled = false;
	}

	/**
	 * Setup the runtime environment if needed.
	 *
	 * @since n.e.x.t
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
	 * @since n.e.x.t
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
	 * @since n.e.x.t
	 */
	function getChecksToRun() {
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', pluginCheck.nonce );
		pluginCheckData.append( 'plugin', pluginsList.value );
		pluginCheckData.append( 'action', pluginCheck.actionGetChecksToRun );

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
	 * @since n.e.x.t
	 *
	 * @param {Object} data The response data.
	 */
	async function runChecks( data ) {
		let isSuccessMessage = true;
		let messageClass = 'notice-success';
		let messageText = pluginCheck.successMessage;
		for ( let i = 0; i < data.checks.length; i++ ) {
			try {
				const results = await runCheck( data.plugin, data.checks[ i ] );
				if (
					isSuccessMessage &&
					( results.errorCount > 0 || results.warningCount > 0 )
				) {
					isSuccessMessage = false;
				}
				renderResults( results );
			} catch ( e ) {
				// Ignore for now.
			}
		}

		if ( ! isSuccessMessage ) {
			messageClass = 'notice-error';
			messageText = pluginCheck.errorMessage;
		}

		resultsContainer.innerHTML += renderTemplate(
			'plugin-check-results-complete',
			{
				class: messageClass,
				message: messageText,
			}
		);
	}

	/**
	 * Run a single check.
	 *
	 * @since n.e.x.t
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
	 * @since n.e.x.t
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
	 * @since n.e.x.t
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
	 * @since n.e.x.t
	 *
	 * @param {string} file     The file name for the results.
	 * @param {Object} errors   The file errors.
	 * @param {Object} warnings The file warnings.
	 */
	function renderFileResults( file, errors, warnings ) {
		const index =
			Date.now().toString( 36 ) +
			Math.random().toString( 36 ).substr( 2 );

		// Render the file table.
		resultsContainer.innerHTML += renderTemplate(
			'plugin-check-results-table',
			{ file, index }
		);
		const resultsTable = document.getElementById(
			'plugin-check__results-body-' + index
		);

		// Render results to the table.
		renderResultRows( 'ERROR', errors, resultsTable );
		renderResultRows( 'WARNING', warnings, resultsTable );
	}

	/**
	 * Renders a result row onto the file table.
	 *
	 * @since n.e.x.t
	 *
	 * @param {string} type    The result type. Either ERROR or WARNING.
	 * @param {Object} results The results object.
	 * @param {Object} table   The HTML table to append a result row to.
	 */
	function renderResultRows( type, results, table ) {
		// Loop over each result by the line, column and messages.
		for ( const line in results ) {
			for ( const column in results[ line ] ) {
				for ( let i = 0; i < results[ line ][ column ].length; i++ ) {
					const message = results[ line ][ column ][ i ].message;
					const code = results[ line ][ column ][ i ].code;

					table.innerHTML += renderTemplate(
						'plugin-check-results-row',
						{
							line,
							column,
							type,
							message,
							code,
						}
					);
				}
			}
		}
	}

	/**
	 * Renders the template with data.
	 *
	 * @since n.e.x.t
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
