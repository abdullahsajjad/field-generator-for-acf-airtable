<?php
/**
 * Generator Page Class
 *
 * @package AFGFA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the ACF field generator admin page.
 */
class AFGFA_Generator_Page {

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'afgfa-generator';

	/**
	 * Field type mapper.
	 *
	 * @var AFGFA_Field_Type_Mapper
	 */
	private $mapper;

	/**
	 * Constructor.
	 *
	 * @param AFGFA_Field_Type_Mapper $mapper Field type mapper.
	 */
	public function __construct( $mapper ) {
		$this->mapper = $mapper;
	}

	/**
	 * Render the generator page.
	 */
	public function render() {
		$acf_field_types = $this->mapper->get_acf_field_types();
		include AFGFA_PLUGIN_DIR . 'views/generator-page.php';
	}

	/**
	 * Get ACF field types for JavaScript.
	 *
	 * @return array
	 */
	public function get_field_types_for_js() {
		return $this->mapper->get_acf_field_types();
	}
}
