<?php

class Red_Csv_File extends Red_FileIO
{
	function collect ($items) {
		if (count ($items) > 0)
		{
			foreach ($items AS $item)
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

	function load( $group, $data, $filename ) {
		$count = 0;
		$file  = fopen( $filename, 'r' );

		if ( $file ) {
			while ( ( $csv = fgetcsv( $file, 1000, ',' ) ) ) {
				if ( $csv[0] != 'source' && $csv[1] != 'target') {
					Red_Item::create( array(
						'source' => trim( $csv[0] ),
						'target' => trim( $csv[1] ),
						'regex'  => $this->is_regex( $csv[0] ),
						'group'  => $group,
						'match'  => 'url',
						'red_action' => 'url'
					) );

					$count++;
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