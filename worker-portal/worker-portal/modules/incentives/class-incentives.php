<?php
/**
 * Módulo de Incentivos
 *
 * @since      1.0.0
 */
class Worker_Portal_Module_Incentives {

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
            'incentive_types' => array(
                'excess_meters' => 'Plus de productividad por exceso de metros ejecutados',
                'quality' => 'Plus de calidad',
                'efficiency' => 'Plus de eficiencia',
                'other' => 'Otros'
            ),
            'incentive_notification_email' => get_option('admin_email')
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
        add_shortcode('worker_incentives', array($this, 'render_incentives_shortcode'));

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
        add_action('wp_ajax_get_user_incentives', array($this, 'ajax_get_user_incentives'));
        add_action('wp_ajax_filter_incentives', array($this, 'ajax_filter_incentives'));
    }

    /**
     * Registra los endpoints de acciones AJAX para el admin
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_admin_ajax_actions() {
        // Acciones AJAX para administradores
        add_action('wp_ajax_admin_add_incentive', array($this, 'ajax_admin_add_incentive'));
        add_action('wp_ajax_admin_approve_incentive', array($this, 'ajax_admin_approve_incentive'));
        add_action('wp_ajax_admin_reject_incentive', array($this, 'ajax_admin_reject_incentive'));
        add_action('wp_ajax_admin_delete_incentive', array($this, 'ajax_admin_delete_incentive'));
        add_action('wp_ajax_admin_get_incentive_details', array($this, 'ajax_admin_get_incentive_details'));
        add_action('wp_ajax_admin_load_incentives', array($this, 'ajax_admin_load_incentives'));
        add_action('wp_ajax_admin_calculate_worksheet_incentive', array($this, 'ajax_admin_calculate_worksheet_incentive'));
    }

    /**
     * Carga los scripts y estilos necesarios
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Cargar estilos
        wp_enqueue_style(
            'worker-portal-incentives',
            WORKER_PORTAL_URL . 'modules/incentives/css/incentives.css',
            array('worker-portal-public'),
            WORKER_PORTAL_VERSION,
            'all'
        );
        
        // Cargar scripts
        wp_register_script(
            'worker-portal-incentives',
            WORKER_PORTAL_URL . 'modules/incentives/js/incentives.js',
            array('jquery'),
            WORKER_PORTAL_VERSION,
            true
        );
        
        // Localizar script con variables necesarias
        wp_localize_script(
            'worker-portal-incentives',
            'workerPortalIncentives',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('worker_portal_incentives_nonce'),
                'admin_nonce' => wp_create_nonce('worker_portal_admin_nonce'),
                'i18n' => array(
                    'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'),
                    'success' => __('Operación completada con éxito.', 'worker-portal'),
                    'loading' => __('Cargando...', 'worker-portal'),
                    'no_incentives' => __('No hay incentivos disponibles.', 'worker-portal')
                )
            )
        );

        // Enqueue el script después de localizarlo
        wp_enqueue_script('worker-portal-incentives');
    }

    /**
     * Renderiza el shortcode para mostrar los incentivos del usuario
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            HTML generado
     */
    public function render_incentives_shortcode($atts) {
        // Si el usuario no está logueado, mostrar mensaje de error
        if (!is_user_logged_in()) {
            return '<div class="worker-portal-error">' . __('Debes iniciar sesión para ver tus incentivos.', 'worker-portal') . '</div>';
        }
        
        // Atributos por defecto
        $atts = shortcode_atts(
            array(
                'limit' => 10,     // Número de incentivos a mostrar
                'type' => ''       // Tipo de incentivo a mostrar
            ),
            $atts,
            'worker_incentives'
        );
        
        // Obtener el usuario actual
        $user_id = get_current_user_id();
        
        // Obtener los incentivos del usuario
        $incentives = $this->get_user_incentives($user_id, $atts['limit'], 0, $atts['type']);
        
        // Obtener los tipos de incentivos disponibles
        $incentive_types = get_option('worker_portal_incentive_types', $this->default_options['incentive_types']);
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir plantilla
        include(WORKER_PORTAL_PATH . 'modules/incentives/templates/incentives-view.php');
        
        // Retornar el contenido
        return ob_get_clean();
    }

    /**
     * Obtiene los incentivos de un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id     ID del usuario
     * @param    int       $limit       Número máximo de incentivos a obtener
     * @param    int       $offset      Desplazamiento para paginación
     * @param    string    $type        Tipo de incentivo (opcional)
     * @return   array                  Lista de incentivos
     */
    public function get_user_incentives($user_id, $limit = 10, $offset = 0, $type = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_incentives';
        
        $query = "SELECT i.*, w.work_date, w.system_type, w.quantity, p.name as project_name 
                 FROM $table_name i
                 LEFT JOIN {$wpdb->prefix}worker_worksheets w ON i.worksheet_id = w.id 
                 LEFT JOIN {$wpdb->prefix}worker_projects p ON w.project_id = p.id
                 WHERE i.user_id = %d";
        $params = array($user_id);
        
        if (!empty($type)) {
            $query .= " AND i.incentive_type = %s";
            $params[] = $type;
        }
        
        $query .= " ORDER BY i.calculation_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        $incentives = $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
        
        return $incentives;
    }

    /**
     * Maneja la petición AJAX para obtener incentivos del usuario
     *
     * @since    1.0.0
     */
    public function ajax_get_user_incentives() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_incentives_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para ver tus incentivos.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        
        // Parámetros de paginación
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $offset = ($page - 1) * $per_page;
        
        // Obtener tipo si existe
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        
        // Obtener incentivos
        $incentives = $this->get_user_incentives($user_id, $per_page, $offset, $type);
        
        // Obtener total para paginación
        global $wpdb;
        $table_name = $wpdb->prefix . 'worker_incentives';
        
        $count_query = "SELECT COUNT(*) FROM $table_name WHERE user_id = %d";
        $count_params = array($user_id);
        
        if (!empty($type)) {
            $count_query .= " AND incentive_type = %s";
            $count_params[] = $type;
        }
        
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, $count_params));
        $total_pages = ceil($total_items / $per_page);
        
        // Generar HTML de la tabla
        $html = $this->generate_incentives_table($incentives, $page, $per_page, $total_items, $total_pages);
        
        wp_send_json_success(array(
            'html' => $html,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'current_page' => $page
        ));
    }

    /**
     * Genera el HTML para la tabla de incentivos
     *
     * @since    1.0.0
     * @param    array    $incentives    Lista de incentivos
     * @param    int      $current_page  Página actual
     * @param    int      $per_page      Elementos por página
     * @param    int      $total_items   Total de elementos
     * @param    int      $total_pages   Total de páginas
     * @return   string                  HTML generado
     */
    private function generate_incentives_table($incentives, $current_page, $per_page, $total_items, $total_pages) {
        // Obtener tipos de incentivos
        $incentive_types = get_option('worker_portal_incentive_types', $this->default_options['incentive_types']);
        
        ob_start();
        
        if (empty($incentives)) {
            echo '<p class="worker-portal-no-data">' . __('No hay incentivos disponibles.', 'worker-portal') . '</p>';
        } else {
            ?>
            <div class="worker-portal-table-responsive">
                <table class="worker-portal-table worker-portal-incentives-table">
                    <thead>
                        <tr>
                            <th><?php _e('FECHA', 'worker-portal'); ?></th>
                            <th><?php _e('PLUS DE PRODUCTIVIDAD', 'worker-portal'); ?></th>
                            <th><?php _e('IMPORTE', 'worker-portal'); ?></th>
                            <th><?php _e('VALIDACIÓN', 'worker-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incentives as $incentive): ?>
                            <tr data-incentive-id="<?php echo esc_attr($incentive['id']); ?>">
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($incentive['calculation_date'])); ?></td>
                                <td><?php echo esc_html($incentive['description']); ?></td>
                                <td><?php echo esc_html(number_format($incentive['amount'], 2, ',', '.')); ?> €</td>
                                <td>
                                    <?php 
                                    switch ($incentive['status']) {
                                        case 'pending':
                                            echo '<span class="worker-portal-badge worker-portal-badge-warning">' . __('PENDIENTE', 'worker-portal') . '</span>';
                                            break;
                                        case 'approved':
                                            echo '<span class="worker-portal-badge worker-portal-badge-success">' . __('APROBADO', 'worker-portal') . '</span>';
                                            break;
                                        case 'rejected':
                                            echo '<span class="worker-portal-badge worker-portal-badge-danger">' . __('DENEGADO', 'worker-portal') . '</span>';
                                            break;
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php
            // Mostrar total de incentivos aprobados
            $total_approved = 0;
            foreach ($incentives as $incentive) {
                if ($incentive['status'] === 'approved') {
                    $total_approved += $incentive['amount'];
                }
            }
            ?>
            
            <div class="worker-portal-incentives-total">
                <p class="worker-portal-incentives-total-label"><?php _e('TOTAL PLUS DE PRODUCTIVIDAD', 'worker-portal'); ?></p>
                <p class="worker-portal-incentives-total-value"><?php echo number_format($total_approved, 2, ',', '.'); ?> euros</p>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="worker-portal-pagination">
                    <div class="worker-portal-pagination-info">
                        <?php
                        printf(
                            __('Mostrando %1$s - %2$s de %3$s incentivos', 'worker-portal'),
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
        }
        
        return ob_get_clean();
    }

    /**
     * Maneja la petición AJAX para filtrar incentivos
     *
     * @since    1.0.0
     */
    public function ajax_filter_incentives() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_incentives_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para ver tus incentivos.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        
        // Parámetros de paginación
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $offset = ($page - 1) * $per_page;
        
        // Parámetros de filtrado
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        // Obtener incentivos filtrados
        $incentives = $this->get_filtered_incentives($user_id, $type, $status, $date_from, $date_to, $per_page, $offset);
        
        // Obtener total para paginación
        $total_items = $this->get_total_filtered_incentives($user_id, $type, $status, $date_from, $date_to);
        $total_pages = ceil($total_items / $per_page);
        
        // Generar HTML de la tabla
        $html = $this->generate_incentives_table($incentives, $page, $per_page, $total_items, $total_pages);
        
        wp_send_json_success(array(
            'html' => $html,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'current_page' => $page
        ));
    }

    /**
     * Obtiene incentivos filtrados de un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario
     * @param    string    $type         Tipo de incentivo
     * @param    string    $status       Estado del incentivo
     * @param    string    $date_from    Fecha de inicio
     * @param    string    $date_to      Fecha de fin
     * @param    int       $limit        Límite de resultados
     * @param    int       $offset       Offset para paginación
     * @return   array                   Lista de incentivos
     */
    private function get_filtered_incentives($user_id, $type = '', $status = '', $date_from = '', $date_to = '', $limit = 10, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_incentives';
        
        $query = "SELECT i.*, w.work_date, w.system_type, w.quantity, p.name as project_name  
                 FROM $table_name i
                 LEFT JOIN {$wpdb->prefix}worker_worksheets w ON i.worksheet_id = w.id 
                 LEFT JOIN {$wpdb->prefix}worker_projects p ON w.project_id = p.id
                 WHERE i.user_id = %d";
        $params = array($user_id);
        
        // Aplicar filtros
        if (!empty($type)) {
            $query .= " AND i.incentive_type = %s";
            $params[] = $type;
        }
        
        if (!empty($status)) {
            $query .= " AND i.status = %s";
            $params[] = $status;
        }
        
        if (!empty($date_from)) {
            $query .= " AND i.calculation_date >= %s";
            $params[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $query .= " AND i.calculation_date <= %s";
            $params[] = $date_to . ' 23:59:59';
        }
        
        // Ordenar y limitar resultados
        $query .= " ORDER BY i.calculation_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }

    /**
     * Obtiene el total de incentivos filtrados
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario
     * @param    string    $type         Tipo de incentivo
     * @param    string    $status       Estado del incentivo
     * @param    string    $date_from    Fecha de inicio
     * @param    string    $date_to      Fecha de fin
     * @return   int                     Total de incentivos
     */
    private function get_total_filtered_incentives($user_id, $type = '', $status = '', $date_from = '', $date_to = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_incentives';
        
        $query = "SELECT COUNT(*) FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        // Aplicar filtros
        if (!empty($type)) {
            $query .= " AND incentive_type = %s";
            $params[] = $type;
        }
        
        if (!empty($status)) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        if (!empty($date_from)) {
            $query .= " AND calculation_date >= %s";
            $params[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $query .= " AND calculation_date <= %s";
            $params[] = $date_to . ' 23:59:59';
        }
        
        return $wpdb->get_var($wpdb->prepare($query, $params));
    }

    /**
     * Registra los endpoints de la API REST
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route('worker-portal/v1', '/incentives', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'rest_get_incentives'),
                'permission_callback' => array($this, 'rest_permissions_check')
            )
        ));
        
        register_rest_route('worker-portal/v1', '/incentives/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'rest_get_incentive'),
                'permission_callback' => array($this, 'rest_permissions_check')
            )
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
     * Método para el endpoint REST de listado de incentivos
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   WP_REST_Response               Respuesta REST
     */
    public function rest_get_incentives($request) {
        // Para implementación futura
        return new WP_REST_Response(array('message' => 'Not implemented yet'), 501);
    }

    /**
     * Método para el endpoint REST de detalle de incentivo
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   WP_REST_Response               Respuesta REST
     */
    public function rest_get_incentive($request) {
        // Para implementación futura
        return new WP_REST_Response(array('message' => 'Not implemented yet'), 501);
    }

    /**
     * Maneja la petición AJAX para añadir un incentivo (admin)
     *
     * @since    1.0.0
     */
    public function ajax_admin_add_incentive() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || 
            (!wp_verify_nonce($_POST['nonce'], 'worker_portal_incentives_nonce') && 
             !wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce'))) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar permisos de administrador
        if (!current_user_can('manage_options') && !current_user_can('wp_worker_manage_incentives')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Validar datos
        $worksheet_id = isset($_POST['worksheet_id']) ? intval($_POST['worksheet_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $incentive_type = isset($_POST['incentive_type']) ? sanitize_text_field($_POST['incentive_type']) : 'excess_meters';
        
        if ($user_id <= 0) {
            wp_send_json_error(__('Usuario no válido', 'worker-portal'));
        }
        
        if (empty($description)) {
            wp_send_json_error(__('La descripción es obligatoria', 'worker-portal'));
        }
        
        if ($amount <= 0) {
            wp_send_json_error(__('El importe debe ser mayor que cero', 'worker-portal'));
        }
        
        // Insertar incentivo en la base de datos
        global $wpdb;
        $table_name = $wpdb->prefix . 'worker_incentives';
        
        $data = array(
            'user_id' => $user_id,
            'worksheet_id' => $worksheet_id > 0 ? $worksheet_id : null,
            'incentive_type' => $incentive_type,
            'calculation_date' => current_time('mysql'),
            'description' => $description,
            'amount' => $amount,
            'status' => 'pending'
        );
        
        $result = $wpdb->insert(
            $table_name,
            $data,
            array('%d', '%d', '%s', '%s', '%s', '%f', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(__('Error al guardar el incentivo en la base de datos', 'worker-portal'));
        }
        
        // Obtener ID del incentivo insertado
        $incentive_id = $wpdb->insert_id;
        
        // Enviar notificación al trabajador
        $this->send_incentive_notification($incentive_id);
        
        wp_send_json_success(array(
            'message' => __('Incentivo añadido correctamente', 'worker-portal'),
            'incentive_id' => $incentive_id
        ));
    }

/**
     * Maneja la petición AJAX para aprobar un incentivo (admin)
     *
     * @since    1.0.0
     */
    public function ajax_admin_approve_incentive() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || 
            (!wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce') && 
             !wp_verify_nonce($_POST['nonce'], 'worker_portal_incentives_nonce'))) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar permisos de administrador
        if (!current_user_can('manage_options') && !current_user_can('wp_worker_manage_incentives')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Validar datos
        $incentive_id = isset($_POST['incentive_id']) ? intval($_POST['incentive_id']) : 0;
        
        if ($incentive_id <= 0) {
            wp_send_json_error(__('ID de incentivo no válido', 'worker-portal'));
        }
        
        // Verificar que el incentivo existe y está pendiente
        global $wpdb;
        
        $incentive = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}worker_incentives WHERE id = %d",
                $incentive_id
            ),
            ARRAY_A
        );
        
        if (!$incentive) {
            wp_send_json_error(__('El incentivo no existe', 'worker-portal'));
        }
        
        if ($incentive['status'] !== 'pending') {
            wp_send_json_error(__('El incentivo ya ha sido procesado', 'worker-portal'));
        }
        
        // Actualizar estado del incentivo
        $result = $wpdb->update(
            $wpdb->prefix . 'worker_incentives',
            array(
                'status' => 'approved',
                'approved_by' => get_current_user_id(),
                'approved_date' => current_time('mysql')
            ),
            array('id' => $incentive_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('Error al aprobar el incentivo. Por favor, inténtalo de nuevo.', 'worker-portal'));
        }
        
        // Enviar notificación al trabajador
        $this->send_incentive_status_notification($incentive_id, 'approved');
        
        wp_send_json_success(array(
            'message' => __('Incentivo aprobado correctamente', 'worker-portal')
        ));
    }

    /**
     * Maneja la petición AJAX para rechazar un incentivo (admin)
     *
     * @since    1.0.0
     */
    public function ajax_admin_reject_incentive() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || 
            (!wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce') && 
             !wp_verify_nonce($_POST['nonce'], 'worker_portal_incentives_nonce'))) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario es administrador
        if (!current_user_can('manage_options') && !current_user_can('wp_worker_manage_incentives')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener ID del incentivo
        $incentive_id = isset($_POST['incentive_id']) ? intval($_POST['incentive_id']) : 0;
        
        if ($incentive_id <= 0) {
            wp_send_json_error(__('ID de incentivo no válido', 'worker-portal'));
        }
        
        // Verificar que el incentivo existe y está pendiente
        global $wpdb;
        
        $incentive = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}worker_incentives WHERE id = %d",
                $incentive_id
            ),
            ARRAY_A
        );
        
        if (!$incentive) {
            wp_send_json_error(__('El incentivo no existe', 'worker-portal'));
        }
        
        if ($incentive['status'] !== 'pending') {
            wp_send_json_error(__('El incentivo ya ha sido procesado', 'worker-portal'));
        }
        
        // Actualizar estado del incentivo
        $result = $wpdb->update(
            $wpdb->prefix . 'worker_incentives',
            array(
                'status' => 'rejected',
                'approved_by' => get_current_user_id(),
                'approved_date' => current_time('mysql')
            ),
            array('id' => $incentive_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('Error al actualizar el incentivo. Por favor, inténtalo de nuevo.', 'worker-portal'));
        }
        
        // Enviar notificación al trabajador
        $this->send_incentive_status_notification($incentive_id, 'rejected');
        
        wp_send_json_success(array(
            'message' => __('Incentivo rechazado correctamente', 'worker-portal')
        ));
    }

    /**
     * Maneja la petición AJAX para eliminar un incentivo (admin)
     *
     * @since    1.0.0
     */
    public function ajax_admin_delete_incentive() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || 
            (!wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce') && 
             !wp_verify_nonce($_POST['nonce'], 'worker_portal_incentives_nonce'))) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario es administrador
        if (!current_user_can('manage_options') && !current_user_can('wp_worker_manage_incentives')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener ID del incentivo
        $incentive_id = isset($_POST['incentive_id']) ? intval($_POST['incentive_id']) : 0;
        
        if ($incentive_id <= 0) {
            wp_send_json_error(__('ID de incentivo no válido', 'worker-portal'));
        }
        
        // Verificar que el incentivo existe
        global $wpdb;
        
        $incentive = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}worker_incentives WHERE id = %d",
                $incentive_id
            ),
            ARRAY_A
        );
        
        if (!$incentive) {
            wp_send_json_error(__('El incentivo no existe', 'worker-portal'));
        }
        
        // Eliminar incentivo
        $result = $wpdb->delete(
            $wpdb->prefix . 'worker_incentives',
            array('id' => $incentive_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('Error al eliminar el incentivo. Por favor, inténtalo de nuevo.', 'worker-portal'));
        }
        
        wp_send_json_success(array(
            'message' => __('Incentivo eliminado correctamente', 'worker-portal')
        ));
    }

    /**
     * Maneja la petición AJAX para obtener detalles de un incentivo (admin)
     *
     * @since    1.0.0
     */
    public function ajax_admin_get_incentive_details() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || 
            (!wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce') && 
             !wp_verify_nonce($_POST['nonce'], 'worker_portal_incentives_nonce'))) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario es administrador
        if (!current_user_can('manage_options') && !current_user_can('wp_worker_manage_incentives')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener ID del incentivo
        $incentive_id = isset($_POST['incentive_id']) ? intval($_POST['incentive_id']) : 0;
        
        if ($incentive_id <= 0) {
            wp_send_json_error(__('ID de incentivo no válido', 'worker-portal'));
        }
        
        // Obtener datos del incentivo
        global $wpdb;
        
        $query = "SELECT i.*, u.display_name, w.work_date, w.system_type, w.quantity, p.name as project_name 
                FROM {$wpdb->prefix}worker_incentives i
                LEFT JOIN {$wpdb->users} u ON i.user_id = u.ID
                LEFT JOIN {$wpdb->prefix}worker_worksheets w ON i.worksheet_id = w.id
                LEFT JOIN {$wpdb->prefix}worker_projects p ON w.project_id = p.id
                WHERE i.id = %d";
        
        $incentive = $wpdb->get_row(
            $wpdb->prepare($query, $incentive_id),
            ARRAY_A
        );
        
        if (!$incentive) {
            wp_send_json_error(__('El incentivo no existe', 'worker-portal'));
        }
        
        // Obtener información del aprobador si existe
        $approver_name = '';
        if (!empty($incentive['approved_by'])) {
            $approver = get_userdata($incentive['approved_by']);
            if ($approver) {
                $approver_name = $approver->display_name;
            }
        }
        
        // Obtener tipos de incentivos
        $incentive_types = get_option('worker_portal_incentive_types', $this->default_options['incentive_types']);
        
        // Preparar datos para la respuesta
        $incentive_data = array(
            'id' => $incentive['id'],
            'user_id' => $incentive['user_id'],
            'user_name' => $incentive['display_name'],
            'worksheet_id' => $incentive['worksheet_id'],
            'project_name' => $incentive['project_name'],
            'work_date' => !empty($incentive['work_date']) ? date_i18n(get_option('date_format'), strtotime($incentive['work_date'])) : '',
            'system_type' => $incentive['system_type'],
            'quantity' => $incentive['quantity'],
            'incentive_type' => $incentive['incentive_type'],
            'incentive_type_name' => isset($incentive_types[$incentive['incentive_type']]) ? $incentive_types[$incentive['incentive_type']] : ucfirst($incentive['incentive_type']),
            'calculation_date' => date_i18n(get_option('date_format'), strtotime($incentive['calculation_date'])),
            'description' => $incentive['description'],
            'amount' => $incentive['amount'],
            'status' => $incentive['status'],
            'approved_by' => $incentive['approved_by'],
            'approver_name' => $approver_name,
            'approved_date' => !empty($incentive['approved_date']) ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($incentive['approved_date'])) : ''
        );
        
        wp_send_json_success($incentive_data);
    }

    /**
     * Maneja la petición AJAX para cargar incentivos (admin)
     *
     * @since    1.0.0
     */
    public function ajax_admin_load_incentives() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || 
            (!wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce') && 
             !wp_verify_nonce($_POST['nonce'], 'worker_portal_incentives_nonce'))) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario es administrador
        if (!current_user_can('manage_options') && !current_user_can('wp_worker_manage_incentives')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Parámetros de filtrado
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // Parámetros de paginación
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 15;
        $offset = ($page - 1) * $per_page;
        
        // Obtener incentivos
        global $wpdb;
        
        $query = "SELECT i.*, u.display_name, w.work_date, w.system_type, w.quantity, p.name as project_name
                FROM {$wpdb->prefix}worker_incentives i
                LEFT JOIN {$wpdb->users} u ON i.user_id = u.ID
                LEFT JOIN {$wpdb->prefix}worker_worksheets w ON i.worksheet_id = w.id
                LEFT JOIN {$wpdb->prefix}worker_projects p ON w.project_id = p.id
                WHERE 1=1";
        $params = array();
        
        // Aplicar filtros
        if ($user_id > 0) {
            $query .= " AND i.user_id = %d";
            $params[] = $user_id;
        }
        
        if (!empty($status)) {
            $query .= " AND i.status = %s";
            $params[] = $status;
        }
        
        if (!empty($date_from)) {
            $query .= " AND i.calculation_date >= %s";
            $params[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $query .= " AND i.calculation_date <= %s";
            $params[] = $date_to . ' 23:59:59';
        }
        
        // Orden y límite
        $query .= " ORDER BY i.calculation_date DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        
        // Ejecutar consulta
        $incentives = $wpdb->get_results(
            empty($params) ? $query : $wpdb->prepare($query, $params),
            ARRAY_A
        );
        
        // Consulta para el total de resultados
        $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}worker_incentives i WHERE 1=1";
        $count_params = array();
        
        if ($user_id > 0) {
            $count_query .= " AND i.user_id = %d";
            $count_params[] = $user_id;
        }
        
        if (!empty($status)) {
            $count_query .= " AND i.status = %s";
            $count_params[] = $status;
        }
        
        if (!empty($date_from)) {
            $count_query .= " AND i.calculation_date >= %s";
            $count_params[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $count_query .= " AND i.calculation_date <= %s";
            $count_params[] = $date_to . ' 23:59:59';
        }
        
        $total_items = $wpdb->get_var(
            empty($count_params) ? $count_query : $wpdb->prepare($count_query, $count_params)
        );
        
        $total_pages = ceil($total_items / $per_page);
        
        // Generar HTML de la tabla
        $incentive_types = get_option('worker_portal_incentive_types', $this->default_options['incentive_types']);
        
        ob_start();
        
        if (empty($incentives)):
        ?>
            <div class="worker-portal-no-items">
                <p><?php _e('No hay incentivos con los criterios seleccionados.', 'worker-portal'); ?></p>
            </div>
        <?php else: ?>
            <div class="worker-portal-table-responsive">
                <table class="worker-portal-admin-table">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'worker-portal'); ?></th>
                            <th><?php _e('Fecha', 'worker-portal'); ?></th>
                            <th><?php _e('Trabajador', 'worker-portal'); ?></th>
                            <th><?php _e('Proyecto', 'worker-portal'); ?></th>
                            <th><?php _e('Descripción', 'worker-portal'); ?></th>
                            <th><?php _e('Importe', 'worker-portal'); ?></th>
                            <th><?php _e('Estado', 'worker-portal'); ?></th>
                            <th><?php _e('Acciones', 'worker-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incentives as $incentive): ?>
                            <tr>
                                <td><?php echo esc_html($incentive['id']); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($incentive['calculation_date'])); ?></td>
                                <td><?php echo esc_html($incentive['display_name']); ?></td>
                                <td><?php echo esc_html($incentive['project_name'] ?: '-'); ?></td>
                                <td><?php echo esc_html($incentive['description']); ?></td>
                                <td><?php echo esc_html(number_format($incentive['amount'], 2, ',', '.')); ?> €</td>
                                <td>
                                    <?php
                                    $status_class = 'worker-portal-badge ';
                                    switch ($incentive['status']) {
                                        case 'pending':
                                            $status_class .= 'worker-portal-badge-warning';
                                            $status_text = __('Pendiente', 'worker-portal');
                                            break;
                                        case 'approved':
                                            $status_class .= 'worker-portal-badge-success';
                                            $status_text = __('Aprobado', 'worker-portal');
                                            break;
                                        case 'rejected':
                                            $status_class .= 'worker-portal-badge-danger';
                                            $status_text = __('Rechazado', 'worker-portal');
                                            break;
                                        default:
                                            $status_class .= 'worker-portal-badge-secondary';
                                            $status_text = ucfirst($incentive['status']);
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                                <td>
                                    <?php if ($incentive['status'] === 'pending'): ?>
                                        <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-primary approve-incentive" data-incentive-id="<?php echo esc_attr($incentive['id']); ?>">
                                            <i class="dashicons dashicons-yes"></i>
                                        </button>
                                        <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-danger reject-incentive" data-incentive-id="<?php echo esc_attr($incentive['id']); ?>">
                                            <i class="dashicons dashicons-no"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-secondary view-incentive" data-incentive-id="<?php echo esc_attr($incentive['id']); ?>">
                                        <i class="dashicons dashicons-visibility"></i>
                                    </button>
                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline delete-incentive" data-incentive-id="<?php echo esc_attr($incentive['id']); ?>">
                                        <i class="dashicons dashicons-trash"></i>
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
                            __('Mostrando %1$s - %2$s de %3$s incentivos', 'worker-portal'),
                            (($page - 1) * $per_page) + 1,
                            min($page * $per_page, $total_items),
                            $total_items
                        );
                        ?>
                    </div>
                    
                    <div class="worker-portal-pagination-links">
                        <?php if ($page > 1): ?>
                            <a href="#" class="worker-portal-pagination-prev" data-page="<?php echo $page - 1; ?>">
                                &laquo; <?php _e('Anterior', 'worker-portal'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        // Mostrar números de página
                        for ($i = 1; $i <= $total_pages; $i++):
                            $class = ($i === $page) ? 'worker-portal-pagination-current' : '';
                        ?>
                            <a href="#" class="worker-portal-pagination-number <?php echo $class; ?>" data-page="<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="#" class="worker-portal-pagination-next" data-page="<?php echo $page + 1; ?>">
                                <?php _e('Siguiente', 'worker-portal'); ?> &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif;
        
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'current_page' => $page
        ));
    }

    /**
     * Maneja la petición AJAX para calcular incentivo a partir de una hoja de trabajo
     *
     * @since    1.0.0
     */
    public function ajax_admin_calculate_worksheet_incentive() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || 
            (!wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce') && 
             !wp_verify_nonce($_POST['nonce'], 'worker_portal_incentives_nonce'))) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario es administrador
        if (!current_user_can('manage_options') && !current_user_can('wp_worker_manage_incentives')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener ID de la hoja de trabajo
        $worksheet_id = isset($_POST['worksheet_id']) ? intval($_POST['worksheet_id']) : 0;
        
        if ($worksheet_id <= 0) {
            wp_send_json_error(__('ID de hoja de trabajo no válido', 'worker-portal'));
        }
        
        // Obtener datos de la hoja de trabajo
        global $wpdb;
        
        $worksheet = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}worker_worksheets WHERE id = %d",
                $worksheet_id
            ),
            ARRAY_A
        );
        
        if (!$worksheet) {
            wp_send_json_error(__('La hoja de trabajo no existe', 'worker-portal'));
        }
        
        // Verificar que la hoja de trabajo está validada
        if ($worksheet['status'] !== 'validated') {
            wp_send_json_error(__('La hoja de trabajo debe estar validada para calcular incentivos', 'worker-portal'));
        }
        
        // Calcular incentivo
        $incentive_data = $this->calculate_incentive_from_worksheet($worksheet);
        
        if (empty($incentive_data)) {
            wp_send_json_error(__('No se pudo calcular ningún incentivo para esta hoja de trabajo', 'worker-portal'));
        }
        
        wp_send_json_success($incentive_data);
    }

    /**
     * Calcula incentivo a partir de una hoja de trabajo
     *
     * @since    1.0.0
     * @param    array    $worksheet    Datos de la hoja de trabajo
     * @return   array                  Datos del incentivo calculado
     */
    private function calculate_incentive_from_worksheet($worksheet) {
        // Verificar que hay datos suficientes para calcular
        if (empty($worksheet['system_type']) || empty($worksheet['quantity']) || $worksheet['quantity'] <= 0) {
            return array();
        }
        
        // Realizar cálculo según el tipo de sistema
        $incentive_amount = 0;
        $description = '';
        
        // Valores base para los sistemas (estos deberían configurarse en las opciones)
        $base_values = array(
            'estructura_techo' => array('base' => 25, 'rate' => 2.08),
            'estructura_tabique' => array('base' => 27, 'rate' => 1.42),
            'aplacado_simple' => array('base' => 35, 'rate' => 1.00),
            'aplacado_doble' => array('base' => 45, 'rate' => 0.71)
        );
        
        // Cantidad base según dificultad
        $difficulty_factor = array(
            'baja' => 0.8,
            'media' => 1.0,
            'alta' => 1.3
        );
        
        // Solo calcular para tipos de sistema con incentivo
        if (isset($base_values[$worksheet['system_type']])) {
            $base = $base_values[$worksheet['system_type']]['base'];
            $rate = $base_values[$worksheet['system_type']]['rate'];
            $factor = isset($difficulty_factor[$worksheet['difficulty']]) ? $difficulty_factor[$worksheet['difficulty']] : 1.0;
            
            // Calcular el incentivo
            $incentive_amount = ($worksheet['quantity'] - $base * $factor) * $rate;
            
            // Si es negativo, no hay incentivo
            if ($incentive_amount <= 0) {
                return array();
            }
            
            // Redondear a 2 decimales
            $incentive_amount = round($incentive_amount, 2);
            
            // Generar descripción
            $system_names = array(
                'estructura_techo' => __('estructurade en tcho continuo de PYL', 'worker-portal'),
                'estructura_tabique' => __('estructura 48/70/90 en tabique o trasdosado', 'worker-portal'),
                'aplacado_simple' => __('aplacado con 1 placa de 13/15 de en tabique o trasdosado', 'worker-portal'),
                'aplacado_doble' => __('aplacado con 2 placas de 25 en tabique o trasdosado', 'worker-portal')
            );
            
            $system_name = isset($system_names[$worksheet['system_type']]) ? $system_names[$worksheet['system_type']] : $worksheet['system_type'];
            
            // Calcular metros extra
            $extra_meters = $worksheet['quantity'] - ($base * $factor);
            
            $description = sprintf(
                __('%s m² de %s', 'worker-portal'),
                number_format($extra_meters, 2, ',', '.'),
                $system_name
            );
        } else {
            // No es un tipo que tenga incentivo
            return array();
        }
        
        return array(
            'user_id' => $worksheet['user_id'],
            'worksheet_id' => $worksheet['id'],
            'incentive_type' => 'excess_meters',
            'description' => $description,
            'amount' => $incentive_amount
        );
    }

