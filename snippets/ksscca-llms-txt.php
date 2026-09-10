<?php
/**
 * Plugin Name: KSSCCA llms.txt
 * Description: Serves /llms.txt, a plain-text index of the site for
 *              assistants and crawlers. See GitHub issue #34 on
 *              spacecowboyian/ksscca.
 *
 * Adds a URL that did not exist before and changes nothing on any
 * existing page. WP Engine serves a real file at the document root ahead
 * of WordPress, so if someone later drops a physical llms.txt there it
 * wins and this snippet becomes dead weight -- delete it at that point.
 *
 * The link list is written out rather than generated from the page tree
 * on purpose: the point of the file is a short, curated set of facts, and
 * a generated list would pull in every stub and orphan page on the site.
 * When a page here moves, update it here. Every URL was checked for a 200
 * on 2026-09-04; note that RallyCross results live at
 * /rallycross-results-2/, not /rallycross-results/, which 404s.
 *
 * Install: Code Snippets (WPCode) -> Add New -> paste this whole file ->
 * Insert Method: Auto Insert, location "Run Everywhere" -> Save Changes
 * and Activate.
 */

if ( ! function_exists( 'ksscca_llms_txt' ) ) {
	function ksscca_llms_txt() {
		$path = strtok( $_SERVER['REQUEST_URI'] ?? '', '?' );
		if ( 'llms.txt' !== ltrim( rtrim( $path, '/' ), '/' ) ) {
			return;
		}

		$body = <<<'TXT'
# Kansas Region SCCA

> Region of the Sports Car Club of America serving Kansas. Runs Autocross
> (Solo), RallyCross, Road Racing, Time Trials and Track Events.
> Registration for events is handled at motorsportreg.com, not on this site.

## Schedules
- [Autocross schedule](https://www.ksscca.org/autocross-schedule/)
- [RallyCross schedule](https://www.ksscca.org/rallycross-schedule/)
- [Track event schedule](https://www.ksscca.org/track-events/track-event-schedule/)
- [Road racing schedule](https://www.ksscca.org/road-racing/clubracing-schedule/)
- [Live timing](https://www.ksscca.org/livetiming/)

## Results
- [Autocross results](https://www.ksscca.org/autocross-results/): class, PAX and raw times by season, 2014 to present
- [RallyCross results](https://www.ksscca.org/rallycross-results-2/): class and raw times by season, 2018 to present
- [Autocross season points](https://www.ksscca.org/autocross-season-points/)
- [RallyCross season points](https://www.ksscca.org/rallycross-season-points/)

## About
- [About the region](https://www.ksscca.org/about-region/)
- [Contacts](https://www.ksscca.org/contacts/)
- [Autocross supplemental rules](https://www.ksscca.org/autocross-supplemental-rules/)
- [RallyCross buddy system](https://www.ksscca.org/kansas-rallycross-buddy-system/)
TXT;

		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' );
		echo $body . "\n";
		exit;
	}
	add_action( 'template_redirect', 'ksscca_llms_txt', 0 );
}
