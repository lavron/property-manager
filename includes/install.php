<?php
/**
 * Installation related functions and actions.
 *
 */


defined( 'ABSPATH' ) || exit;

/**
 * PM_Install Class.
 */
class PM_Install {


	/**
	 * Hook in tabs.
	 */
	public static function init() {
		self::install();
	}

	/**
	 * Install PM.
	 */
	public static function install() {

		self::setup_environment();
		self::create_terms();
	}

	/**
	 * Setup PM environment - post types, taxonomies, endpoints.
	 *
	 * @since 3.2.0
	 */
	private static function setup_environment() {
		PM_Post_types::register_post_types();
		PM_Post_types::register_taxonomies();
	}

	

	/**
	 * Add the default terms for PM taxonomies - product types and order statuses. Modify this at your own risk.
	 */
	public static function create_terms() {
		$taxonomies = array(
			'property_type'       => array(
				'object',
				'offer',
			),
			'property_visibility' => array(
				'exclude-from-search',
				'exclude-from-catalog',
				'featured',
			),
			'deal_type' => array(
				'rent',
				'sale',
			),
		);

		foreach ( $taxonomies as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				if ( ! get_term_by( 'name', $term, $taxonomy ) ) { // @codingStandardsIgnoreLine.
					wp_insert_term( $term, $taxonomy );
				}
			}
		}

	}

}

PM_Install::init();
