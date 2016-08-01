<?php

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

/**
 * Hook into `after_wp_load` to change current site domain.
 */
WP_CLI::add_hook( 'after_wp_load', function () {
	global $current_site;

	// Only modify `site create` command.
	if ( implode( ' ', WP_CLI::get_runner()->arguments ) !== 'site create' ) {
		return;
	}

	// Subdomains are required.
	if ( ! is_subdomain_install() ) {
		WP_CLI::error( 'Hercules requires subdomains mode' );
	}

	// Support `domain` arg even if `wp site create` don't.
	if ( isset( WP_CLI::get_runner()->assoc_args['domain'] ) ) {
		$domain = WP_CLI::get_runner()->assoc_args['domain'];
		unset( WP_CLI::get_runner()->assoc_args['domain'] );
	} else {
		$domain = WP_CLI::get_runner()->assoc_args['slug'];
	}

	// Not a valid host.
	if ( parse_url( 'http://' . $domain, PHP_URL_HOST ) !== $domain ) {
		WP_CLI::error( 'Hercules requires a valid top domain, e.g example.com' );
	}

	// Remove `www.` from the domain if any.
	$domain = preg_replace( '|^www\.|', '', $domain );

	// Split the domain.
	$domain = explode( '.', $domain );

	// Can only work with two parts.
	if ( count( $domain ) !== 2 ) {
		WP_CLI::error( 'Hercules requires a valid top domain, e.g example.com' );
	}

	// Set new slug in WP CLI.
	WP_CLI::get_runner()->assoc_args['slug'] = $domain[0];

	// Set current site domain.
	$current_site->domain = $domain[1];
} );
