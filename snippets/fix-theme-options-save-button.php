<?php
/**
 * Plugin Name: Fix KingSize Theme Options Save button (jQuery .live() removed)
 * Description: Re-binds the "Save All Changes" button on Appearance -> Theme
 *              Options, which has been a silent no-op since jQuery Migrate
 *              stopped shimming .live(). See GitHub issue #17 on
 *              spacecowboyian/ksscca -- that issue traced the exact cause
 *              (smof.js:524, `$('#of_save').live('click', fn)` throws before
 *              the handler is ever registered) and the exact AJAX call the
 *              button was always supposed to make.
 *
 * Scope: wp-admin only, and only on this one settings screen. Does not touch
 * anything a site visitor ever sees.
 *
 * Install: Code Snippets (WPCode) -> Add New -> paste this whole file ->
 * Insert Method: Auto Insert, location "Run Everywhere" (or "Admin Only" if
 * offered -- either works, this only enqueues on the one matching screen
 * regardless) -> Save Changes and Activate.
 *
 * Not fixed here: the other `.live()` calls in the same file (image reset,
 * slider add/edit/delete, backup/restore/import buttons). Those involve more
 * UI state than a straight click-to-AJAX-POST, and reproducing them blind
 * without being able to click through the result myself risks getting one
 * subtly wrong. This fixes the specific button the issue is named for.
 */

if ( ! function_exists( 'ksscca_fix_theme_options_save_button' ) ) {
	function ksscca_fix_theme_options_save_button( $hook ) {
		if ( ( $_GET['page'] ?? '' ) !== 'optionsframework' ) {
			return;
		}

		// Attached to 'jquery-core' rather than 'jquery' -- the 'jquery' handle
		// itself has no script of its own to attach after in some WP versions
		// (it's a dependency-only alias for jquery-core + jquery-migrate), which
		// can silently drop an inline addition. jquery-core always has a real
		// <script> tag.
		wp_add_inline_script(
			'jquery-core',
			<<<'JS'
jQuery(function ($) {
	// Delegated binding -- the modern replacement for the removed .live().
	// Payload and endpoint copied exactly from smof.js's own (never-reached)
	// handler, so this saves through the theme's own existing save action.
	$(document).on('click', '#of_save', function () {
		var nonce = $('#security').val();

		$('.ajax-loading-img').fadeIn();

		var serializedReturn = $('#of_form :input[name][name!="security"][name!="of_reset"]').serialize();

		var data = {
			type: 'save',
			action: 'of_ajax_post_action',
			security: nonce,
			data: serializedReturn
		};

		$.post(ajaxurl, data, function (response) {
			var success = $('#of-popup-save');
			var fail = $('#of-popup-fail');
			var loading = $('.ajax-loading-img');
			loading.fadeOut();

			if (response == 1) {
				success.fadeIn();
			} else {
				fail.fadeIn();
			}

			window.setTimeout(function () {
				success.fadeOut();
				fail.fadeOut();
			}, 2000);
		});

		return false;
	});
});
JS
		);
	}
	add_action( 'admin_enqueue_scripts', 'ksscca_fix_theme_options_save_button' );
}
