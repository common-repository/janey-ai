<?php
/**
* @link https://janey.ai/
* @since 0.0.1
* @package Janey_AI
*
* @wordpress-plugin
* Plugin Name:  Janey AI
* Plugin URI:   https://janey.ai
* Description:  AI for content creators
* Version:      0.0.1
* Author:       sepiariver
* Author URI:   https://sepiariver.com/
* License:      GPL-2.0+
* License URI:  https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain:       janey-ai
* Domain Path:       /languages
*
* Janey AI is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 2 of the License, or
* any later version.
*
* Janey AI is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Janey AI. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
define('JANEY_AI_VERSION', '0.1.1');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-janey-ai.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.0.1
 */
function run_janey_ai()
{
    $plugin = new Janey_AI();
    $plugin->run();
}
run_janey_ai();
