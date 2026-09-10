<?php
/**
 * Plugin Name: KSSCCA former homepage handling
 * Description: Points the superseded homepage (page 1342, now reachable at
 *              /home/) at the real front page and keeps it out of the XML
 *              sitemap.
 *
 * Context: on 2026-09-04 the V2 homepage (page 1655) was promoted to be the
 * front page. The previous homepage did not disappear; it simply became
 * reachable at its own slug, /home/, where it self-canonicalised and stayed
 * in the sitemap. That leaves two pages both presenting themselves as the
 * region's home page, competing for the same searches.
 *
 * The gentle fix is used here rather than a redirect, deliberately. A 301
 * would make the page impossible to view, and it is still being worked on
 * in a parallel homepage redesign. A canonical says "the front page is the
 * real one" to search engines while leaving the page fully visible to
 * anyone who opens it.
 *
 * When the redesign work is finished, replace this with a decision: either
 * redirect /home/ to / and delete the page, or keep it deliberately. This
 * snippet is a holding position, not an answer.
 *
 * Structured data and headers only. Nothing a visitor sees changes.
 *
 * Install: Code Snippets (WPCode) -> Add New -> paste this whole file ->
 * Insert Method: Auto Insert, location "Run Everywhere" -> Save Changes
 * and Activate.
 */

if ( ! defined( 'KSSCCA_FORMER_HOME_ID' ) ) {
	define( 'KSSCCA_FORMER_HOME_ID', 1342 );
}

if ( ! function_exists( 'ksscca_former_home_canonical' ) ) {
	function ksscca_former_home_canonical( $canonical ) {
		if ( is_page( KSSCCA_FORMER_HOME_ID ) ) {
			return home_url( '/' );
		}
		return $canonical;
	}
	add_filter( 'wpseo_canonical', 'ksscca_former_home_canonical', 10, 1 );
}

if ( ! function_exists( 'ksscca_former_home_sitemap' ) ) {
	/**
	 * Keep it out of the sitemap too. A canonical tells a crawler which URL
	 * wins; listing it in the sitemap simultaneously invites the crawl in
	 * the first place, which is a mixed signal.
	 */
	function ksscca_former_home_sitemap( $excluded ) {
		if ( ! is_array( $excluded ) ) {
			$excluded = array();
		}
		$excluded[] = KSSCCA_FORMER_HOME_ID;
		return $excluded;
	}
	add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', 'ksscca_former_home_sitemap', 10, 1 );
}
