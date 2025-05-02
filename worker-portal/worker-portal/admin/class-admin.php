<?php
/**
 * La clase de administración del plugin
 *
 * @since      1.0.0
 */
class Worker_Portal_Admin {

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

    /**
 * Registra los menús de administración
 *
 * @since    1.0.0
 */
public function register_admin_menu() {
    // Menú principal
    add_menu_page(
        __('Portal del Trabajador', 'worker-portal'),
        __('Portal Trabajador', 'worker-portal'),
        'manage_options',
        'worker-portal',
        array($this, 'render_admin_dashboard'),
        'dashicons-groups',
        30
    );
    
    // Submenú para documentos
    add_submenu_page(
        'worker-portal',
        __('Documentos', 'worker-portal'),
        __('Documentos', 'worker-portal'),
        'manage_options',
        'worker-portal-documents',
        array($this, 'render_documents_page')
    );
    
    // Otros submenús (gastos, hojas de trabajo, etc.)
    add_submenu_page(
        'worker-portal',
        __('Gastos', 'worker-portal'),
        __('Gastos', 'worker-portal'),
        'manage_options',
        'worker-portal-expenses',
        array($this, 'render_expenses_page')
    );
    
    add_submenu_page(
        'worker-portal',
        __('Hojas de Trabajo', 'worker-portal'),
        __('Hojas de Trabajo', 'worker-portal'),
        'manage_options',
        'worker-portal-worksheets',
        array($this, 'render_worksheets_page')
    );
    
    add_submenu_page(
        'worker-portal',
        __('Incentivos', 'worker-portal'),
        __('Incentivos', 'worker-portal'),
        'manage_options',
        'worker-portal-incentives',
        array($this, 'render_incentives_page')
    );
    
    add_submenu_page(
        'worker-portal',
        __('Configuración', 'worker-portal'),
        __('Configuración', 'worker-portal'),
        'manage_options',
        'worker-portal-settings',
        array($this, 'render_settings_page')
    );
}

/**
 * Renderiza la página de administración de documentos
 *
 * @since    1.0.0
 */
public function render_documents_page() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Incluir plantilla
    include(WORKER_PORTAL_PATH . 'modules/documents/templates/admin-page.php');
}
}