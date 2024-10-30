<?php
/**
 * Provide a admin area view for the plugin
 *
 * @link https://janey.ai/
 * @since 0.0.1
 * @package Janey_AI
 * @subpackage Janey_AI/admin/partials
 * @author sepiariver
 */
?>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div id="wrap">
	<form method="post" action="options.php">
		<?php
            settings_fields('janey-ai-settings');
            do_settings_sections('janey-ai-settings');
            submit_button();
        ?>
	</form>
</div>
