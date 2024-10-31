<?php

/**
 * Taxonomy: Brands for Products.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cmbird_register_custom_product_taxonomies() {

	$labels = array(
		'name'                       => esc_html__( 'Brands', 'commercebird' ),
		'singular_name'              => esc_html__( 'Brand', 'commercebird' ),
		'menu_name'                  => esc_html__( 'Brands', 'commercebird' ),
		'all_items'                  => esc_html__( 'All Brands', 'commercebird' ),
		'edit_item'                  => esc_html__( 'Edit Brand', 'commercebird' ),
		'view_item'                  => esc_html__( 'View Brand', 'commercebird' ),
		'update_item'                => esc_html__( 'Update Brand name', 'commercebird' ),
		'add_new_item'               => esc_html__( 'Add new Brand', 'commercebird' ),
		'new_item_name'              => esc_html__( 'New Brand name', 'commercebird' ),
		'parent_item'                => esc_html__( 'Parent Brand', 'commercebird' ),
		'parent_item_colon'          => esc_html__( 'Parent Brand:', 'commercebird' ),
		'search_items'               => esc_html__( 'Search Brands', 'commercebird' ),
		'popular_items'              => esc_html__( 'Popular Brands', 'commercebird' ),
		'separate_items_with_commas' => esc_html__( 'Separate Brands with commas', 'commercebird' ),
		'add_or_remove_items'        => esc_html__( 'Add or remove Brands', 'commercebird' ),
		'choose_from_most_used'      => esc_html__( 'Choose from the most used Brands', 'commercebird' ),
		'not_found'                  => esc_html__( 'No Brands found', 'commercebird' ),
		'no_terms'                   => esc_html__( 'No Brands', 'commercebird' ),
		'items_list_navigation'      => esc_html__( 'Brands list navigation', 'commercebird' ),
		'items_list'                 => esc_html__( 'Brands list', 'commercebird' ),
		'back_to_items'              => esc_html__( 'Back to Brands', 'commercebird' ),
		'name_field_description'     => esc_html__( 'The name is how it appears on your site.', 'commercebird' ),
		'parent_field_description'   => esc_html__( 'Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.', 'commercebird' ),
		'slug_field_description'     => esc_html__( 'The slug is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'commercebird' ),
		'desc_field_description'     => esc_html__( 'The description is not prominent by default; however, some themes may show it.', 'commercebird' ),
	);

	$args = array(
		'label'                 => esc_html__( 'Brands', 'commercebird' ),
		'labels'                => $labels,
		'public'                => true,
		'publicly_queryable'    => true,
		'hierarchical'          => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'query_var'             => true,
		'rewrite'               => array(
			'slug'       => 'product-brands',
			'with_front' => true,
		),
		'show_admin_column'     => true,
		'show_in_rest'          => true,
		'rest_base'             => 'brands',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
		'show_tagcloud'         => false,
		'show_in_quick_edit'    => true,
		'sort'                  => true,
		'show_in_graphql'       => true,
	);
	if ( ! taxonomy_exists( 'product_brands' ) ) {
		register_taxonomy( 'product_brands', array( 'product' ), $args );
	}
	register_taxonomy_for_object_type( 'product_brands', 'product' );
}
add_action( 'init', 'cmbird_register_custom_product_taxonomies' );
