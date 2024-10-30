<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link https://janey.ai/
 * @since 0.0.1
 * @package Janey_AI
 * @subpackage Janey_AI/public
 * @author sepiariver
 */
class Janey_AI_Public
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
        $this->twig_loader = new \Twig\Loader\FilesystemLoader(plugin_dir_path(__FILE__) . 'templates');
        $this->twig = new \Twig\Environment($this->twig_loader);
        $this->twig_cached = new \Twig\Environment($this->twig_loader, ['cache' => plugin_dir_path(__FILE__) . 'cache']);
    }



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
     * Replace thumbnail image src.
     *
     * @since    0.0.1
     * @return boolean
     */
    public function thumbnail_external_replace($html, $post_id)
    {
        $meta = $this->get_postmeta($post_id);
        $pic_id = $meta['selected_pic'];
        if (!empty($pic_id)) {
            $pic = $meta['pictures'][$pic_id];
            $doc = DOMDocument::loadHTML($html);
            if ($doc) {
                $tags = $doc->getElementsByTagName('img');
                if (count($tags) > 0) {
                   $tag = $tags->item(0);
                   $tag->setAttribute('src', $pic['urls']['regular']);
                   $tag->setAttribute('width', '1080');
                   $pic_height = $pic['height'] / $pic['width'] * 1080;
                   $tag->setAttribute('height', $pic_height);
                   $srcset = [
                       $pic['urls']['regular'] . ' 1080w',
                       $pic['urls']['small'] . ' 400w',
                       $pic['urls']['thumb'] . ' 200w',
                   ];
                   $tag->setAttribute('srcset', implode(', ', $srcset));
                   $tag->setAttribute('alt', 'Photo from Unsplash by ' . $pic['user']['name']);
                   $tag->setAttribute('title', 'Photo from Unsplash by ' . $pic['user']['name']);
                   return $doc->saveHTML($tag);
               }
            }
        }
        return $html;
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
}
