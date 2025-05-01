<?php
/**
 * Módulo de Hojas de Trabajo
 *
 * @since      1.0.0
 */
class Worker_Portal_Module_Worksheets {

    /**
     * Opciones por defecto del módulo
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $default_options    Opciones por defecto
     */
    private $default_options = array();

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Inicializar opciones por defecto
        $this->init_default_options();
    }

    /**
     * Inicializa las opciones por defecto
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_default_options() {
        $this->default_options = array(
            'system_types' => array(
                'estructura_techo' => 'Estructura en techo continuo de PYL',
                'estructura_tabique' => 'Estructura en tabique o trasdosado',
                'aplacado_simple' => 'Aplacado 1 placa en tabique/trasdosado',
                'aplacado_doble' => 'Aplacado 2 placas en tabique/trasdosado',
                'horas_ayuda' => 'Horas de ayudas, descargas, etc.'
            ),
            'unit_types' => array(
                'm2' => 'Metros cuadrados',
                'h' => 'Horas'
            ),
            'difficulty_levels' => array(
                'baja' => 'Baja',
                'media' => 'Media',
                'alta' => 'Alta'
            ),
            'worksheet_validators' => array(),
            'worksheet_notification_email' => get_option('admin_email')
        );
    }

    /**
     * Inicializa el módulo
     *
     * @since    1.0.0
     */
    public function init() {
        // Registrar hooks para frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Registrar shortcodes
        add_shortcode('worker_worksheets', array($this, 'render_worksheets_shortcode'));

        // Registrar endpoints de REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Registrar AJAX actions para frontend
        $this->register_frontend_ajax_actions();
        
        // Registrar AJAX actions para admin
        $this->register_admin_ajax_actions();
    }

    /**
     * Registra los endpoints de acciones AJAX para el frontend
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_frontend_ajax_actions() {
        // Acciones AJAX para usuarios normales
        add_action('wp_ajax_submit_worksheet', array($this, 'ajax_submit_worksheet'));
        add_action('wp_ajax_delete_worksheet', array($this, 'ajax_delete_worksheet'));
        add_action('wp_ajax_get_worksheet_details', array($this, 'ajax_get_worksheet_details'));
        add_action('wp_ajax_filter_worksheets', array($this, 'ajax_filter_worksheets'));
        add_action('wp_ajax_export_worksheets', array($this, 'ajax_export_worksheets'));
    }

    /**
     * Registra los endpoints de acciones AJAX para el admin
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_admin_ajax_actions() {
        // Acciones AJAX para administradores
        add_action('wp_ajax_admin_load_worksheets', array($this, 'ajax_admin_load_worksheets'));
        add_action('wp_ajax_admin_validate_worksheet', array($this, 'ajax_admin_validate_worksheet'));
        add_action('wp_ajax_admin_bulk_worksheet_action', array($this, 'ajax_admin_bulk_worksheet_action'));
        add_action('wp_ajax_admin_get_worksheet_details', array($this, 'ajax_admin_get_worksheet_details'));
    }

    /**
     * Registra las páginas de menú en el área de administración
     *
     * @since    1.0.0
     */
    public function register_admin_menu() {
        add_submenu_page(
            'worker-portal',
            __('Gestión de Hojas de Trabajo', 'worker-portal'),
            __('Hojas de Trabajo', 'worker-portal'),
            'manage_options',
            'worker-portal-worksheets',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Registra las opciones de configuración
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Registrar configuraciones para los tipos de sistemas
        register_setting(
            'worker_portal_worksheets',
            'worker_portal_system_types',
            array(
                'type' => 'array',
                'description' => 'Tipos de sistemas disponibles',
                'sanitize_callback' => array($this, 'sanitize_system_types'),
                'default' => $this->default_options['system_types']
            )
        );

        // Registrar configuraciones para los tipos de unidades
        register_setting(
            'worker_portal_worksheets',
            'worker_portal_unit_types',
            array(
                'type' => 'array',
                'description' => 'Tipos de unidades disponibles',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => $this->default_options['unit_types']
            )
        );

        // Registrar configuraciones para los niveles de dificultad
        register_setting(
            'worker_portal_worksheets',
            'worker_portal_difficulty_levels',
            array(
                'type' => 'array',
                'description' => 'Niveles de dificultad',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => $this->default_options['difficulty_levels']
            )
        );

        // Registrar configuraciones para los validadores de hojas
        register_setting(
            'worker_portal_worksheets',
            'worker_portal_worksheet_validators',
            array(
                'type' => 'array',
                'description' => 'Usuarios que pueden validar hojas de trabajo',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => $this->default_options['worksheet_validators']
            )
        );

        // Registrar configuraciones para el email de notificación
        register_setting(
            'worker_portal_worksheets',
            'worker_portal_worksheet_notification_email',
            array(
                'type' => 'string',
                'description' => 'Email para notificaciones de hojas de trabajo',
                'sanitize_callback' => 'sanitize_email',
                'default' => $this->default_options['worksheet_notification_email']
            )
        );
    }

    /**
     * Sanitiza los tipos de sistemas
     *
     * @since    1.0.0
     * @param    array    $input    Tipos de sistemas a sanitizar
     * @return   array              Tipos de sistemas sanitizados
     */
    public function sanitize_system_types($input) {
        $sanitized_input = array();
        
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $sanitized_input[sanitize_key($key)] = sanitize_text_field($value);
            }
        }
        
        return $sanitized_input;
    }

    /**
     * Carga los scripts y estilos necesarios
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Cargar estilos
        wp_enqueue_style(
            'worker-portal-worksheets',
            WORKER_PORTAL_URL . 'modules/worksheets/css/worksheets.css',
            array(),
            WORKER_PORTAL_VERSION,
            'all'
        );
        
        // Cargar scripts
        wp_enqueue_script(
            'worker-portal-worksheets',
            WORKER_PORTAL_URL . 'modules/worksheets/js/worksheets.js',
            array('jquery'),
            WORKER_PORTAL_VERSION,
            true
        );
        
        // Localizar script con variables necesarias
        wp_localize_script(
            'worker-portal-worksheets',
            'workerPortalWorksheets',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('worker_portal_worksheets_nonce'),
                'i18n' => array(
                    'confirm_delete' => __('¿Estás seguro de que deseas eliminar esta hoja de trabajo?', 'worker-portal'),
                    'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'),
                    'success' => __('Operación completada con éxito.', 'worker-portal'),
                    'loading' => __('Cargando...', 'worker-portal'),
                    'error_load' => __('Error al cargar los datos. Por favor, inténtalo de nuevo.', 'worker-portal'),
                    'error_delete' => __('Error al eliminar la hoja de trabajo.', 'worker-portal')
                )
            )
        );
    }
    /**
     * Renderiza la página de administración
     *
     * @since    1.0.0
     */
    public function render_admin_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Incluir plantilla
        include(WORKER_PORTAL_PATH . 'modules/worksheets/templates/admin-page.php');
    }

    /**
     * Renderiza el shortcode para mostrar las hojas de trabajo del usuario
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            HTML generado
     */
    public function render_worksheets_shortcode($atts) {
        // Si el usuario no está logueado, mostrar mensaje de error
        if (!is_user_logged_in()) {
            return '<div class="worker-portal-error">' . __('Debes iniciar sesión para ver tus hojas de trabajo.', 'worker-portal') . '</div>';
        }
        
        // Atributos por defecto
        $atts = shortcode_atts(
            array(
                'limit' => 10,     // Número de hojas a mostrar
                'show_form' => 'yes'  // Mostrar formulario para añadir hojas
            ),
            $atts,
            'worker_worksheets'
        );
        
        // Obtener el usuario actual
        $user_id = get_current_user_id();
        
        // Obtener las hojas de trabajo del usuario
        $worksheets = $this->get_user_worksheets($user_id, $atts['limit']);
        
        // Obtener proyectos disponibles
        $projects = $this->get_available_projects();
        
        // Obtener configuración
        $system_types = get_option('worker_portal_system_types', $this->default_options['system_types']);
        $unit_types = get_option('worker_portal_unit_types', $this->default_options['unit_types']);
        $difficulty_levels = get_option('worker_portal_difficulty_levels', $this->default_options['difficulty_levels']);
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir plantilla
        include(WORKER_PORTAL_PATH . 'modules/worksheets/templates/worksheets-view.php');
        
        // Retornar el contenido
        return ob_get_clean();
    }

    /**
     * Obtiene las hojas de trabajo de un usuario
     *
     * @since    1.0.0
     * @param    int     $user_id    ID del usuario
     * @param    int     $limit      Número máximo de hojas a obtener
     * @param    int     $offset     Desplazamiento para paginación
     * @param    string  $order_by   Campo para ordenar
     * @param    string  $order      Orden (ASC o DESC)
     * @return   array               Lista de hojas de trabajo
     */
    public function get_user_worksheets($user_id, $limit = 10, $offset = 0, $order_by = 'work_date', $order = 'DESC') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_worksheets';
        $projects_table = $wpdb->prefix . 'worker_projects';
        
        // Verificar orden válido
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        // Verificar campo de ordenación válido
        $valid_fields = array('id', 'work_date', 'difficulty', 'quantity', 'hours', 'status');
        $order_by = in_array($order_by, $valid_fields) ? $order_by : 'work_date';
        
        $worksheets = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT w.*, p.name as project_name, p.location as project_location
                FROM $table_name w
                LEFT JOIN $projects_table p ON w.project_id = p.id
                WHERE w.user_id = %d
                ORDER BY w.$order_by $order
                LIMIT %d OFFSET %d",
                $user_id,
                $limit,
                $offset
            ),
            ARRAY_A
        );
        
        return $worksheets;
    }

    /**
     * Obtiene proyectos disponibles para el usuario
     *
     * @since    1.0.0
     * @return   array    Lista de proyectos
     */
    public function get_available_projects() {
        global $wpdb;
        
        // Consultar directamente la tabla de proyectos
        $projects_table = $wpdb->prefix . 'worker_projects';
        
        // Verificar si la tabla existe
        if($wpdb->get_var("SHOW TABLES LIKE '$projects_table'") != $projects_table) {
            // Si la tabla no existe, devolver un array vacío
            return array();
        }
        
        // Obtener proyectos activos
        $projects = $wpdb->get_results(
            "SELECT * FROM $projects_table WHERE status = 'active' ORDER BY name ASC",
            ARRAY_A
        );
        
        // Si no hay proyectos, crear uno por defecto para desarrollo
        if (empty($projects) && defined('WP_DEBUG') && WP_DEBUG) {
            // Solo insertar proyecto de prueba en entorno de desarrollo
            $default_project = array(
                'name' => 'Proyecto de Prueba',
                'description' => 'Proyecto de prueba para desarrollo',
                'location' => 'Barcelona',
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+30 days')),
                'status' => 'active'
            );
            
            $wpdb->insert($projects_table, $default_project);
            
            // Volver a consultar
            $projects = $wpdb->get_results(
                "SELECT * FROM $projects_table WHERE status = 'active' ORDER BY name ASC",
                ARRAY_A
            );
        }
        
        return $projects;
    }

    /**
     * Registra los endpoints de la API REST
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route('worker-portal/v1', '/worksheets', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'rest_get_worksheets'),
                'permission_callback' => array($this, 'rest_permissions_check')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'rest_create_worksheet'),
                'permission_callback' => array($this, 'rest_permissions_check')
            )
        ));
        
        register_rest_route('worker-portal/v1', '/worksheets/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'rest_get_worksheet'),
                'permission_callback' => array($this, 'rest_permissions_check')
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'rest_update_worksheet'),
                'permission_callback' => array($this, 'rest_permissions_check')
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'rest_delete_worksheet'),
                'permission_callback' => array($this, 'rest_permissions_check')
            )
        ));
        
        register_rest_route('worker-portal/v1', '/worksheets/(?P<id>\d+)/validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_validate_worksheet'),
            'permission_callback' => array($this, 'rest_validate_permissions_check')
        ));
    }

    /**
     * Verifica los permisos para las peticiones a la API REST
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   bool                           Si tiene permisos
     */
    public function rest_permissions_check($request) {
        return is_user_logged_in();
    }

    /**
     * Verifica los permisos para validar hojas de trabajo
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   bool                           Si tiene permisos
     */
    public function rest_validate_permissions_check($request) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_id = get_current_user_id();
        $validators = get_option('worker_portal_worksheet_validators', array());
        
        // Los administradores siempre pueden validar
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Verificar si el usuario está en la lista de validadores
        return in_array($user_id, $validators);
    }
    /**
     * Maneja la petición AJAX para enviar una hoja de trabajo
     *
     * @since    1.0.0
     */
    public function ajax_submit_worksheet() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_worksheets_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para registrar una hoja de trabajo.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        
        // Obtener datos del formulario
        $work_date = isset($_POST['work_date']) ? sanitize_text_field($_POST['work_date']) : '';
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $difficulty = isset($_POST['difficulty']) ? sanitize_text_field($_POST['difficulty']) : '';
        $system_type = isset($_POST['system_type']) ? sanitize_text_field($_POST['system_type']) : '';
        $unit_type = isset($_POST['unit_type']) ? sanitize_text_field($_POST['unit_type']) : '';
        $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
        $hours = isset($_POST['hours']) ? floatval($_POST['hours']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        // Validar datos
        if (empty($work_date) || empty($project_id) || empty($system_type) || 
            empty($unit_type) || $quantity <= 0 || $hours <= 0) {
            wp_send_json_error(__('Faltan campos obligatorios o son inválidos.', 'worker-portal'));
        }
        
        // Insertar la hoja de trabajo en la base de datos
        $result = $this->insert_worksheet(
            $user_id,
            $work_date,
            $project_id,
            $difficulty,
            $system_type,
            $unit_type,
            $quantity,
            $hours,
            $notes
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Enviar notificación
        $this->send_worksheet_notification($result);
        
        wp_send_json_success(array(
            'worksheet_id' => $result,
            'message' => __('Hoja de trabajo registrada correctamente.', 'worker-portal')
        ));
    }

    /**
     * Maneja la petición AJAX para obtener los detalles de una hoja de trabajo
     *
     * @since      1.0.0
     */
    public function ajax_get_worksheet_details() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_worksheets_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para ver los detalles.', 'worker-portal'));
        }
        
        // Obtener ID de la hoja de trabajo
        $worksheet_id = isset($_POST['worksheet_id']) ? intval($_POST['worksheet_id']) : 0;
        
        if ($worksheet_id <= 0) {
            wp_send_json_error(__('ID de hoja de trabajo no válido.', 'worker-portal'));
        }
        
        // Obtener detalles de la hoja de trabajo
        $worksheet = $this->get_worksheet_details($worksheet_id);
        
        if (is_wp_error($worksheet)) {
            wp_send_json_error($worksheet->get_error_message());
        }
        
        // Verificar permisos: el usuario debe ser propietario o administrador
        $current_user_id = get_current_user_id();
        
        if ($worksheet['user_id'] != $current_user_id && 
            !current_user_can('manage_options') && 
            !$this->is_worksheet_validator($current_user_id)) {
            wp_send_json_error(__('No tienes permiso para ver esta hoja de trabajo.', 'worker-portal'));
        }
        
        // Generar HTML para mostrar los detalles
        $html = $this->generate_worksheet_details_html($worksheet);
        
        wp_send_json_success($html);
    }
    
    /**
     * Verifica si un usuario es validador de hojas de trabajo
     *
     * @since    1.0.0
     * @param    int       $user_id    ID del usuario
     * @return   bool                  Si es validador
     */
    private function is_worksheet_validator($user_id) {
        // Los administradores siempre son validadores
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Verificar en la lista de validadores configurados
        $validators = get_option('worker_portal_worksheet_validators', array());
        return in_array($user_id, $validators);
    }
    
    /**
     * Obtiene los detalles de una hoja de trabajo
     *
     * @since    1.0.0
     * @param    int       $worksheet_id    ID de la hoja de trabajo
     * @return   array|WP_Error            Datos de la hoja o error
     */
    private function get_worksheet_details($worksheet_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_worksheets';
        $projects_table = $wpdb->prefix . 'worker_projects';
        
        $worksheet = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT w.*, p.name as project_name, p.location as project_location
                FROM $table_name w
                LEFT JOIN $projects_table p ON w.project_id = p.id
                WHERE w.id = %d",
                $worksheet_id
            ),
            ARRAY_A
        );
        
        if (!$worksheet) {
            return new WP_Error('not_found', __('Hoja de trabajo no encontrada.', 'worker-portal'));
        }
        
        // Obtener información del validador si existe
        if (!empty($worksheet['validated_by'])) {
            $validator = get_userdata($worksheet['validated_by']);
            if ($validator) {
                $worksheet['validator_name'] = $validator->display_name;
            }
        }
        
        return $worksheet;
    }
    
    /**
     * Genera el HTML para mostrar los detalles de una hoja de trabajo
     * 
     * @since    1.0.0
     * @param    array     $worksheet    Datos de la hoja de trabajo
     * @return   string                 HTML generado
     */
    private function generate_worksheet_details_html($worksheet) {
        // Obtener configuración
        $system_types = get_option('worker_portal_system_types', $this->default_options['system_types']);
        $unit_types = get_option('worker_portal_unit_types', $this->default_options['unit_types']);
        $difficulty_levels = get_option('worker_portal_difficulty_levels', $this->default_options['difficulty_levels']);
        
        $current_user_id = get_current_user_id();
        
        // Iniciar buffer de salida
        ob_start();
        ?>
        <div class="worker-portal-worksheet-details">
            <table class="worker-portal-details-table">
                <tr>
                    <th><?php _e('ID:', 'worker-portal'); ?></th>
                    <td><?php echo esc_html($worksheet['id']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Fecha de trabajo:', 'worker-portal'); ?></th>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($worksheet['work_date'])); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Proyecto:', 'worker-portal'); ?></th>
                    <td><?php echo esc_html($worksheet['project_name']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Ubicación:', 'worker-portal'); ?></th>
                    <td><?php echo esc_html($worksheet['project_location']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Dificultad:', 'worker-portal'); ?></th>
                    <td>
                        <?php 
                        echo isset($difficulty_levels[$worksheet['difficulty']]) 
                            ? esc_html($difficulty_levels[$worksheet['difficulty']]) 
                            : esc_html(ucfirst($worksheet['difficulty'])); 
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Sistema:', 'worker-portal'); ?></th>
                    <td>
                        <?php 
                        echo isset($system_types[$worksheet['system_type']]) 
                            ? esc_html($system_types[$worksheet['system_type']]) 
                            : esc_html($worksheet['system_type']); 
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Cantidad:', 'worker-portal'); ?></th>
                    <td>
                        <?php echo esc_html($worksheet['quantity']); ?> 
                        <?php echo $worksheet['unit_type'] == 'm2' ? 'm²' : 'H'; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Horas:', 'worker-portal'); ?></th>
                    <td><?php echo esc_html($worksheet['hours']); ?> h</td>
                </tr>
                <tr>
                    <th><?php _e('Estado:', 'worker-portal'); ?></th>
                    <td>
                        <?php if ($worksheet['status'] === 'pending'): ?>
                            <span class="worker-portal-badge worker-portal-badge-warning"><?php _e('Pendiente', 'worker-portal'); ?></span>
                        <?php else: ?>
                            <span class="worker-portal-badge worker-portal-badge-success"><?php _e('Validada', 'worker-portal'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($worksheet['notes'])): ?>
                <tr>
                    <th><?php _e('Notas:', 'worker-portal'); ?></th>
                    <td><?php echo esc_html($worksheet['notes']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (isset($worksheet['validator_name'])): ?>
                    <tr>
                        <th><?php _e('Validada por:', 'worker-portal'); ?></th>
                        <td><?php echo esc_html($worksheet['validator_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Fecha de validación:', 'worker-portal'); ?></th>
                        <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($worksheet['validated_date'])); ?></td>
                    </tr>
                <?php endif; ?>
            </table>
            
            <?php if ($worksheet['status'] === 'pending' && $worksheet['user_id'] == $current_user_id): ?>
                <div class="worker-portal-worksheet-actions">
                    <button type="button" class="worker-portal-button worker-portal-button-danger worker-portal-delete-worksheet" data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                        <i class="dashicons dashicons-trash"></i> <?php _e('Eliminar', 'worker-portal'); ?>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($worksheet['status'] === 'pending' && $this->is_worksheet_validator($current_user_id)): ?>
                <div class="worker-portal-worksheet-actions">
                    <button type="button" class="worker-portal-button worker-portal-button-primary validate-worksheet" data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                        <i class="dashicons dashicons-yes"></i> <?php _e('Validar esta hoja', 'worker-portal'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Maneja la petición AJAX para eliminar una hoja de trabajo
     *
     * @since    1.0.0
     */
    public function ajax_delete_worksheet() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_worksheets_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para eliminar una hoja de trabajo.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        $worksheet_id = isset($_POST['worksheet_id']) ? intval($_POST['worksheet_id']) : 0;
        
        // Verificar que existe la hoja de trabajo
        $worksheet = $this->get_worksheet_details($worksheet_id);
        
        if (is_wp_error($worksheet)) {
            wp_send_json_error($worksheet->get_error_message());
        }
        
        // Verificar que la hoja pertenece al usuario
        if ($worksheet['user_id'] != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permiso para eliminar esta hoja de trabajo.', 'worker-portal'));
        }
        
        // Verificar que la hoja no ha sido validada
        if ($worksheet['status'] != 'pending') {
            wp_send_json_error(__('No puedes eliminar una hoja de trabajo que ya ha sido validada.', 'worker-portal'));
        }
        
        // Eliminar la hoja de trabajo
        $result = $this->delete_worksheet($worksheet_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Hoja de trabajo eliminada correctamente.', 'worker-portal'));
    }
    
    /**
     * Elimina una hoja de trabajo
     *
     * @since    1.0.0
     * @param    int       $worksheet_id    ID de la hoja a eliminar
     * @return   bool|WP_Error             True si se eliminó o error
     */
    private function delete_worksheet($worksheet_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_worksheets';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $worksheet_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('delete_error', __('Error al eliminar la hoja de trabajo.', 'worker-portal'));
        }
        
        return true;
    }

    /**
     * Inserta una nueva hoja de trabajo en la base de datos
     *
     * @since    1.0.0
     * @param    int       $user_id       ID del usuario
     * @param    string    $work_date     Fecha de trabajo
     * @param    int       $project_id    ID del proyecto
     * @param    string    $difficulty    Nivel de dificultad
     * @param    string    $system_type   Tipo de sistema
     * @param    string    $unit_type     Tipo de unidad
     * @param    float     $quantity      Cantidad
     * @param    float     $hours         Horas trabajadas
     * @param    string    $notes         Notas adicionales
     * @return   int|WP_Error            ID de la hoja insertada o error
     */
    private function insert_worksheet($user_id, $work_date, $project_id, $difficulty, $system_type, 
                                     $unit_type, $quantity, $hours, $notes) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_worksheets';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'work_date' => $work_date,
                'project_id' => $project_id,
                'difficulty' => $difficulty,
                'system_type' => $system_type,
                'unit_type' => $unit_type,
                'quantity' => $quantity,
                'hours' => $hours,
                'notes' => $notes,
                'status' => 'pending'
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error(
                'db_error',
                __('Error al insertar la hoja de trabajo en la base de datos.', 'worker-portal')
            );
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Envía una notificación por email de una nueva hoja de trabajo
     *
     * @since    1.0.0
     * @param    int       $worksheet_id    ID de la hoja de trabajo
     */
    private function send_worksheet_notification($worksheet_id) {
        // Obtener detalles de la hoja
        $worksheet = $this->get_worksheet_details($worksheet_id);
        
        if (is_wp_error($worksheet)) {
            return;
        }
        
        // Obtener datos necesarios
        $user = get_userdata($worksheet['user_id']);
        $notification_email = get_option('worker_portal_worksheet_notification_email', get_option('admin_email'));
        
        if (!$user || !is_email($notification_email)) {
            return;
        }
        
        // Configurar email
        $subject = sprintf(
            __('[%s] Nueva hoja de trabajo registrada por %s', 'worker-portal'),
            get_bloginfo('name'),
            $user->display_name
        );
        
        // Obtener nombres legibles para los datos
        $system_types = get_option('worker_portal_system_types', $this->default_options['system_types']);
        $system_type_name = isset($system_types[$worksheet['system_type']]) 
            ? $system_types[$worksheet['system_type']] 
            : $worksheet['system_type'];
        
        $unit_types = get_option('worker_portal_unit_types', $this->default_options['unit_types']);
        $unit_type_name = isset($unit_types[$worksheet['unit_type']]) 
            ? $unit_types[$worksheet['unit_type']] 
            : ($worksheet['unit_type'] == 'm2' ? __('Metros cuadrados', 'worker-portal') : __('Horas', 'worker-portal'));
        
        $difficulty_levels = get_option('worker_portal_difficulty_levels', $this->default_options['difficulty_levels']);
        $difficulty_name = isset($difficulty_levels[$worksheet['difficulty']]) 
            ? $difficulty_levels[$worksheet['difficulty']] 
            : $worksheet['difficulty'];
        
        // Texto del mensaje
        $message = sprintf(
            __('Se ha registrado una nueva hoja de trabajo:
Usuario: %s
Fecha: %s
Proyecto: %s
Dificultad: %s
Sistema: %s
Cantidad: %s %s
Horas: %s

Para validar esta hoja de trabajo, accede al panel de administración: %s', 'worker-portal'),
            $user->display_name,
            $worksheet['work_date'],
            $worksheet['project_name'],
            $difficulty_name,
            $system_type_name,
            $worksheet['quantity'],
            $unit_type_name,
            $worksheet['hours'],
            admin_url('admin.php?page=worker-portal-worksheets')
        );
        
        // Enviar email
        wp_mail($notification_email, $subject, $message);
    }
    /**
     * Maneja la petición AJAX para filtrar hojas de trabajo
     *
     * @since    1.0.0
     */
    public function ajax_filter_worksheets() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_worksheets_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para ver tus hojas de trabajo.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        
        // Obtener parámetros de filtrado
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        // Página actual y elementos por página
        $current_page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 10;
        $offset = ($current_page - 1) * $per_page;
        
        // Obtener las hojas filtradas
        $worksheets = $this->get_filtered_worksheets(
            $user_id,
            $project_id,
            $date_from,
            $date_to,
            $status,
            $per_page,
            $offset
        );
        
        // Obtener el total de hojas para la paginación
        $total_items = $this->get_total_filtered_worksheets(
            $user_id,
            $project_id,
            $date_from,
            $date_to,
            $status
        );
        
        $total_pages = ceil($total_items / $per_page);
        
        // Obtener configuración
        $system_types = get_option('worker_portal_system_types', $this->default_options['system_types']);
        $unit_types = get_option('worker_portal_unit_types', $this->default_options['unit_types']);
        $difficulty_levels = get_option('worker_portal_difficulty_levels', $this->default_options['difficulty_levels']);
        
        // Si no hay hojas
        if (empty($worksheets)) {
            wp_send_json_success('<p class="worker-portal-no-data">' . __('No se encontraron hojas de trabajo con los filtros seleccionados.', 'worker-portal') . '</p>');
            return;
        }
        
        // Generar HTML de la tabla
        $html = $this->generate_worksheets_table($worksheets, $current_page, $per_page, $total_items, $total_pages);
        
        wp_send_json_success($html);
    }

    /**
     * Genera el HTML para la tabla de hojas de trabajo
     *
     * @since    1.0.0
     * @param    array    $worksheets     Lista de hojas de trabajo
     * @param    int      $current_page   Página actual
     * @param    int      $per_page       Elementos por página
     * @param    int      $total_items    Total de elementos
     * @param    int      $total_pages    Total de páginas
     * @return   string                   HTML generado
     */
    private function generate_worksheets_table($worksheets, $current_page, $per_page, $total_items, $total_pages) {
        // Obtener configuración
        $system_types = get_option('worker_portal_system_types', $this->default_options['system_types']);
        $difficulty_levels = get_option('worker_portal_difficulty_levels', $this->default_options['difficulty_levels']);
        
        ob_start();
        ?>
        <div class="worker-portal-table-responsive">
            <table class="worker-portal-table worker-portal-worksheets-table">
                <thead>
                    <tr>
                        <th><?php _e('FECHA', 'worker-portal'); ?></th>
                        <th><?php _e('OBRA', 'worker-portal'); ?></th>
                        <th><?php _e('DIF.', 'worker-portal'); ?></th>
                        <th><?php _e('Ud.', 'worker-portal'); ?></th>
                        <th><?php _e('SISTEMA', 'worker-portal'); ?></th>
                        <th><?php _e('HORAS', 'worker-portal'); ?></th>
                        <th><?php _e('VALIDACIÓN', 'worker-portal'); ?></th>
                        <th><?php _e('ACCIONES', 'worker-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($worksheets as $worksheet): ?>
                        <tr data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($worksheet['work_date'])); ?></td>
                            <td><?php echo esc_html($worksheet['project_name']); ?></td>
                            <td>
                                <?php 
                                echo isset($difficulty_levels[$worksheet['difficulty']]) 
                                    ? esc_html($difficulty_levels[$worksheet['difficulty']]) 
                                    : esc_html(ucfirst($worksheet['difficulty'])); 
                                ?>
                            </td>
                            <td><?php echo esc_html($worksheet['quantity']); ?> <?php echo $worksheet['unit_type'] == 'm2' ? 'm²' : 'H'; ?></td>
                            <td>
                                <?php 
                                echo isset($system_types[$worksheet['system_type']]) 
                                    ? esc_html($system_types[$worksheet['system_type']]) 
                                    : esc_html($worksheet['system_type']); 
                                ?>
                            </td>
                            <td><?php echo esc_html($worksheet['hours']); ?> h</td>
                            <td>
                                <?php
                                switch ($worksheet['status']) {
                                    case 'pending':
                                        echo '<span class="worker-portal-badge worker-portal-badge-warning">' . __('PENDIENTE', 'worker-portal') . '</span>';
                                        break;
                                    case 'validated':
                                        echo '<span class="worker-portal-badge worker-portal-badge-success">' . __('VALIDADA', 'worker-portal') . '</span>';
                                        break;
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($worksheet['status'] === 'pending'): ?>
                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-delete-worksheet" data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                                        <i class="dashicons dashicons-trash"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-view-worksheet" data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                                    <i class="dashicons dashicons-visibility"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="worker-portal-pagination">
                <div class="worker-portal-pagination-info">
                    <?php
                    printf(
                        __('Mostrando %1$s - %2$s de %3$s hojas de trabajo', 'worker-portal'),
                        (($current_page - 1) * $per_page) + 1,
                        min($current_page * $per_page, $total_items),
                        $total_items
                    );
                    ?>
                </div>
                
                <div class="worker-portal-pagination-links">
                    <?php if ($current_page > 1): ?>
                        <a href="#" class="worker-portal-pagination-prev" data-page="<?php echo $current_page - 1; ?>">
                            &laquo; <?php _e('Anterior', 'worker-portal'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    // Mostrar números de página
                    for ($i = 1; $i <= $total_pages; $i++):
                        $class = ($i === $current_page) ? 'worker-portal-pagination-current' : '';
                    ?>
                        <a href="#" class="worker-portal-pagination-number <?php echo $class; ?>" data-page="<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="#" class="worker-portal-pagination-next" data-page="<?php echo $current_page + 1; ?>">
                            <?php _e('Siguiente', 'worker-portal'); ?> &raquo;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Obtiene hojas de trabajo filtradas de un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario
     * @param    int       $project_id   ID del proyecto
     * @param    string    $date_from    Fecha inicial
     * @param    string    $date_to      Fecha final
     * @param    string    $status       Estado de la hoja
     * @param    int       $limit        Límite de resultados
     * @param    int       $offset       Offset para paginación
     * @return   array                   Lista de hojas de trabajo
     */
    private function get_filtered_worksheets($user_id, $project_id = 0, $date_from = '', $date_to = '', $status = '', $limit = 10, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_worksheets';
        $projects_table = $wpdb->prefix . 'worker_projects';
        
        $query = "SELECT w.*, p.name as project_name, p.location as project_location
                FROM $table_name w
                LEFT JOIN $projects_table p ON w.project_id = p.id
                WHERE w.user_id = %d";
        $params = array($user_id);
        
        // Aplicar filtros
        if (!empty($project_id)) {
            $query .= " AND w.project_id = %d";
            $params[] = $project_id;
        }
        
        if (!empty($date_from)) {
            $query .= " AND w.work_date >= %s";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND w.work_date <= %s";
            $params[] = $date_to;
        }
        
        if (!empty($status)) {
            $query .= " AND w.status = %s";
            $params[] = $status;
        }
        
        // Ordenar y limitar resultados
        $query .= " ORDER BY w.work_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }
    /**
     * Obtiene el total de hojas de trabajo filtradas
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario
     * @param    int       $project_id   ID del proyecto
     * @param    string    $date_from    Fecha inicial
     * @param    string    $date_to      Fecha final
     * @param    string    $status       Estado de la hoja
     * @return   int                     Total de hojas
     */
    private function get_total_filtered_worksheets($user_id, $project_id = 0, $date_from = '', $date_to = '', $status = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_worksheets';
        
        $query = "SELECT COUNT(*) FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        // Aplicar filtros
        if (!empty($project_id)) {
            $query .= " AND project_id = %d";
            $params[] = $project_id;
        }
        
        if (!empty($date_from)) {
            $query .= " AND work_date >= %s";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND work_date <= %s";
            $params[] = $date_to;
        }
        
        if (!empty($status)) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        return $wpdb->get_var($wpdb->prepare($query, $params));
    }

    /**
     * Procesa la exportación de hojas de trabajo
     *
     * @since    1.0.0
     */
    public function ajax_export_worksheets() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_worksheets_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para exportar tus hojas de trabajo.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        
        // Obtener parámetros de filtrado
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        // Obtener todas las hojas filtradas (sin límite)
        $worksheets = $this->get_filtered_worksheets(
            $user_id,
            $project_id,
            $date_from,
            $date_to,
            $status,
            1000, // Límite más alto para exportación
            0
        );
        
        if (empty($worksheets)) {
            wp_send_json_error(__('No hay hojas de trabajo para exportar con los filtros seleccionados.', 'worker-portal'));
            return;
        }
        
        // Exportar las hojas de trabajo
        $export_result = $this->export_worksheets_to_csv($worksheets);
        
        if (is_wp_error($export_result)) {
            wp_send_json_error($export_result->get_error_message());
            return;
        }
        
        // Devolver la URL del archivo generado
        wp_send_json_success(array(
            'file_url' => $export_result['file_url'],
            'filename' => $export_result['filename'],
            'count' => count($worksheets)
        ));
    }
    
    /**
     * Exporta hojas de trabajo a un archivo CSV
     *
     * @since    1.0.0
     * @param    array    $worksheets    Lista de hojas de trabajo
     * @return   array|WP_Error         Información del archivo o error
     */
    private function export_worksheets_to_csv($worksheets) {
        // Obtener configuración
        $system_types = get_option('worker_portal_system_types', $this->default_options['system_types']);
        $unit_types = get_option('worker_portal_unit_types', $this->default_options['unit_types']);
        $difficulty_levels = get_option('worker_portal_difficulty_levels', $this->default_options['difficulty_levels']);
        
        // Generar nombre de archivo
        $filename = 'hojas_trabajo_' . date('Y-m-d') . '.csv';
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/worker-portal/exports/';
        $file_path = $export_dir . $filename;
        
        // Crear directorio si no existe
        if (!file_exists($export_dir)) {
            if (!wp_mkdir_p($export_dir)) {
                return new WP_Error('directory_error', __('No se pudo crear el directorio de exportación.', 'worker-portal'));
            }
            
            // Añadir archivo index.php para proteger el directorio
            file_put_contents($export_dir . 'index.php', '<?php // Silence is golden');
        }
        
        // Abrir archivo para escritura
        $file = fopen($file_path, 'w');
        if ($file === false) {
            return new WP_Error('file_error', __('No se pudo crear el archivo de exportación.', 'worker-portal'));
        }
        
        // Escribir BOM para UTF-8
        fputs($file, "\xEF\xBB\xBF");
        
        // Escribir cabecera
        fputcsv($file, array(
            __('Fecha', 'worker-portal'),
            __('Proyecto', 'worker-portal'),
            __('Ubicación', 'worker-portal'),
            __('Dificultad', 'worker-portal'),
            __('Sistema', 'worker-portal'),
            __('Unidad', 'worker-portal'),
            __('Cantidad', 'worker-portal'),
            __('Horas', 'worker-portal'),
            __('Estado', 'worker-portal'),
            __('Notas', 'worker-portal')
        ));
        
        // Escribir datos
        foreach ($worksheets as $worksheet) {
            // Obtener nombres legibles
            $difficulty_text = isset($difficulty_levels[$worksheet['difficulty']]) 
                ? $difficulty_levels[$worksheet['difficulty']] 
                : ucfirst($worksheet['difficulty']);
                
            $system_text = isset($system_types[$worksheet['system_type']]) 
                ? $system_types[$worksheet['system_type']] 
                : $worksheet['system_type'];
                
            $unit_text = $worksheet['unit_type'] == 'm2' 
                ? __('Metros cuadrados', 'worker-portal') 
                : __('Horas', 'worker-portal');
                
            $status_text = $worksheet['status'] === 'pending' 
                ? __('Pendiente', 'worker-portal') 
                : __('Validada', 'worker-portal');
            
            // Escribir fila
            fputcsv($file, array(
                date_i18n(get_option('date_format'), strtotime($worksheet['work_date'])),
                $worksheet['project_name'],
                $worksheet['project_location'],
                $difficulty_text,
                $system_text,
                $unit_text,
                $worksheet['quantity'],
                $worksheet['hours'],
                $status_text,
                $worksheet['notes']
            ));
        }
        
        // Cerrar archivo
        fclose($file);
        
        // Devolver información del archivo generado
        return array(
            'file_url' => $upload_dir['baseurl'] . '/worker-portal/exports/' . $filename,
            'filename' => $filename
        );
    }

    /**
     * Carga hojas de trabajo para el panel de administración
     *
     * @since    1.0.0
     */
    public function ajax_admin_load_worksheets() {
        // Verificar nonce
        check_ajax_referer('worker_portal_ajax_nonce', 'nonce');
        
        // Verificar permisos de administrador
        if (!current_user_can('manage_options') && !$this->is_worksheet_validator(get_current_user_id())) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener parámetros de filtrado
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        // Obtener parámetros de paginación
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 10;
        $offset = ($page - 1) * $per_page;
        
        // Obtener hojas de trabajo
        $worksheets = $this->get_admin_filtered_worksheets(
            $user_id,
            $project_id,
            $date_from,
            $date_to,
            $status,
            $per_page,
            $offset
        );
        
        // Obtener total de elementos
        $total_items = $this->get_admin_total_worksheets(
            $user_id,
            $project_id,
            $date_from,
            $date_to,
            $status
        );
        
        // Crear respuesta HTML
        $response = $this->generate_admin_worksheets_html($worksheets, $page, $per_page, $total_items);
        
        wp_send_json_success($response);
    }
    
    /**
     * Obtiene hojas de trabajo filtradas para el admin
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario (0 para todos)
     * @param    int       $project_id   ID del proyecto (0 para todos)
     * @param    string    $date_from    Fecha inicial
     * @param    string    $date_to      Fecha final
     * @param    string    $status       Estado de la hoja
     * @param    int       $limit        Límite de resultados
     * @param    int       $offset       Offset para paginación
     * @return   array                   Lista de hojas de trabajo
     */
    private function get_admin_filtered_worksheets($user_id = 0, $project_id = 0, $date_from = '', $date_to = '', $status = '', $limit = 10, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_worksheets';
        $projects_table = $wpdb->prefix . 'worker_projects';
        $users_table = $wpdb->users;
        
        $query = "SELECT w.*, u.display_name as user_name, p.name as project_name
                FROM $table_name w
                LEFT JOIN $users_table u ON w.user_id = u.ID
                LEFT JOIN $projects_table p ON w.project_id = p.id
                WHERE 1=1";
        $params = array();
        
        // Aplicar filtros
        if ($user_id > 0) {
            $query .= " AND w.user_id = %d";
            $params[] = $user_id;
        }
        
        if ($project_id > 0) {
            $query .= " AND w.project_id = %d";
            $params[] = $project_id;
        }
        
        if (!empty($date_from)) {
            $query .= " AND w.work_date >= %s";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND w.work_date <= %s";
            $params[] = $date_to;
        }
        
        if (!empty($status)) {
            $query .= " AND w.status = %s";
            $params[] = $status;
        }
        
        // Ordenar y limitar resultados
        $query .= " ORDER BY w.work_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results(
            empty($params) ? $query : $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }
    /**
     * Obtiene el total de hojas de trabajo para admin
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario (0 para todos)
     * @param    int       $project_id   ID del proyecto (0 para todos)
     * @param    string    $date_from    Fecha inicial
     * @param    string    $date_to      Fecha final
     * @param    string    $status       Estado de la hoja
     * @return   int                     Total de hojas
     */
    private function get_admin_total_worksheets($user_id = 0, $project_id = 0, $date_from = '', $date_to = '', $status = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_worksheets';
        
        $query = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
        $params = array();
        
        // Aplicar filtros
        if ($user_id > 0) {
            $query .= " AND user_id = %d";
            $params[] = $user_id;
        }
        
        if ($project_id > 0) {
            $query .= " AND project_id = %d";
            $params[] = $project_id;
        }
        
        if (!empty($date_from)) {
            $query .= " AND work_date >= %s";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND work_date <= %s";
            $params[] = $date_to;
        }
        
        if (!empty($status)) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        return $wpdb->get_var(
            empty($params) ? $query : $wpdb->prepare($query, $params)
        );
    }
    
    /**
     * Genera el HTML para la tabla de hojas de trabajo en el admin
     *
     * @since    1.0.0
     * @param    array     $worksheets    Lista de hojas de trabajo
     * @param    int       $current_page  Página actual
     * @param    int       $per_page      Elementos por página
     * @param    int       $total_items   Total de elementos
     * @return   array                    HTML y datos para la respuesta
     */
    private function generate_admin_worksheets_html($worksheets, $current_page, $per_page, $total_items) {
        // Obtener configuración
        $system_types = get_option('worker_portal_system_types', $this->default_options['system_types']);
        $difficulty_levels = get_option('worker_portal_difficulty_levels', $this->default_options['difficulty_levels']);
        
        // Calcular total de páginas
        $total_pages = ceil($total_items / $per_page);
        
        ob_start();
        ?>
        <div class="worker-portal-admin-table-container">
            <?php if (empty($worksheets)): ?>
                <p><?php _e('No se encontraron hojas de trabajo.', 'worker-portal'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Fecha', 'worker-portal'); ?></th>
                            <th><?php _e('Trabajador', 'worker-portal'); ?></th>
                            <th><?php _e('Proyecto', 'worker-portal'); ?></th>
                            <th><?php _e('Dificultad', 'worker-portal'); ?></th>
                            <th><?php _e('Sistema', 'worker-portal'); ?></th>
                            <th><?php _e('Cantidad', 'worker-portal'); ?></th>
                            <th><?php _e('Horas', 'worker-portal'); ?></th>
                            <th><?php _e('Estado', 'worker-portal'); ?></th>
                            <th><?php _e('Acciones', 'worker-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($worksheets as $worksheet): ?>
                            <tr>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($worksheet['work_date'])); ?></td>
                                <td><?php echo esc_html($worksheet['user_name']); ?></td>
                                <td><?php echo esc_html($worksheet['project_name']); ?></td>
                                <td>
                                    <?php 
                                    echo isset($difficulty_levels[$worksheet['difficulty']]) 
                                        ? esc_html($difficulty_levels[$worksheet['difficulty']]) 
                                        : esc_html(ucfirst($worksheet['difficulty'])); 
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    echo isset($system_types[$worksheet['system_type']]) 
                                        ? esc_html($system_types[$worksheet['system_type']]) 
                                        : esc_html($worksheet['system_type']); 
                                    ?>
                                </td>
                                <td><?php echo esc_html($worksheet['quantity']); ?> <?php echo $worksheet['unit_type'] == 'm2' ? 'm²' : 'H'; ?></td>
                                <td><?php echo esc_html($worksheet['hours']); ?> h</td>
                                <td>
                                    <?php
                                    $status_class = $worksheet['status'] == 'pending' ? 'worker-portal-badge-warning' : 'worker-portal-badge-success';
                                    $status_text = $worksheet['status'] == 'pending' ? 'Pendiente' : 'Validada';
                                    ?>
                                    <span class="worker-portal-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($worksheet['status'] === 'pending'): ?>
                                        <button class="button button-small validate-worksheet" data-id="<?php echo esc_attr($worksheet['id']); ?>">
                                            <?php _e('Validar', 'worker-portal'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <button class="button button-small view-worksheet" data-id="<?php echo esc_attr($worksheet['id']); ?>">
                                        <?php _e('Detalles', 'worker-portal'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo sprintf(__('%s elementos', 'worker-portal'), number_format_i18n($total_items)); ?></span>
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page,
                            'type' => 'plain'
                        ));
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        
        $html = ob_get_clean();
        
        return array(
            'html' => $html,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'current_page' => $current_page
        );
    }

    /**
     * Maneja la petición AJAX para validar una hoja de trabajo
     *
     * @since    1.0.0
     */
    public function ajax_admin_validate_worksheet() {
        // Verificar nonce
        check_ajax_referer('worker_portal_ajax_nonce', 'nonce');
        
        // Verificar que el usuario tiene permisos
        if (!is_user_logged_in() || !$this->is_worksheet_validator(get_current_user_id())) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener ID de la hoja
        $worksheet_id = isset($_POST['worksheet_id']) ? intval($_POST['worksheet_id']) : 0;
        
        if ($worksheet_id <= 0) {
            wp_send_json_error(__('ID de hoja de trabajo no válido', 'worker-portal'));
        }
        
        // Validar la hoja de trabajo
        $result = $this->validate_worksheet($worksheet_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'message' => __('Hoja de trabajo validada correctamente', 'worker-portal')
        ));
    }

    /**
     * Valida una hoja de trabajo
     *
     * @since    1.0.0
     * @param    int       $worksheet_id    ID de la hoja de trabajo
     * @return   bool|WP_Error             True si se validó o error
     */
    private function validate_worksheet($worksheet_id) {
        global $wpdb;
        
        // Verificar que la hoja existe y está pendiente
        $worksheet = $this->get_worksheet_details($worksheet_id);
        
        if (is_wp_error($worksheet)) {
            return $worksheet;
        }
        
        if ($worksheet['status'] !== 'pending') {
            return new WP_Error('already_processed', __('La hoja de trabajo ya ha sido procesada', 'worker-portal'));
        }
        
        // Validar la hoja
        $updated = $wpdb->update(
            $wpdb->prefix . 'worker_worksheets',
            array(
                'status' => 'validated',
                'validated_by' => get_current_user_id(),
                'validated_date' => current_time('mysql')
            ),
            array('id' => $worksheet_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        if ($updated === false) {
            return new WP_Error('update_error', __('Error al validar la hoja de trabajo. Por favor, inténtalo de nuevo.', 'worker-portal'));
        }
        
        // Calcular incentivo
        $this->calculate_incentive($worksheet_id);
        
        // Enviar notificación al trabajador
        $this->send_validation_notification($worksheet_id);
        
        return true;
    }

    /**
     * Maneja la petición AJAX para acciones masivas de hojas de trabajo
     *
     * @since    1.0.0
     */
    public function ajax_admin_bulk_worksheet_action() {
        // Verificar nonce
        check_ajax_referer('worker_portal_ajax_nonce', 'nonce');
        
        // Verificar que el usuario tiene permisos
        if (!is_user_logged_in() || !$this->is_worksheet_validator(get_current_user_id())) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener acción y hojas seleccionadas
        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $worksheet_ids = isset($_POST['worksheet_ids']) ? array_map('intval', $_POST['worksheet_ids']) : array();
        
        if (empty($action) || $action !== 'validate') {
            wp_send_json_error(__('Acción no válida', 'worker-portal'));
        }
        
        if (empty($worksheet_ids)) {
            wp_send_json_error(__('No se han seleccionado hojas de trabajo', 'worker-portal'));
        }
        
        // Procesar las hojas seleccionadas
        $processed = 0;
        $errors = array();
        
        foreach ($worksheet_ids as $worksheet_id) {
            // Validar la hoja
            $result = $this->validate_worksheet($worksheet_id);
            
            if (is_wp_error($result)) {
                $errors[] = sprintf(
                    __('Error al validar la hoja #%d: %s', 'worker-portal'),
                    $worksheet_id,
                    $result->get_error_message()
                );
            } else {
                $processed++;
            }
        }
        
        // Enviar respuesta
        if ($processed === 0) {
            wp_send_json_error(__('No se ha podido procesar ninguna hoja de trabajo', 'worker-portal'));
        } else {
            $response = array(
                'message' => sprintf(
                    _n(
                        '%d hoja de trabajo validada correctamente', 
                        '%d hojas de trabajo validadas correctamente', 
                        $processed, 
                        'worker-portal'
                    ),
                    $processed
                ),
                'processed' => $processed
            );
            
            if (!empty($errors)) {
                $response['errors'] = $errors;
            }
            
            wp_send_json_success($response);
        }
    }

    /**
     * Obtener detalles de una hoja de trabajo para mostrar en modal de admin
     *
     * @since    1.0.0
     */
    public function ajax_admin_get_worksheet_details() {
        // Verificar nonce
        check_ajax_referer('worker_portal_ajax_nonce', 'nonce');
        
        // Verificar que el usuario tiene permisos
        if (!is_user_logged_in() || !$this->is_worksheet_validator(get_current_user_id())) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener ID de la hoja
        $worksheet_id = isset($_POST['worksheet_id']) ? intval($_POST['worksheet_id']) : 0;
        
        if ($worksheet_id <= 0) {
            wp_send_json_error(__('ID de hoja de trabajo no válido', 'worker-portal'));
        }
        
        // Obtener detalles de la hoja
        $worksheet = $this->get_worksheet_details($worksheet_id);
        
        if (is_wp_error($worksheet)) {
            wp_send_json_error($worksheet->get_error_message());
        }
        
        // Generar HTML para los detalles
        $html = $this->generate_worksheet_details_html($worksheet);
        
        wp_send_json_success($html);
    }

    /**
     * Calcula incentivo para una hoja de trabajo validada
     *
     * @since    1.0.0
     * @param    int    $worksheet_id    ID de la hoja de trabajo
     */
    private function calculate_incentive($worksheet_id) {
        $worksheet = $this->get_worksheet_details($worksheet_id);
        
        if (is_wp_error($worksheet)) {
            return;
        }
        
        // Factores de incentivo según dificultad
        $difficulty_factors = array(
            'baja' => 0.5,
            'media' => 1.0,
            'alta' => 1.5
        );
        
        // Factor según sistema
        $system_factors = array(
            'estructura_techo' => 2.0,
            'estructura_tabique' => 1.4,
            'aplacado_simple' => 1.0,
            'aplacado_doble' => 1.8,
            'horas_ayuda' => 0.5
        );
        
        // Calcular incentivo
        $difficulty_factor = isset($difficulty_factors[$worksheet['difficulty']]) 
            ? $difficulty_factors[$worksheet['difficulty']] 
            : 1.0;
        
        $system_factor = isset($system_factors[$worksheet['system_type']]) 
            ? $system_factors[$worksheet['system_type']] 
            : 1.0;
        
        // Para metros cuadrados, el incentivo es por exceso de metros
        $incentive_amount = 0;
        if ($worksheet['unit_type'] == 'm2') {
            // Metros base según sistema (los que se esperan normalmente)
            $base_meters = array(
                'estructura_techo' => 25,
                'estructura_tabique' => 30,
                'aplacado_simple' => 35,
                'aplacado_doble' => 20,
                'horas_ayuda' => 0
            );
            
            $expected_meters = isset($base_meters[$worksheet['system_type']]) 
                ? $base_meters[$worksheet['system_type']] 
                : 30;
            
            // Si hay más metros de los esperados, calcular incentivo
            if ($worksheet['quantity'] > $expected_meters) {
                $excess_meters = $worksheet['quantity'] - $expected_meters;
                // El incentivo es 1€ por metro extra, ajustado por factores
                $incentive_amount = $excess_meters * $difficulty_factor * $system_factor;
            }
        } else {
            // Para horas, solo hay incentivo si completa el trabajo en menos tiempo del esperado
            $expected_hours = 8; // 8 horas por defecto
            
            if ($worksheet['hours'] < $expected_hours) {
                $saved_hours = $expected_hours - $worksheet['hours'];
                // El incentivo es 2€ por hora ahorrada
                $incentive_amount = $saved_hours * 2.0 * $difficulty_factor;
            }
        }
        
        // Si hay incentivo, registrarlo
        if ($incentive_amount > 0) {
            $this->register_incentive($worksheet, $incentive_amount);
        }
    }
    
    /**
     * Registra un incentivo en la base de datos
     *
     * @since    1.0.0
     * @param    array     $worksheet         Datos de la hoja de trabajo
     * @param    float     $incentive_amount  Cantidad del incentivo
     */
    private function register_incentive($worksheet, $incentive_amount) {
        global $wpdb;
        
        $incentives_table = $wpdb->prefix . 'worker_incentives';
        
        // Obtener datos necesarios
        $system_types = get_option('worker_portal_system_types', $this->default_options['system_types']);
        $system_type_name = isset($system_types[$worksheet['system_type']]) 
            ? $system_types[$worksheet['system_type']] 
            : $worksheet['system_type'];
        
        // Crear descripción
        $description = sprintf(
            __('%s %s de %s', 'worker-portal'),
            $worksheet['quantity'],
            $worksheet['unit_type'] == 'm2' ? 'm²' : 'horas',
            $system_type_name
        );
        
        // Insertar incentivo
        $wpdb->insert(
            $incentives_table,
            array(
                'user_id' => $worksheet['user_id'],
                'worksheet_id' => $worksheet['id'],
                'calculation_date' => current_time('mysql'),
                'description' => $description,
                'amount' => round($incentive_amount, 2),
                'status' => 'pending' // Pendiente de aprobación
            ),
            array('%d', '%d', '%s', '%s', '%f', '%s')
        );
    }

    /**
     * Envía una notificación al trabajador cuando su hoja es validada
     *
     * @since    1.0.0
     * @param    int    $worksheet_id    ID de la hoja de trabajo
     */
    private function send_validation_notification($worksheet_id) {
        // Obtener detalles de la hoja
        $worksheet = $this->get_worksheet_details($worksheet_id);
        
        if (is_wp_error($worksheet)) {
            return;
        }
        
        // Obtener datos del usuario
        $user = get_userdata($worksheet['user_id']);
        
        if (!$user || !is_email($user->user_email)) {
            return;
        }
        
        // Configurar asunto del email
        $subject = sprintf(
            __('[%s] Tu hoja de trabajo ha sido validada', 'worker-portal'),
            get_bloginfo('name')
        );
        
        // Texto del mensaje
        $message = sprintf(
            __('Hola %s,

Tu hoja de trabajo del %s para el proyecto "%s" ha sido validada por %s.

Puedes ver todas tus hojas de trabajo en el Portal del Trabajador.

Saludos,
%s', 'worker-portal'),
            $user->display_name,
            date_i18n(get_option('date_format'), strtotime($worksheet['work_date'])),
            $worksheet['project_name'],
            isset($worksheet['validator_name']) ? $worksheet['validator_name'] : __('un administrador', 'worker-portal'),
            get_bloginfo('name')
        );
        
        // Enviar email
        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Métodos de API REST para futura implementación
     */
    public function rest_get_worksheets($request) {
        return new WP_REST_Response(array('message' => 'Not implemented yet'), 501);
    }
    
    public function rest_create_worksheet($request) {
        return new WP_REST_Response(array('message' => 'Not implemented yet'), 501);
    }
    
    public function rest_get_worksheet($request) {
        return new WP_REST_Response(array('message' => 'Not implemented yet'), 501);
    }
    
    public function rest_update_worksheet($request) {
        return new WP_REST_Response(array('message' => 'Not implemented yet'), 501);
    }
    
    public function rest_delete_worksheet($request) {
        return new WP_REST_Response(array('message' => 'Not implemented yet'), 501);
    }
    
    public function rest_validate_worksheet($request) {
        return new WP_REST_Response(array('message' => 'Not implemented yet'), 501);
    }
}
