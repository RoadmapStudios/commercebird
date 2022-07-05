<?php

// CPT of Purchase Orders
function create_posttype()
{

    register_post_type('purchase_orders',
        // CPT Options
        array(
            'labels' => array(
                'name' => __('Purchase Orders'),
                'singular_name' => __('Purchase Order'),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('author', 'comments', 'custom-fields'),
            'rewrite' => array('slug' => 'purchase-orders'),
            'show_in_rest' => true,
        )
    );
}
// Hooking up our function to theme setup
add_action('init', 'create_posttype');

/* Create Vendor Member User Role */
add_role(
    'vendor', //  System name of the role.
    __( 'Vendor'  ), // Display name of the role.
    array(
        'read'  => true,
        'delete_posts'  => false,
        'delete_published_posts' => false,
        'edit_posts'   => false,
        'publish_posts' => false,
        'upload_files'  => false,
        'edit_pages'  => false,
        'edit_published_pages'  =>  false,
        'publish_pages'  => false,
        'delete_published_pages' => false, // This user will NOT be able to  delete published pages.
    )
);