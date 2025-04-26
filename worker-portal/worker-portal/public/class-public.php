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

        // Estilos específicos de gastos
        wp_enqueue_style(
            'worker-portal-expenses',
            WORKER_PORTAL_URL . 'modules/expenses/css/expenses.css',
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
        // Cargar librería jQuery si no está cargada
        wp_enqueue_script('jquery');

        // Scripts generales del portal
        wp_enqueue_script(
            'worker-portal-public',
            WORKER_PORTAL_URL . 'public/js/public-script.js',
            array('jquery'),
            WORKER_PORTAL_VERSION,
            true
        );

        // Scripts de gastos
        wp_enqueue_script(
            'worker-portal-expenses',
            WORKER_PORTAL_URL . 'public/js/expenses.js',
            array('jquery'),
            WORKER_PORTAL_VERSION,
            true
        );

        // Localizar script de portal
        wp_localize_script(
            'worker-portal-public', 
            'worker_portal_params', 
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('worker_portal_ajax_nonce')
            )
        );

        // Localizar script de gastos
        wp_localize_script(
            'worker-portal-expenses',
            'workerPortalExpenses',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('worker_portal_expenses_nonce'),
                'i18n' => array(
                    'confirm_delete' => __('¿Estás seguro de que deseas eliminar este gasto?', 'worker-portal'),
                    'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'),
                    'success' => __('Operación completada con éxito.', 'worker-portal')
                )
            )
        );
    }

    /**
     * Registra shortcodes para el portal
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('worker_portal', array($this, 'render_portal_shortcode'));
        add_shortcode('worker_expenses', array($this, 'render_expenses_shortcode'));
        add_shortcode('worker_documents', array($this, 'render_documents_shortcode'));
        add_shortcode('worker_worksheets', array($this, 'render_worksheets_shortcode'));
        add_shortcode('worker_incentives', array($this, 'render_incentives_shortcode'));
    }

    /**
     * Añade hooks de AJAX
     *
     * @since    1.0.0
     */
    public function add_ajax_hooks() {
        // Hook para cargar secciones del portal
        add_action('wp_ajax_load_portal_section', array($this, 'ajax_load_portal_section'));
        add_action('wp_ajax_nopriv_load_portal_section', array($this, 'ajax_load_portal_section'));
    }

    /**
     * Carga dinámica de secciones del portal
     *
     * @since    1.0.0
     */
    public function ajax_load_portal_section() {
        // Verificar nonce
        check_ajax_referer('worker_portal_ajax_nonce', 'nonce');

        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'worker-portal'));
        }

        // Obtener la sección solicitada
        $section = isset($_POST['section']) ? sanitize_text_field($_POST['section']) : '';

        // Contenido de la sección
        $content = '';

        // Generar contenido según la sección
        switch ($section) {
            case 'expenses':
                $content = do_shortcode('[worker_expenses]');
                break;
            case 'documents':
                $content = do_shortcode('[worker_documents]');
                break;
            case 'worksheets':
                $content = do_shortcode('[worker_worksheets]');
                break;
            case 'incentives':
                $content = do_shortcode('[worker_incentives]');
                break;
            default:
                wp_send_json_error(__('Sección no válida', 'worker-portal'));
        }

        // Enviar respuesta
        wp_send_json_success($content);
    }

    /**
     * Renderiza shortcode de gastos
     *
     * @since    1.0.0
     */
    public function render_expenses_shortcode($atts) {
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            return '<div class="worker-portal-login-required">' . 
                __('Debes iniciar sesión para ver tus gastos.', 'worker-portal') . 
                ' <a href="' . wp_login_url(get_permalink()) . '">' . 
                __('Iniciar sesión', 'worker-portal') . 
                '</a></div>';
        }
        
        // Cargar dependencias necesarias
        require_once WORKER_PORTAL_PATH . 'includes/class-database.php';
        
        // Atributos por defecto del shortcode
        $atts = shortcode_atts(
            array(
                'limit' => 10,  // Número de gastos a mostrar
                'show_form' => 'yes'  // Mostrar formulario para añadir gastos
            ),
            $atts,
            'worker_expenses'
        );
        
        // Obtener el usuario actual
        $user_id = get_current_user_id();
        
        // Obtener los gastos del usuario
        $database = Worker_Portal_Database::get_instance();
        $expenses = $database->get_user_expenses($user_id, $atts['limit']);
        
        // Obtener los tipos de gastos disponibles
        $expense_types = get_option('worker_portal_expense_types', array(
            'km' => __('Kilometraje', 'worker-portal'),
            'hours' => __('Horas de desplazamiento', 'worker-portal'),
            'meal' => __('Dietas', 'worker-portal'),
            'other' => __('Otros', 'worker-portal')
        ));
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir plantilla de gastos
        include(WORKER_PORTAL_PATH . 'public/partials/expenses-view.php');
        
        // Devolver contenido
        return ob_get_clean();
    }

    /**
     * Renderiza shortcode de documentos
     *
     * @since    1.0.0
     */
    public function render_documents_shortcode($atts) {
        return '<div class="worker-portal-section-placeholder">' . 
            __('Sección de Documentos (Próximamente)', 'worker-portal') . 
            '</div>';
    }

    /**
     * Renderiza shortcode de hojas de trabajo
     *
     * @since    1.0.0
     */
    public function render_worksheets_shortcode($atts) {
        return '<div class="worker-portal-section-placeholder">' . 
            __('Sección de Hojas de Trabajo (Próximamente)', 'worker-portal') . 
            '</div>';
    }

    /**
     * Renderiza shortcode de incentivos
     *
     * @since    1.0.0
     */
    public function render_incentives_shortcode($atts) {
        return '<div class="worker-portal-section-placeholder">' . 
            __('Sección de Incentivos (Próximamente)', 'worker-portal') . 
            '</div>';
    }

    /**
     * Renderiza shortcode del portal
     *
     * @since    1.0.0
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