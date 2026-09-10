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

/* Mobile submenus. The theme ships styling for these rows in
   mobile_navigation.css but nothing that ever reveals them: no has-dropdown
   class for Foundation to bind to, and no open state of its own. So tapping a
   discipline simply followed its link and the 15 child pages had no route on
   a phone. The disclosure buttons are added in JS below; these rules give the
   open state somewhere to land, and repaint rows the theme styles for a light
   background (#333 text on white stripes) that would be invisible here. */
.top-bar li.ksr-open > ul.sub-menu{display:block !important;}
.top-bar ul.sub-menu,.top-bar ul.sub-menu li{background:#0d0d0d !important;background-image:none !important;border:0 !important;}
.top-bar ul.sub-menu li{border-top:1px solid #262626 !important;}
.top-bar ul.sub-menu a{color:#e7e5df !important;font-size:15px !important;line-height:1.4 !important;padding:12px 56px 12px 34px !important;display:block !important;background-image:none !important;}
.top-bar li.ksr-parent{position:relative;}
/* The theme prints a child-count badge in the same corner the control needs. */
.top-bar li.ksr-parent span.cnt{display:none !important;}
.ksr-subtoggle{position:absolute;top:0;right:0;width:56px;height:45px;padding:0;border:0;background:none;color:#e7e5df;font-size:20px;line-height:45px;cursor:pointer;z-index:5;}
.ksr-subtoggle::before{content:"\25BE";display:inline-block;transition:transform .15s;}
.ksr-subtoggle[aria-expanded="true"]::before{transform:rotate(180deg);}
.ksr-subtoggle:focus-visible{outline:2px solid #e2c47c;outline-offset:-2px;}

/* Desktop logo. The image sat 9px from each side of a 218px column and
   overflowed its own container's height, so it read as jammed into the
   corner. Padding plus a fluid image gives it room without resizing it by
   hand. */
#logo{padding:22px 18px 18px !important;height:auto !important;box-sizing:border-box;}
#logo img{max-width:100% !important;height:auto !important;display:block;margin:0 auto !important;}
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

	// Mobile submenu disclosure. A separate control rather than hijacking the
	// parent link: three of the four parents point at real pages
	// (/rallycross/, /road-racing/, /track-events/), so intercepting the tap
	// would cut off the only route to them.
	//
	// Run more than once on purpose. Foundation's top bar rebuilds this markup
	// during its own init, which happens after this inline script, and that
	// rebuild discards anything already appended. The duplicate guard below
	// makes repeat calls harmless.
	function ksrAddSubToggles() {
	document.querySelectorAll('.top-bar li').forEach(function (li) {
		var sub = li.querySelector(':scope > ul.sub-menu');
		var link = li.querySelector(':scope > a');
		if (!sub || !link || li.querySelector(':scope > .ksr-subtoggle')) { return; }
		li.classList.add('ksr-parent');
		var name = link.textContent.replace(/\s*\d+\s*$/, '').trim();
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'ksr-subtoggle';
		btn.setAttribute('aria-expanded', 'false');
		btn.setAttribute('aria-label', 'Show ' + name + ' pages');
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var open = li.classList.toggle('ksr-open');
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
		li.appendChild(btn);
	});
	}
	ksrAddSubToggles();
	window.addEventListener('load', ksrAddSubToggles);
	setTimeout(ksrAddSubToggles, 600);
})();
</script>
		<?php
	}
	add_action( 'wp_footer', 'ksscca_nav_a11y_js', 20 );
}
