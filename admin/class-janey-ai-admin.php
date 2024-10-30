<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link https://janey.ai/
 * @since 0.0.1
 * @package Janey_AI
 * @subpackage Janey_AI/admin
 * @author sepiariver
 */
class Janey_AI_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    0.0.1
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    0.0.1
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The Janey AI API Token.
     *
     * @since    0.0.1
     * @access   private
     * @var      string    $api_token   The api token.
     */
    private $api_token;

    /**
     * Exclude native taxonomies.
     *
     * @since    0.0.1
     * @access   private
     * @var      array    $exclude_taxonomies   Native taxonomies to exclude from targeting.
     */
    private $exclude_taxonomies;

    /**
     * The Janey AI SDK.
     *
     * @since    0.0.1
     * @access   private
     * @var      Janey    $sdk    \Janey\SDK\Janey
     */
    private $sdk;

    /**
     * The Twig Loader.
     *
     * @since    0.0.1
     * @access   private
     * @var      FilesystemLoader    $twig_loader    \Twig\Loader\FilesystemLoader
     */
    private $twig_loader;

    /**
     * The Twig Environment.
     *
     * @since    0.0.1
     * @access   private
     * @var      Environment    $twig    \Twig\Environment
     */
    private $twig;

    /**
     * Placeholder to activate featured image in posts.
     *
     * @since    0.0.1
     * @access   private
     * @var      string    $image_placeholder   URL to placeholder image.
     */
    private $image_placeholder;

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.0.1
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->exclude_taxonomies = ['category', 'link_category', 'post_format'];

        $this->api_token = $this->get_option('janey-ai-api-token');
        $this->sdk = new \Janey\SDK\Janey($this->api_token, get_site_url(), $this->plugin_name, $this->version, 'https://api.janey.ai/');

        $this->twig_loader = new \Twig\Loader\FilesystemLoader(plugin_dir_path(__FILE__) . 'templates');
        $this->twig = new \Twig\Environment($this->twig_loader, ['autoescape' => 'html_attr']);
        $this->twig_cached = new \Twig\Environment($this->twig_loader, [
            'cache' => plugin_dir_path(__FILE__) . 'cache',
            'autoescape' => 'html_attr'
        ]);

        $this->image_placeholder = 'https://janey.ai/assets/uploads/janey-featured-image-pixel.png';
    }

    // POST VIEW

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    0.0.1
     */
    public function enqueue_scripts()
    {
        if ($this->is_version() && !$this->is_classic()) {
            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'js/janey-ai-admin-gutenberg.js',
                ['jquery', 'wp-data', 'wp-notices'],
                $this->version,
                false
            );
        }
    }

    /**
     * Create Meta Boxes for Janey AI.
     *
     * @since    0.0.1
     */
    public function add_meta_boxes()
    {
        $screens = ['post'];
        foreach ($screens as $screen) {
            add_meta_box(
                $this->plugin_name . '-enable',     // Unique ID
                'Enable Janey AI',                  // Box title
                [$this, 'meta_box_html_enable'],    // Content callback, must be of type callable. Array format for class member
                $screen,                            // Post type
                'side',                             // Context
                'high'								// Priority (order)
                //['__block_editor_compatible_meta_box' => false]
            );
            add_meta_box(
                $this->plugin_name . '-tags',
                'Janey AI Suggested Tags',
                [$this, 'meta_box_html_tags'],
                $screen,
                'advanced',
                'default'
            );
            add_meta_box(
                $this->plugin_name . '-pictures',
                'Janey AI Suggested Pictures',
                [$this, 'meta_box_html_pictures'],
                $screen,
                'advanced',
                'default'
            );
        }
    }

    /**
     * Define Meta Box HTML for Janey AI enable checkbox.
     *
     * @since	0.0.1
     * @param 	WP_Post		$post	Wordpress post object
     */
    public function meta_box_html_enable($post)
    {
        $meta = $this->get_postmeta($post->ID);
        $meta['notice'] = ($this->is_version() && !$this->is_classic()) ? $this->twig->render('meta_box_guten_notice.html') : '';
        echo $this->twig->render('meta_box_html_enable.html', $meta);
    }

    /**
     * Define Meta Box HTML for Janey AI suggested tags.
     *
     * @since    0.0.1
     * @param 	WP_Post		$post	Wordpress post object
     */
    public function meta_box_html_tags($post)
    {
        $meta = $this->get_postmeta($post->ID);
        echo $this->twig->render('meta_box_html_tags.html', $meta);
    }

    /**
     * Define Meta Box HTML for Janey AI suggested tags.
     *
     * @since    0.0.1
     * @param 	WP_Post		$post	Wordpress post object
     */
    public function meta_box_html_pictures($post)
    {
        $meta = $this->get_postmeta($post->ID);
        $meta['nonce'] = wp_create_nonce('janey-ai-selected-picture-' . $post->ID . get_current_blog_id());
        echo $this->twig->render('meta_box_html_pictures.html', $meta);
    }

    /**
     * Extend thumbnail URL box.
     *
     * @since   0.0.1
     * @param 	string	$html		HTML of post thumbnail widget.
     * @param 	int		$post_id	ID of current post.
     */
    public function thumbnail_url_field($html, $post_id)
    {
        $meta = $this->get_postmeta($post_id);
        $img = $meta['selected_pic'];
        if (!empty($img)) {
            $img = $meta['pictures'][$img]['urls']['thumb'];
            return $this->twig->render('thumbnail_url_field.html', ['img' => $img]);
        }
        return $html;
    }

    // TOOLS VIEW

    /**
     * Register the settings page for the admin area.
     *
     * @since    0.0.1
     */
    public function register_settings_page()
    {
        // Create our settings page as a submenu page.
        add_submenu_page(
            'tools.php',                            // parent slug
            __('Janey AI', 'janey-ai'),      		// page title
            __('Janey AI', 'janey-ai'),      		// menu title
            'manage_options',                       // capability
            'janey-ai',                           	// menu_slug
            array( $this, 'display_settings_page' ) // callable function
        );
    }

    /**
     * Display the settings page content for the page we have created.
     *
     * @since    0.0.1
     */
    public function display_settings_page()
    {
        require_once plugin_dir_path(__FILE__) . 'partials/janey-ai-admin-display.php';
    }

    /**
     * Register the settings for our settings page.
     *
     * @since    0.0.1
     */
    public function register_settings()
    {
        register_setting(
            $this->plugin_name . '-settings',
            $this->plugin_name . '-settings'
        );

        add_settings_section(
            $this->plugin_name . '-settings-section',
            __('Settings', 'janey-ai'),
            array( $this, 'add_settings_section' ),
            $this->plugin_name . '-settings'
        );

        add_settings_field(
            'janey-ai-api-token',
            __('API Access Token', 'janey-ai'),
            array($this, 'add_setting_input_text'),
            $this->plugin_name . '-settings',
            $this->plugin_name . '-settings-section',
            array(
                'label_for' => 'janey-ai-api-token',
                'default'   => __('Enter your Janey AI API key.', 'janey-ai'),
                'input_type' => 'password'
            )
        );
        add_settings_field(
            'janey-ai-target-taxonomy',
            __('Target Taxonomy', 'janey-ai'),
            array($this, 'add_setting_select_taxonomies'),
            $this->plugin_name . '-settings',
            $this->plugin_name . '-settings-section',
            array(
                'label_for' => 'janey-ai-target-taxonomy',
                'default'   => __('post_tag', 'janey-ai')
            )
        );
        add_settings_field(
            'janey-ai-persist-analyze-enabled',
            __('Remember "Analyze on Save"', 'janey-ai'),
            array($this, 'add_setting_select_bool'),
            $this->plugin_name . '-settings',
            $this->plugin_name . '-settings-section',
            array(
                'label_for' => 'janey-ai-persist-analyze-enabled',
                'default'   => __('0', 'janey-ai')
            )
        );
        add_settings_field(
            'janey-ai-pictures-landscape-only',
            __('Only Landscape-oriented Pictures', 'janey-ai'),
            array($this, 'add_setting_select_bool'),
            $this->plugin_name . '-settings',
            $this->plugin_name . '-settings-section',
            array(
                'label_for' => 'janey-ai-pictures-landscape-only',
                'default'   => __('1', 'janey-ai')
            )
        );
    }

    /**
     * Settings section callback.
     *
     * @since    0.0.1
     */
    public function add_settings_section()
    {
        echo $this->twig->render('add_settings_section.html');
    }

    /**
     * Settings field input callback.
     *
     * @since    0.0.1
     * @param 	array		$args	Array of arguments from add_settings_field()
     */
    public function add_setting_input_text($args)
    {
        $field_id = $args['label_for'];
        $data = [
            'field_id' => $field_id,
            'field_name' => $this->plugin_name . '-settings[' . $field_id . ']',
            'field_type' => $args['input_type'],
            'value' => $args['default']
        ];

        $options = get_option($this->plugin_name . '-settings');
        if (!empty($options[$field_id])) {
            $data['value'] = $options[$field_id];
        }
        echo $this->twig->render('add_setting_input_text.html', $data);
    }

    /**
     * Settings field select taxonomies callback.
     *
     * @since    0.0.1
     * @param 	array		$args	Array of arguments from add_settings_field()
     */
    public function add_setting_select_taxonomies($args)
    {
        $field_id = $args['label_for'];
        $taxonomies = array_diff_key(get_object_taxonomies('post', 'object'), array_flip($this->exclude_taxonomies));
        $data = [
            'field_id' => $field_id,
            'field_name' => $this->plugin_name . '-settings[' . $field_id . ']',
            'value' => $args['default'],
            'taxonomies' => $taxonomies,
        ];

        $options = get_option($this->plugin_name . '-settings');
        if (!empty($options[$field_id])) {
            $data['value'] = $options[$field_id];
        }
        echo $this->twig->render('add_setting_select_taxonomies.html', $data);
    }

    /**
     * Settings field select boolean callback.
     *
     * @since    0.0.1
     * @param 	array		$args	Array of arguments from add_settings_field()
     */
    public function add_setting_select_bool($args)
    {
        $field_id = $args['label_for'];
        $data = [
            'field_id' => $field_id,
            'field_name' => $this->plugin_name . '-settings[' . $field_id . ']',
            'value' => $args['default'],
            'values' => ['1', '0']
        ];

        $options = get_option($this->plugin_name . '-settings');
        if (!empty($options[$field_id])) {
            $data['value'] = $options[$field_id];
        }
        echo $this->twig->render('add_setting_select_bool.html', $data);
    }

    // SDK

    /**
     * Analyze content for tags.
     *
     * @since    0.0.1
     * @param 	string		$content	Text content to analyze
     * @return 	mixed
     */
    private function analyse_tags($content = '', $post_id = null)
    {
        if (empty($content) || !is_string($content) || !is_numeric($post_id)) {
            return false;
        }
        $url = get_permalink($post_id);
        $response = $this->sdk->analyses->tags($content, $post_id, null, null, $url);
        if (method_exists($response, 'getStatusCode') && $response->getStatusCode() === 200) {
            $response_body = json_decode($response->getBody()->getContents(), true);
            // Successful response returns private data, not to be stored.
            if (is_array($response_body['data'])) {
                $data = $response_body['data'];
                foreach ($data as $k => $v) {
                    $v['slug'] = sanitize_title($v['text']);
                    $data[$k] = $v;
                }
                return $data;
            }
            return $response_body;
        }
        return false;
    }

    /**
     * Search for pictures.
     *
     * @since    0.0.1
     * @param 	string	$search	  Search terms
     * @return 	mixed
     */
    private function analyse_pictures($search = '', $post_id = null)
    {
        if (empty($search) || !is_string($search) || !is_numeric($post_id)) {
            return false;
        }
        $url = get_permalink($post_id);
        if ($this->get_option('janey-ai-pictures-landscape-only') == '1') {
            $response = $this->sdk->analyses->pictures($search, $post_id, $url, 1, 3, 'landscape');
        } else {
            $this->sdk->analyses->pictures($search, $post_id, $url);
        }
        if (method_exists($response, 'getStatusCode') && $response->getStatusCode() === 200) {
            $response_body = json_decode($response->getBody()->getContents(), true);
            // Successful response returns private data, not to be stored.
            if (is_array($response_body['data'])) {
                return $response_body['data'];
            }
            return $response_body;
        }
        return false;
    }

    // SAVE EVENT

    /**
     * Gateway method for save_post action.
     *
     * @since    0.0.1
     * @param 	int		$post_id	ID of post
     * @param 	WP_Post		$post	Wordpress post object
     */
    public function on_save_post($post_id, $post)
    {
        if (current_user_can('edit_post', $post_id)) {
            $post_meta = [
                'page_settings' => [],
                'tags' => [],
                'terms_added' => [],
                'api_errors' => [],
                'pictures' => [],
                'selected_pic' => '',
            ];
            $post_meta = array_merge($post_meta, $this->get_postmeta($post_id));

            // Override page settings
            $post_meta['page_settings'] = array_filter($_POST, function ($key) {
                return (strpos($key, $this->plugin_name . '-setting-') === 0);
            }, ARRAY_FILTER_USE_KEY);

            // Sync tags added to post
            $taxonomy = $this->get_option('janey-ai-target-taxonomy');
            $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'names']);
            $post_meta['terms_added'] = (is_array($_POST['janey-ai-tags'])) ? array_merge($terms, $_POST['janey-ai-tags']) : $terms;
            wp_set_post_terms($post_id, $post_meta['terms_added'], $taxonomy);

            // Janey AI metadata
            if ($post_meta['page_settings']['janey-ai-setting-enable']) {
                $content = $post->post_title . ' ' . $post->post_content;
                $result = $this->analyse_tags($content, $post_id);

                // @TODO: figure out a better check for successful result.
                if (is_array($result) && isset($result[0]['text'])) {
                    $post_meta['tags'] = $result;
                } else {
                    $post_meta['api_errors']['janey'] = $result;
                }
            }

            // Janey AI pictures
            $pic_setting = $post_meta['page_settings']['janey-ai-setting-picture'];
            if (!empty($pic_setting)) {
                switch ($pic_setting) {
                    case 'terms':
                        $unsplash_terms = implode(',', $post_meta['terms_added']);
                        break;
                    case 'relevance':
                    default:
                        $unsplash_terms = $post_meta['tags'][0]['text'];
                        break;
                }
                if (empty($unsplash_terms)) {
                    $post_meta['pictures'] = [];
                } else {
                    $unsplash_result = $this->analyse_pictures($unsplash_terms, $post_id);
                    // @TODO: figure out a better check for successful result.
                    if (is_array($unsplash_result) && !isset($unsplash_result['status'])) {
                        $post_meta['pictures'] = $unsplash_result;
                        if ($post_meta['selected_pic'] && !isset($post_meta['pictures'][$post_meta['selected_pic']])) {
                            $post_meta['selected_pic'] = '';
                            delete_post_thumbnail($post_id);
                        }
                    } else {
                        $post_meta['api_errors']['unsplash'] = $unsplash_result;
                    }
                }
            }

            // Sync external photo url
            $pic_action = 'janey-ai-selected-picture-' . $post_id . get_current_blog_id();
            $pic_nonce = filter_input(INPUT_POST, 'janey-ai-selected-picture-nonce', FILTER_SANITIZE_STRING );
            if (wp_verify_nonce($pic_nonce, $pic_action)) {
                $posted_pic = filter_input(INPUT_POST, 'janey-ai-selected-picture', FILTER_SANITIZE_STRING);
                if (!empty($posted_pic) && isset($post_meta['pictures'][$posted_pic])) {
                    $post_meta['selected_pic'] = $posted_pic;
                    $attachment_id = $this->get_attachment_id();
                    if (intval($attachment_id) < 2) {
                        // Attachment post_id should never be 1, or less
                        $attachment_id = media_sideload_image($this->image_placeholder, $post_id, '', 'id');
                    }
                    set_post_thumbnail($post_id, $attachment_id);
                } else {
                    $post_meta['selected_pic'] = '';
                    delete_post_thumbnail($post_id);
                }
            }
            $this->update_postmeta($post_id, $post_meta);
        }
    }

    /**
     * Update post metadata.
     *
     * @since    0.0.1
     * @param 	int		$post_id	ID of post
     * @param 	array	$data		Array of metadata to persist
     * @param 	string	$namespace	Optional namespace of metadata
     */
    private function update_postmeta($post_id, $data = [], $namespace = null)
    {
        if (!is_array($data)) {
            $data = [];
        }
        // Handle serialization issues
        $data = json_decode(json_encode($data), true);
        // Prep for storage
        if (is_string($namespace)) {
            $metadata = [$namespace => $data];
        } else {
            $metadata = $data;
        }
        update_metadata('post', $post_id, $this->plugin_name, $metadata);
    }

    // UTILITIES

    /**
     * Get a value from the plugin's options namespace.
     *
     * @since    0.0.1
     * @param 	string		$key		Key of option to fetch
     * @param 	mixed		$default	Default value if key is not set
     * @return 	mixed		$value
     */
    private function get_option($key, $default = false)
    {
        $value = $default;
        $options = get_option($this->plugin_name . '-settings');
        if (is_array($options) && isset($options[$key])) {
            $value = $options[$key];
        }
        return $value;
    }

    /**
     * Utility to get post meta plus plugin variables.
     *
     * @since    0.0.1
     * @param 	int		$post_id	ID of post
     * @return 	array 	$meta
     */
    private function get_postmeta($post_id)
    {
        $meta = get_post_meta($post_id, $this->plugin_name, true);
        if (!is_array($meta)) {
            $meta = [];
        }
        $meta['plugin_name'] = $this->plugin_name;
        // Override relevant plugin settings
        $meta['plugin_settings'] = [
            'janey-ai-persist-analyze-enabled' => $this->get_option('janey-ai-persist-analyze-enabled'),
            'janey-ai-pictures-landscape-only' => $this->get_option('janey-ai-pictures-landscape-only'),
        ];
        return $meta;
    }

    /**
     * Compares the version of WordPress running to the $version specified.
     *
     * @since    0.0.1
     * @param 	string 		$operator	Operator for version_compare()
     * @param 	string 		$version	Version number to check
     * @return 	boolean
     */
    public function is_version($version = '5.0', $operator = '>')
    {
        global $wp_version;
        return version_compare($wp_version, $version, $operator);
    }

    /**
     * Detect if classic-editor plugin is activated.
     *
     * @since    0.0.1
     * @return boolean
     */
    public function is_classic()
    {
        return is_plugin_active('classic-editor/classic-editor.php');
    }

    /**
     * Detect if url is for image file.
     *
     * @since    0.0.1
     * @return boolean
     */
    public function url_is_image($url)
    {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) return false;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Range: bytes=0-32768']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        return (imagecreatefromstring($data) === false) ? false : true;
    }

    /**
     * Get an attachment ID based on image placeholder.
     *
     * @since    0.0.1
     * @return 	int
     */
    private function get_attachment_id()
    {
        global $wpdb;
        $filename = basename($this->image_placeholder);
        return $wpdb->get_var($wpdb->remove_placeholder_escape($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid LIKE %s", '%' . $filename)));
    }
}
