<?php
/**
 * Plugin Name: KSSCCA design tokens
 * Description: Declares the DESIGN.md palette, fonts, radii and row metrics
 *              as CSS custom properties on :root, site-wide.
 *
 * Issue #37. Until now every surface wrote the palette out as literal hex,
 * four separate times: the Solo Nationals stylesheet, bin/build-results-page,
 * the homepage body, and the calendar snippet. `#e7e5df`, `#99978f`,
 * `#2d2d2d` and `#e2c47c` appear in all four independently.
 *
 * This is step 1 of docs/design/component-library-plan.md. It changes nothing
 * on its own: every value here is the value already in use. Consumers move to
 * var() one at a time, each verified by computed-style parity against the page
 * before the change.
 *
 * Delivered as a snippet rather than through the theme's custom.css so the
 * source stays in this repo and survives a theme change. Ian's call, 2026-09-14.
 *
 * Two values in use are NOT in DESIGN.md and are named here for the first
 * time: the wheat gold family, which the results pages and the Register button
 * use, and the quiet seam grey used for separator dots. See issue #73.
 *
 * Install: Code Snippets (WPCode) -> Add New -> paste this whole file ->
 * Insert Method: Auto Insert, location "Run Everywhere" -> Save Changes
 * and Activate.
 */

if ( ! function_exists( 'ksscca_tokens' ) ) {
	function ksscca_tokens() {
		// One line, no newline after the opening tag: wpautop inserts a <p>
		// into a style block that starts on the next line and the CSS parser
		// then eats the following rule. This prints in wp_head rather than
		// through the_content, but the habit is cheap and the cost of getting
		// it wrong is a silently missing rule.
		echo '<style id="ksscca-tokens">:root{'
			// colour: DESIGN.md frontmatter, verbatim
			. '--ks-floodlight-blue:#5cb3dd;'
			. '--ks-paddock-lamp-blue:#8ccdef;'
			. '--ks-paddock-lamp-well:#17313c;'
			. '--ks-grid-light-green:#6cc98d;'
			. '--ks-grid-light-green-hover-text:#12281c;'
			. '--ks-cta-text:#1a1a1a;'
			. '--ks-rallycross-amber:#e2a768;'
			. '--ks-rallycross-amber-well:#3a2a14;'
			. '--ks-roadracing-rose:#e08277;'
			. '--ks-roadracing-rose-well:#3a201d;'
			. '--ks-fog-ground:#111111;'
			. '--ks-fog-lift:#161616;'
			. '--ks-ground-seam:#2d2d2d;'
			. '--ks-paddock-white:#e7e5df;'
			. '--ks-morning-mist:#99978f;'
			// colour: in use on the live site, not yet in DESIGN.md (#73)
			. '--ks-wheat-gold:#e2c47c;'
			. '--ks-wheat-gold-bright:#f2d999;'
			. '--ks-wheat-gold-edge:#6b4d1d;'
			. '--ks-wheat-gold-edge-hover:#a86e1d;'
			. '--ks-wheat-gold-wash:rgba(226,196,124,.1);'
			. '--ks-seam-quiet:#4a4a4a;'
			. '--ks-mist-deep:#6f6d67;'
			// type
			. "--ks-font-display:'PT Sans Narrow',Verdana,Arial,sans-serif;"
			. "--ks-font-body:'Inter',Verdana,Arial,sans-serif;"
			. '--ks-label-size:12px;'
			. '--ks-label-tracking:.08em;'
			// shape and rhythm
			. '--ks-radius-badge:10px;'
			. '--ks-radius-button:8px;'
			. '--ks-row-y:14px;'
			. '--ks-row-x:18px;'
			. '--ks-card-gap:12px;'
			. '}</style>';
	}
	// Late enough that a page can still override a token in its own block.
	add_action( 'wp_head', 'ksscca_tokens', 5 );
}
