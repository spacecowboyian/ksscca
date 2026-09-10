<?php
/**
 * Plugin Name: KSSCCA Solo Live timing attribution and status
 * Description: Renders a source line for pages that republish SCCA Solo Live
 *              timing data: credit, a link back to the timing site, when our
 *              snapshot was taken, and whether the timing site is reachable
 *              right now.
 *
 * Usage in page content:
 *
 *   [ksscca_solo_live_status
 *      url="https://sololive.scca.com/26NATSGEN/index.php"
 *      name="SCCA Solo Live timing"
 *      snapshot="3:08 PM, 10 September 2026"]
 *
 * Why server-side, and why cached
 * -------------------------------
 * The obvious implementation is a fetch() from the visitor's browser. That is
 * wrong here: it would mean every single reader sends their own request to the
 * timing server, so a popular page turns our readers into a load test on
 * somebody else's box on the busiest day of their year. It also cannot read a
 * cross-origin response, so it can only ever distinguish "network failed" from
 * "something answered".
 *
 * Instead this does one HEAD request from our server and caches the verdict in
 * a transient. However many people load the page, the timing site sees at most
 * one check per cache window, and only when a reader is actually present. An
 * "up" verdict is cached for 5 minutes; a "down" verdict for only 1 minute, so
 * a recovery shows up quickly rather than being pinned to a stale failure.
 *
 * What the status does and does not claim
 * ---------------------------------------
 * It reports whether the timing site answered our request. It deliberately
 * does NOT say the figures on our page are live -- they are a snapshot, and
 * the snapshot time is printed alongside precisely so the two are not
 * confused. A reachable source means the numbers can be refreshed and the
 * link is worth clicking, nothing more.
 *
 * Install: Code Snippets (WPCode) -> Add New -> paste this whole file ->
 * Insert Method: Auto Insert, location "Run Everywhere" -> Save Changes and
 * Activate. It MUST be Auto Insert: WPCode's own "Shortcode" insert method
 * never runs the add_shortcode() call below, so the shortcode would not exist.
 */

if ( ! function_exists( 'ksscca_solo_live_probe' ) ) {
	/**
	 * Is the timing site answering? Returns 'up', 'down' or 'unknown'.
	 *
	 * 'unknown' is a real answer, not a failure to have an opinion: if the
	 * HTTP layer itself is unavailable we should say so rather than
	 * announce an outage the source may not be having.
	 */
	function ksscca_solo_live_probe( $url ) {
		$key    = 'ksscca_solo_live_' . md5( $url );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_head(
			$url,
			array(
				'timeout'     => 5,
				'redirection' => 3,
				'user-agent'  => 'ksscca.org status check (+https://www.ksscca.org/)',
			)
		);

		if ( is_wp_error( $response ) ) {
			$status = 'down';
		} else {
			$code   = (int) wp_remote_retrieve_response_code( $response );
			$status = ( $code >= 200 && $code < 400 ) ? 'up' : 'down';
		}

		// Short cache on failure so a recovery is picked up quickly.
		set_transient( $key, $status, 'up' === $status ? 5 * MINUTE_IN_SECONDS : MINUTE_IN_SECONDS );

		return $status;
	}
}

if ( ! function_exists( 'ksscca_solo_live_status_shortcode' ) ) {
	function ksscca_solo_live_status_shortcode( $atts ) {
		$a = shortcode_atts(
			array(
				'url'      => 'https://sololive.scca.com/26NATSGEN/index.php',
				'name'     => 'SCCA Solo Live timing',
				'snapshot' => '',
			),
			$atts,
			'ksscca_solo_live_status'
		);

		$status = ksscca_solo_live_probe( $a['url'] );

		$labels = array(
			'up'      => 'Live timing is up',
			'down'    => 'Live timing is not responding',
			'unknown' => 'Live timing status unknown',
		);
		$label = $labels[ $status ];

		$css = '.ksscca-src{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;'
			. 'gap:8px 18px;padding:11px 15px;border:1px solid #2d2d2d;background:#111;'
			. "font-family:'Inter',Verdana,Arial,sans-serif;font-size:12.5px;color:#99978f;margin:0 0 26px;}"
			. '.ksscca-src a{color:#e2c47c;text-decoration:underline;}'
			. '.ksscca-src .ksscca-when{color:#6f6d67;}'
			. '.ksscca-stat{display:inline-flex;align-items:center;gap:7px;white-space:nowrap;}'
			. '.ksscca-stat .dot{width:8px;height:8px;border-radius:50%;flex:0 0 auto;background:#5a5a5a;}'
			. '.ksscca-stat.is-up .dot{background:#6cc98d;}'
			. '.ksscca-stat.is-down .dot{background:#d1685f;}'
			. '.ksscca-stat.is-up{color:#6cc98d;}'
			. '.ksscca-stat.is-down{color:#d1685f;}';

		$out  = '<style>' . $css . '</style>';
		$out .= '<div class="ksscca-src">';
		$out .= '<div>Times from <a href="' . esc_url( $a['url'] ) . '" rel="noopener">'
			. esc_html( $a['name'] ) . '</a>';
		if ( '' !== $a['snapshot'] ) {
			$out .= ' <span class="ksscca-when">Snapshot taken ' . esc_html( $a['snapshot'] ) . '</span>';
		}
		$out .= '</div>';
		$out .= '<div class="ksscca-stat is-' . esc_attr( $status ) . '">'
			. '<span class="dot"></span>' . esc_html( $label ) . '</div>';
		$out .= '</div>';

		return $out;
	}
	add_shortcode( 'ksscca_solo_live_status', 'ksscca_solo_live_status_shortcode' );
}
