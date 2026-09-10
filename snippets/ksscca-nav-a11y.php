<?php
/**
 * Plugin Name: KSSCCA nav accessibility
 * Description: Makes the main navigation usable with a keyboard and gives the
 *              mobile menu button a name and a real tap target. See GitHub
 *              issue #40 on spacecowboyian/ksscca.
 *
 * Three problems this addresses, all measured on the live nav 2026-09-04:
 *
 * 1. Submenu links could not be reached by keyboard at all. The theme reveals
 *    them with `#mainNavigation ul li:hover > ul{display:block}` and nothing
 *    else, and `display:none` takes an element out of the tab order entirely,
 *    so 15 of the 23 nav links were unreachable without a mouse. Mirroring
 *    that rule with :focus-within fixes it without touching the theme.
 *
 * 2. No focus indicator existed anywhere in the nav. A keyboard user had no
 *    idea where they were. :focus-visible means a mouse click still shows
 *    nothing, so this is invisible to mouse users.
 *
 * 3. The mobile menu button is a 16x16 empty <a> with no accessible name.
 *    A transparent 44x44 pseudo-element centred over it extends the tap
 *    target without moving the icon, since the anchor's own box is pinned
 *    by having all four offsets set and will not grow with padding.
 *
 * The gold matches the palette's rallycross-amber, already in use for the
 * hero eyebrow and the results links.
 *
 * Install: Code Snippets (WPCode) -> Add New -> paste this whole file ->
 * Insert Method: Auto Insert, location "Run Everywhere" -> Save Changes
 * and Activate.
 */

if ( ! function_exists( 'ksscca_nav_a11y_css' ) ) {
	function ksscca_nav_a11y_css() {
		?>
<style id="ksscca-nav-a11y">
/* Keyboard equivalent of the theme's own li:hover > ul rule. */
#mainNavigation ul li:focus-within > ul{display:block;}
/* A visible focus ring. :focus-visible keeps it off for mouse users. */
#mainNavigation a:focus-visible,.top-bar a:focus-visible,.toggle-topbar a:focus-visible{outline:2px solid #e2c47c;outline-offset:2px;}
/* Grow the mobile toggle from 16x16 to a 44x44 target. Padding cannot do it:
   the anchor has all four offsets set, which pins its size regardless. An
   empty pseudo-element centred over it extends the hit area instead, and
   since it paints nothing the icon does not move and nothing reflows. The
   4px upward nudge keeps all 44 pixels inside the 45px bar; without it the
   bottom edge falls onto page content, which both loses the tap and would
   put an invisible target over the hero. */
/* Submenu legibility. The links inherit the nav anchor's pure red #ff0000,
   except where a current-item rule happens to override it to white, so the
   four flyouts were three red and one white at 5.25:1. One documented token
   for all of them, at 16.67:1, and 15px instead of 12px. Only the two
   longest labels wrap to a second line. */
#mainNavigation ul li ul li a{color:#e7e5df !important;font-size:15px !important;line-height:1.45 !important;}
#mainNavigation ul li ul li a:hover,#mainNavigation ul li ul li a:focus{color:#ffffff !important;}
/* Submenu rows: make the link the row. The theme puts the padding on the li
   and fixes both the li and the panel to a width, so the anchor was a 150x22
   island inside a 41px row: the top 10px, bottom 8px and left 10px of every
   row did nothing when clicked. Padding moves to the anchor, the row goes
   full width, and the bottom border now runs edge to edge with it. Rows come
   out 45px, so each one also clears the 44px comfortable tap size.
   The panel widens 182 -> 200 because the 15px type wraps two labels at the
   old width; past 200 nothing further fits on one line. */
#mainNavigation ul li ul{width:200px !important;padding-left:0 !important;padding-right:0 !important;}
#mainNavigation ul li ul li{width:100% !important;padding:0 !important;}
#mainNavigation ul li ul li a{display:block !important;width:auto !important;max-width:none !important;padding:11px 16px !important;}
.toggle-topbar a{position:absolute;}
.toggle-topbar a::after{content:"";position:absolute;top:calc(50% - 4px);left:50%;width:44px;height:44px;transform:translate(-50%,-50%);}
</style>
		<?php
	}
	add_action( 'wp_head', 'ksscca_nav_a11y_css', 20 );
}

if ( ! function_exists( 'ksscca_nav_a11y_js' ) ) {
	/**
	 * Semantics the theme's markup never had. Done in JS because the nav is
	 * printed by the theme template, not by wp_nav_menu(), so there is no
	 * filter to hook.
	 *
	 * aria-expanded is deliberately omitted: it would have to track focus and
	 * hover to stay truthful, and an attribute that always says "false" is
	 * worse than no attribute at all.
	 */
	function ksscca_nav_a11y_js() {
		?>
<script id="ksscca-nav-a11y-js">
(function(){
	var nav = document.getElementById('mainNavigation');
	if (nav) {
		if (!nav.getAttribute('role')) { nav.setAttribute('role', 'navigation'); }
		if (!nav.getAttribute('aria-label')) { nav.setAttribute('aria-label', 'Main'); }
		nav.querySelectorAll('li > ul').forEach(function (sub) {
			var link = sub.parentElement.querySelector(':scope > a');
			if (link) { link.setAttribute('aria-haspopup', 'true'); }
		});
	}
	document.querySelectorAll('.toggle-topbar a').forEach(function (a) {
		if (!a.textContent.trim() && !a.getAttribute('aria-label')) {
			a.setAttribute('aria-label', 'Open menu');
		}
	});
})();
</script>
		<?php
	}
	add_action( 'wp_footer', 'ksscca_nav_a11y_js', 20 );
}
