<?php
/**
 * WP Reporting
 * @package WPReporting
 * @version 1.6.0
 */

namespace WPReporting;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists('WPReporting\Settings')) {
    class Settings{
        var $option_name = 'wp_reporting';
        var $options;
        var $defaults;
        var $mu = false;
        var $not_set_projects = [];
        var $setting_url;

        function __construct(){
            require_once(ABSPATH.'/wp-admin/includes/plugin.php');
            $this->mu = (defined('WP_REPORTING_NETWORK') && WP_REPORTING_NETWORK);
            add_action(($this->mu ? 'network_' : '').'admin_menu', array(&$this, 'admin_menu'));
            add_action('admin_init', array(&$this, 'admin_init'));
            add_action('admin_post_update', array(&$this, 'update_network_settings') );
            add_action( 'admin_notices', array(&$this, 'notices'));

            $this->defaults = [];

            $this->setting_url = add_query_arg(
                [
                    'page' => 'wp-reporting-settings',
                ],
                admin_url( $this->mu ? 'network/settings.php' : 'options-general.php' )
            );
        }

        public function get_option($option){
            $func = ($this->mu ? 'get_site_option' : 'get_option');
            return $func($option);
        }

        public function update_option($option, $value){
            $func = ($this->mu ? 'update_site_option' : 'update_option');
            return $func($option, $value);
        }

        public function Options(){
            if(!$this->options){
                $this->options = wp_parse_args((array) $this->get_option($this->option_name), $this->defaults);
            }
            return $this->options;
        }

        public function Get($setting, $default=false){
            return (isset($this->Options()[$setting]) ? $this->Options()[$setting] : $default);
        }

        public function admin_menu() {
            add_submenu_page($this->mu ? 'settings.php' : 'options-general.php', __('Error Report'), __('Error Report'), 'manage_options', 'wp-reporting-settings', array(&$this, 'manage_settings'));
        }

        public function admin_init() {
            $categories = WPReporting()->get_categories();
            $displayed_categories = [];
            register_setting('wp-reporting-settings', $this->option_name);

            foreach(WPReporting()->get_projects() as $project_name => $project){
                // Populate defaults from registered projects
                $this->defaults[$project_name] = $project['default_enabled'];

                // Populate section, depending on usefull categories
                if(!isset($displayed_categories[$project['category']])){
                    $displayed_categories[$project['category']] = $project['category'];
                    add_settings_section(
                          'wp-reporting-settings-'.$project['category'], 
                          __($categories[$project['category']]), 
                          array(&$this, 'settings_section_callback'), 
                          'wp-reporting-settings'
                    );
                }

                // Add setting field
                add_settings_field(
                        $this->option_name.'_'.$project_name,
                        $project['label'],
                        array(&$this, 'settings_field_enable_callback'),
                        'wp-reporting-settings',
                        'wp-reporting-settings-'.$project['category'],
                        $project
                );

                // Show notice if project is not set
                if(false === $this->Get($project_name)){
                    $this->not_set_projects[] = $project;
                }
                
            }
        }

        function notices(){
            if(count($this->not_set_projects)){
                echo '<div class="notice notice-warning is-dismissible">
                    <p>'.__( 'Missing settings, please tell us if you agree to send reports.').'</p>
                    <p><a href="'.$this->setting_url.'">'.__('Settings').'</a></p>
                </div>';
            }
        }

        public function update_network_settings(){
            if(!$this->mu){
                return;
            }
            if(!isset($_POST[$this->option_name])){
                return;
            }

            if (!wp_verify_nonce(\filter_input(INPUT_POST, 'wp-reporting-settings'), 'wp-reporting-settings')) {
                wp_die(__('Sorry, you are not allowed to do that.'));
            }
            $input_args = [];
            foreach(WPReporting()->get_projects() as $project_name => $project){
                $input_args[$project_name] = FILTER_SANITIZE_INT;
                $input_args[$project_name.'_context'] = FILTER_SANITIZE_INT;
            }
            $settings = filter_input_array(INPUT_POST, $input_args);
            $update = $this->update_option(  $this->option_name, $settings);
            wp_redirect(
                add_query_arg(
                    [
                        'confirm' => $update,
                    ],
                    $this->setting_url
                )
            );
            exit;
        }

        public function manage_settings() {
            ?>
            <div class="wrap">
                <h2>
                    <?php _e('Error Report'); ?>
                    <small><?php echo WPReporting()->get_version(); ?></small>
                </h2>
                <form action="<?php echo admin_url($this->mu ? 'admin-post.php' : 'options.php'); ?>" method="post">
                    <input type="hidden" name="<?php esc_attr_e($this->option_name); ?>[_setting]" value="1" />
                    <?php wp_nonce_field('wp-reporting-settings', 'wp-reporting-settings'); ?>
                    <?php settings_fields('wp-reporting-settings'); ?>
                    <?php do_settings_sections('wp-reporting-settings'); ?>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

        public function settings_section_callback($arg) {

        }

        public function settings_field_enable_callback($args) {
            ?>
        <div class="wp-reporting-project">
            
            <div class="wp-reporting-project-levels">
            <?php foreach(\WPReporting()->get_levels() as $level => $label): ?>
                <div class="wp-reporting-level">
            <?php $level = (int) $level; ?>
                    <input type="radio" name="<?php esc_attr_e($this->option_name); ?>[<?php esc_attr_e($args['name']); ?>]" id="<?php esc_attr_e($args['name']); ?>-<?php esc_attr_e($level); ?>" value="<?php esc_attr_e($level); ?>" <?php checked( $args['enabled'], $level); ?>/>
                    <label for="<?php esc_attr_e($args['name']); ?>-<?php esc_attr_e($level); ?>">
                        <?php _e($label); ?>
                    </label>
                </div>
            <?php endforeach; ?>
            </div>

            <blockquote class="wp-reporting-project-context-levels">
                <h5><?php _e('Context'); ?></h5>
            <?php foreach(\WPReporting()->get_context_levels() as $level => $label): ?>
                <div class="wp-reporting-context-level">
            <?php $level = (int) $level; ?>
                    <input type="radio" name="<?php esc_attr_e($this->option_name); ?>[<?php esc_attr_e($args['name']); ?>_context]" id="<?php esc_attr_e($args['name']); ?>-context-<?php esc_attr_e($level); ?>" value="<?php esc_attr_e($level); ?>" <?php checked( $args['context_level'], $level); ?>/>
                    <label for="<?php esc_attr_e($args['name']); ?>-context-<?php esc_attr_e($level); ?>">
                        <?php _e($label); ?>
                    </label>
                </div>
            <?php endforeach; ?>
            </blockquote>
            <p>
                <?php if (isset($args['description']) && $args['description']): ?>
                    <span class="wp-reporting-desc">
                    <?php esc_html_e($args['description']); ?>
                    </span>
                <?php endif; ?>
                <div class="wp-reportgin-to">
                   »
                  <?php esc_html_e($args['to']); ?>
                </div>
            </p>
        </div>
            <?php
        }
    }
}