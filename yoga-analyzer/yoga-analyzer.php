<?php
/**
 * Plugin Name: YOGA — YouTube Opportunity & Growth Analyzer
 * Description: Free YouTube video opportunity analyzer for BestYouTubeViews. Includes public-data checks, restrictions, embeddability, channel context, lead capture and exportable action plans.
 * Version: 0.1.2
 * Author: BestYouTubeViews
 * Text Domain: yoga-analyzer
 */

if (!defined('ABSPATH')) {
    exit;
}

define('YOGA_ANALYZER_VERSION', '0.1.2');
define('YOGA_ANALYZER_FILE', __FILE__);
define('YOGA_ANALYZER_DIR', plugin_dir_path(__FILE__));
define('YOGA_ANALYZER_URL', plugin_dir_url(__FILE__));

require_once YOGA_ANALYZER_DIR . 'includes/class-yoga-analyzer.php';

register_activation_hook(__FILE__, array('YOGA_Analyzer', 'activate'));

add_action('plugins_loaded', static function () {
    YOGA_Analyzer::instance();
});
