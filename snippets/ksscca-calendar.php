<?php
/**
 * Plugin Name: KSSCCA Calendar (msr-calendar replacement)
 * Description: Fetches the Kansas Region SCCA calendar directly from MotorsportReg
 *              and renders it, fixing the msr-calendar plugin bug that silently
 *              drops any event whose type contains a "/" (e.g. "Autocross/Solo",
 *              "Time Trial/HPDE") — see GitHub issue #4 on spacecowboyian/ksscca.
 *              Also adds a `type` filter the old plugin never supported, so the
 *              Autocross and RallyCross schedule pages can finally show different
 *              events instead of the same unfiltered feed.
 *
 * Install: Code Snippets -> Add New -> paste this whole file -> "Run snippet
 * everywhere" -> Save Changes and Activate. No SFTP or file access needed.
 *
 * Usage (page content):
 *   [ksscca_calendar]                 all upcoming events, unfiltered
 *   [ksscca_calendar type="autocross"]  only events whose type contains "autocross"
 *   [ksscca_calendar type="rallycross"] only events whose type contains "rallycross"
 *
 * The match is a case-insensitive substring against the raw MotorsportReg type
 * field, so type="autocross" matches "Autocross/Solo" and type="time trial"
 * would match "Time Trial/HPDE" if that ever shows up in this feed.
 */

if ( ! function_exists( 'ksscca_calendar_feed_url' ) ) {
	function ksscca_calendar_feed_url() {
		// Same organization ID the old msr-calendar plugin is configured with
		// (Calendar Settings -> Calendar URL). Kansas Region SCCA on MotorsportReg.
		return 'https://api.motorsportreg.com/rest/calendars/organization/85F4C839-1D72-822B-798D90283FD4F720';
	}
}

if ( ! function_exists( 'ksscca_calendar_fetch_events' ) ) {
	/**
	 * Fetches and parses the feed, cached in a transient for 24h to match the
	 * old plugin's "Update cache after" setting and avoid hitting the API on
	 * every page load.
	 *
	 * @return array<int, array<string, string>> Parsed event rows, or [] on
	 *                                            fetch/parse failure.
	 */
	function ksscca_calendar_fetch_events() {
		$cache_key = 'ksscca_calendar_events_v1';
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get(
			ksscca_calendar_feed_url(),
			array( 'timeout' => 10 )
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Fetch failed - serve the last good cache for up to a week rather
			// than showing an empty calendar, if one exists.
			$stale = get_option( 'ksscca_calendar_events_stale' );
			return is_array( $stale ) ? $stale : array();
		}

		$body = wp_remote_retrieve_body( $response );
		$xml  = @simplexml_load_string( $body );

		if ( false === $xml || ! isset( $xml->events->event ) ) {
			$stale = get_option( 'ksscca_calendar_events_stale' );
			return is_array( $stale ) ? $stale : array();
		}

		$events = array();
		foreach ( $xml->events->event as $event ) {
			// Skip cancelled or non-public events, same as the source feed intends.
			$cancelled = strtolower( (string) $event->cancelled ) === 'true';
			$public    = strtolower( (string) $event->public ) === 'true';
			if ( $cancelled || ! $public ) {
				continue;
			}

			$events[] = array(
				'name'      => (string) $event->name,
				'start'     => (string) $event->start,
				'type'      => (string) $event->type,
				'detailuri' => (string) $event->detailuri,
				'venue'     => (string) $event->venue->name,
				'city'      => (string) $event->venue->city,
				'region'    => (string) $event->venue->region,
			);
		}

		// Sort by start date ascending - the feed is usually already in this
		// order, but don't rely on it.
		usort(
			$events,
			function ( $a, $b ) {
				return strcmp( $a['start'], $b['start'] );
			}
		);

		set_transient( $cache_key, $events, DAY_IN_SECONDS );
		// A separate non-expiring copy purely as a fallback if the next fetch fails.
		update_option( 'ksscca_calendar_events_stale', $events, false );

		return $events;
	}
}

if ( ! function_exists( 'ksscca_calendar_shortcode' ) ) {
	function ksscca_calendar_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'type'  => '',
				'title' => 'Register for upcoming events',
			),
			$atts,
			'ksscca_calendar'
		);

		$events = ksscca_calendar_fetch_events();

		if ( '' !== $atts['type'] ) {
			$events = array_values(
				array_filter(
					$events,
					function ( $event ) use ( $atts ) {
						return false !== stripos( $event['type'], $atts['type'] );
					}
				)
			);
		}

		ob_start();
		?>
		<div class="msrcalendar">
			<h2><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php if ( empty( $events ) ) : ?>
				<p>No upcoming events are currently posted. Check <a href="https://www.motorsportreg.com/calendar/" target="_blank" rel="noopener">MotorsportReg.com</a> directly.</p>
			<?php else : ?>
				<table>
					<thead>
						<tr>
							<th>Date</th>
							<th>Name</th>
							<th>Venue</th>
							<th>Location</th>
							<th>Type</th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $events as $event ) : ?>
							<tr>
								<td><?php echo esc_html( date_i18n( 'M j', strtotime( $event['start'] ) ) ); ?></td>
								<td><?php echo esc_html( $event['name'] ); ?></td>
								<td><?php echo esc_html( $event['venue'] ); ?></td>
								<td><?php echo esc_html( $event['city'] . ', ' . $event['region'] ); ?></td>
								<td><?php echo esc_html( $event['type'] ); ?></td>
								<td>
									<a href="<?php echo esc_url( $event['detailuri'] ); ?>" class="imglink">
										<img loading="lazy" decoding="async" src="/wp-content/plugins/msr-calendar/calendar/images/register.gif" height="17" width="85" alt="Register now on MotorsportReg.com" />
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<div class="morelink">
				Use MotorsportReg.com for <a href="https://www.motorsportreg.com/index.cfm/event/event-management">online driving event registration</a>. Register for thousands of <a href="https://www.motorsportreg.com/calendar/">autocross, HPDE, race &amp; social events</a>.
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
	add_shortcode( 'ksscca_calendar', 'ksscca_calendar_shortcode' );
}
