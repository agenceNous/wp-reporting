<?php

namespace WPReporting;

if(!class_exists('WPReporting\Reporting')) {
    class WP_Reporting{
        var $projects;
        var $settings;
        var $categories;

        public function __construct() {
            $this->projects = [];

            $this->categories = [
                'main' => 'Main',
                'plugin' => 'Plugins',
                'theme' => 'Themes',
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
        public function get_project(string $project_name) : array | null{
            return (isset($this->projects[$project_name]) ? $this->projects[$project_name] : null);
        }

        /**
         * Send a report
         * @param Exception $exception
         * @param string $project_name
         * @return bool
         */
        public function send(\Throwable|\Exception $exception, string $project_name) : bool {

            // Get project
            $project = $this->get_project($project_name);
            if(null === $project){
                error_log(sprintf('Try to send report on unfound project: "%s"', $project_name));
                return false;
            }
            
            $enabled = $this->settings->get($project_name);
            
            
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
            if(!$enabled){
                return false;
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