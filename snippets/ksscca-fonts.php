<?php
/**
 * Plugin Name: KSSCCA web fonts
 * Description: Loads the PT Sans Narrow and Inter faces once from the
 *              document head instead of via @import inside seven separate
 *              page-content <style> blocks. See GitHub issue #36 on
 *              spacecowboyian/ksscca.
 *
 * Same families, same weights, same source. Nothing renders differently;
 * the browser just discovers the stylesheet at the start of the head
 * rather than after it has parsed a <style> block halfway down the body.
 * An @import inside <style> blocks parsing until it resolves, and cannot
 * start downloading until the block containing it has been parsed.
 *
 * Loaded on every page rather than only the pages that use these faces:
 * the file is one small cached request, the pages using it are the main
 * ones, and gating it per page ID means editing this snippet every time a
 * page starts using the shared styles.
 *
 * The theme separately enqueues its own PT Sans Narrow at 400 under the
 * handle google-fonts-css. That is left alone; this adds the 700 weight
 * and Inter, which the theme never requested.
 *
 * Install: Code Snippets (WPCode) -> Add New -> paste this whole file ->
 * Insert Method: Auto Insert, location "Run Everywhere" -> Save Changes
 * and Activate.
 */

if ( ! function_exists( 'ksscca_fonts_url' ) ) {
	function ksscca_fonts_url() {
		// Byte-identical to the URL the @import used, so the same cached
		// response serves both during any overlap.
		return 'https://fonts.googleapis.com/css2?family=PT+Sans+Narrow:wght@700&family=Inter:wght@400;500;600;700&display=swap';
	}
}

if ( ! function_exists( 'ksscca_enqueue_fonts' ) ) {
	function ksscca_enqueue_fonts() {
		wp_enqueue_style( 'ksscca-fonts', ksscca_fonts_url(), array(), null );
	}
	add_action( 'wp_enqueue_scripts', 'ksscca_enqueue_fonts' );
}

if ( ! function_exists( 'ksscca_font_resource_hints' ) ) {
	/**
	 * The stylesheet comes from googleapis.com but the font files it
	 * references come from gstatic.com, so the second connection is worth
	 * opening early too. gstatic needs crossorigin because font files are
	 * fetched in CORS mode.
	 */
	function ksscca_font_resource_hints( $hints, $relation ) {
		if ( 'preconnect' !== $relation ) {
			return $hints;
		}
		$hints[] = array( 'href' => 'https://fonts.googleapis.com' );
		$hints[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
		return $hints;
	}
	add_filter( 'wp_resource_hints', 'ksscca_font_resource_hints', 10, 2 );
}
