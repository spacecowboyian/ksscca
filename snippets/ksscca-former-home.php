<?php
/**
 * Plugin Name: KSSCCA former homepage handling
 * Description: Redirects /home/, the superseded homepage, to the front page.
 *
 * Context: on 2026-09-04 the V2 homepage (page 1655) was promoted to be the
 * front page. The previous homepage did not disappear; it simply became
 * reachable at its own slug, /home/, where it self-canonicalised and stayed
 * in the sitemap. That left two pages both presenting themselves as the
 * region's home page, competing for the same searches.
 *
 * The first version of this snippet was a holding position: a canonical and
 * a sitemap exclusion, no redirect, because a parallel session was mid
 * redesign on page 1342 and a 301 would have made previewing impossible.
 * That work concluded, and on 2026-09-14 Ian chose to redirect and delete
 * (issue #44). Page 1342 is in the trash; its content was a YouTube embed
 * and the events calendar, both of which the front page already covers.
 *
 * The redirect matches on the request path rather than on the page, because
 * the page no longer exists to match against. That also means it keeps
 * working if the trashed page is ever purged.
 *
 * Install: Code Snippets (WPCode) -> Add New -> paste this whole file ->
 * Insert Method: Auto Insert, location "Run Everywhere" -> Save Changes
 * and Activate.
 */

if ( ! function_exists( 'ksscca_former_home_redirect' ) ) {
	function ksscca_former_home_redirect() {
		if ( is_admin() ) {
			return;
		}

		$path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
		$path = trim( (string) $path, '/' );

		// /home/ and /home-v2/ both pointed at the old page at various times.
		// The second already redirected on its own; keeping it here means one
		// place to look rather than two.
		if ( 'home' === $path || 'home-v2' === $path ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}
	add_action( 'template_redirect', 'ksscca_former_home_redirect', 1 );
}
