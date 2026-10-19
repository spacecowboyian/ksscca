<?php
// Run with: php tests/ksscca-calendar.php
// Minimal WordPress doubles exercise the real fetcher and shortcode offline.
define( 'DAY_IN_SECONDS', 86400 );

$transients = array();
$options    = array();
$requests   = 0;
$now        = strtotime( '2026-10-19 04:59:59 UTC' ); // Still October 18 in Chicago.
$response   = array( 'code' => 200, 'body' => '' );

function get_transient( $key ) {
	return $GLOBALS['transients'][ $key ] ?? false;
}
function set_transient( $key, $value, $expiration ) {
	$GLOBALS['transients'][ $key ] = $value;
}
function get_option( $key ) {
	return $GLOBALS['options'][ $key ] ?? false;
}
function update_option( $key, $value, $autoload ) {
	$GLOBALS['options'][ $key ] = $value;
}
function wp_remote_get( $url, $args ) {
	++$GLOBALS['requests'];
	return $GLOBALS['response'];
}
function is_wp_error( $response ) {
	return false;
}
function wp_remote_retrieve_response_code( $response ) {
	return $response['code'];
}
function wp_remote_retrieve_body( $response ) {
	return $response['body'];
}
function shortcode_atts( $defaults, $atts, $tag ) {
	return array_merge( $defaults, $atts );
}
function add_shortcode( $tag, $callback ) {}
function wp_date( $format, $timestamp, $timezone ) {
	return ( new DateTimeImmutable( '@' . ( $timestamp ?? $GLOBALS['now'] ) ) )
		->setTimezone( $timezone )->format( $format );
}
function date_i18n( $format, $timestamp ) {
	return date( $format, $timestamp );
}
function esc_html( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $value ) {
	return esc_html( $value );
}
function esc_url( $value ) {
	return esc_html( $value );
}

require __DIR__ . '/../snippets/ksscca-calendar.php';

function check( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}
function calendar_names( $atts = array() ) {
	$html = ksscca_calendar_shortcode( $atts );
	preg_match_all( '~<td>(Event [^<]+)</td>~', $html, $matches );
	return $matches[1];
}

// Deliberately unsorted: fresh parsing must retain end dates and sort by start.
$fixtures = array(
	array( 'Future social', '2026-11-03', '2026-11-03', 'Social' ),
	array( 'Past', '2026-10-16', '2026-10-16', 'Autocross/Solo' ),
	array( 'Multi-day ended', '2026-10-16', '2026-10-17', 'Time Trial/HPDE' ),
	array( 'Multi-day final', '2026-10-17', '2026-10-18', 'Autocross/Solo' ),
	array( 'Today', '2026-10-18', '2026-10-18', 'RallyCross' ),
	array( 'Multi-day ongoing', '2026-10-18', '2026-10-20', 'Time Trial/HPDE' ),
	array( 'Future autocross', '2026-11-01', '2026-11-01', 'Autocross/Solo' ),
	array( 'Future rallycross', '2026-11-02', '2026-11-02', 'RallyCross' ),
);
$xml = '<calendar><events>';
foreach ( $fixtures as $index => $fixture ) {
	list( $name, $start, $end, $type ) = $fixture;
	$xml .= '<event><name>Event ' . $name . '</name><start>' . $start . '</start><end>' . $end
		. '</end><type>' . $type . '</type><detailuri>https://example.com/event/' . $index
		. '</detailuri><public>true</public><cancelled>false</cancelled>'
		. '<venue><name>Venue</name><city>Topeka</city><region>KS</region></venue></event>';
}
$xml .= '<event><name>Private</name><public>false</public></event>'
	. '<event><name>Cancelled</name><public>true</public><cancelled>true</cancelled></event>'
	. '</events></calendar>';
$response['body'] = $xml;
$transients['ksscca_calendar_events_v2'] = array(); // Must not reuse rows missing end.

$shared = ksscca_calendar_fetch_events();
check( count( $shared ) === 8, 'Shared feed must retain past events but skip private/cancelled events' );
check( $shared[0]['end'] === '2026-10-16', 'Fresh parsing must retain end' );
check( $requests === 1, 'New cache version must fetch end dates' );

$expected = array(
	'Event Multi-day final', 'Event Today', 'Event Multi-day ongoing',
	'Event Future autocross', 'Event Future rallycross', 'Event Future social',
);
check( calendar_names() === $expected, 'Keep final-day and future events of all types in feed order' );
check( $requests === 1, 'Rendering must use the cached feed' );
check(
	calendar_names( array( 'type' => 'AUTOCROSS' ) ) === array( 'Event Multi-day final', 'Event Future autocross' ),
	'Type filtering must remain case-insensitive'
);
check(
	str_contains( ksscca_calendar_shortcode( array( 'type' => 'unknown' ) ), 'No upcoming events are currently posted.' ),
	'An unmatched type must render the existing empty state even with a populated feed'
);
check(
	calendar_names( array( 'completed' => ' https://example.com/event/3, https://example.com/event/4 ' ) )
		=== array_slice( $expected, 2 ),
	'Completion overrides must hide exact events before local midnight'
);
check(
	calendar_names( array( 'completed' => 'https://example.com/event/30' ) ) === $expected,
	'Completion overrides must not hide partial URL matches'
);
check( ksscca_calendar_fetch_events() === $shared, 'Rendering must not mutate shared cached data' );

$now = strtotime( '2026-10-19 05:00:00 UTC' );
$next_day = array_slice( $expected, 2 );
check( calendar_names() === $next_day, 'Cached events must roll off exactly at Chicago midnight' );
check( $requests === 1, 'Midnight rollover must not depend on a new fetch' );

unset( $transients['ksscca_calendar_events_v3'] );
$response['code'] = 503;
check( calendar_names() === $next_day, 'Failed fetch fallback must also filter completed events' );
check( ksscca_calendar_fetch_events() === $shared, 'Fallback data must remain unfiltered for other consumers' );
$response = array( 'code' => 200, 'body' => 'invalid XML' );
check( calendar_names() === $next_day, 'Parse-failure fallback must also filter completed events' );

// Legacy fallback rows and absent/empty end dates use the start date.
$legacy = $shared[0];
unset( $legacy['end'] );
$legacy['start'] = '2026-10-19';
$legacy['name']  = 'Event Legacy';
$options['ksscca_calendar_events_stale'] = array( $legacy );
check( calendar_names() === array( 'Event Legacy' ), 'Missing end must use start' );
$options['ksscca_calendar_events_stale'][0]['end'] = '';
check( calendar_names() === array( 'Event Legacy' ), 'Empty end must use start' );
$now = strtotime( '2026-10-20 05:00:00 UTC' );
check( calendar_names() === array(), 'Legacy rows must also expire' );
check(
	str_contains( ksscca_calendar_shortcode( array() ), 'No upcoming events are currently posted.' ),
	'All-expired feeds must render the existing empty state'
);

$transients['ksscca_calendar_events_v3'] = array();
check( calendar_names() === array(), 'Empty cached feeds must remain empty' );
check(
	str_contains( ksscca_calendar_shortcode( array() ), 'No upcoming events are currently posted.' ),
	'Empty calendars must render the existing empty state'
);
$transients = array();
$options = array();
check( calendar_names() === array(), 'Unavailable feeds without fallback must remain empty' );

echo "Calendar verification passed.\n";
