<?php

// Code heavily borrowed from the Redirection plugin (http://urbangiraffe.com/plugins/redirection/) for WordPress by John Godley

class Red_Csv_File extends Red_FileIO
{
	function collect ($items) {
		if ( count( $items ) > 0 )
		{
			foreach ( $items as $item )
				$this->items[] = array ( 'source' => '/' . $item->keyword . '/', 'target' => stripslashes( $item->url ), 'hits' => $item->clicks );
		}
	}

	function feed ($filename = '')
	{
		$filename = empty( $filename ) ? 'yourls_export.csv' : $filename;

		header ("Content-Type: text/csv");
		header ("Cache-Control: no-cache, must-revalidate");
		header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header ('Content-Disposition: attachment; filename="'.$filename.'"');

		if (count ($this->items) > 0)
		{
			echo "source,target,hits\r\n";

			foreach ($this->items AS $line)
				echo implode (",", array_map (array (&$this, 'escape'), $line))."\r\n";
		}
	}

	function escape ($value)
	{
		// Escape any special values
		$double = false;
		if (strpos ($value, ',') !== false || $value == '')
			$double = true;

		if (strpos ($value, '"') !== false)
		{
			$double = true;
			$value  = str_replace ('"', '""', $value);
		}

		if ($double)
			$value = '"'.$value.'"';
		return $value;
	}

	function parse_csv ($string, $separator = ',')
	{
		$string   = str_replace('""', "'", $string);
		$bits     = explode ('"',$string);
		$elements = array ();

		for ($i = 0; $i < count ($bits) ; $i++)
			{
				if (($i % 2) == 1)
					$elements[] = $bits[$i];
				else
				{
					$rest = $bits[$i];
					$rest = preg_replace ('/^'.$separator.'/', '', $rest);
					$rest = preg_replace ('/'.$separator.'$/', '', $rest);

					$elements = array_merge ($elements, explode ($separator, $rest));
				}
		}

		return $elements;
	}

	function load( $data, $filename ) {
		global $ydb;

		$count = 0;
		$file  = fopen( $filename, 'r' );
		$table = YOURLS_DB_TABLE_URL;

		if ( $file ) {
			while ( $csv = fgetcsv( $file, 1000, ',' ) ) {
				if ( $csv[0] != 'source' && $csv[1] != 'target') {
					$keyword = trim( str_replace( '/', '', $csv[0] ) );

					if ( !yourls_keyword_is_free( $keyword ) )
						$keyword = '';

					$result = yourls_add_new_link( trim( $csv[1] ), $keyword );

					if ( $result['status'] == 'success' ) {
						$count++;

						/* @see http://code.google.com/p/yourls/issues/detail?id=1036 */
						if ( !empty( $csv[2] ) )
							$ydb->query( "UPDATE `$table` SET `clicks` = " . trim( $csv[2] ) . " WHERE `keyword` = '" . $result['url']['keyword'] . "'" );
					}
				}
			}
		}

		return $count;
	}

	function is_regex ($url)
	{
		$regex  = '()[]$^?+';
		$escape = false;

		for ($x = 0; $x < strlen ($url); $x++)
		{
			if ($url{$x} == '\\')
				$escape = true;
			else if (strpos ($regex, $url{$x}) !== false && !$escape)
				return true;
			else
				$escape = false;
		}

		return false;
	}
}
?>