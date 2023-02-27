<?php
/**
 * Template Loader
 *
 * @package PropertyManager\Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Template loader class.
 */
class PM_Template_Loader {

	/**
	 * Store the catalogue page ID.
	 *
	 * @var integer
	 */
	private static $catalogue_page_id = 0;


	/**
	 * Hook in methods.
	 */
	public static function init() {
		self::$catalogue_page_id  = pm_get_page_id( 'catalogue' );

		add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
	}

	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the theme's.
	 *
	 * Templates are in the 'templates' folder. PropertyManager looks for theme
	 * overrides in /theme/pm/ by default.
	 *
	 * For beginners, it also looks for a pm.php template first. If the user adds
	 * this to the theme (containing a pm() inside) this will be used for all
	 * PropertyManager templates.
	 *
	 * @param string $template Template to load.
	 * @return string
	 */
	public static function template_loader( $template ) {
		if ( is_embed() ) {
			return $template;
		}

		$default_file = self::get_template_loader_default_file();

		if ( $default_file ) {
			/**
			 * Filter hook to choose which files to find before PropertyManager does it's own logic.
			 *
			 * @since 3.0.0
			 * @var array
			 */
			$search_files = self::get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );

			if ( ! $template || PM_TEMPLATE_DEBUG_MODE ) {
				if ( false !== strpos( $default_file, 'product_cat' ) || false !== strpos( $default_file, 'product_tag' ) ) {
					$cs_template = str_replace( '_', '-', $default_file );
					$template    = PM()->plugin_path() . '/templates/' . $cs_template;
				} else {
					$template = PM()->plugin_path() . '/templates/' . $default_file;
				}
			}
		}

		return $template;
	}

	/**
	 * Get the default filename for a template.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	private static function get_template_loader_default_file() {
		if ( is_singular( 'property' ) ) {
			$default_file = 'single-property.php';
		} elseif ( is_product_taxonomy() ) {
			$object = get_queried_object();

			if ( is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) {
				$default_file = 'taxonomy-' . $object->taxonomy . '.php';
			} else {
				$default_file = 'archive-property.php';
			}
		} elseif ( is_post_type_archive( 'property' ) || is_page( pm_get_page_id( 'catalogue' ) ) ) {
			$default_file =  'archive-property.php';
		} else {
			$default_file = '';
		}
		return $default_file;
	}

	/**
	 * Get an array of filenames to search for a given template.
	 *
	 * @since  3.0.0
	 * @param  string $default_file The default file name.
	 * @return string[]
	 */
	private static function get_template_loader_files( $default_file ) {
		$templates   = array();
		$templates[] = 'pm.php';

		if ( is_page_template() ) {
			$page_template = get_page_template_slug();

			if ( $page_template ) {
				$validated_file = validate_file( $page_template );
				if ( 0 === $validated_file ) {
					$templates[] = $page_template;
				} else {
					error_log( "PropertyManager: Unable to validate template path: \"$page_template\". Error Code: $validated_file." );
				}
			}
		}

		if ( is_singular( 'property' ) ) {
			$object       = get_queried_object();
			$name_decoded = urldecode( $object->post_name );
			if ( $name_decoded !== $object->post_name ) {
				$templates[] = "single-product-{$name_decoded}.php";
			}
			$templates[] = "single-product-{$object->post_name}.php";
		}

		if ( is_product_taxonomy() ) {
			$object = get_queried_object();
			if ( is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) {
				$cs_taxonomy = str_replace( '_', '-', $object->taxonomy );
				$cs_default  = str_replace( '_', '-', $default_file );
				$templates[] = 'taxonomy-' . $object->taxonomy . '-' . $object->slug . '.php';
				$templates[] = PM()->template_path() . 'taxonomy-' . $cs_taxonomy . '-' . $object->slug . '.php';
				$templates[] = 'taxonomy-' . $object->taxonomy . '.php';
				$templates[] = PM()->template_path() . 'taxonomy-' . $cs_taxonomy . '.php';
				$templates[] = $cs_default;
			} else {
				$templates[] = 'taxonomy-' . $object->taxonomy . '-' . $object->slug . '.php';
				$templates[] = PM()->template_path() . 'taxonomy-' . $object->taxonomy . '-' . $object->slug . '.php';
				$templates[] = 'taxonomy-' . $object->taxonomy . '.php';
				$templates[] = PM()->template_path() . 'taxonomy-' . $object->taxonomy . '.php';
			}
		}

		$templates[] = $default_file;
		if ( isset( $cs_default ) ) {
			$templates[] = PM()->template_path() . $cs_default;
		}
		$templates[] = PM()->template_path() . $default_file;

		return array_unique( $templates );
	}


}

add_action( 'init', array( 'PM_Template_Loader', 'init' ) );
