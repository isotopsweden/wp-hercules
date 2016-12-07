<?php

namespace Isotop\Hercules;

use WP_Site;

final class Hercules {

	/**
	 * The class instance.
	 *
	 * @var \Isotop\Hercules\Hercules
	 */
	protected static $instance;

	/**
	 * Current site.
	 *
	 * @var \WP_Site
	 */
	protected $site;

	/**
	 * The construct.
	 */
	protected function __construct() {
		add_action( 'muplugins_loaded', [$this, 'muplugins_loaded'] );
		add_action( 'load-site-new.php', [$this, 'site_new'] );
	}

	/**
	 * Disable site new.
	 */
	public function site_new() {
		wp_die( 'Site new is disabled. Please use WP-CLI instead.' );
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
	 * @param  string $domain The domain to look for.
	 *
	 * @return \WP_Site|null
	 */
	protected function find_site( $domain ) {
		if ( empty( $domain ) || ! is_string( $domain ) ) {
			return;
		}

		// Get site by domain.
		if ( $site = get_site_by_path( $domain, '' ) ) {
			return $site instanceof WP_Site ? $site : new WP_Site( $site );
		}

		if ( $site = get_site( 1 ) ) {
			$scheme = is_ssl() ? 'https' : 'http';
			$uri = sprintf( '%s://%s', $scheme, $site->domain );

			header( 'Location: ' . $uri );
			die;
		}
	}

	/**
	 * Get value by key from site.
	 *
	 * @param  string $key     The key to look for.
	 * @param  mixed  $default Default null.
	 * @param  int    $blog_id The blog id. Default zero.
	 *
	 * @return mixed
	 */
	public function get( $key, $default = null, $blog_id = 0 ) {
		if ( $site = $this->get_site( $blog_id ) ) {
			return isset( $site->$key ) ? $site->$key : $default;
		}

		return $default;
	}

	/**
	 * Get domain from current site or from `HTTP_HOST`.
	 *
	 * @param  int $blog_id Optional, default zero.
	 *
	 * @return string
	 */
	public function get_domain( $blog_id = 0 ) {
		if ( ! empty( $blog_id ) && $domain = $this->get( 'domain', null, $blog_id ) ) {
			return $domain;
		}

		if ( $this->site instanceof WP_Site ) {
			return $this->site->domain;
		}

		$domain = strtolower( stripslashes( $_SERVER['HTTP_HOST'] ) );

		if ( substr( $domain, -3 ) === ':80' ) {
			$domain = substr( $domain, 0, -3 );
			$_SERVER['HTTP_HOST'] = substr( $_SERVER['HTTP_HOST'], 0, -3 );
		} else if ( substr( $domain, -4 ) === ':443' ) {
			$domain = substr( $domain, 0, -4 );
			$_SERVER['HTTP_HOST'] = substr( $_SERVER['HTTP_HOST'], 0, -4 );
		}

		return $domain;
	}

	/**
	 * Get site by the current domain.
	 *
	 * @param  int $blog_id Optional, default zero.
	 *
	 * @return \WP_Site|null
	 */
	public function get_site( $blog_id = 0 ) {
		if ( ! empty( $blog_id ) ) {
			// Check so the current site is the site we looking for or
			// try to get it from the database.
			if ( $this->site instanceof WP_Site && (int) $this->site->blog_id === $blog_id ) {
				return $this->site;
			} else if ( $blog_details = get_blog_details( $blog_id ) ) {
				$this->site = new WP_Site( $blog_details );
			}
		}

		// If a site exists, return it.
		if ( $this->site instanceof WP_Site ) {
			return $this->site;
		}

		// Find site by current domain.
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
	 * Mangle url to right domain, it will fetch the site
	 * if `$blog_id` is different from the existing one.
	 *
	 * @param  string $url         The url.
	 * @param  string $path        The path. Not used.
	 * @param  string $orig_scheme The scheme. Not used.
	 * @param  int    $blog_id     The blog id.
	 *
	 * @return string
	 */
	public function mangle_url( $url, $path = '', $orig_scheme = '', $blog_id = 0 ) {
		$domain = parse_url( $url, PHP_URL_HOST );
		$regex = '#^(\w+://)' . preg_quote( $domain, '#' ) . '#i';

		if ( empty( $blog_id ) ) {
			$blog_id = (int) $GLOBALS['blog_id'];
		}

		return preg_replace( $regex, '${1}' . $this->get_domain( $blog_id ), $url );
	}

	/**
	 * Dynamic build site url if possible
	 * or return database url.
	 *
	 * @param  mixed $url
	 *
	 * @return mixed $url
	 */
	public function pre_option_siteurl( $url ) {
		if ( defined( 'WP_SITEURL' ) ) {
			$url = WP_SITEURL;

			if ( defined( 'WP_CLI' ) ) {
				$url = str_replace( 'wp', '', WP_SITEURL );
			}

			return $url;
		}

		return $url;
	}

	/**
	 * Dynamic build home url if possible
	 * or return database url.
	 *
	 * @param  mixed $url
	 *
	 * @return mixed $url
	 */
	public function pre_option_home( $url ) {
		if ( defined( 'WP_HOME' ) ) {
			return WP_HOME;
		}

		return $url;
	}

	/**
	 * Must use plugins loaded hook.
	 */
	public function muplugins_loaded() {
		// Replace current site url and home url with right domain.
		add_filter( 'site_url', [$this, 'mangle_url'], -10, 4 );
		add_filter( 'home_url', [$this, 'mangle_url'], -10, 4 );

		// Change database option for site url and home url to the current one.
		add_filter( 'pre_option_siteurl', [$this, 'pre_option_siteurl'] );
		add_filter( 'pre_option_home', [$this, 'pre_option_home'] );

		// Only change network site urls for main site.
		if ( is_main_site() ) {
			add_filter( 'network_site_url', [$this, 'mangle_url'], -10, 4 );
			add_filter( 'network_home_url', [$this, 'mangle_url'], -10, 4 );
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
	 *
	 * @return bool
	 */
	public function start() {
		if ( ! ( $site = $this->get_site() ) ) {
			return false;
		}

		// Set global variables.
		global $current_blog, $blog_id;
		$current_blog = $site;
		$blog_id = $site->blog_id;

		/**
		 * Hercules is loaded.
		 */
		do_action( 'hercules_loaded' );

		// Started!
		return true;
	}
}
