<?php
/*
Plugin Name: FWP+: Preserve Taxonomy (Catcher)
Plugin URI: http://projects.radgeek.com/
Description: install on feed consumer to preserve WordPress taxonomies across FeedWordPress-based syndication
Version: 2016.0330
Author: Charles Johnson
Author URI: http://radgeek.com/
License: GPL
*/

class FWPPreserveTaxonomyCatcher {
	private $feed_terms;
	
	public function __construct () {
		add_filter('syndicated_item_feed_terms', array($this, 'syndicated_item_categories'), 10, 2);
		add_filter('syndicated_post_terms_mapping', array($this, 'syndicated_post_terms_mapping'), 10, 2);
		add_filter('syndicated_post_terms_match', array($this, 'syndicated_post_terms_match'), 10, 3);
		add_filter('syndicated_post_terms_unfamiliar', array($this, 'syndicated_post_terms_unfamiliar'), 10, 3);
	} /* FWPPreserveTaxonomyCatcher::__construct() */
	
	public function syndicated_item_categories ($cats, $post) {
		// OK, let's start from scratch.
		$cats = array();
		$this->feed_terms = array();

		$post_cats = $post->entry->get_categories();
		if (is_array($post_cats)) : foreach ($post_cats as $cat) :
			// When our pitcher is on the mound, taxonomy is indicated by
			// an HTTP GET parameter in category/@scheme or category/@domain
			//
			// If a taxonomy is *not* indicated, or if the taxonomy does not
			// exist over here, then we toss these into a bin called '*'
			// which can hold just any old term and is processed using
			// standard-style inclusive search rules.
			$cat_scheme = $cat->get_scheme();
			$cat_tax = '*';
			if (preg_match(':[?&]taxonomy=([^&]+)(&|$):i', $cat_scheme, $ref)) :
				if (taxonomy_exists($ref[1])) :
					$cat_tax = $ref[1];
				endif;
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
		
		if (count($this->feed_terms['*'])) :
			if (!isset($this->feed_terms['post_tag'])) :
				$this->feed_terms['post_tag'] = array();
			endif;
			$this->feed_terms['post_tag'] = array_merge($this->feed_terms['post_tag'], $post->inline_tags());
		endif;
		
		return $this->feed_terms;
	} /* FWPPreserveTaxonomyCatcher::syndicated_item_feed_terms () */
	
	public function syndicated_post_terms_match ($taxonomies, $what, $post) {
		if ($what != '*') :
			// Stay in your lane.
			$taxonomies = array($what);
		endif;
		
		return $taxonomies;
	} /* FWPPreserveTaxonomyCatcher::syndicated_post_terms_match() */
	
	public function syndicated_post_terms_mapping ($map, $post) {
		$map['*'] = $map['category'];
		return $map;
	} /* FWPPreserveTaxonomyCatcher::syndicated_post_term_mapping() */

	public function syndicated_post_terms_unfamiliar ($unfamiliar, $what, $post) {
		if (preg_match('/^create(:(.*))?$/i', $unfamiliar, $ref)) :
			if ($what != '*') :
				// Stay in your lane.
				$unfamiliar = 'create:' . $what;
			endif;
		endif;
		
		return $unfamiliar;
	} /* FWPPreserveTaxonomyCatcher::syndicated_post_terms_unfamiliar () */
	
} /* FWPPreserveTaxonomyCatcher */

$fwpPTC = new FWPPreserveTaxonomyCatcher;

