<?php

/*
 * Main plugin class
 */

class Shifter_Migrate
{

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    public $plugin_name;

    /**
     * The plugin public name.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $plugin_name    The name for plugin, not the identifier but just a name.
     */
    public $plugin_public_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $version    The current version of the plugin.
     */
    public $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->plugin_name = 'shifter-migrate';
        $this->version = '1.0.0';
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Shifter_Migrate_Admin. Defines all hooks and functionality for the admin area.
     * - Shifter_Migrate_i18n. Defines internationalization functionality.
     *
     * Include all classes needed for plugin
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(__FILE__) . 'class-shifter-migrate-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-shifter-migrate-admin.php';
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Shifter_Migrate_Admin($this->plugin_name, $this->version);

        // Add menu page
        add_action('admin_menu', array( $plugin_admin, 'add_menu_page' ));

        // Add backup process action
        add_action('admin_post_start_shifter_migrate', array( $plugin_admin, 'make_site_backup' ));

        // Add delete backup file action
        add_action('admin_post_delete_shifter_migrate', array( $plugin_admin, 'delete_backup_file' ));

        // Chekc for $_GET params and add admin notices for each
        add_action('init', array( $plugin_admin, 'check_and_add_admin_notices' ));
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Shifter_Migrate_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new Shifter_Migrate_i18n();
        $plugin_i18n->set_domain($this->plugin_name);

        add_action('plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ));
    }

    /**
     * Load dependencies, loads internationalization files and execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        // Load only for admin
        if (is_admin()) {
            $this->load_dependencies();
            $this->set_locale();
            $this->define_admin_hooks();
        }
    }

    /**
     * Abort loading all plugin hooks and show admin notice
     *
     * @since    1.0.0
     */
    public function abort()
    {
        $this->load_dependencies();
        $plugin_admin = new Shifter_Migrate_Admin($this->plugin_name, $this->version);
        $plugin_admin->add_admin_notice(esc_html__('Shifter Migrate requires at least PHP version 5.3.0', 'shifter-migrate'), 'notice-error');
    }
}
