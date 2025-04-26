<?php
/**
 * La clase pública del plugin
 *
 * @since      1.0.0
 */
class Worker_Portal_Public {

    /**
     * Carga de estilos para el frontend
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Estilos generales del portal
        wp_enqueue_style(
            'worker-portal-public',
            WORKER_PORTAL_URL . 'public/css/public-style.css',
            array(),
            WORKER_PORTAL_VERSION,
            'all'
        );
    }

    /**
     * Carga de scripts para el frontend
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Scripts generales del portal
        wp_enqueue_script(
            'worker-portal-public',
            WORKER_PORTAL_URL . 'public/js/public-script.js',
            array('jquery'),
            WORKER_PORTAL_VERSION,
            true
        );
    }

    /**
     * Registra shortcodes para el portal
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        // Shortcode principal del portal
        add_shortcode('worker_portal', array($this, 'render_portal_shortcode'));
    }

    /**
     * Renderiza el shortcode principal del portal
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            Contenido del portal
     */
    public function render_portal_shortcode($atts) {
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            return '<div class="worker-portal-login-required">' . 
                __('Debes iniciar sesión para acceder al Portal del Trabajador.', 'worker-portal') . 
                ' <a href="' . wp_login_url(get_permalink()) . '">' . 
                __('Iniciar sesión', 'worker-portal') . 
                '</a></div>';
        }
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir plantilla del portal
        include(WORKER_PORTAL_PATH . 'public/partials/portal-page.php');
        
        // Devolver contenido
        return ob_get_clean();
    }
}