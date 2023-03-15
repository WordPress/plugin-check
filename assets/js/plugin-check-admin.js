( function ( pluginCheck ) {
	const checkItButton = document.getElementById( 'plugin-check__submit' );
	const pluginsList   = document.getElementById( 'plugin-check__plugins-dropdown' );

	// Return early if the elements cannot be found on the page.
	if ( ! checkItButton || ! pluginsList ) {
		console.error( 'Missing form elements on page' );
		return;
	}

	checkItButton.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		getChecksToRun()
		.then( runChecks )
		.then(
			( data ) => {
				console.log( data.message );
			}
		)
		.catch(
			( error ) => {
				console.error( error );
			}
		);
	} );

	/**
	 * Get the Checks to run.
	 *
	 * @since n.e.x.t
	 */
	function getChecksToRun() {
		// Collect the data to pass along for generating a check results.
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', pluginCheck.nonce );
		pluginCheckData.append( 'plugin', pluginsList.value );
		pluginCheckData.append( 'checks', [] );
		pluginCheckData.append( 'action', 'plugin_check_get_checks_to_run' );

		return fetch(
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
		.then( handleDataErrors )
		.then(
			( data ) => {
				if ( ! data.data || ! data.data.plugin || ! data.data.checks ) {
					throw new Error( 'Plugin and Checks are missing from the response.' );
				}

				return data.data;
			}
		);
	}


	/**
	 * Run Checks.
	 *
	 * @since n.e.x.t
	 */
	function runChecks( data ) {
		const pluginCheckData = new FormData();
		pluginCheckData.append( 'nonce', pluginCheck.nonce );
		pluginCheckData.append( 'plugin', data.plugin );
		pluginCheckData.append( 'checks', data.checks );
		pluginCheckData.append( 'action', 'plugin_check_run_checks' );

		return fetch(
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
		.then( handleDataErrors )
		.then(
			( data ) => {
				// If the response is successful and there is no message in the response.
				if ( ! data.data || ! data.data.message ) {
					throw new Error( 'Response contains no data' );
				}

				return data.data;
			}
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
