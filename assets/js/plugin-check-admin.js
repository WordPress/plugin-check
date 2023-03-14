( function ( data ) {
	const checkItButton = document.getElementById( 'plugin-check__submit' );
	const pluginsList   = document.getElementById( 'plugin-check__plugins-dropdown' );

	// Return early if the elements cannot be found on the page.
	if ( ! checkItButton || ! pluginsList ) {
		console.error( 'Missing form elements on page' );
		return;
	}

	// Create a state object for the plugin checker.
	const pluginChecker = {
		plugin: false,
		checks_to_run: [],
	};

	checkItButton.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		// Collect the data to pass along for generating a check results.
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', data.nonce );
		pluginCheckData.append( 'plugin', pluginsList.value );
		pluginCheckData.append( 'checks', [] );
		pluginCheckData.append( 'action', 'plugin_check_get_checks_to_run' );

		fetch(
			ajaxurl,
			{
				method: 'POST',
				credentials: 'same-origin',
				body: pluginCheckData
			}
		)
		.then(
			( response ) => {
				return response.json();
			}
		)
		.then(
			( data ) => {
				return handleDataErrors( data );
			}
		)
		.then(
			( data ) => {
				console.log( 'plugin_check_get_checks_to_run', data );

				if ( ! data.data || ! data.data.plugin || ! data.data.checks ) {
					throw new Error( 'Plugin and Checks are missing from the response.' );
				}

				// Store the plugin and checks to run to be used later.
				pluginChecker.plugin = data.data.plugin;
				pluginChecker.checks = data.data.checks;

				runChecks();
			}
		)
		.catch(
			( error ) => { console.error( error ) }
		);
	} );


	/**
	 * Run Checks.
	 *
	 * @since n.e.x.t
	 */
	function runChecks() {
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', data.nonce );
		pluginCheckData.append( 'plugin', pluginChecker.plugin );
		pluginCheckData.append( 'checks', pluginChecker.checks );
		pluginCheckData.append( 'action', 'plugin_check_run_checks' );

		fetch(
			ajaxurl,
			{
				method: 'POST',
				credentials: 'same-origin',
				body: pluginCheckData
			}
		)
		.then(
			( response ) => {
				return response.json();
			}
		)
		.then(
			( data ) => {
				return handleDataErrors(data);
			}
		)
		.then(
			( data ) => {
				// If the response is successful and there is no message in the response.
				if ( ! data.data || ! data.data.message ) {
					throw new Error( 'Response contains no data' );
				}

				// If the response is successful and there is a message in the response.
				console.log( 'plugin_check_run_checks', data.data.message );
			}
		)
		.catch(
			( error ) => { console.error( error ); }
		);
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
			if ( ! data.data || ! data.data[0].message ) {
				throw new Error( 'Response contains no data' );
			}

			// If not successful and there is a message in the response.
			throw new Error( data.data[0].message );
		}

		return data;
	}

} )( PLUGIN_CHECK ); /* global PLUGIN_CHECK */
