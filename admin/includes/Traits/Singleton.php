<?php

namespace CommerceBird\Admin\Traits;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Singleton {
	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static ?self $instance = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function __construct() {
	}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance(): self {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Prevent unserializing.
	 *
	 * @throws Exception
	 */
	final public function __wakeup() {
		throw new Exception( 'Serializing instances of this class is forbidden' );
	}

	/**
	 * Prevent cloning.
	 *
	 * @throws Exception
	 */
	private function __clone() {
		throw new Exception( 'Serializing instances of this class is forbidden' );
	}
}
