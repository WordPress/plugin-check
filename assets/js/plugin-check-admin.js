( function ( pluginCheck ) {
	const checkItButton = document.getElementById( 'plugin-check__submit' );
	const pluginsList = document.getElementById(
		'plugin-check__plugins-dropdown'
	);

	// Return early if the elements cannot be found on the page.
	if ( ! checkItButton || ! pluginsList ) {
		console.error( 'Missing form elements on page' );
		return;
	}

	checkItButton.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		getChecksToRun()
			.then( setUpEnvironment )
			.then( runChecks )
			.then( cleanUpEnvironment )
			.then( ( data ) => {
				console.log( data.message );
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
		const results = [];

		for ( let i = 0; i < data.checks.length; i++ ) {
			const result = await runCheck( data.plugin, data.checks[ i ] );
			console.log( result );
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
} )( PLUGIN_CHECK ); /* global PLUGIN_CHECK */
