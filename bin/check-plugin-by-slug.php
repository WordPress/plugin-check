<?php
namespace WordPressdotorg\Plugin_Check;

$_SERVER['HTTP_HOST'] = 'wordpress.org';
$_SERVER['REQUEST_URI'] = '/plugins/';
require dirname( __DIR__, 4 ) . '/wp-load.php';
require ABSPATH . '/wp-admin/includes/admin.php';

include_once dirname( __DIR__ ) . '/plugin.php';

$slug = $argv[1] ?? false;
if ( ! $slug ) {
	die( "Usage: {$argv[0]} <slug>" );
}

$tempname = wp_tempnam( 'plugin-check' );
unlink( $tempname );
mkdir( $tempname );

$zipfile = download_url( "https://downloads.wordpress.org/plugin/{$slug}.latest-stable.zip" );
if ( is_file( $zipfile ) ) {
	exec( "unzip -q {$zipfile} -d {$tempname}" );
}

add_action( 'shutdown', function() use( $tempname, $zipfile ) {
	if ( $zipfile ) {
		unlink( $zipfile );
	}

	$files = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator( $tempname, \RecursiveDirectoryIterator::SKIP_DOTS ),
		\RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $files as $fileinfo ) {
		$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink' );
		$todo( $fileinfo->getRealPath() );
	}

	rmdir( $tempname );
} );

var_dump(
	run_all_checks( $tempname )
);
