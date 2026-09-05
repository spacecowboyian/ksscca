<?php
/**
 * Plugin Name: KSSCCA sitemap https
 * Description: Forces every ksscca.org URL Yoast prints into its XML sitemaps
 *              to https. See GitHub issue #24 on spacecowboyian/ksscca.
 *
 * Output only, for crawlers. Nothing a visitor sees changes, and no stored
 * value is rewritten.
 *
 * Why this is needed at all: WP_SITEURL and WP_HOME are wp-config.php
 * constants that cannot be changed without file access, so the stored site
 * URL is still http. At runtime is_ssl() is true, so home_url() and
 * site_url() scheme-correct themselves and page <loc> entries come out
 * https already. The two places that do not are values Yoast assembles
 * from stored data rather than from home_url():
 *
 *   - the child sitemap links in sitemap_index.xml (3 of them)
 *   - <image:loc> entries, built from attachment URLs stored as http (8)
 *
 * Both are covered below. If the siteurl constant is ever fixed properly
 * this snippet becomes a no-op and can be deleted.
 *
 * Install: Code Snippets (WPCode) -> Add New -> paste this whole file ->
 * Insert Method: Auto Insert, location "Run Everywhere" -> Save Changes
 * and Activate.
 */

if ( ! function_exists( 'ksscca_https_url' ) ) {
	/**
	 * Only ever upgrades our own host. Leaves other domains alone, since a
	 * third-party URL we do not control may genuinely not serve https.
	 */
	function ksscca_https_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}
		return preg_replace( '#^http://(www\.)?ksscca\.org#i', 'https://www.ksscca.org', $url );
	}
}

if ( ! function_exists( 'ksscca_sitemap_index_https' ) ) {
	function ksscca_sitemap_index_https( $links ) {
		if ( ! is_array( $links ) ) {
			return $links;
		}
		foreach ( $links as $i => $link ) {
			if ( isset( $link['loc'] ) ) {
				$links[ $i ]['loc'] = ksscca_https_url( $link['loc'] );
			}
		}
		return $links;
	}
	add_filter( 'wpseo_sitemap_index_links', 'ksscca_sitemap_index_https', 10, 1 );
}

if ( ! function_exists( 'ksscca_sitemap_images_https' ) ) {
	function ksscca_sitemap_images_https( $images ) {
		if ( ! is_array( $images ) ) {
			return $images;
		}
		foreach ( $images as $i => $image ) {
			if ( isset( $image['src'] ) ) {
				$images[ $i ]['src'] = ksscca_https_url( $image['src'] );
			}
		}
		return $images;
	}
	add_filter( 'wpseo_sitemap_urlimages', 'ksscca_sitemap_images_https', 10, 1 );
}

if ( ! function_exists( 'ksscca_sitemap_entry_https' ) ) {
	/**
	 * Belt and braces for the per-URL entries. These already come out https
	 * today because they are built from home_url(), but this costs nothing
	 * and covers the case where one is assembled from a stored value.
	 */
	function ksscca_sitemap_entry_https( $url ) {
		if ( is_array( $url ) && isset( $url['loc'] ) ) {
			$url['loc'] = ksscca_https_url( $url['loc'] );
		}
		return $url;
	}
	add_filter( 'wpseo_sitemap_entry', 'ksscca_sitemap_entry_https', 10, 1 );
}
