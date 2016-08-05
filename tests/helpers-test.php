<?php

namespace Isotop\Tests\Hercules;

use Isotop\Hercules\Hercules;
use WP_UnitTestCase;

class Helpers_Test extends WP_UnitTestCase {

	public function test_hercules() {
		$this->assertInstanceOf( Hercules::class, hercules() );
	}
}
