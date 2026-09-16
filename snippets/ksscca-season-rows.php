<?php
/**
 * KSSCCA season rows: the upcoming half of a "Schedule and Results" page.
 *
 * The results half of the page is static markup generated from the results
 * JSON (bin/build-results-page --schedule). Events that have not happened yet
 * only exist in the MotorsportReg feed, which changes on its own, so they are
 * rendered here at request time, in the same row markup the results rows use.
 *
 * Usage (page content, on its own line inside the current-season list):
 *   [ksscca_season_rows type="autocross" after="2026-06-27" timing="https://..."]
 *
 *   type   case-insensitive substring of the MotorsportReg event type
 *   after  the date of the last row the page already carries; only events
 *          after it are drawn, so an event never appears twice once its
 *          results row is added and the page rebuilt
 *   timing the discipline's live timing page
 *
 * An upcoming event offers Register and Live Timing while registration is
 * open (or not yet open). Once its registration window closes, the event is
 * about to run or running, so the row offers Live Timing alone. The day after,
 * it becomes "Results pending". The window comes from the feed, in UTC.
 *
 * The feed lists upcoming events only: an event drops out of it the day it
 * runs. Every event this shortcode has seen is remembered in an option, so an
 * event that has run but has no results row yet still shows, as "Results
 * pending", instead of vanishing until someone posts results.
 *
 * Depends on ksscca_calendar_fetch_events() from the ksscca-calendar snippet
 * (cached 24h). Insert Method: Auto Insert, Run Everywhere.
 */

if ( ! function_exists( 'ksscca_season_rows_events' ) ) {
	function ksscca_season_rows_events() {
		$live = function_exists( 'ksscca_calendar_fetch_events' ) ? ksscca_calendar_fetch_events() : array();
		$seen = get_option( 'ksscca_season_rows_seen', array() );
		if ( ! is_array( $seen ) ) {
			$seen = array();
		}
		$changed = false;
		foreach ( $live as $event ) {
			$key = $event['detailuri'] ? $event['detailuri'] : $event['start'] . '|' . $event['name'];
			if ( ! isset( $seen[ $key ] ) || $seen[ $key ] !== $event ) {
				$seen[ $key ] = $event;
				$changed      = true;
			}
		}
		// An event that was seen once but is no longer in the feed either ran
		// or was cancelled. Only keep it if its date has passed: a future event
		// that left the feed was cancelled or unpublished.
		$today  = current_time( 'Y-m-d' );
		$in_now = array();
		foreach ( $live as $event ) {
			$in_now[ $event['detailuri'] ? $event['detailuri'] : $event['start'] . '|' . $event['name'] ] = true;
		}
		foreach ( $seen as $key => $event ) {
			$gone_future = ! isset( $in_now[ $key ] ) && substr( $event['start'], 0, 10 ) >= $today && ! empty( $live );
			$too_old     = substr( $event['start'], 0, 4 ) < (string) ( (int) substr( $today, 0, 4 ) - 1 );
			if ( $gone_future || $too_old ) {
				unset( $seen[ $key ] );
				$changed = true;
			}
		}
		if ( $changed ) {
			update_option( 'ksscca_season_rows_seen', $seen, false );
		}
		$events = array_values( $seen );
		usort(
			$events,
			function ( $a, $b ) {
				return strcmp( $a['start'], $b['start'] );
			}
		);
		return $events;
	}
}

if ( ! function_exists( 'ksscca_season_rows_shortcode' ) ) {
	function ksscca_season_rows_shortcode( $atts ) {
		$atts  = shortcode_atts( array( 'type' => '', 'after' => '', 'timing' => '' ), $atts, 'ksscca_season_rows' );
		$today = current_time( 'Y-m-d' );
		$now   = gmdate( 'Y-m-d H:i' );
		// Preview another moment: ?ksr_now=2026-09-18+12:00 (UTC), admins only.
		if ( isset( $_GET['ksr_now'] ) && current_user_can( 'manage_options' ) ) {
			$now   = substr( sanitize_text_field( wp_unslash( $_GET['ksr_now'] ) ), 0, 16 );
			$today = substr( $now, 0, 10 );
		}
		$live  = '';
		if ( '' !== $atts['timing'] ) {
			$external = 0 === strpos( $atts['timing'], 'http' );
			$live     = '<a class="ksr-link" href="' . esc_url( $atts['timing'] ) . '"'
				. ( $external ? ' target="_blank" rel="noopener"' : '' ) . '>Live Timing</a>';
		}
		$sep = '<span class="ksr-sep"> &middot; </span>';
		$year  = substr( $today, 0, 4 );
		$rows  = array();
		$next  = false;

		foreach ( ksscca_season_rows_events() as $event ) {
			$day = substr( $event['start'], 0, 10 );
			if ( '' !== $atts['type'] && false === stripos( $event['type'], $atts['type'] ) ) {
				continue;
			}
			if ( substr( $day, 0, 4 ) !== $year || ( '' !== $atts['after'] && $day <= $atts['after'] ) ) {
				continue;
			}
			$ts = strtotime( $day . ' 12:00:00' );
			if ( $day < $today ) {
				$action = '<span class="ksr-pending">Results pending</span>';
				$class  = 'ksr-row ksr-cal-row';
			} else {
				$register = '<a class="ksr-link" href="' . esc_url( $event['detailuri'] ) . '" target="_blank" rel="noopener">Register</a>';
				$closed   = ! empty( $event['reg_end'] ) && $now >= $event['reg_end'];
				if ( $closed && '' !== $live ) {
					$action = $live;
				} elseif ( '' !== $live ) {
					$action = $register . $sep . $live;
				} else {
					$action = $register;
				}
				$class  = 'ksr-row ksr-cal-row is-upcoming' . ( $next ? '' : ' is-next' );
				$next   = true;
			}
			$rows[] = '<div class="' . $class . '"><div class="ksr-cal" aria-hidden="true"><span class="ksr-cal-m">'
				. esc_html( strtoupper( date( 'M', $ts ) ) ) . '</span><span class="ksr-cal-d">' . esc_html( date( 'j', $ts ) )
				. '</span></div><div class="ksr-cal-main"><div class="ksr-event-name">' . esc_html( $event['name'] )
				. '</div><div class="ksr-meta"><div class="ksr-event-sub"><span class="ksr-dl">' . esc_html( date( 'l', $ts ) )
				. '</span><span class="ksr-ds">' . esc_html( date( 'D', $ts ) ) . '</span>, ' . esc_html( date( 'F j', $ts ) )
				. '</div><div class="ksr-links">' . $action . '</div></div></div></div>';
		}

		if ( ! $next ) {
			$rows[] = '<div class="ksr-row ksr-cal-row ksr-cal-none"><div class="ksr-cal" aria-hidden="true"><span class="ksr-cal-m">&nbsp;</span><span class="ksr-cal-d">&ndash;</span></div>'
				. '<div class="ksr-cal-main"><div class="ksr-event-name">No more events posted yet</div><div class="ksr-meta"><div class="ksr-event-sub">New dates appear here as soon as they are scheduled.</div>'
				. '<div class="ksr-links"></div></div></div></div>';
		}
		return implode( '', $rows );
	}
	add_shortcode( 'ksscca_season_rows', 'ksscca_season_rows_shortcode' );
}
