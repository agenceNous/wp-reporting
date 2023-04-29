<?php

namespace WPReporting;

if(!class_exists('WPReporting\Reporting')) {
    class WP_Reporting{
        var $projects;
        var $settings;
        var $categories;
        var $levels;
        var $context_levels;

        public function __construct() {
            $this->projects = [];

            $this->categories = [
                'main' => 'Main',
                'plugin' => 'Plugins',
                'theme' => 'Themes',
            ];
            
            $this->levels = [
                0 => 'Disabled',
                1 => 'Error',
                2 => 'Warning',
            ];
            
            $this->context_levels = [
                0 => 'No context',
                1 => 'Minimal (server environment)',
                2 => 'Accurate (URL + Version of WordPress, Plugins and Theme)',
                3 => 'Full (anonymized POST data)',
            ];         
            
            require_once __DIR__.'/settings.php';
            $this->settings = new Settings();
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
                'default_enabled' => false,
                'category' => 'main',
                'max_level' => 1,
            ] );

            if(!isset($this->categories[$params['category']])){
                $params['category'] = 'main';
            }

            // Allows to pass base64_encode email addresses, in order to prevent from spaming by exposing them in the code
            if(!strstr($params['to'], '@') && $params['to']!=''){
                $params['to'] = base64_decode($params['to']);
            }
            // Ensure email address is correct
            if(filter_var($params['to'], FILTER_VALIDATE_EMAIL) === false){
                $params['to'] = get_option('admin_email');
            }

            $params['enabled'] = $this->settings->Get($project_name);
            
            $params['levels'] = array_slice($this->get_levels(), 0, $params['max_level']);
            
            $params['context_levels'] = $this->get_context_levels();

            $this->projects[$project_name] = apply_filters('wp-reporting:project:register', $params, $project_name);
            return $this;
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
        
        private function wrap_data($data){
            return json_encode($data, JSON_PRETTY_PRINT);
        }
        
        private function get_context_server(){
            return $thks->wrap_data($server_json);
        }

        /**
         * Send a report
         * @param Exception $exception
         * @param string $project_name
         * @return bool
         */
        public function send($exception, string $project_name) : bool {

            // Get project
            $project = $this->get_project($project_name);
            if(null === $project){
                error_log(sprintf('Try to send report on unfound project: "%s"', $project_name));
                return false;
            }
            
            $level = $this->settings->get($project_name);
            $context_level = $this->settings->get($project_name.'_context');
            
            
            // Get recipient
            $to = $project['to'];
            $to = apply_filters('wp-reporting:send:to', $to);
            
            // Get subject
            $prefix = $project['prefix'];
            $subject_prefix = apply_filters('wp-reporting:send:subject_prefix', sprintf('[%s]', $prefix), $exception, $project);
            $subject = apply_filters('wp-reporting:send:subject', $subject_prefix.' '.$exception->getMessage(), $exception, $project);
            
            // Get message
            $stack = apply_filters('wp-reporting:send:stack', $exception->getTrace());
            $json = json_encode($stack, JSON_PRETTY_PRINT);
            $message = \apply_filters('wp-reporting:send:message', sprintf('Error in %s', get_option('blog_name')));
            $body = $message."\n\n".$json;
            $body = \apply_filters('wp-reporting:send:body', $body);
            
            if(defined('WP_DEBUG') && WP_DEBUG){
                if(defined('WP_DEBUG_LOG') && WP_DEBUG_LOG){
                    error_log("Fatal Error ".$subject."\t".json_encode($stack));
                }
            }
            if(!$level{
                return false;
            }
            
            // Add data for 1rst context level
            if($context_level > 0){
                $body.="\n\nServer:\n".$this->get_context_server();
            }

            // Send report by mail
            if(function_exists('wp_mail')){
                $mail = \wp_mail($to, $subject, $body);
            }
            else{
                $mail = mail($to, $subject, $body);
            }

            return $mail;
        }
    }
}