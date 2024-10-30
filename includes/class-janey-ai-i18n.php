<?php

/**
 * Define the internationalization functionality
 *
 * @link https://janey.ai/
 * @since 0.0.1
 * @package Janey_AI
 * @subpackage Janey_AI/includes
 * @author sepiariver
 */
 
class Janey_AI_i18n
{
    
    /**
     * Load the plugin text domain for translation.
     *
     * @since    0.0.1
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'janey-ai',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
