<?php
/**
 * Bootstraps the plugin. load class.
 */

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Singleton;

class Autoloader {
    use Singleton;

    protected function __construct() {

        // load class.
        I18n::get_instance();
        Enqueue_Assets::get_instance();
        Users_Post_Type::get_instance();
        API_DB_Factory::get_instance();
        Display_Users::get_instance();
        Settings_Page::get_instance();
        Shops_Post_Type::get_instance();
        Display_Shops::get_instance();
    }
}