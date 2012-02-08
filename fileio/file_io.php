<?php

// Code heavily borrowed from the Redirection plugin (http://urbangiraffe.com/plugins/redirection/) for WordPress by John Godley

class Red_FileIO
{
	var $items = array();

	function export ( $type ) {
		global $ydb;

		// Variables
		$table_url = YOURLS_DB_TABLE_URL;
		// Default SQL behavior
		$where = '';
		$sort_by_sql = 'timestamp';
		$sort_order_sql = 'desc';

		// Main Query
		$items = $ydb->get_results("SELECT * FROM `$table_url` WHERE 1=1 $where ORDER BY `$sort_by_sql` $sort_order_sql;");

		if ( !empty( $items ) )
		{
			require_once $type . '.php';

			if ($type == 'rss')
				$exporter = new Red_Rss_File();
			else if ($type == 'xml')
				$exporter = new Red_Xml_File();
			else if ($type == 'csv')
				$exporter = new Red_Csv_File();
			else if ($type == 'apache')
				$exporter = new Red_Apache_File();

			$exporter->collect($items);
			$exporter->feed();
			return true;
		}

		return false;
	}

	function import ( $file ) {
		if ( is_uploaded_file( $file['tmp_name'] ) ) {
			$parts = pathinfo( $file['name'] );

			if ( $parts['extension'] == 'xml') {
				include dirname( __FILE__ ).'/xml.php';
				$importer = new Red_Xml_File();
				$data = @file_get_contents( $file['tmp_name'] );
			}
			elseif ( $parts['extension'] == 'csv' ) {
				include dirname( __FILE__ ).'/csv.php';
				$importer = new Red_Csv_File();
				$data = '';
			}
			else {
				include dirname( __FILE__ ).'/apache.php';
				$importer = new Red_Apache_File();
				$data = @file_get_contents( $file['tmp_name'] );
			}

			return $importer->load( $data, $file['tmp_name'] );
		}

		return 0;
	}

	function load ( $data, $filename ) { }
}

?>