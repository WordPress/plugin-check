<?php
/**
 * Tests for the Demo_Posts_Creation_Preparation class.
 *
 * @package plugin-check
 */

namespace Checker\Preparations;

use WordPress\Plugin_Check\Checker\Preparations\Demo_Posts_Creation_Preparation;
use WP_UnitTestCase;
use Exception;

class Demo_Posts_Creation_Preparation_Tests extends WP_UnitTestCase {

	public function test_prepare() {
		$test_post = array(
			'post_title'   => 'Test post title',
			'post_content' => 'Test post content',
			'post_status'  => 'publish',
		);

		$preparation = new Demo_Posts_Creation_Preparation( array( $test_post ) );
		$cleanup     = $preparation->prepare();

		$posts = get_posts();

		$cleanup();

		$this->assertSame( 1, count( $posts ) );
		$this->assertSame( $test_post['post_title'], $posts[0]->post_title );
		$this->assertSame( $test_post['post_content'], $posts[0]->post_content );
		$this->assertSame( $test_post['post_status'], $posts[0]->post_status );
	}

	public function test_prepare_cleanup() {
		$test_post = array(
			'post_title'   => 'Test post title',
			'post_content' => 'Test post content',
			'post_status'  => 'publish',
		);

		$preparation = new Demo_Posts_Creation_Preparation( array( $test_post ) );
		$cleanup     = $preparation->prepare();
		$cleanup();

		$posts = get_posts();

		$this->assertEmpty( $posts );
	}

	public function test_prepare_throws_exception() {
		$test_post = array(
			'post_title'   => '',
			'post_content' => '',
			'post_status'  => 'publish',
		);

		$preparation = new Demo_Posts_Creation_Preparation( array( $test_post ) );

		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( 'Content, title, and excerpt are empty.' );

		$preparation->prepare();
	}
}
