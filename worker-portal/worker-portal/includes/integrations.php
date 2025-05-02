<?php
/**
 * Integraciones con otros plugins
 *
 * @since      1.1.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar las integraciones con otros plugins
 */
class Worker_Portal_Integrations {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Añadir hooks para las integraciones
        add_action('plugins_loaded', array($this, 'init_integrations'));
    }
    
    /**
     * Inicializar integraciones con otros plugins
     */
    public function init_integrations() {
        // Comprobar si los plugins están activos
        $this->init_staff_calendar_integration();
        $this->init_wp_time_clock_integration();
    }
    
    /**
     * Inicializar integración con Staff Calendar
     */
    private function init_staff_calendar_integration() {
        // Comprobar si el plugin de calendario está activo
        if (shortcode_exists('staff_calendar')) {
            // Añadir filtros y acciones para la integración
            add_filter('staff_calendar_can_view', array($this, 'staff_calendar_permissions'), 10, 2);
            
            // Permitir que los usuarios del portal puedan ver el calendario
            add_action('init', array($this, 'add_staff_calendar_caps_to_portal_users'));
        }
    }
    
    /**
     * Inicializar integración con WP Time Clock
     */
    private function init_wp_time_clock_integration() {
        // Comprobar si el plugin de fichaje está activo
        if (shortcode_exists('wp_time_clock')) {
            // Añadir filtros y acciones para la integración
            add_filter('wp_time_clock_user_can_view', array($this, 'wp_time_clock_permissions'), 10, 2);
            
            // Permitir que los usuarios del portal puedan usar el fichaje
            add_action('init', array($this, 'add_wp_time_clock_caps_to_portal_users'));
        }
    }
    
    /**
     * Filtrar permisos para ver el calendario
     */
    public function staff_calendar_permissions($can_view, $user_id) {
        // Permitir a todos los usuarios ver el calendario en el portal
        if (is_user_logged_in() && $user_id === get_current_user_id()) {
            return true;
        }
        
        return $can_view;
    }
    
    /**
     * Filtrar permisos para usar el fichaje
     */
    public function wp_time_clock_permissions($can_view, $user_id) {
        // Permitir a todos los usuarios usar el fichaje en el portal
        if (is_user_logged_in() && $user_id === get_current_user_id()) {
            return true;
        }
        
        return $can_view;
    }
    
    /**
     * Añadir capacidades para el calendario a los usuarios del portal
     */
    public function add_staff_calendar_caps_to_portal_users() {
        // Obtener roles que deberían tener acceso
        $roles = array('subscriber', 'contributor', 'author', 'editor');
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                // Añadir capacidad para ver el calendario
                $role->add_cap('view_staff_calendar');
            }
        }
    }
    
    /**
     * Añadir capacidades para el fichaje a los usuarios del portal
     */
    public function add_wp_time_clock_caps_to_portal_users() {
        // Obtener roles que deberían tener acceso
        $roles = array('subscriber', 'contributor', 'author', 'editor');
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                // Añadir capacidad para usar el fichaje
                $role->add_cap('use_wp_time_clock');
            }
        }
    }
}

// Inicializar la clase de integraciones
new Worker_Portal_Integrations();