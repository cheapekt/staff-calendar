<?php
/**
 * Módulo de trabajadores para el Portal del Trabajador
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del módulo de trabajadores
 */
class Worker_Portal_Module_Workers {

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Cargar dependencias
        $this->load_dependencies();
    }

    /**
     * Carga las dependencias del módulo
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Cargar clase de AJAX handler
        require_once plugin_dir_path(dirname(__FILE__)) . 'workers/workers-ajax-handler.php';
        new Worker_Portal_Worker_Ajax_Handler();
    }

    /**
     * Inicializa el módulo
     *
     * @since    1.0.0
     */
    public function init() {
        // Registrar estilos y scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Registrar shortcodes
       add_shortcode('worker_profile', array($this, 'render_profile_shortcode'));
        add_shortcode('worker_admin_panel', array($this, 'render_admin_panel_shortcode')); 
        
        // Añadir panel de administración
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_pages'));
        }
        
        // Añadir hooks de inicio de sesión
        add_action('wp_login', array($this, 'record_user_login'), 10, 2);
    }

    /**
     * Carga los estilos del módulo
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Cargar estilos solo si estamos en la página del portal
        if (is_page('portal-del-trabajador') || has_shortcode(get_the_content(), 'worker_portal') || has_shortcode(get_the_content(), 'worker_profile')) {
            wp_enqueue_style(
                'worker-portal-workers',
                plugin_dir_url(__FILE__) . 'css/workers.css',
                array(),
                WORKER_PORTAL_VERSION,
                'all'
            );
        }
    }

    /**
     * Carga los scripts del módulo
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Cargar scripts solo si estamos en la página del portal
        if (is_page('portal-del-trabajador') || has_shortcode(get_the_content(), 'worker_portal') || has_shortcode(get_the_content(), 'worker_profile')) {
            wp_enqueue_script(
                'worker-portal-workers',
                plugin_dir_url(__FILE__) . 'js/workers.js',
                array('jquery'),
                WORKER_PORTAL_VERSION,
                true
            );
            
            // Localizar script
            wp_localize_script(
                'worker-portal-workers',
                'worker_portal_params',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('worker_portal_ajax_nonce')
                )
            );
        }
    }

    /**
     * Renderiza el shortcode del perfil de trabajador
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            HTML generado
     */
    public function render_profile_shortcode($atts) {
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            return '<div class="worker-portal-login-required">' . 
                __('Debes iniciar sesión para ver tu perfil.', 'worker-portal') . 
                ' <a href="' . wp_login_url(get_permalink()) . '">' . 
                __('Iniciar sesión', 'worker-portal') . 
                '</a></div>';
        }
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir plantilla
        include(plugin_dir_path(__FILE__) . 'templates/workers-view.php');
        
        // Retornar contenido
        return ob_get_clean();
    }

    /**
     * Añade páginas de administración
     *
     * @since    1.0.0
     */
    public function add_admin_pages() {
        // Verificar que el usuario puede gestionar usuarios
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'worker-portal',                        // Página padre
                __('Trabajadores', 'worker-portal'),    // Título de la página
                __('Trabajadores', 'worker-portal'),    // Título del menú
                'manage_options',                       // Capacidad requerida
                'worker-portal-workers',                // Slug del menú
                array($this, 'render_admin_page')        // Función de callback
            );
        }
    }

    /**
     * Renderiza la página de administración
     *
     * @since    1.0.0
     */
    public function render_admin_page() {
        // Verificar permisos del usuario
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Incluir plantilla de administración
        include(plugin_dir_path(__FILE__) . 'templates/admin-page.php');
    }

    /**
     * Registra el último inicio de sesión de un usuario
     *
     * @since    1.0.0
     * @param    string    $user_login    Nombre de usuario
     * @param    WP_User   $user          Objeto de usuario
     */
    public function record_user_login($user_login, $user) {
        update_user_meta($user->ID, 'last_login', current_time('mysql'));
    }

    /**
     * Renderiza el panel de administración de trabajadores
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            HTML generado
     */
    public function render_admin_panel_shortcode($atts) {
        // Verificar permisos
        if (!Worker_Portal_Utils::is_portal_admin()) {
            return '<div class="worker-portal-error">' . 
                __('No tienes permisos para gestionar trabajadores.', 'worker-portal') . 
                '</div>';
        }
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir plantilla
        include(plugin_dir_path(__FILE__) . 'templates/admin-page.php');
        
        // Retornar contenido
        return ob_get_clean();
    }
}