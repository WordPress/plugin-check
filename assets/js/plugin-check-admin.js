( function ( data ) {
	const checkItButton = document.getElementById( 'pc_check_it' );
	const pluginsList   = document.getElementById( 'pc_plugins' );

	checkItButton.addEventListener( 'click', () => {

		const pluginCheckData = new FormData();

		// Collect the data to pass along for generating a check results.
		pluginCheckData.append( 'action', 'plugin_check_run_check' );
		pluginCheckData.append( 'nonce', data.nonce );
		pluginCheckData.append( 'plugin', pluginsList.value );

		fetch(
			data.ajaxUrl,
			{
				method: 'POST',
				credentials: 'same-origin',
				body: pluginCheckData
			}
		)
		.then(
			( response ) => {
				if ( ! response.ok ) {

					throw new Error(`[${response.message}]`);
				}

				return response.json();
			}
		)
		.then(
			( data ) => {
				console.log( data.data.message );
			}
		)
		.catch(
			( error ) => { console.log( error ); }
		);

	} );

} )( PLUGIN_CHECK ); /* global PLUGIN_CHECK */
