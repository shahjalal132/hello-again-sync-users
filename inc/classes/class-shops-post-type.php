<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Shops_Post_Type {

    use Singleton;
    use Program_Logs;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        add_action( 'init', [ $this, 'hea_shops_post_type' ], 0 );
        add_action( 'init', [ $this, 'register_sync_shops_category' ], 0 );
        
        // add sidebar
        add_action( 'init', [ $this, 'boilerplate_register_sidebars' ] );
    }

    // Register Custom Post Type
    public function hea_shops_post_type() {

        $labels = array(
            'name'                  => _x( 'Sync Shops', 'Post Type General Name', 'hello-again' ),
            'singular_name'         => _x( 'Sync Shop', 'Post Type Singular Name', 'hello-again' ),
            'menu_name'             => __( 'Sync Shops', 'hello-again' ),
            'name_admin_bar'        => __( 'Sync Shops', 'hello-again' ),
            'archives'              => __( 'Item Shops', 'hello-again' ),
            'attributes'            => __( 'Item Attributes', 'hello-again' ),
            'parent_item_colon'     => __( 'Parent Shop:', 'hello-again' ),
            'all_items'             => __( 'All Shops', 'hello-again' ),
            'add_new_item'          => __( 'Add New Shop', 'hello-again' ),
            'add_new'               => __( 'Add New', 'hello-again' ),
            'new_item'              => __( 'New Item', 'hello-again' ),
            'edit_item'             => __( 'Edit Shop', 'hello-again' ),
            'update_item'           => __( 'Update Shop', 'hello-again' ),
            'view_item'             => __( 'View Shop', 'hello-again' ),
            'view_items'            => __( 'View Shops', 'hello-again' ),
            'search_items'          => __( 'Search Shop', 'hello-again' ),
            'not_found'             => __( 'Not found', 'hello-again' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'hello-again' ),
            'featured_image'        => __( 'Featured Image', 'hello-again' ),
            'set_featured_image'    => __( 'Set featured image', 'hello-again' ),
            'remove_featured_image' => __( 'Remove featured image', 'hello-again' ),
            'use_featured_image'    => __( 'Use as featured image', 'hello-again' ),
            'insert_into_item'      => __( 'Insert into Shop', 'hello-again' ),
            'uploaded_to_this_item' => __( 'Uploaded to this Shop', 'hello-again' ),
            'items_list'            => __( 'Shops list', 'hello-again' ),
            'items_list_navigation' => __( 'Shops list navigation', 'hello-again' ),
            'filter_items_list'     => __( 'Filter Shops list', 'hello-again' ),
        );
        $args   = array(
            'label'               => __( 'Shop', 'hello-again' ),
            'description'         => __( 'Shops', 'hello-again' ),
            'labels'              => $labels,
            'supports'            => array( 'title', 'thumbnail', 'editor' ),
            // 'taxonomies'          => array( 'category', 'post_tag' ),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 46,
            'menu_icon'           => 'dashicons-store',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'page',
        );
        register_post_type( 'sync_shops', $args );

    }
    // add Category taxonomy to post type
    public function register_sync_shops_category() {
        $labels = [
            'name'              => _x('Shop Categories', 'taxonomy general name', 'hello-again'),
            'singular_name'     => _x('Shop Category', 'taxonomy singular name', 'hello-again'),
            'search_items'      => __('Search Shop Categories', 'hello-again'),
            'all_items'         => __('All Shop Categories', 'hello-again'),
            'parent_item'       => __('Parent Shop Category', 'hello-again'),
            'parent_item_colon' => __('Parent Shop Category:', 'hello-again'),
            'edit_item'         => __('Edit Shop Category', 'hello-again'),
            'update_item'       => __('Update Shop Category', 'hello-again'),
            'add_new_item'      => __('Add New Shop Category', 'hello-again'),
            'new_item_name'     => __('New Shop Category Name', 'hello-again'),
            'menu_name'         => __('Shop Categories', 'hello-again'),
        ];

        $args = [
            'hierarchical'      => true, // Set to false for non-hierarchical (tags-like behavior)
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'sync-shops-category'],
        ];

        register_taxonomy('sync_shops_category', ['sync_shops'], $args);
    }
    
    

    public function boilerplate_register_sidebars() {
        register_sidebar(
            [
                'name'          => __( 'Shop Sidebar', 'boilerplate' ),
                'id'            => 'shop-sidebar',
                'description'   => __( 'Sidebar for the shop pages.', 'boilerplate' ),
                'before_widget' => '<div id="%1$s" class="widget %2$s">',
                'after_widget'  => '</div>',
                'before_title'  => '<h4 class="widget-title">',
                'after_title'   => '</h4>',
            ]
        );
    }

}
