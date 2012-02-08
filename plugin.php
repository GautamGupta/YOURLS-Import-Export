<?php
/*
Plugin Name: YOURLS Import Export
Plugin URI: http://gaut.am/plugins/yourls/yourls-import-export/
Description: Import and Export the URLs
Version: 1.0
Author: Gautam
Author URI: http://gaut.am/
============================================================================================================
This software is provided "as is" and any express or implied warranties, including, but not limited to, the
implied warranties of merchantibility and fitness for a particular purpose are disclaimed. In no event shall
the copyright owner or contributors be liable for any direct, indirect, incidental, special, exemplary, or
consequential damages(including, but not limited to, procurement of substitute goods or services; loss of
use, data, or profits; or business interruption) however caused and on any theory of liability, whether in
contract, strict liability, or tort(including negligence or otherwise) arising in any way out of the use of
this software, even if advised of the possibility of such damage.

For full license details see license.txt
============================================================================================================
*/

/**
 * Get the supported export formats
 *
 * @return array Supported export formats
 */
function yourls_imex_get_export_formats() {
	return array(
	//	'apache' => 'Apache',
		'csv' => 'Comma separated values (CSV)',
	//	'rss' => 'Really Simple Syndication (RSS)',
	//	'xml' => 'Extensible Markup Language (XML)'
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
		$export_urls[$export_option] = '<a href="' . yourls_nonce_url( 'imex_export_' . $export_option, yourls_add_query_arg( array( 'export' => $export_option ) ) ) . '" title="Export URLs in ' . $export_label . ' format">' . $export_label . '</a>';
	}

	echo '<h2>Import</h2>

		<p>Here you can import redirections from an existing a CSV file.</p>

		<form action="' . yourls_add_query_arg( array( 'export' => $export_option ) ) . '" method="post" accept-charset="utf-8" enctype="multipart/form-data">
			' . yourls_nonce_field( 'imex_import', 'nonce', false, false ) . '

			<input type="file" name="import" value="" />
			<input class="button-primary" type="submit" name="import" value="Upload" />
		</form>';

	echo <<<HTML
		<br />
		<h2>Export</h2>
HTML;

	echo 'Export URLs in: ' . implode( ' | ', $export_urls );

	echo <<<HTML
		<br /><br />
		<h2>Donate</h2>

		Did the plugin help you? <a href="http://gaut.am/donate/">Donate</a> to the author to keep him releasing more open source and free software!
HTML;
}

/**
 * Handle import/export
 */
function yourls_imex_handle_post()
{
	// Import
	if ( !empty( $_FILES['import'] ) && !empty( $_POST['nonce'] ) && yourls_verify_nonce( 'imex_import', $_POST['nonce'] ) ) {
		$count = yourls_imex_import_urls( $_FILES['import'] );

		if ( $count > 0 )
			$message = $count . ' redirection(s) were successfully imported.';
		else
			$message = 'No items were imported.';
	}

	// Export
	if( isset( $_GET['export'] ) && !empty( $_GET['nonce'] ) ) {

		$format = in_array( $_GET['export'], array_keys( yourls_imex_get_export_formats() ) ) ? $_GET['export'] : 'csv';

		yourls_verify_nonce( 'imex_export_' . $format, $_GET['nonce'] );

		if ( yourls_imex_export_urls( $format ) )
			die();
		else
			$message = 'YOURLS export failed!';
	}

	// Message
	if ( !empty( $message ) )
		yourls_add_notice($message);
}

/**
 * Import the urls
 * @param type $import_file Uploaded file to be imported
 * @return int|bool Count of imported redirections or false on failure
 */
function yourls_imex_import_urls( $import_file ) {
	require_once 'fileio/file_io.php';

	$importer = new Red_FileIO;
	return $importer->import( $import_file );
}

/**
 * Export the urls
 *
 * @param string $format Format of the file to be exported. Check {@link yourls_imex_get_export_formats()}
 * @return boolean True on success, false on failure
 */
function yourls_imex_export_urls($format = 'csv') {
	$format = in_array( $format, array_keys( yourls_imex_get_export_formats() ) ) ? $format : 'csv';

	require_once 'fileio/file_io.php';

	$exporter = new Red_FileIO;
	return $exporter->export( $format );
}

// Register our plugin admin page
yourls_add_action( 'plugins_loaded', 'yourls_imex_add_page' );

// Handle import/export
yourls_add_action( 'load-import_export', 'yourls_imex_handle_post' );
