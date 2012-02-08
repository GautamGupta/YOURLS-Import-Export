<?php

// Code heavily borrowed from the Redirection plugin (http://urbangiraffe.com/plugins/redirection/) for WordPress by John Godley

class Red_Rss_File extends Red_FileIO
{
	function collect ( $items )
	{
		if ( count ( $items ) > 0 )
		{
			foreach ( $items as $item )
				$this->items[] = array( 'id' => $item->keyword, 'url' => '/' . $item->keyword . '/', 'destination' => stripslashes( $item->url ), 'created_at' => 'timestamp' );
		}
	}

	function feed ( $title = '' )
	{
		$title = empty( $title ) ? 'YOURLS Log' : $title;

		header( 'Content-type: text/xml; charset=utf-8', true );
		echo '<?xml version="1.0" encoding="utf-8"?>' . "\r\n";
?>
<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
	<title><?php echo $title; ?></title>
	<link><?php yourls_site_url(); ?></link>
	<description>Log of <?php yourls_site_url(); ?> short URLs</description>
	<?php if ( !empty( $this->items[0]['created_at'] ) ) : ?>
	<pubDate><?php echo htmlspecialchars( date( 'D, d M Y H:i:s +0000', strtotime( $this->items[0]['created_at'] ) ) ); ?></pubDate>
	<?php endif; ?>
	<language>en</language>
	<?php
		if ( count( $this->items ) > 0 ) :
			foreach ( $this->items as $log ) : ?>
	<item>
		<title><![CDATA[<?php echo $log['url']; ?>]]></title>
		<link><![CDATA[<?php echo yourls_link( $log['id'] ); ?>]]></link>
		<pubDate><?php echo date( 'D, d M Y H:i:s +0000', strtotime( $log['created_at'] ) ); ?></pubDate>
		<guid isPermaLink="false"><?php echo $log['id']; ?></guid>
		<description><![CDATA[<?php echo $log['url']; ?>]]></description>
		<content:encoded><![CDATA[<?php echo $log['destination']; ?>]]></content:encoded>
	</item>
		<?php endforeach; endif; ?>
</channel>
</rss>
<?php
		die();
	}
}

?>