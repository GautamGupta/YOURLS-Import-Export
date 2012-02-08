<?php
/*
Plugin Name: YOURLS Import Export
Plugin URI: http://gaut.am/plugins/yourls/yourls-import-export/
Description: Import and Export the URLs
Version: 1.0
Author: Gautam Gupta
Author URI: http://gaut.am/
*/

/**
 * Get the supported export formats
 *
 * @return array Supported export formats
 */
function yourls_imex_get_export_formats() {
	return array(
		'apache' => 'Apache',
		'csv' => 'Comma separated values (CSV)',
		'rss' => 'Really Simple Syndication (RSS)',
		'xml' => 'Extensible Markup Language (XML)'
	);
}

/**
 * Add the plugin page in the menu
 */
function yourls_imex_add_page() {
	yourls_register_plugin_page( 'import_export', 'Import/Export', 'yourls_imex_do_page' );
}

/**
 * Display admin page
 */
function yourls_imex_do_page() {
	$export_urls = array();

	foreach ( yourls_imex_get_export_formats() as $export_option => $export_label ) {
		$export_urls[$export_option] = '<a href="' . yourls_nonce_url( 'export_' . $export_option, yourls_add_query_arg( array( 'export' => $export_option ) ) ) . '" title="Export URLs in ' . $export_label . ' format">' . $export_label . '</a>';
	}

	echo <<<HTML
		<h2>Export</h2>
HTML;

	echo implode( ' | ', $export_urls );

	echo <<<HTML
		<br /><br />
		<h2>Import</h2>
HTML;
}

/**
 * Handle export
 * @return boolean
 */
function yourls_imex_handle_export()
{
	// Check if a form was submitted
	if( !isset( $_GET['export'] ) || empty( $_GET['nonce'] ) )
		return false;

	$format = in_array( $_GET['export'], array_keys( yourls_imex_get_export_formats() ) ) ? $_GET['export'] : 'csv';

	yourls_verify_nonce( 'export_' . $format, $_GET['nonce'] );

	yourls_imex_export_urls( $format );
}

/**
 * Export the urls
 */
function yourls_imex_export_urls($format = 'csv') {
	$format = in_array( $format, array_keys( yourls_imex_get_export_formats() ) ) ? $format : 'csv';

	require_once 'fileio/file_io.php';

	$exporter = new Red_FileIO;
	if ( $exporter->export( $format ) )
		die();
}

// Register our plugin admin page
yourls_add_action( 'plugins_loaded', 'yourls_imex_add_page' );

// Handle export
yourls_add_action( 'load-import_export', 'yourls_imex_handle_export' );
