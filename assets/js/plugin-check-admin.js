( function ( data ) {
	const checkItButton = document.getElementById( 'plugin-check__submit' );
	const pluginsList   = document.getElementById( 'plugin-check__plugins-dropdown' );

	// Return early if the elements cannot be found on the page.
	if ( ! checkItButton || ! pluginsList ) {
		console.error( 'Missing form elements on page' );
		return;
	}

	checkItButton.addEventListener( 'click', (e) => {
		e.preventDefault();

		const pluginCheckData = new FormData();

		// Collect the data to pass along for generating a check results.
		pluginCheckData.append( 'action', 'plugin_check_run_checks' );
		pluginCheckData.append( 'nonce', data.nonce );
		pluginCheckData.append( 'plugin', pluginsList.value );

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
				if ( ! data ) {
					throw new Error( 'Response contains no data' );
				}

				if ( ! data.success ) {
					// // If not successful and no message in the response.
					if ( ! data.data || ! data.data[0].message ) {
						throw new Error( 'Response contains no data' );
					}

					// If not successful and there is a message in the response.
					throw new Error( data.data[0].message );
				}

				// If the response is successful and there is no message in the response.
				if ( ! data.data || ! data.data.message ) {
					throw new Error( 'Response contains no data' );
				}

				// If the response is successful and there is a message in the response.
				console.log( data.data.message );
			}
		)
		.catch(
			( error ) => { console.error( error ); }
		);

	} );

} )( PLUGIN_CHECK ); /* global PLUGIN_CHECK */
