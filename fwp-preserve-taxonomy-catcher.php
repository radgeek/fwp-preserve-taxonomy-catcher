<?php
/*
Plugin Name: FWP+: Preserve Taxonomy (Catcher)
Plugin URI: http://projects.radgeek.com/
Description: install on feed consumer to preserve WordPress taxonomies across FeedWordPress-based syndication
Version: 2011.1109
Author: Charles Johnson
Author URI: http://radgeek.com/
License: GPL
*/

class FWPPreserveTaxonomyCatcher {
	var $feed_terms;

	function __construct () {
		add_filter('syndicated_item_categories', array($this, 'syndicated_item_categories'), 10, 2);
		add_filter('syndicated_item_tags', array($this, 'syndicated_item_tags'), 10, 2);
	}
	
	function syndicated_item_categories ($cats, $post) {
		// OK, let's start from scratch.
		$cats = array();

		$this->feed_terms = array();
		
		$post_cats = $post->entry->get_categories();
		if (is_array($post_cats)) : foreach ($post_cats as $cat) :
			// When our pitcher is on the mound, taxonomy is indicated by
			// an HTTP GET parameter in category/@scheme or category/@domain
			$cat_scheme = $cat->get_scheme();
			$cat_tax = 'category';
			if (preg_match(':[?&]taxonomy=([^&]+)(&|$):i', $cat_scheme, $ref)) :
				$cat_tax = $ref[1];
			endif;
			
			$cat_name = $cat->get_term();
			if (!$cat_name) : $cat_name = $cat->get_label(); endif;
			
			if (!isset($this->feed_terms[$cat_tax])) :
				$this->feed_terms[$cat_tax] = array();
			endif;
			
			if ($post->link->setting('cat_split', NULL, NULL)) :
				$pcre = "\007".$post->feedmeta['cat_split']."\007";
				$this->feed_terms[$cat_tax] = array_merge(
					$this->feed_terms[$cat_tax],
					preg_split(
						$pcre,
						$cat_name,
						-1 /*=no limit*/,
						PREG_SPLIT_NO_EMPTY
					)
				);
			else :
				$this->feed_terms[$cat_tax][] = $cat_name;
			endif;
		endforeach; endif;
		
		if (isset($this->feed_terms['category'])) :
			// Replace, don't add, because normally category includes all the
			// category elements.
			$cats = $this->feed_terms['category'];
		
			// Once it's passed back, we take it off the pile.
			unset($this->feed_terms['category']);
		endif;
		
		return $cats;
	} /* FWPPreserveTaxonomyCatcher::syndicated_item_categories () */
	
	function syndicated_item_tags ($tags, $post) {
		if (isset($this->feed_terms['post_tag'])) :
			$tags = array_merge($tags, $this->feed_terms['post_tag']);
			unset($this->feed_terms['post_tag']);
		endif;
		
		return $tags;
	} /* FWPPreserveTaxonomyCatcher::syndicated_item_tags () */
}

$fwpPTC = new FWPPreserveTaxonomyCatcher;

