<?php

// Code heavily borrowed from the Redirection plugin (http://urbangiraffe.com/plugins/redirection/) for WordPress by John Godley

class Red_Xml_File extends Red_FileIO
{
	function collect ( $items )
	{
		if (count( $items ) > 0)
		{
			foreach ( $items as $item )
				$this->items[] = array ( 'keyword' => $item->keyword, 'source' => '/' . $item->keyword . '/', 'url' => stripslashes( $item->url ), 'clicks' => $item->clicks, 'title' => $item->title, 'timestamp' => $item->timestamp, 'ip' => $item->ip );
		}
	}

	function feed ($filename = '')
	{
		$filename = empty( $filename ) ? 'yourls_export.xml' : $filename;

		header ("Content-Type: text/xml");
		header ("Cache-Control: no-cache, must-revalidate");
		header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
//	 	header ('Content-Disposition: attachment; filename="'.$filename.'"');

		echo '<?xml version="1.0" encoding="utf-8"?>';
		?>

<redirection>
	<module name="YOURLS" id="1" type="wp">
		<group id="1" name="YOURLS" status="enabled" position="0" tracking="1">
<?php
// URLs are fetched in reverse order and positions start from 0
$position = count( $this->items ) - 1;
if ( count( $this->items ) > 0 ) :
	foreach ( $this->items as $item ) : ?>
			<item id="<?php echo $item['keyword']; ?>" position="<?php echo $position; ?>" status="enabled">
				<source><?php echo htmlspecialchars( $item['source'] ); ?></source>
				<title><?php echo htmlspecialchars( $item['title'] ); ?></title>
				<ip><?php echo htmlspecialchars( $item['ip'] ); ?></ip>
				<match type="url" regex="0"></match>
				<action type="url" code="301"><?php echo htmlspecialchars( $item['url'] ); ?></action>
				<statistic count="<?php echo $item['clicks']; ?>" access="<?php echo $item['timestamp']; ?>"/>
			</item>
<?php
		$position--;
	endforeach;
endif;
?>
		</group>
	</module>
</redirection>
<?php
	}

	function load ( $data ) {
		global $ydb;

		$count = 0;

		if ( function_exists( 'simplexml_load_string' ) ) {
			$xml = simplexml_load_string( $data );
			$table = YOURLS_DB_TABLE_URL;

			// From Redirection Plugin
			if ( count( $xml->module->group ) > 0 ) {
				foreach ( $xml->module->group as $group ) {

					if ( count( $group->item ) > 0 ) {
						foreach ( $group->item as $item ) {
							// Not supported
							if ( !empty( $item->action->option ) )
								continue;

							$keyword = trim( str_replace( '/', '', $item->source ) );

							if ( !yourls_keyword_is_free( $keyword ) )
								$keyword = '';

							$title = !empty( $item->title ) ? trim( $item->title ) : '';

							$result = yourls_add_new_link( trim( (string) $item->action ), $keyword, $title );

							if ( $result['status'] == 'success' ) {
								$count++;

								$update_arr = array();

								if ( !empty( $item->statistic['access'] ) )
									$update_arr[] = '`timestamp` = "' . trim( $item->statistic['access'] ) . '"';

								if ( !empty( $item->ip ) )
									$update_arr[] = '`ip` = "' . trim( $item->ip ) . '"';

								/* @see http://code.google.com/p/yourls/issues/detail?id=1036 */
								if ( !empty( $item->statistic['count'] ) )
									$update_arr[] = '`clicks` = ' . trim( $item->statistic['count'] );

								$update_sql = implode( ', ', $update_arr );

								if ( !empty( $update_sql ) )
									$ydb->query( "UPDATE `$table` SET " . $update_sql . " WHERE `keyword` = '" . $result['url']['keyword'] . "'" );
							}
						}
					}
					
				}
			}
		}
		else
		{
			die( 'XML importing is only available with PHP5.' );
		}

		return $count;
	}
}
?>