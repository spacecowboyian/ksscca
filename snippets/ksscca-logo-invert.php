<?php
/**
 * Plugin Name: KSSCCA logo colour correction
 * Description: Renders the sidebar logo as white on black on every page,
 *              not just the front page.
 *
 * The logo in the Media Library (147672418856777.jpg) is black artwork on
 * a WHITE box. The theme drops it into a black sidebar, so on most pages
 * it appears as a bright white rectangle -- the opposite of the intended
 * treatment. The front page looked correct only because the homepage's
 * own inline CSS carried a `body.page-id-1655` rule inverting it; every
 * other page missed out.
 *
 * A CSS invert is the right tool here and not a bodge: the artwork is
 * monochrome (sampled: 250,250,250 ground and 195,195,195 midtone), so
 * inverting maps white to black and the black type to white with nothing
 * else disturbed. It is also reversible and touches no files.
 *
 * The real fix is a logo asset with a transparent background, uploaded
 * through Appearance -> Theme Options. That needs the kingsize options
 * screen, whose Save button is broken (issue #17), so it waits for a
 * person. When that day comes, delete this snippet: the rule is keyed to
 * the current file name, so a new logo simply stops being inverted rather
 * than being inverted wrongly.
 *
 * Install: Code Snippets (WPCode) -> Add New -> paste this whole file ->
 * Insert Method: Auto Insert, location "Run Everywhere" -> Save Changes
 * and Activate.
 */

if ( ! function_exists( 'ksscca_logo_invert_css' ) ) {
	function ksscca_logo_invert_css() {
		echo '<style id="ksscca-logo-invert">'
			. 'img[src*="147672418856777"]{filter:invert(1);}'
			. '</style>' . "\n";
	}
	add_action( 'wp_head', 'ksscca_logo_invert_css', 99 );
}
