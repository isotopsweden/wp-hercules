<?php

namespace Isotop\Tests\WordPress\Hercules;

use Isotop\WordPress\Hercules\Hercules;
use WP_Site;
use WP_UnitTestCase;

class Hercules_Test extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		add_filter( 'blog_details', [$this, '_blog_details'], 10 );
		add_filter( 'pre_get_site_by_path', [$this, '_get_site'], 10, 2 );
	}

	public function tearDown() {
		parent::tearDown();

		remove_filter( 'blog_details', [$this, '_blog_details'], 10 );
		remove_filter( 'pre_get_site_by_path', [$this, '_get_site'], 10, 2 );
	}

	public function _blog_details( $blog_details ) {
		if ( (int) $blog_details->blog_id === 1 ) {
			$blog_details->domain = 'test.dev';
			$blog_details->siteurl = 'http://test.dev';
			$blog_details->home = 'http://test.dev';
		} else {
			$blog_details->domain = 'test-en.dev';
			$blog_details->siteurl = 'http://test-en.dev';
			$blog_details->home = 'http://test-en.dev';
		}

		return $blog_details;
	}

	public function _get_site( $site, $domain ) {
		if ( $domain === 'test.dev' ) {
			return new WP_Site( (object) ['blog_id' => 1, 'domain' => 'test.dev', 'site_id' => 1] );
		}

		return new WP_Site( (object) ['blog_id' => 2, 'domain' => 'test-en.dev', 'site_id' => 1] );
	}

	public function test_domain_mapping() {
		$hercules = Hercules::instance();

		$_SERVER['HTTP_HOST'] = 'test.dev';
		$hercules->start();

		$this->assertSame( 1, get_current_blog_id() );
		$hercules->destroy();

		$_SERVER['HTTP_HOST'] = 'test-en.dev';
		$hercules->start();

		$this->assertSame( 2, get_current_blog_id() );
		$hercules->destroy();
	}

	public function test_get_domain() {
		$hercules = Hercules::instance();

		$_SERVER['HTTP_HOST'] = 'test.dev';
		$hercules->start();

		$this->assertSame( 'test.dev', $hercules->get_domain() );
		$hercules->destroy();

		$_SERVER['HTTP_HOST'] = 'test-en.dev';
		$hercules->start();

		$this->assertSame( 'test-en.dev', $hercules->get_domain() );
		$hercules->destroy();
	}

	public function test_get_site_url() {
		$hercules = Hercules::instance();
		$hercules->muplugins_loaded();

		$_SERVER['HTTP_HOST'] = 'test.dev';
		$hercules->start();

		$this->assertSame( 'http://test.dev', get_site_url( 1 ) );
		$hercules->destroy();

		$_SERVER['HTTP_HOST'] = 'test-en.dev';
		$hercules->start();

		$this->assertSame( 'http://test-en.dev', get_site_url( 2 ) );
		$hercules->destroy();
	}

	public function test_get_home_url() {
		$hercules = Hercules::instance();
		$hercules->muplugins_loaded();

		$_SERVER['HTTP_HOST'] = 'test.dev';
		$hercules->start();

		$this->assertSame( 'http://test.dev', get_home_url( 1 ) );
		$hercules->destroy();

		$_SERVER['HTTP_HOST'] = 'test-en.dev';
		$hercules->start();

		$this->assertSame( 'http://test-en.dev', get_home_url( 2 ) );
		$hercules->destroy();
	}

	public function test_get_site() {
		$hercules = Hercules::instance();

		$_SERVER['HTTP_HOST'] = 'test.dev';
		$hercules->start();

		$this->assertSame( 'test.dev', $hercules->get_site()->domain );
		$this->assertSame( 1, $hercules->get_site()->blog_id );
		$hercules->destroy();

		$_SERVER['HTTP_HOST'] = 'test-en.dev';
		$hercules->start();

		$this->assertSame( 'test-en.dev', $hercules->get_site()->domain );
		$this->assertSame( 2, $hercules->get_site()->blog_id );
		$hercules->destroy();
	}

	public function test_mangle_url() {
		$hercules = Hercules::instance();

		$_SERVER['HTTP_HOST'] = 'test.dev';
		$hercules->start();

		$this->assertSame( 'http://test.dev/wp/wp-admin/', $hercules->mangle_url( 'http://test-en.dev/wp/wp-admin/' ) );
		$hercules->destroy();
	}
}
