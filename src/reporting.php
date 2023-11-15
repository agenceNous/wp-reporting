<?php
/**
 * WP Reporting
 * @package WPReporting
 * @version 1.6.0
 */

namespace WPReporting;

if(!class_exists('WPReporting\Reporting')) {
    class WP_Reporting{
        var $projects;
        var $settings;
        var $categories;
        private $levels;
        private $context_levels;
        private $sensitive_keys;
        private $current_project;

        public function __construct() {
            $this->projects = [];

            $this->categories = [
                'main' => 'Main',
                'plugin' => 'Plugins',
                'theme' => 'Themes',
            ];
            
            $this->levels = apply_filters('wp-reporting:levels', [
                0 => 'No',
                1 => 'Yes',
            ]);
            
            $this->context_levels = apply_filters('wp-reporting:context_levels', [
                0 => 'No context',
                1 => 'Minimal (server environment)',
                2 => 'Accurate (URL + Version of WordPress, Plugins and Theme)',
                3 => 'Full (anonymized POST data)',
            ]);
            
           $this->sensitive_keys = apply_filters('wp-reporting:sensitive-keys', [
                '/pass/',
                '/mail/',
                '/address/',
            ]);         
            

            require_once __DIR__.'/settings.php';
            $this->settings = new Settings();

            add_action('wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts'));
            add_action('wp_ajax_wpreporting_logerror', array(&$this, 'ajax_log_error'));
            add_action('wp_ajax_nopriv_wpreporting_logerror', array(&$this, 'ajax_log_error'));
        }

        /**
         * Register a project
         * @param string $project_name
         * @param array $params
         * @return WP_Reporting
         */
        public function register(string $project_name, array $params) : WP_Reporting{
            $params = wp_parse_args( $params, [
                'to' => null,
                'name' => $project_name,
                'label' => $project_name,
                'description' => null,
                'prefix' => $project_name,
                'only_in_dir' => null,
                'default_enabled' => false,
                'category' => 'main',
                'max_level' => 1,
                'trace_in_logs' => false,
                'javascript' => false,
            ] );

            if(!isset($this->categories[$params['category']])){
                $params['category'] = 'main';
            }

            // Allows to pass base64_encode email addresses, in order to prevent from spaming by exposing them in the code
            if($params['to'] && !strstr($params['to'], '@')){
                $params['to'] = base64_decode($params['to']);
            }
            // Ensure email address is correct
            if(filter_var($params['to'], FILTER_VALIDATE_EMAIL) === false){
                $params['to'] = get_option('admin_email');
            }

            $params['enabled'] = $this->settings->Get($project_name, $params['default_enabled']);
            
            $params['context_level'] = $this->settings->Get("{$project_name}_context", array_keys($this->context_levels)[0]);

            $this->projects[$project_name] = apply_filters('wp-reporting:project:register', $params, $project_name);
            return $this;
        }

        public function load_scripts(){
            wp_register_script('wp-reporting', plugins_url( 'wp-reporting.js', __FILE__), array('jquery', 'wp-util'), $this->get_version());
            wp_enqueue_script('wp-reporting');
            wp_add_inline_script('wp-reporting', 'var wp_reporting='.json_encode(
                [
                    'nonce' => wp_create_nonce('wp-reporting-logerror'),
                ]
            ), 'before');
        }
        
        public function wp_enqueue_scripts(){
            foreach($this->projects as $project_name => $project){
                if($project['javascript']){
                    $this->load_scripts();
                    break;
                }
            }

        }

        /**
         * Get all categories
         * @return array
         */
        public function get_categories() : array {
            return $this->categories;
        }
        
        public function get_levels() : array {
            return $this->levels;
        }
        
        public function get_context_levels() : array {
            return $this->context_levels;
        }
        
        /**
         * Get all projects
         * @return array
         */
        public function get_projects() : array {
            return $this->projects;
        }

        /**
         * Get a project
         * @param string $project_name
         * @return array|null
         */
        public function get_project(string $project_name){
            return (isset($this->projects[$project_name]) ? $this->projects[$project_name] : null);
        }
        
        private function anonymize(array $array) : array{
            
            foreach($array as $key => $value){
                if(is_array($value)){
                     $array[$key] = $this->anonymize($value);
                }
                elseif(is_object($value)){
                     $array[$key] = $this->anonymize((array) $value);
                }
                
                // Remove sensitive data
                foreach($this->sensitive_keys as $regex){
                    if(preg_match($regex, $key)){
                        $array[$key] = 'xxxxxxx';
                    }
                }
            }
            return $array;  
        }
        
        private function wrap_data(string $title, array $data, bool $open=false) : string {
            $data = json_encode($data, JSON_PRETTY_PRINT);
            return "\n\n<details".($open ? "open='open'" : "")."><summary><h2>{$title}</h2></summary>\n<pre>```\n{$data}\n```</pre></details>\n\n";
        }
        
        private function get_context_server() : array {
            return $_SERVER;
        }
        
        private function get_context_wp() : array {
            if ( ! function_exists( 'update' ) ) {
                require_once ABSPATH . 'wp-admin/includes/update.php';
                require_once ABSPATH . 'wp-admin/includes/misc.php';
            }
            if ( ! class_exists( 'WP_Debug_Data' ) ) {
                require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
            }
            $data = \WP_Debug_Data::debug_data();

            return $data;
        }
        
        private function get_context_input() : array {
            $data = [
                'GET' => $this->anonymize($_GET),
                'POST' => $this->anonymize($_POST),
            ];
            return $data;
        }

        /**
         * Get current project
         */
        public function get_current_project(){
            return $this->current_project;
        }

        /**
         * Set current project
         * @var string $project_name
         */
        public function set_current_project(string $project_name){
            $this->current_project = $project_name;
            return $this;
        }

        /**
         * Send a report
         * @param Exception $exception
         * @param string $project_name
         * @param bool $skip_dir_check
         * @param array $trace
         * @return bool
         */
        public function send($exception, string $project_name, $skip_dir_check=false, $trace=null) : bool {
            
            // Get project
            $project = $this->get_project($project_name);
            if(null === $project){
                error_log(sprintf('[WP-Report]: Try to send report on unfound project: "%s"', $project_name));
                return false;
            }

            if($skip_dir_check === false && isset($project['only_in_dir'])){
                $error_file = $exception->getFile();
                // Check if if file is in directory
                if(!strstr($error_file, $project['only_in_dir'])){
                    return false;
                }
            }
            
            $enabled = $project['enabled'];
            $context_level = $project['context_level'];
            
            
            // Get recipient
            $to = $project['to'];
            $to = apply_filters('wp-reporting:send:to', $to);
            
            // Get subject
            $prefix = $project['prefix'];
            $subject_prefix = apply_filters('wp-reporting:send:subject_prefix', sprintf('[%s]', $prefix), $exception, $project);
            $subject = apply_filters('wp-reporting:send:subject', $subject_prefix.' '.$exception->getMessage(), $exception, $project);
            
            // Get message
            $stack = apply_filters('wp-reporting:send:stack', $exception->getTrace());
            $trace = $trace ? $trace : debug_backtrace();
            // Cleanup first items if it was listened
            if(isset($trace[0]['class']) && $trace[0]['class'] === 'WPReporting\\WP_Reporting'){
                array_shift($trace);
            }
            if(isset($trace[0]['class']) && $trace[0]['class'] === 'WPReporting\\WP_Reporting'){
                array_shift($trace);
            }

            if(defined('WP_DEBUG') && WP_DEBUG){
                if(defined('WP_DEBUG_LOG') && WP_DEBUG_LOG){
                    $error_location = sprintf('%s:%s', $exception->getFile(), $exception->getLine());
                    error_log("[WP-Report]: {$subject}\t{$error_location}".($project['trace_in_logs'] ? "\t".json_encode($stack) : ''));
                }
            }
            if(!$enabled){
                return false;
            }

            $message = \apply_filters('wp-reporting:send:message', '<h1>'.sprintf('Error in %s', get_option('blogname')).'</h1>'."\n".'<p>'.sprintf('<code>%s</code> in <em>%s</em> at line <strong>%s</strong>.', $exception->getMessage(), $exception->getFile(), $exception->getLine()).'</p>');
            $body = $message;
            $body.=str_replace(ABSPATH, '', $this->wrap_data("Stack", $stack, true));
            
            // Add data for 1rst context level
            if($context_level > 0){
                $body.=$this->wrap_data("Server", $this->get_context_server());
            }
            
            if($context_level > 1){
                $body.=$this->wrap_data("WordPress", $this->get_context_wp());                
            }
            
            if($context_level > 2){
                $body.=$this->wrap_data("Input", $this->get_context_input());
            }
            
            // Reduce trace, because too much data causes error
            $trace = array_slice($trace, 0, 10);
            $body.=str_replace(ABSPATH, '', $this->wrap_data("Trace", $trace));

            $body = \apply_filters('wp-reporting:send:body', $body, $exception, $project);

            // Send report by mail
            if(function_exists('wp_mail')){
                $mail = \wp_mail($to, $subject, $body, 'Content-Type: text/html; charset=UTF-8');
            }
            else{
                $mail = mail($to, $subject, $body, 'Content-Type: text/html; charset=UTF-8');
            }

            return $mail;
        }

        /**
         * Start error listening
         * Set error handler to catch errors
         * @param $level E_WARNING
         */
        public function listen(string $project, $level = E_WARNING){
            $this->set_current_project($project);
            set_error_handler(function($errno, $errstr, $errfile, $errline) {
                // error was suppressed with the @-operator
                if (0 === \error_reporting()) {
                    return false;
                }

                $this->send(new \ErrorException($errstr, 0, $errno, $errfile, $errline), WPReporting()->get_current_project());
                return false;
            }, $level);
        }

        /**
         * Stop error listening
         */
        public function stop(){
            restore_error_handler();
        }

        /**
         * Log an error
         * sent over ajax
         */
        public function ajax_log_error(){
            // check nonce
            if(!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp-reporting-logerror')){
                wp_send_json_error('Invalid nonce');
                exit;
            }

            if(isset($_POST['project']) && isset($_POST['error'])){
                $project = $_POST['project'];
                $error = $_POST['error'];
                if(!isset($error['message']) || !isset($error['stack']) || !isset($error['file']) || !isset($error['line'])){
                    wp_send_json_error('Invalid error');
                    exit;
                }
                $err = new \ErrorException($error['message'], 0, E_ERROR, $error['file'], $error['line']);
                $trace = explode("\n", $error['stack']);
                $sent = $this->send($err, $project, true, $trace);
                if($sent){
                    wp_send_json_success('Error sent');
                    exit;
                }
                wp_send_json_error('Error not sent');
                exit;
            }
        }

        /**
         * Get the plugin version
         */
        public function get_version(){
            $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'));
            return $composer->version;
        }
    }
}