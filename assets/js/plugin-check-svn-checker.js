/* global pluginCheckSvnChecker */
( function () {
	'use strict';

	const form = document.getElementById( 'plugin-check-svn-checker-form' );
	const result = document.getElementById( 'plugin-check-svn-checker-result' );

	if ( ! form || ! result ) {
		return;
	}

	const spinner = document.getElementById(
		'plugin-check-svn-checker-spinner'
	);
	const { i18n } = pluginCheckSvnChecker;

	form.addEventListener( 'submit', async ( e ) => {
		e.preventDefault();

		const slug = document
			.getElementById( 'plugin-check-svn-checker-slug-input' )
			.value.trim();

		result.innerHTML = '';
		spinner.classList.add( 'is-active' );

		try {
			const params = new URLSearchParams();
			params.set( 'action', pluginCheckSvnChecker.action );
			params.set( 'nonce', pluginCheckSvnChecker.nonce );
			params.set( 'slug', slug );

			const res = await fetch( pluginCheckSvnChecker.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: params,
			} );

			const { success, data } = await res.json();

			if ( ! success ) {
				result.innerHTML = `<div class="notice notice-error inline"><p>${ esc(
					( data && data.message ) || i18n.error
				) }</p></div>`;
				return;
			}

			result.innerHTML = renderReport( data );
		} catch {
			result.innerHTML = `<div class="notice notice-error inline"><p>${ esc(
				i18n.requestFailed
			) }</p></div>`;
		} finally {
			spinner.classList.remove( 'is-active' );
		}
	} );

	function renderReport( data ) {
		const { meta, sections } = data;
		let html = '';

		if ( meta.plugin_name ) {
			html += `<p class="plugin-check-svn-checker-plugin-name">${ esc(
				meta.plugin_name
			) }</p><hr>`;
		}

		// Sections.
		for ( const section of sections ) {
			if ( ! section.checks.length ) {
				continue;
			}

			const svnLink =
				section.id === 'root' && meta.svn_url
					? ` <a href="${ esc(
							meta.svn_url
					  ) }" target="_blank" rel="noopener" class="plugin-check-svn-checker-svn-link" title="${ esc(
							i18n.viewInSvn
					  ) }"><span class="dashicons dashicons-external"></span></a>`
					: '';
			html += `<h2>${ esc( section.label ) }${ svnLink }</h2>`;

			if ( section.id === 'root' ) {
				html += '<div class="plugin-check-svn-checker-inline-checks">';
				for ( const check of section.checks ) {
					html += `<span class="plugin-check-svn-checker-inline-check" title="${ esc(
						check.detail
					) }">${ badge( check.status ) } ${ esc(
						check.label
					) }</span>`;
				}
				html += '</div>';
			} else {
				html +=
					'<table class="wp-list-table widefat striped plugin-check-svn-checker-checks-table">' +
					'<thead><tr>' +
					`<th class="plugin-check-svn-checker-col-status">${ esc(
						i18n.colStatus
					) }</th>` +
					`<th class="plugin-check-svn-checker-col-check">${ esc(
						i18n.colCheck
					) }</th>` +
					`<th class="plugin-check-svn-checker-col-detail">${ esc(
						i18n.colDetail
					) }</th>` +
					'</tr></thead><tbody>';

				for ( const check of section.checks ) {
					html += `<tr class="plugin-check-svn-checker-check-row plugin-check-svn-checker-row-${ esc(
						check.status
					) }">`;
					html += `<td class="plugin-check-svn-checker-col-status">${ badge(
						check.status
					) }</td>`;
					html += `<td class="plugin-check-svn-checker-col-check">${ esc(
						check.label
					) }</td>`;
					html += `<td class="plugin-check-svn-checker-col-detail"><span class="plugin-check-svn-checker-detail-text">${ esc(
						check.detail
					) }</span></td>`;
					html += '</tr>';
				}

				html += '</tbody></table>';
			}
		}

		return html;
	}

	function badge( status ) {
		const map = {
			pass: [
				'dashicons-yes-alt',
				i18n.pass,
				'plugin-check-svn-checker-badge-pass',
			],
			warn: [
				'dashicons-info',
				i18n.warning,
				'plugin-check-svn-checker-badge-warn',
			],
			fail: [
				'dashicons-dismiss',
				i18n.fail,
				'plugin-check-svn-checker-badge-fail',
			],
			info: [
				'dashicons-info',
				i18n.info,
				'plugin-check-svn-checker-badge-info',
			],
		};
		const [ icon, label, cls ] = map[ status ] || map.warn;
		return `<span class="plugin-check-svn-checker-status-badge ${ cls }"><span class="dashicons ${ icon }"></span> ${ esc(
			label
		) }</span>`;
	}

	function esc( str ) {
		return String( str ?? '' )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}
} )();
