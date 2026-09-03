<?php
/**
 * Plugin Name: KSSCCA SportsEvent structured data
 * Description: Emits a SportsEvent JSON-LD node for each upcoming event on
 *              the pages that render the calendar, joining Yoast's existing
 *              schema graph. See GitHub issue #31 on spacecowboyian/ksscca.
 *
 * Purely structured data -- adds nothing to the rendered page a visitor
 * would see. Reuses the event list from the ksscca_calendar snippet (#4)
 * rather than re-fetching MotorsportReg, so a page's schema always matches
 * what that page actually displays -- the autocross page's Solo event gets
 * marked up as Autocross, not RallyCross.
 *
 * Install: Code Snippets (WPCode) -> Add New -> paste this whole file ->
 * Insert Method: Auto Insert, "Run Everywhere" -> Save Changes and Activate.
 * Requires the ksscca_calendar snippet to be active (defines
 * ksscca_calendar_fetch_events(), which this calls directly -- both are
 * "Run Everywhere" snippets, so by the time wpseo_schema_graph actually
 * fires during page render, both have already loaded regardless of save
 * order). If that snippet isn't active, this silently adds nothing rather
 * than erroring.
 */

if ( ! function_exists( 'ksscca_sportsevent_page_config' ) ) {
	/**
	 * Which pages get event schema, and how their events map to schema.org
	 * fields. Mirrors the type filter each page's [ksscca_calendar] shortcode
	 * actually uses, so the schema never disagrees with what's on the page.
	 */
	function ksscca_sportsevent_page_config( $post_id ) {
		$pages = array(
			1342 => array( 'type_filter' => '' ),          // Home -- unfiltered
			899  => array( 'type_filter' => 'autocross' ),  // Autocross Schedule
			31   => array( 'type_filter' => 'rallycross' ), // RallyCross Schedule
		);
		return $pages[ $post_id ] ?? null;
	}
}

if ( ! function_exists( 'ksscca_sportsevent_sport_name' ) ) {
	/**
	 * MotorsportReg's raw type field ("Autocross/Solo", "RallyCross") isn't
	 * itself a schema.org sport name. Map the known values; fall back to the
	 * raw string for anything unrecognized rather than dropping the event.
	 */
	function ksscca_sportsevent_sport_name( $raw_type ) {
		$map = array(
			'autocross' => 'Autocross',
			'solo'      => 'Autocross',
			'rallycross' => 'RallyCross',
			'time trial' => 'Time Trial',
			'hpde'      => 'Time Trial',
		);
		foreach ( $map as $needle => $sport ) {
			if ( false !== stripos( $raw_type, $needle ) ) {
				return $sport;
			}
		}
		return $raw_type;
	}
}

if ( ! function_exists( 'ksscca_sportsevent_add_to_graph' ) ) {
	function ksscca_sportsevent_add_to_graph( $data, $context ) {
		if ( ! function_exists( 'ksscca_calendar_fetch_events' ) ) {
			return $data; // ksscca_calendar snippet not active -- add nothing.
		}

		$config = ksscca_sportsevent_page_config( $context->id );
		if ( null === $config ) {
			return $data;
		}

		$events = ksscca_calendar_fetch_events();

		if ( '' !== $config['type_filter'] ) {
			$events = array_filter(
				$events,
				function ( $event ) use ( $config ) {
					return false !== stripos( $event['type'], $config['type_filter'] );
				}
			);
		}

		foreach ( $events as $event ) {
			$data[] = array(
				'@type'               => 'SportsEvent',
				'@id'                 => $context->canonical . '#event-' . md5( $event['name'] . $event['start'] ),
				'name'                => $event['name'],
				'startDate'           => $event['start'],
				'sport'               => ksscca_sportsevent_sport_name( $event['type'] ),
				'eventStatus'         => 'https://schema.org/EventScheduled',
				'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
				'location'            => array(
					'@type'   => 'Place',
					'name'    => $event['venue'],
					'address' => array(
						'@type'           => 'PostalAddress',
						'addressLocality' => $event['city'],
						'addressRegion'   => $event['region'],
						'addressCountry'  => 'US',
					),
				),
				'organizer'           => array( '@id' => 'https://www.ksscca.org/#organization' ),
				'offers'              => array(
					'@type'        => 'Offer',
					'url'          => $event['detailuri'],
					'availability' => 'https://schema.org/InStock',
				),
			);
		}

		return $data;
	}
	add_filter( 'wpseo_schema_graph', 'ksscca_sportsevent_add_to_graph', 10, 2 );
}
