<?php

/**
 * Janey AI core plugin class
 *
 * @link https://janey.ai/
 * @since 0.0.1
 * @package Janey_AI
 * @subpackage Janey_AI/includes
 * @author sepiariver
 */
class Janey_AI
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    0.0.1
     * @access   protected
     * @var      Janey_AI_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    0.0.1
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    0.0.1
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    0.0.1
     */
    public function __construct()
    {
        if (defined('JANEY_AI_VERSION')) {
            $this->version = JANEY_AI_VERSION;
        } else {
            $this->version = '0.0.1';
        }
        $this->plugin_name = 'janey-ai';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Janey_AI_Loader. Orchestrates the hooks of the plugin.
     * - Janey_AI_i18n. Defines internationalization functionality.
     * - Janey_AI_Admin. Defines all hooks for the admin area.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    0.0.1
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-janey-ai-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-janey-ai-i18n.php';

        /**
         * The Janey SDK class.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/janey-sdk/vendor/autoload.php';
        //require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/janey-sdk/src/Janey.php';

        /**
         * The Twig class.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/twig/vendor/autoload.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-janey-ai-admin.php';

        /**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-janey-ai-public.php';

        $this->loader = new Janey_AI_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Janey_AI_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    0.0.1
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new Janey_AI_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    0.0.1
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Janey_AI_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_menu', $plugin_admin, 'register_settings_page');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_meta_boxes');
        $this->loader->add_action('save_post', $plugin_admin, 'on_save_post', 10, 2);

        $this->loader->add_filter('admin_post_thumbnail_html', $plugin_admin, 'thumbnail_url_field', 10, 2);
    }

    /**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function define_public_hooks()
    {
		$plugin_public = new Janey_AI_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_filter('post_thumbnail_html', $plugin_public, 'thumbnail_external_replace', 10, 2);
	}

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    0.0.1
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     0.0.1
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     0.0.1
     * @return    Janey_AI_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     0.0.1
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}
