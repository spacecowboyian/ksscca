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

if ( ! function_exists( 'ksscca_calendar_assets' ) ) {
	/**
	 * The calendar's own styling and its table-to-cards enhancer.
	 *
	 * These used to be pasted into every page that ran the shortcode: the
	 * homepage and both schedule pages carried byte-for-byte copies, and by the
	 * time this moved here they had already drifted apart (the homepage Register
	 * button had been restyled, the schedule pages had not). See issue #42.
	 *
	 * Printed at most once per request, so a page with two calendars on it does
	 * not emit either twice, and pages without a calendar carry neither.
	 */
	function ksscca_calendar_assets() {
		static $done = false;
		if ( $done ) {
			return '';
		}
		$done = true;

		$css = <<<'KSSCCA_CSS'
.msrcalendar{text-align:left;}.msrcalendar h2{display:none;}.msrcalendar .morelink{margin-top:14px;font-family:'Inter',Verdana,Arial,sans-serif;font-size:12.5px;color:#99978f;text-align:left;}.msrcalendar .morelink a{color:#5cb3dd;text-decoration:none;}.msrcalendar .morelink a:hover{text-decoration:underline;}.ksscca-card{background:transparent;}.ksscca-row{display:grid;grid-template-columns:68px 1fr 90px;align-items:center;padding:14px 18px;border-bottom:1px solid #2d2d2d;}.ksscca-card>.ksscca-row:last-child{border-bottom:none;}.ksscca-row:hover:not(.ksscca-head-row){background:#161616;}.ksscca-head-row{background:transparent;}.ksscca-h{font-family:'Inter',Verdana,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#99978f;}.ksscca-date{display:flex;flex-direction:column;align-items:center;justify-content:center;width:52px;height:52px;border-radius:10px;background:#17313c;color:#8ccdef;}.ksscca-date .mon{font-family:'Inter',Verdana,Arial,sans-serif;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;line-height:1;}.ksscca-date .day{font-family:'PT Sans Narrow',Verdana,Arial,sans-serif;font-size:22px;font-weight:700;line-height:1.1;font-variant-numeric:tabular-nums;}.ksscca-cell-name{display:flex;flex-direction:column;gap:2px;min-width:0;padding-right:12px;font-family:'Inter',Verdana,Arial,sans-serif;}.ksscca-cell-name .name{font-weight:600;font-size:14.5px;color:#e7e5df;line-height:1.35;}.ksscca-cell-name .venue{font-size:12.5px;color:#99978f;}.ksscca-cell-action{display:flex;justify-content:flex-end;}.ksscca-register{display:inline-block;font-family:'Inter',Verdana,Arial,sans-serif;padding:8px 16px;border-radius:8px;background:transparent;border:1px solid #6b4d1d;color:#e2c47c !important;font-size:13px;line-height:1;font-weight:700;text-decoration:none;white-space:nowrap;}.ksscca-register:hover{background:rgba(226,196,124,.1);border-color:#a86e1d;color:#f2d999 !important;}.ksscca-cards{display:none;}@media (max-width:700px){.ksscca-card{display:none;}.ksscca-cards{display:flex;flex-direction:column;gap:12px;font-family:'Inter',Verdana,Arial,sans-serif;}.ksscca-event-card{background:#161616;border:1px solid #2d2d2d;padding:16px;display:flex;gap:14px;}.ksscca-event-card .ksscca-date{width:48px;height:48px;flex:none;}.ksscca-event-card .body{flex:1;min-width:0;}.ksscca-event-card .name{font-weight:600;font-size:15px;color:#e7e5df;}.ksscca-event-card .venue{font-size:13px;color:#99978f;margin-top:3px;}}
KSSCCA_CSS;

		$js = <<<'KSSCCA_JS'
(function () { function el(tag, className) { var e = document.createElement(tag); if (className) e.className = className; return e; } function dateBlock(ev) { var d = el('div', 'ksscca-date'); var mon = el('span', 'mon'); mon.textContent = ev.month; var day = el('span', 'day'); day.textContent = ev.day; d.appendChild(mon); d.appendChild(day); return d; } function registerLink(ev, block) { var a = document.createElement('a'); a.className = 'ksscca-register'; a.href = ev.href; a.target = '_blank'; a.rel = 'noopener'; a.textContent = 'Register'; if (block) { a.style.display = 'block'; a.style.textAlign = 'center'; a.style.marginTop = '12px'; } return a; } function enhance() { var cal = document.querySelector('.msrcalendar'); if (!cal) return; var table = cal.querySelector('table'); if (!table || table.dataset.ksscca) return; var rows = table.querySelectorAll('tbody tr'); var events = []; rows.forEach(function (tr) { var tds = tr.querySelectorAll('td'); if (!tds[5]) return; var dp = tds[0].textContent.trim().split(/\s+/); var link = tds[5].querySelector('a'); var venue = tds[2].textContent.trim(); var location = tds[3].textContent.trim(); events.push({ month: dp[0] || '', day: dp[1] || '', type: tds[4].textContent.trim(), venueLocation: venue + (location ? ' - ' + location : ''), href: link ? link.href : '#' }); }); if (!events.length) return; var wrap = el('div', 'ksscca-card'); var headRow = el('div', 'ksscca-row ksscca-head-row'); ['Date', 'Event', ''].forEach(function (h) { var s = el('span', 'ksscca-h'); s.textContent = h; headRow.appendChild(s); }); wrap.appendChild(headRow); events.forEach(function (ev) { var row = el('div', 'ksscca-row'); var dateCell = el('div', 'ksscca-cell-date'); dateCell.appendChild(dateBlock(ev)); row.appendChild(dateCell); var nameCell = el('div', 'ksscca-cell-name'); var nm = el('span', 'name'); nm.textContent = ev.type; var vn = el('span', 'venue'); vn.textContent = ev.venueLocation; nameCell.appendChild(nm); nameCell.appendChild(vn); row.appendChild(nameCell); var actionCell = el('div', 'ksscca-cell-action'); actionCell.appendChild(registerLink(ev, false)); row.appendChild(actionCell); wrap.appendChild(row); }); var cardsWrap = el('div', 'ksscca-cards'); events.forEach(function (ev) { var card = el('div', 'ksscca-event-card'); card.appendChild(dateBlock(ev)); var body = el('div', 'body'); var nm = el('div', 'name'); nm.textContent = ev.type; var vn = el('div', 'venue'); vn.textContent = ev.venueLocation; body.appendChild(nm); body.appendChild(vn); body.appendChild(registerLink(ev, true)); card.appendChild(body); cardsWrap.appendChild(card); }); table.dataset.ksscca = '1'; var parent = table.parentNode; parent.replaceChild(wrap, table); parent.insertBefore(cardsWrap, wrap.nextSibling); } if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', enhance); } else { enhance(); } })();
KSSCCA_JS;

		return '<style>' . $css . '</style>' . '<script>' . $js . '</script>';
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
		echo ksscca_calendar_assets();
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
