<?php
/**
 * Plugin Name: KSSCCA Organization structured data
 * Description: Upgrades the Organization node Yoast emits into a
 *              SportsOrganization with sport, service area and parent
 *              organization, and points the logo at the full-size file
 *              instead of the 45x19 crop. See GitHub issue #32 on
 *              spacecowboyian/ksscca.
 *
 * Structured data only -- changes nothing a visitor sees. Yoast's own UI
 * only offers Organization or Person, so the retype has to happen in the
 * wpseo_schema_organization filter.
 *
 * The logo Yoast picks (set in Site representation) is the cropped
 * 45x19 thumbnail logo11-e1476725101848.png. Google discards any logo
 * under 112x112, so the node's logo is repointed at the uncropped
 * original, logo11.png at 317x136, which clears that bar. A purpose-made
 * square logo is still worth uploading; when one exists, set it in Yoast
 * and delete the logo block below rather than editing it here.
 *
 * Install: Code Snippets (WPCode) -> Add New -> paste this whole file ->
 * Insert Method: Auto Insert, location "Run Everywhere" -> Save Changes
 * and Activate.
 */

if ( ! function_exists( 'ksscca_organization_schema' ) ) {
	function ksscca_organization_schema( $data ) {
		// SportsOrganization is a subtype of Organization, so every consumer
		// that understood the old node still understands this one.
		$data['@type'] = 'SportsOrganization';

		$data['alternateName'] = 'Kansas Region Sports Car Club of America';

		// The disciplines the region actually runs. Matches the site's own
		// top-level navigation.
		$data['sport'] = array( 'Autocross', 'RallyCross', 'Road Racing', 'Time Trial' );

		$data['areaServed'] = array(
			'@type' => 'State',
			'name'  => 'Kansas',
		);

		$data['parentOrganization'] = array(
			'@type' => 'SportsOrganization',
			'name'  => 'Sports Car Club of America',
			'url'   => 'https://www.scca.com/',
		);

		// Repoint the logo at the uncropped original. Keeps Yoast's own @id
		// so the WebSite node's image reference still resolves.
		if ( isset( $data['logo'] ) && is_array( $data['logo'] ) ) {
			$full = 'https://www.ksscca.org/wp-content/uploads/2016/10/logo11.png';
			$data['logo']['url']        = $full;
			$data['logo']['contentUrl'] = $full;
			$data['logo']['width']      = 317;
			$data['logo']['height']     = 136;
		}

		return $data;
	}
	add_filter( 'wpseo_schema_organization', 'ksscca_organization_schema', 10, 1 );
}
