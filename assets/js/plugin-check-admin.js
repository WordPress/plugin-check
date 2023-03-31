( function ( pluginCheck ) {
	const checkItButton = document.getElementById( 'plugin-check__submit' );
	const resultsContainer = document.getElementById( 'plugin-check__results' );
	const pluginsList = document.getElementById(
		'plugin-check__plugins-dropdown'
	);

	// Return early if the elements cannot be found on the page.
	if ( ! checkItButton || ! pluginsList || ! resultsContainer ) {
		console.error( 'Missing form elements on page' );
		return;
	}

	checkItButton.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		// Empty the results container.
		resultsContainer.innerText = '';

		getChecksToRun()
			.then( setUpEnvironment )
			.then( runChecks )
			.then( cleanUpEnvironment )
			.then( ( data ) => {
				console.log( data.message );

				resultsContainer.innerHTML += 'Checks complete';
			} )
			.catch( ( error ) => {
				console.error( error );
			} );
	} );

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
		pluginCheckData.append( 'action', 'plugin_check_set_up_environment' );

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
		pluginCheckData.append( 'action', 'plugin_check_clean_up_environment' );

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
	 * Get the Checks to run.
	 *
	 * @since n.e.x.t
	 */
	function getChecksToRun() {
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', pluginCheck.nonce );
		pluginCheckData.append( 'plugin', pluginsList.value );
		pluginCheckData.append( 'action', 'plugin_check_get_checks_to_run' );

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
		for ( let i = 0; i < data.checks.length; i++ ) {
			try {
				const results = await runCheck( data.plugin, data.checks[ i ] );
				renderResults( results );
			} catch ( e ) {
				// Ignore for now.
			}
		}
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
		pluginCheckData.append( 'action', 'plugin_check_run_checks' );

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
		const tableTemplate = wp.template( 'plugin-check-results-table' );
		const index = Date.now();

		// Render the file table.
		resultsContainer.innerHTML += tableTemplate( { file, index } );
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
		const rowTemplate = wp.template( 'plugin-check-results-row' );

		// Loop over each result by the line, column and messages.
		for ( const line in results ) {
			for ( const column in results[ line ] ) {
				for ( let i = 0; i < results[ line ][ column ].length; i++ ) {
					const message = results[ line ][ column ][ i ].message;
					const code = results[ line ][ column ][ i ].code;

					table.innerHTML += rowTemplate( {
						line,
						column,
						type,
						message,
						code,
					} );
				}
			}
		}
	}
} )( PLUGIN_CHECK ); /* global PLUGIN_CHECK */
