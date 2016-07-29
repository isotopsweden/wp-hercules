<?php

namespace Isotop\WordPress\Hercules\Admin;

class Site_New {
	public function __construct() {
		add_action( 'load-site-new.php', [$this, 'site_new_php'] );
	}

	protected function create_new_site($site) {
		global $wpdb;

		if ( empty( $site['domain'] ) ) {
			wp_die( __( 'Missing site domain.' ) );
		}

		if ( empty( $site['email'] ) ) {
			wp_die( __( 'Missing site administrator email.' ) );
		}

		if ( empty( $site['title'] ) ) {
			wp_die( __( 'Missing site title.' ) );
		}

		$email = sanitize_email( $site['email'] );

		if ( ! is_email( $email ) ) {
			wp_die( __( 'Invalid email address.' ) );
		}

		$domain = explode( '//', $site['domain'] );

		var_dump($domain);exit;
	}

	public function site_new_php() {
		global $title, $parent_file;

		if ( isset( $_REQUEST['action'] ) && 'add-site' === $_REQUEST['action'] ) {
			check_admin_referer( 'add-blog', '_wpnonce_add-blog' );

			if ( ! is_array( $_POST['blog'] ) ) {
				wp_die( __( 'Can&#8217;t create an empty site.' ) );
			}

			$this->create_new_site( $_POST['blog'] );

			exit;
		}
	}
}
