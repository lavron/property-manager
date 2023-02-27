<?php


defined( 'ABSPATH' ) || exit;

/**
 * Post types Class.
 */
class PM_Post_Types {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
        add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 5 );
        
	}

	/**
	 * Register core taxonomies.
	 */
	public static function register_taxonomies() {


		if ( taxonomy_exists( 'property_type' ) ) {
			return;
		}


		register_taxonomy(
			'property_type',
			array( 'property' ),
			array(
                'hierarchical'      => false,
                'show_ui'           => false,
                'show_in_nav_menus' => false,
                'query_var'         => is_admin(),
                'rewrite'           => false,
                'public'            => false,
                'label'             => _x( 'Property type', 'Taxonomy name', 'pm' ),
            )
		);

		register_taxonomy(
			'property_visibility',
            array( 'property', 'property_offer' ),
			array(
                'hierarchical'      => false,
                'show_ui'           => false,
                'show_in_nav_menus' => false,
                'query_var'         => is_admin(),
                'rewrite'           => false,
                'public'            => false,
                'label'             => _x( 'Property visibility', 'Taxonomy name', 'pm' ),
            )
        );
        
		register_taxonomy(
			'deal_type',
            array( 'property_offer' ),
			array(
                'hierarchical'      => false,
                'show_ui'           => false,
                'show_in_nav_menus' => false,
                'query_var'         => is_admin(),
                'rewrite'           => false,
                'public'            => false,
                'label'             => _x( 'Deal Type', 'Taxonomy name', 'pm' ),
            )
		);

		register_taxonomy(
			'property_cat',
			array( 'property' ),
			array(
                'hierarchical'          => true,
                'update_count_callback' => '_pm_term_recount',
                'label'                 => __( 'Categories', 'pm' ),
                'labels'                => array(
                    'name'              => __( 'Property categories', 'pm' ),
                    'singular_name'     => __( 'Category', 'pm' ),
                    'menu_name'         => _x( 'Categories', 'Admin menu name', 'pm' ),
                    'search_items'      => __( 'Search categories', 'pm' ),
                    'all_items'         => __( 'All categories', 'pm' ),
                    'parent_item'       => __( 'Parent category', 'pm' ),
                    'parent_item_colon' => __( 'Parent category:', 'pm' ),
                    'edit_item'         => __( 'Edit category', 'pm' ),
                    'update_item'       => __( 'Update category', 'pm' ),
                    'add_new_item'      => __( 'Add new category', 'pm' ),
                    'new_item_name'     => __( 'New category name', 'pm' ),
                    'not_found'         => __( 'No categories found', 'pm' ),
                ),
                'show_ui'               => true,
                'query_var'             => true,
                'capabilities'          => array(
                    'manage_terms' => 'manage_property_terms',
                    'edit_terms'   => 'edit_property_terms',
                    'delete_terms' => 'delete_property_terms',
                    'assign_terms' => 'assign_property_terms',
                ),
                'rewrite'               => array(
                    'slug'         => 'property-category',
                    'with_front'   => false,
                    'hierarchical' => true,
                ),
            )
		);

	
		
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types() {

		if ( ! is_blog_installed() || post_type_exists( 'property' ) ) {
			return;
        }
        
        

        $permalinks = pm_get_permalink_structure();

        $supports   = array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'publicize', 'wpcom-markdown' );

        $shop_page_id = pm_get_page_id( 'catalogue' );
        
        $has_archive = $shop_page_id && get_post( $shop_page_id ) ? urldecode( get_page_uri( $shop_page_id ) ) : 'catalogue';
		register_post_type(
			'property',
			array(
                'labels'              => array(
                    'name'                  => __( 'Properties', 'pm' ),
                    'singular_name'         => __( 'Property', 'pm' ),
                    'all_items'             => __( 'All Properties', 'pm' ),
                    'menu_name'             => _x( 'Properties', 'Admin menu name', 'pm' ),
                    'add_new'               => __( 'Add New', 'pm' ),
                    'add_new_item'          => __( 'Add new property', 'pm' ),
                    'edit'                  => __( 'Edit', 'pm' ),
                    'edit_item'             => __( 'Edit property', 'pm' ),
                    'new_item'              => __( 'New property', 'pm' ),
                    'view_item'             => __( 'View property', 'pm' ),
                    'view_items'            => __( 'View properties', 'pm' ),
                    'search_items'          => __( 'Search properties', 'pm' ),
                    'not_found'             => __( 'No properties found', 'pm' ),
                    'not_found_in_trash'    => __( 'No properties found in trash', 'pm' ),
                    'parent'                => __( 'Parent property', 'pm' ),
                    'featured_image'        => __( 'Property image', 'pm' ),
                    'set_featured_image'    => __( 'Set property image', 'pm' ),
                    'remove_featured_image' => __( 'Remove property image', 'pm' ),
                    'use_featured_image'    => __( 'Use as property image', 'pm' ),
                    'insert_into_item'      => __( 'Insert into property', 'pm' ),
                    'uploaded_to_this_item' => __( 'Uploaded to this property', 'pm' ),
                    'filter_items_list'     => __( 'Filter properties', 'pm' ),
                    'items_list_navigation' => __( 'Properties navigation', 'pm' ),
                    'items_list'            => __( 'Properties list', 'pm' ),
                ),
                'description'         => __( 'This is where you can add new properties to your store.', 'pm' ),
                'public'              => true,
                'show_ui'             => true,
                'capability_type'     => 'property',
                'map_meta_cap'        => true,
                'publicly_queryable'  => true,
                'exclude_from_search' => false,
                'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
                'rewrite'             => $permalinks['property_rewrite_slug'] ? array(
                    'slug'       => $permalinks['property_rewrite_slug'],
                    'with_front' => false,
                    'feeds'      => true,
                ) : false,
                'query_var'           => true,
                'supports'            => $supports,
                'has_archive'         => $has_archive,
                'show_in_nav_menus'   => true,
                'show_in_rest'        => true,
            )
		);
       
		register_post_type(
			'property_offer',
			array(
                'label'           => __( 'Offers', 'pm' ),
                'public'          => true,
                'hierarchical'    => false,
                'supports'        => $supports,
                'capability_type' => 'property',
                'rewrite'         => false,
                'has_archive'     => false,
            )
		);



	}

}

PM_Post_types::init();
