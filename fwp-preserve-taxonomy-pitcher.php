<?php
/*
Plugin Name: FWP+: Preserve Taxonomy (Pitcher)
Plugin URI: http://projects.radgeek.com/
Description: install on feed producer to preserve WordPress taxonomies across FeedWordPress-based syndication
Version: 2011.1109
Author: Charles Johnson
Author URI: http://radgeek.com/
License: GPL
*/

class FWPPreserveTaxonomyPitcher {
	function __construct () {
		add_filter('the_category_rss', array($this, 'the_category_rss'), 10, 2);
	} /* FWPPreserveTaxonomyPitcher::__construct () */
	
	function the_category_rss ($xml, $feed) {
		$terms = array();
		$terms['category'] = get_the_category();
		$terms['post_tag'] = get_the_tags();
		
		$term_names = array();
		
		$filter = (('atom'==$feed) ? 'raw' : 'rss');
		foreach ($terms as $tax => $list) :
			$term_names[$tax] = array();
			if ( !empty($list) ) : foreach ( (array) $list as $term ) :
				$term_names[$tax][] = array(
					sanitize_term_field(
						'name', $term->name, $term->term_id, $tax, $filter
					),
					$term->name,
				);
			endforeach; endif;
		endforeach;

		$xml = '';
		foreach ($term_names as $tax => $list) :
			foreach ($list as $term) :
				list($t, $l) = $term;
				
				// Stuck into CDATA or run through esc_attr, so we don't need to
				// worry about &amp; but we do need to worry about funkity HTML
				// entities that Atom, RSS and RDF don't know from &Adam;
				$t = @html_entity_decode($t, ENT_COMPAT, get_option('blog_charset'));
				
				// FWP+: Taxonomy pitching. Let's indicate it here.
				$tax_url = apply_filters('get_bloginfo_rss', get_bloginfo('url'));
				if (strpos($tax_url, '?') !== false) :
					$sep = '&';
				else :
					$sep = '?';
				endif;
				$tax_url .= $sep . "taxonomy=".urlencode($tax);
				
				switch ($feed) :
				case 'rdf' :
					$xml .= "\t\t<dc:subject><![CDATA[$t]]></dc:subject>\n";
					break;
				case 'atom' :
					$xml .= "\t\t"
						.sprintf(
							'<category scheme="%1$s" term="%2$s" label="%3$s"/>',
							esc_attr( $tax_url ),
							esc_attr( $t ),
							esc_attr( $l )
						)
						."\n";
					break;
				default :
					$xml .= "\t\t"
						.sprintf(
							'<category domain="%1$s"><![CDATA[%2$s]]></category>',
							esc_attr ( $tax_url ),
							$t
						)
						."\n";
						
				endswitch;
			endforeach;
		endforeach;
		return $xml;
	}
	
} /* class FWPPreserveTaxonomyPitcher */

$fwpPTP = new FWPPreserveTaxonomyPitcher;