/**
     * Envía una notificación al usuario sobre un nuevo incentivo
     *
     * @since    1.0.0
     * @param    int       $incentive_id    ID del incentivo
     */
    private function send_incentive_notification($incentive_id) {
        global $wpdb;
        
        // Obtener detalles del incentivo
        $incentive = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}worker_incentives WHERE id = %d",
                $incentive_id
            ),
            ARRAY_A
        );
        
        if (empty($incentive)) {
            return;
        }
        
        // Obtener datos del usuario
        $user = get_userdata($incentive['user_id']);
        
        if (!$user || !is_email($user->user_email)) {
            return;
        }
        
        // Tipos de incentivos disponibles
        $incentive_types = get_option('worker_portal_incentive_types', $this->default_options['incentive_types']);
        $incentive_type_name = isset($incentive_types[$incentive['incentive_type']]) ? $incentive_types[$incentive['incentive_type']] : ucfirst($incentive['incentive_type']);
        
        // Configurar asunto del email
        $subject = sprintf(
            __('[%s] Nuevo incentivo registrado', 'worker-portal'),
            get_bloginfo('name')
        );
        
        // Texto del mensaje
        $message = sprintf(
            __('Hola %s,

Se ha registrado un nuevo incentivo en tu cuenta:

Tipo: %s
Descripción: %s
Importe: %s €
Fecha de cálculo: %s

Este incentivo está pendiente de aprobación. Puedes verlo en el Portal del Trabajador.

Saludos,
%s', 'worker-portal'),
            $user->display_name,
            $incentive_type_name,
            $incentive['description'],
            number_format($incentive['amount'], 2, ',', '.'),
            date_i18n(get_option('date_format'), strtotime($incentive['calculation_date'])),
            get_bloginfo('name')
        );
        
        // Enviar email
        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Envía una notificación al usuario sobre cambio de estado del incentivo
     *
     * @since    1.0.0
     * @param    int       $incentive_id    ID del incentivo
     * @param    string    $status          Nuevo estado
     */
    private function send_incentive_status_notification($incentive_id, $status) {
        global $wpdb;
        
        // Obtener detalles del incentivo
        $incentive = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}worker_incentives WHERE id = %d",
                $incentive_id
            ),
            ARRAY_A
        );
        
        if (empty($incentive)) {
            return;
        }
        
        // Obtener datos del usuario
        $user = get_userdata($incentive['user_id']);
        
        if (!$user || !is_email($user->user_email)) {
            return;
        }
        
        // Obtener datos del aprobador
        $approver = get_userdata($incentive['approved_by']);
        $approver_name = $approver ? $approver->display_name : __('Administrador', 'worker-portal');
        
        // Tipos de incentivos disponibles
        $incentive_types = get_option('worker_portal_incentive_types', $this->default_options['incentive_types']);
        $incentive_type_name = isset($incentive_types[$incentive['incentive_type']]) ? $incentive_types[$incentive['incentive_type']] : ucfirst($incentive['incentive_type']);
        
        // Configurar asunto del email según estado
        if ($status === 'approved') {
            $subject = sprintf(
                __('[%s] Incentivo aprobado', 'worker-portal'),
                get_bloginfo('name')
            );
            
            $message = sprintf(
                __('Hola %s,

Tu incentivo ha sido aprobado por %s:

Tipo: %s
Descripción: %s
Importe: %s €
Fecha de cálculo: %s

Puedes ver todos tus incentivos en el Portal del Trabajador.

Saludos,
%s', 'worker-portal'),
                $user->display_name,
                $approver_name,
                $incentive_type_name,
                $incentive['description'],
                number_format($incentive['amount'], 2, ',', '.'),
                date_i18n(get_option('date_format'), strtotime($incentive['calculation_date'])),
                get_bloginfo('name')
            );
        } else {
            $subject = sprintf(
                __('[%s] Incentivo rechazado', 'worker-portal'),
                get_bloginfo('name')
            );
            
            $message = sprintf(
                __('Hola %s,

Tu incentivo ha sido rechazado por %s:

Tipo: %s
Descripción: %s
Importe: %s €
Fecha de cálculo: %s

Si tienes alguna duda, contacta con tu responsable.

Saludos,
%s', 'worker-portal'),
                $user->display_name,
                $approver_name,
                $incentive_type_name,
                $incentive['description'],
                number_format($incentive['amount'], 2, ',', '.'),
                date_i18n(get_option('date_format'), strtotime($incentive['calculation_date'])),
                get_bloginfo('name')
            );
        }
        
        // Enviar email
        wp_mail($user->user_email, $subject, $message);
    }
}