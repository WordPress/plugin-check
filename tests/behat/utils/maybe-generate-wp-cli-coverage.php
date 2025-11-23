<?php

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\Clover;

$root_folder = realpath( dirname( __DIR__, 3 ) );

if ( ! class_exists( 'SebastianBergmann\CodeCoverage\Filter' ) ) {
	require "{$root_folder}/vendor/autoload.php";
}

$filtered_items = new CallbackFilterIterator(
  new DirectoryIterator( $root_folder ),
  function ( $file ) {
    if ( $file->isDir() && in_array( $file->getFilename(), [ 'includes' ], true ) ) {
      return true;
    }

    if ( $file->isFile() && false !== strpos( $file->getFilename(), 'plugin.php' ) ) {
      return true;
    }

    return false;
  }
);

$files = [];

foreach ( $filtered_items as $item ) {
  if ( $item->isDir() ) {
    foreach (
      new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $item->getPathname(), RecursiveDirectoryIterator::SKIP_DOTS )
      ) as $file
    ) {
      if ( $file->isFile() && $file->getExtension() === 'php' ) {
        $files[] = $file->getPathname();
      }
    }
  } else {
    $files[] = $item->getPathname();
  }
}

$filter = new Filter();

if ( method_exists( $filter, 'includeFiles' ) ) {
	$filter->includeFiles( $files );
} else {
	$filter->addFilesToWhitelist( $files );
}

$coverage = new CodeCoverage(
	( new Selector() )->forLineCoverage( $filter ),
	$filter
);

$feature  = getenv( 'BEHAT_FEATURE_TITLE' );
$scenario = getenv( 'BEHAT_SCENARIO_TITLE' );
$name     = "{$feature} - {$scenario}";

$coverage->start( $name );

register_shutdown_function(
	static function () use ( $coverage, $feature, $scenario, $name ) {
		$coverage->stop();

		$project_dir = getenv( 'BEHAT_PROJECT_DIR' );

		$feature_suffix  = preg_replace( '/[^a-z0-9]+/', '-', strtolower( $feature ) );
		$scenario_suffix = preg_replace( '/[^a-z0-9]+/', '-', strtolower( $scenario ) );
		$filename        = "clover-behat/{$feature_suffix}-{$scenario_suffix}.xml";
		$destination     = "{$project_dir}/build/logs/{$filename}";

		( new Clover() )->process( $coverage, $destination, $name );
	}
);
