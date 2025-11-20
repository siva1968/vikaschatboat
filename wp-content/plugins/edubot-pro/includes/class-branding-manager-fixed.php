<?php

/**
 * EduBot Branding Manager
 * Handles school branding, themes, and visual customization
 */
class EduBot_Branding_Manager {

    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * School configuration instance
     */
    private $school_config;

    /**
     * Color scheme cache
     */
    private static $color_cache = null;

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor (private for singleton)
     */
    private function __construct() {
        $this->school_config = EduBot_School_Config::getInstance();
    }

    /**
     * Get color scheme with caching to prevent loops
     */
    public function get_color_scheme() {
        // Return cached colors if available
        if (self::$color_cache !== null) {
            return self::$color_cache;
        }
        
        $config = $this->school_config->get_config();
        
        $primary = isset($config['school_info']['colors']['primary']) ? $config['school_info']['colors']['primary'] : get_option('edubot_primary_color', '#4facfe');
        $secondary = isset($config['school_info']['colors']['secondary']) ? $config['school_info']['colors']['secondary'] : get_option('edubot_secondary_color', '#00f2fe');
        
        self::$color_cache = array(
            'primary' => $primary,
            'secondary' => $secondary,
            'gradient' => sprintf('linear-gradient(135deg, %s 0%%, %s 100%%)', $primary, $secondary)
        );
        
        return self::$color_cache;
    }

    /**
     * Get gradient CSS without circular dependency
     */
    public function get_gradient_css() {
        $colors = $this->get_color_scheme();
        return $colors['gradient'];
    }

    /**
     * Generate custom CSS for branding
     */
    public function generate_custom_css() {
        $colors = $this->get_color_scheme();
        $config = $this->school_config->get_config();
        
        $logo_url = isset($config['school_info']['logo']) ? $config['school_info']['logo'] : get_option('edubot_school_logo', '');
        
        $css = "
        :root {
            --edubot-primary-color: {$colors['primary']};
            --edubot-secondary-color: {$colors['secondary']};
            --edubot-gradient: {$colors['gradient']};
        }
        
        .edubot-chatbot-widget {
            --edubot-primary-color: {$colors['primary']};
            --edubot-secondary-color: {$colors['secondary']};
            --edubot-gradient: {$colors['gradient']};
        }
        ";
        
        return $css;
    }

    /**
     * Get logo HTML (simplified version)
     */
    public function get_logo_html($size = 'medium') {
        $config = $this->school_config->get_config();
        $logo_url = isset($config['school_info']['logo']) ? $config['school_info']['logo'] : get_option('edubot_school_logo', '');
        
        if (empty($logo_url)) {
            return '';
        }
        
        $dimensions = array(
            'small' => 'max-height: 30px; max-width: 40px;',
            'medium' => 'max-height: 50px; max-width: 80px;',
            'large' => 'max-height: 100px; max-width: 150px;'
        );
        
        $style = isset($dimensions[$size]) ? $dimensions[$size] : $dimensions['medium'];
        $school_name = isset($config['school_info']['name']) ? $config['school_info']['name'] : 'School';
        
        return sprintf(
            '<img src="%s" alt="%s" style="%s object-fit: contain;">',
            esc_url($logo_url),
            esc_attr($school_name),
            $style
        );
    }

    /**
     * Clear color cache (useful for debugging)
     */
    public static function clear_cache() {
        self::$color_cache = null;
    }
}
