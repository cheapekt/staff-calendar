<?php
/**
 * La clase de administración del plugin
 *
 * @since      1.0.0
 */
class Worker_Portal_Admin {

    /**
     * Registra las páginas de menú en el área de administración
     *
     * @since    1.0.0
     */
    public function register_admin_menu() {
        // Página principal del portal
        add_menu_page(
            __('Portal del Trabajador', 'worker-portal'),
            __('Portal del Trabajador', 'worker-portal'),
            'manage_options',
            'worker-portal',
            array($this, 'render_dashboard'),
            'dashicons-building',
            30
        );

        // Submenú de Dashboard
        add_submenu_page(
            'worker-portal',
            __('Dashboard', 'worker-portal'),
            __('Dashboard', 'worker-portal'),
            'manage_options',
            'worker-portal',
            array($this, 'render_dashboard')
        );
    }

    /**
     * Carga de estilos para el área de administración
     *
     * @since    1.0.0
     * @param    string    $hook    Página actual de administración
     */
    public function enqueue_styles($hook) {
        // Cargar estilos generales de administración
        wp_enqueue_style(
            'worker-portal-admin',
            WORKER_PORTAL_URL . 'admin/css/admin-style.css',
            array(),
            WORKER_PORTAL_VERSION,
            'all'
        );
    }

    /**
     * Carga de scripts para el área de administración
     *
     * @since    1.0.0
     * @param    string    $hook    Página actual de administración
     */
    public function enqueue_scripts($hook) {
        // Cargar scripts generales de administración
        wp_enqueue_script(
            'worker-portal-admin',
            WORKER_PORTAL_URL . 'admin/js/admin-script.js',
            array('jquery'),
            WORKER_PORTAL_VERSION,
            true
        );
    }

    /**
     * Renderiza el dashboard de administración
     *
     * @since    1.0.0
     */
    public function render_dashboard() {
        // Verificar permisos de acceso
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes suficientes permisos para acceder a esta página.', 'worker-portal'));
        }

        // Incluir plantilla del dashboard
        include(WORKER_PORTAL_PATH . 'admin/partials/dashboard.php');
    }

    /**
     * Registra las configuraciones de los módulos
     *
     * @since    1.0.0
     */
    public function register_module_settings() {
        // Registro de secciones y campos de configuración para cada módulo
        
        // Configuración de documentos
        register_setting(
            'worker_portal_documents',
            'worker_portal_document_categories',
            array(
                'type' => 'array',
                'description' => 'Categorías de documentos',
                'sanitize_callback' => array($this, 'sanitize_document_categories'),
                'default' => array(
                    'payroll' => __('Nóminas', 'worker-portal'),
                    'contract' => __('Contratos', 'worker-portal'),
                    'other' => __('Otros', 'worker-portal')
                )
            )
        );

        // Configuración de módulos adicionales se pueden añadir aquí
    }

    /**
     * Sanitiza las categorías de documentos
     *
     * @since    1.0.0
     * @param    array    $input    Categorías a sanitizar
     * @return   array              Categorías sanitizadas
     */
    public function sanitize_document_categories($input) {
        $sanitized_input = array();
        
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $sanitized_input[sanitize_key($key)] = sanitize_text_field($value);
            }
        }
        
        return $sanitized_input;
    }
}