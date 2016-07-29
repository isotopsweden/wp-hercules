<?php

namespace Isotop\WordPress\Hercules;

use WP_Site;

final class Hercules {

	/**
	 * The class instance.
	 *
	 * @var \Isotop\WordPress\Hercules\Hercules
	 */
	protected static $instance;

	/**
	 * Current site.
	 *
	 * @var WP_Site
	 */
	protected $site;

	/**
	 * The construct.
	 */
	protected function __construct() {
		add_action( 'muplugins_loaded', [$this, 'muplugins_loaded'] );
	}

	/**
	 * Boot domain mapping.
	 */
	public function boot() {
		// Don't continue if installation is running.
		if ( defined( 'WP_INSTALLING' ) ) {
			return;
		}

		// Don't continue if not in sunrise.
		if ( did_action( 'muplugins_loaded' ) ) {
			wp_die( 'Hercules must be loaded in your <code>sunrise.php</code>.' );
		}

		// Don't continue if not in multisite mode.
		if ( ! is_multisite() ) {
			wp_die( 'Hercules requires WordPress to be in multisite mode.' );
		}

		// Don't continue if `WP_Site` don't exists.
		if ( ! class_exists( 'WP_Site' ) ) {
			wp_die( 'Hercules requires WordPress 4.5 or newer. Update now!' );
		}

		$this->start();
	}

	/**
	 * Destroy global variables and set current site to null.
	 */
	public function destroy() {
		global $current_blog, $blog_id;

		$blog_id = 1;
		$this->site = $current_blog = null;
	}

	/**
	 * Find site by domain.
	 *
	 * @param  string $domain
	 *
	 * @return WP_Site|null
	 */
	protected function find_site( $domain ) {
		if ( empty( $domain ) || ! is_string( $domain ) ) {
			return;
		}

		// Get site by domain.
		if ( $site = get_site_by_path( $domain, '' ) ) {
			return $site instanceof WP_Site ? $site : new WP_Site( $site );
		}
	}

	/**
	 * Get current domain.
	 *
	 * @return string
	 */
	public function get_domain() {
		if ( $this->site instanceof WP_Site ) {
			return $this->site->domain;
		}

		$domain = strtolower( stripslashes( $_SERVER['HTTP_HOST'] ) );

		if ( substr( $domain, -3 ) == ':80' ) {
			$domain = substr( $domain, 0, -3 );
			$_SERVER['HTTP_HOST'] = substr( $_SERVER['HTTP_HOST'], 0, -3 );
		} elseif ( substr( $domain, -4 ) == ':443' ) {
			$domain = substr( $domain, 0, -4 );
			$_SERVER['HTTP_HOST'] = substr( $_SERVER['HTTP_HOST'], 0, -4 );
		}

		return $domain;
	}

	/**
	 * Get site by the current domain.
	 *
	 * @return WP_Site|null
	 */
	public function get_site() {
		if ( $this->site instanceof WP_Site ) {
			return $this->site;
		}

		return $this->site = $this->find_site( $this->get_domain() );
	}

	/**
	 * Get class instance.
	 *
	 * @return \Isotop\WordPress\Hercules\Hercules
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * Mangle url to right domain.
	 *
	 * @param  string $url
	 *
	 * @return string
	 */
	public function mangle_url( $url ) {
		$domain = parse_url( $url, PHP_URL_HOST );
		$regex = '#^(\w+://)' . preg_quote( $domain, '#' ) . '#i';

		return preg_replace( $regex, '${1}' . $this->get_domain(), $url );
	}

	/**
	 * Must use plugins loaded hook.
	 */
	public function muplugins_loaded() {
		add_filter( 'site_url', [$this, 'mangle_url'], -10 );
		add_filter( 'home_url', [$this, 'mangle_url'], -10 );

		// Only change network site urls for main site.
		if ( is_main_site() ) {
			add_filter( 'network_site_url', [$this, 'mangle_url'], -10 );
			add_filter( 'network_home_url', [$this, 'mangle_url'], -10 );
		}

		$this->set_cookie_domain();
	}

	/**
	 * Set right cookie domain.
	 */
	protected function set_cookie_domain() {
		if ( defined( 'COOKIE_DOMAIN' ) ) {
			return;
		}

		$cookie_domain = $this->get_domain();

		// Remove `www.` from cookie domain.
		if ( substr( $cookie_domain, 0, 4 ) === 'www.' ) {
			$cookie_domain = substr( $cookie_domain, 4 );
		}

		/**
		 * Modify cookie domain before definition.
		 *
		 * @param  string $cookie_domain
		 *
		 * @return string
		 */
		$cookie_domain = apply_filters( 'hercules_cookie_domain', $cookie_domain );

		if ( is_string( $cookie_domain ) ) {
			define( 'COOKIE_DOMAIN', $cookie_domain );
		}
	}

	/**
	 * Start domain mapping.
	 */
	public function start() {
		if ( ! ( $site = $this->get_site() ) ) {
			return;
		}

		// Set global variables.
		global $current_blog, $blog_id;
		$current_blog = $site;
		$blog_id = $site->blog_id;

		/**
		 * Hercules is loaded.
		 */
		do_action( 'hercules_loaded' );
	}
}
