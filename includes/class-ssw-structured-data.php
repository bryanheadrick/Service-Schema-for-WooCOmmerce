<?php
/**
 * Overrides structured data output for service products.
 *
 * @package Service_Schema_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrites JSON-LD markup to schema.org Service for flagged products.
 */
class SSW_Structured_Data {

	/**
	 * Registers hooks.
	 */
	public function __construct() {
		// Populated in a later task.
	}
}
