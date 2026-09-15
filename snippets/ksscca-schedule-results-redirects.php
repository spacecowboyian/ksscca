<?php
/**
 * Plugin Name: KSSCCA schedule and results redirects
 * Description: 301s the old separate Schedule and Results pages to the
 *              combined Schedule and Results page for each discipline.
 *
 * On 2026-09-15 each discipline's schedule and results were combined into one
 * page (autocross 2139, RallyCross 2145). The old pages stay in WordPress,
 * because Autocross Results (898) and RallyCross Results (996) are still the
 * pages bin/build-results-page publishes, but visitors and old links land on
 * the combined page. A fragment such as /autocross-results/#2024 survives the
 * redirect: browsers carry it across a Location without one.
 *
 * Matches on the request path, like ksscca-former-home, so it keeps working
 * whatever happens to the old pages themselves.
 *
 * Install: Code Snippets (WPCode) -> Add New -> PHP -> Insert Method: Auto
 * Insert, location "Run Everywhere" -> Save and Activate.
 */

if ( ! function_exists( 'ksscca_schedule_results_redirect' ) ) {
	function ksscca_schedule_results_redirect() {
		if ( is_admin() ) {
			return;
		}

		$map = array(
			'autocross-schedule'   => '/autocross-schedule-and-results/',
			'autocross-results'    => '/autocross-schedule-and-results/',
			'rallycross-schedule'  => '/rallycross-schedule-and-results/',
			'rallycross-results-2' => '/rallycross-schedule-and-results/',
		);

		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		if ( isset( $map[ $path ] ) ) {
			wp_safe_redirect( home_url( $map[ $path ] ), 301 );
			exit;
		}
	}
	add_action( 'template_redirect', 'ksscca_schedule_results_redirect', 1 );
}
