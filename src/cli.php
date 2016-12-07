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
	if ( ! function_exists( 'is_subdomain_install' ) || ! is_subdomain_install() ) {
		WP_CLI::error( 'Hercules requires subdomains mode' );
	}

	// Get domain.
	$domain = isset( WP_CLI::get_runner()->assoc_args['slug'] ) ? WP_CLI::get_runner()->assoc_args['slug'] : '';

	// Not a valid host.
	if ( parse_url( 'http://' . $domain, PHP_URL_HOST ) !== $domain ) {
		WP_CLI::error( 'Hercules requires a valid top domain, e.g example.com' );
	}

	// Remove `www.` from the domain if any.
	$domain = preg_replace( '|^www\.|', '', $domain );

	// Split the domain.
	$domain = explode( '.', $domain );

	// Set current site domain.
	$current_site->domain = 'dev';

	// Set new slug.
	WP_CLI::get_runner()->run_command( ['site', 'create'], ['slug' => $domain[0]] );

	exit;
} );
