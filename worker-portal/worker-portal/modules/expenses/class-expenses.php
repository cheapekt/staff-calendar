<?php
/**
 * Módulo de Gastos
 *
 * @since      1.0.0
 */
class Worker_Portal_Module_Expenses {

/**
 * Inicializa el módulo
 *
 * @since    1.0.0
 */
public function init() {
    // Registrar hooks para admin
    add_action('admin_menu', array($this, 'register_admin_menu'), 20);
    add_action('admin_init', array($this, 'register_settings'));
    
    // Registrar hooks para frontend
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    
    // Registrar shortcodes
    add_shortcode('worker_expenses', array($this, 'render_expenses_shortcode'));
    
    // Registrar endpoints de REST API
    add_action('rest_api_init', array($this, 'register_rest_routes'));
    
    // Registrar AJAX actions para frontend
    add_action('wp_ajax_submit_expense', array($this, 'ajax_submit_expense'));
    add_action('wp_ajax_delete_expense', array($this, 'ajax_delete_expense'));
    add_action('wp_ajax_approve_expense', array($this, 'ajax_approve_expense'));
    add_action('wp_ajax_reject_expense', array($this, 'ajax_reject_expense'));
    add_action('wp_ajax_filter_expenses', array($this, 'ajax_filter_expenses'));
    add_action('wp_ajax_export_expenses', array($this, 'ajax_export_expenses'));
    
    // Registrar AJAX actions para admin
    add_action('wp_ajax_bulk_expense_action', array($this, 'ajax_bulk_expense_action'));
    add_action('wp_ajax_admin_export_expenses', array($this, 'ajax_admin_export_expenses'));
    add_action('wp_ajax_get_expense_details', array($this, 'ajax_get_expense_details'));
}

    /**
     * Registra las páginas de menú en el área de administración
     *
     * @since    1.0.0
     */
    public function register_admin_menu() {
        add_submenu_page(
            'worker-portal',
            __('Gestión de Gastos', 'worker-portal'),
            __('Gastos', 'worker-portal'),
            'manage_options',
            'worker-portal-expenses',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Registra las opciones de configuración
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting(
            'worker_portal_expenses',
            'worker_portal_expense_types',
            array(
                'type' => 'array',
                'description' => 'Tipos de gastos disponibles',
                'sanitize_callback' => array($this, 'sanitize_expense_types'),
                'default' => array(
                    'km' => __('Kilometraje', 'worker-portal'),
                    'hours' => __('Horas de desplazamiento', 'worker-portal'),
                    'meal' => __('Dietas', 'worker-portal'),
                    'other' => __('Otros', 'worker-portal')
                )
            )
        );

        register_setting(
            'worker_portal_expenses',
            'worker_portal_expense_approvers',
            array(
                'type' => 'array',
                'description' => 'Usuarios que pueden aprobar gastos',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        register_setting(
            'worker_portal_expenses',
            'worker_portal_expense_notification_email',
            array(
                'type' => 'string',
                'description' => 'Email para notificaciones de gastos',
                'sanitize_callback' => 'sanitize_email',
                'default' => get_option('admin_email')
            )
        );
    }

    /**
     * Sanitiza los tipos de gastos
     *
     * @since    1.0.0
     * @param    array    $input    Tipos de gastos a sanitizar
     * @return   array              Tipos de gastos sanitizados
     */
    public function sanitize_expense_types($input) {
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
        // Solo cargar en páginas que usen el shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'worker_expenses')) {
            wp_enqueue_style(
                'worker-portal-expenses',
                WORKER_PORTAL_URL . 'modules/expenses/css/expenses.css',
                array(),
                WORKER_PORTAL_VERSION
            );
            
            wp_enqueue_script(
                'worker-portal-expenses',
                WORKER_PORTAL_URL . 'modules/expenses/js/expenses.js',
                array('jquery'),
                WORKER_PORTAL_VERSION,
                true
            );
            
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
        include(WORKER_PORTAL_PATH . 'modules/expenses/templates/admin-page.php');
    }

    /**
     * Renderiza el shortcode para mostrar los gastos del usuario
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            HTML generado
     */
    public function render_expenses_shortcode($atts) {
        // Si el usuario no está logueado, mostrar mensaje de error
        if (!is_user_logged_in()) {
            return '<div class="worker-portal-error">' . __('Debes iniciar sesión para ver tus gastos.', 'worker-portal') . '</div>';
        }
        
        // Atributos por defecto
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
        $expenses = $this->get_user_expenses($user_id, $atts['limit']);
        
        // Obtener los tipos de gastos disponibles
        $expense_types = get_option('worker_portal_expense_types', array(
            'km' => __('Kilometraje', 'worker-portal'),
            'hours' => __('Horas de desplazamiento', 'worker-portal'),
            'meal' => __('Dietas', 'worker-portal'),
            'other' => __('Otros', 'worker-portal')
        ));
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir plantilla
        include(WORKER_PORTAL_PATH . 'modules/expenses/templates/expenses-view.php');
        
        // Retornar el contenido
        return ob_get_clean();
    }

    /**
     * Obtiene los gastos de un usuario
     *
     * @since    1.0.0
     * @param    int     $user_id    ID del usuario
     * @param    int     $limit      Número máximo de gastos a obtener
     * @return   array               Lista de gastos
     */
    public function get_user_expenses($user_id, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        $expenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d ORDER BY report_date DESC LIMIT %d",
                $user_id,
                $limit
            ),
            ARRAY_A
        );
        
        return $expenses;
    }

    /**
     * Registra los endpoints de la API REST
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route('worker-portal/v1', '/expenses', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'rest_get_expenses'),
                'permission_callback' => array($this, 'rest_permissions_check')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'rest_create_expense'),
                'permission_callback' => array($this, 'rest_permissions_check')
            )
        ));
        
        register_rest_route('worker-portal/v1', '/expenses/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'rest_get_expense'),
                'permission_callback' => array($this, 'rest_permissions_check')
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'rest_update_expense'),
                'permission_callback' => array($this, 'rest_permissions_check')
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'rest_delete_expense'),
                'permission_callback' => array($this, 'rest_permissions_check')
            )
        ));
        
        register_rest_route('worker-portal/v1', '/expenses/(?P<id>\d+)/approve', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_approve_expense'),
            'permission_callback' => array($this, 'rest_approve_permissions_check')
        ));
        
        register_rest_route('worker-portal/v1', '/expenses/(?P<id>\d+)/reject', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_reject_expense'),
            'permission_callback' => array($this, 'rest_approve_permissions_check')
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
     * Verifica los permisos para aprobar/rechazar gastos
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   bool                           Si tiene permisos
     */
    public function rest_approve_permissions_check($request) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_id = get_current_user_id();
        $approvers = get_option('worker_portal_expense_approvers', array());
        
        // Los administradores siempre pueden aprobar
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Verificar si el usuario está en la lista de aprobadores
        return in_array($user_id, $approvers);
    }

    /**
     * Obtiene la lista de gastos del usuario (endpoint REST)
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   WP_REST_Response                Respuesta
     */
    public function rest_get_expenses($request) {
        $user_id = get_current_user_id();
        $limit = $request->get_param('limit') ? intval($request->get_param('limit')) : 10;
        
        $expenses = $this->get_user_expenses($user_id, $limit);
        
        return rest_ensure_response($expenses);
    }

    /**
     * Crea un nuevo gasto (endpoint REST)
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   WP_REST_Response                Respuesta
     */
    public function rest_create_expense($request) {
        $user_id = get_current_user_id();
        
        $expense_date = sanitize_text_field($request->get_param('expense_date'));
        $expense_type = sanitize_text_field($request->get_param('expense_type'));
        $description = sanitize_textarea_field($request->get_param('description'));
        $amount = floatval($request->get_param('amount'));
        $has_receipt = (bool) $request->get_param('has_receipt');
        
        // Validar datos
        if (empty($expense_date) || empty($expense_type) || empty($description) || $amount <= 0) {
            return new WP_Error(
                'missing_fields',
                __('Faltan campos obligatorios o son inválidos.', 'worker-portal'),
                array('status' => 400)
            );
        }
        
        // Procesar imagen/recibo si existe
        $receipt_path = '';
        $file = $request->get_file_params();
        
        if ($has_receipt && !empty($file) && !empty($file['receipt'])) {
            $receipt_path = $this->process_receipt_upload($file['receipt']);
            
            if (is_wp_error($receipt_path)) {
                return $receipt_path;
            }
        }
        
        // Insertar el gasto en la base de datos
        $result = $this->insert_expense(
            $user_id,
            $expense_date,
            $expense_type,
            $description,
            $amount,
            $has_receipt,
            $receipt_path
        );
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Enviar notificación
        $this->send_expense_notification($result);
        
        return rest_ensure_response(array(
            'success' => true,
            'expense_id' => $result,
            'message' => __('Gasto registrado correctamente.', 'worker-portal')
        ));
    }

    /**
     * Obtiene un gasto específico (endpoint REST)
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   WP_REST_Response                Respuesta
     */
    public function rest_get_expense($request) {
        $expense_id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        $expense = $this->get_expense($expense_id);
        
        if (!$expense) {
            return new WP_Error(
                'not_found',
                __('Gasto no encontrado.', 'worker-portal'),
                array('status' => 404)
            );
        }
        
        // Verificar que el gasto pertenece al usuario o es un aprobador
        if ($expense['user_id'] != $user_id && !$this->rest_approve_permissions_check($request)) {
            return new WP_Error(
                'forbidden',
                __('No tienes permiso para ver este gasto.', 'worker-portal'),
                array('status' => 403)
            );
        }
        
        return rest_ensure_response($expense);
    }

    /**
     * Actualiza un gasto existente (endpoint REST)
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   WP_REST_Response                Respuesta
     */
    public function rest_update_expense($request) {
        $expense_id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        // Obtener el gasto existente
        $expense = $this->get_expense($expense_id);
        
        if (!$expense) {
            return new WP_Error(
                'not_found',
                __('Gasto no encontrado.', 'worker-portal'),
                array('status' => 404)
            );
        }
        
        // Verificar que el gasto pertenece al usuario
        if ($expense['user_id'] != $user_id) {
            return new WP_Error(
                'forbidden',
                __('No tienes permiso para editar este gasto.', 'worker-portal'),
                array('status' => 403)
            );
        }
        
        // Verificar que el gasto no ha sido aprobado o rechazado
        if ($expense['status'] != 'pending') {
            return new WP_Error(
                'invalid_status',
                __('No puedes editar un gasto que ya ha sido aprobado o rechazado.', 'worker-portal'),
                array('status' => 400)
            );
        }
        
        // Obtener los datos a actualizar
        $expense_date = $request->get_param('expense_date') ? sanitize_text_field($request->get_param('expense_date')) : $expense['expense_date'];
        $expense_type = $request->get_param('expense_type') ? sanitize_text_field($request->get_param('expense_type')) : $expense['expense_type'];
        $description = $request->get_param('description') ? sanitize_textarea_field($request->get_param('description')) : $expense['description'];
        $amount = $request->get_param('amount') ? floatval($request->get_param('amount')) : $expense['amount'];
        
        // Validar datos
        if (empty($expense_date) || empty($expense_type) || empty($description) || $amount <= 0) {
            return new WP_Error(
                'missing_fields',
                __('Faltan campos obligatorios o son inválidos.', 'worker-portal'),
                array('status' => 400)
            );
        }
        
        // Actualizar el gasto
        $result = $this->update_expense(
            $expense_id,
            $expense_date,
            $expense_type,
            $description,
            $amount
        );
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Gasto actualizado correctamente.', 'worker-portal')
        ));
    }

    /**
     * Elimina un gasto (endpoint REST)
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   WP_REST_Response                Respuesta
     */
    public function rest_delete_expense($request) {
        $expense_id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        // Obtener el gasto existente
        $expense = $this->get_expense($expense_id);
        
        if (!$expense) {
            return new WP_Error(
                'not_found',
                __('Gasto no encontrado.', 'worker-portal'),
                array('status' => 404)
            );
        }
        
        // Verificar que el gasto pertenece al usuario
        if ($expense['user_id'] != $user_id) {
            return new WP_Error(
                'forbidden',
                __('No tienes permiso para eliminar este gasto.', 'worker-portal'),
                array('status' => 403)
            );
        }
        
        // Verificar que el gasto no ha sido aprobado o rechazado
        if ($expense['status'] != 'pending') {
            return new WP_Error(
                'invalid_status',
                __('No puedes eliminar un gasto que ya ha sido aprobado o rechazado.', 'worker-portal'),
                array('status' => 400)
            );
        }
        
        // Eliminar el gasto
        $result = $this->delete_expense($expense_id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Gasto eliminado correctamente.', 'worker-portal')
        ));
    }

    /**
     * Aprueba un gasto (endpoint REST)
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   WP_REST_Response                Respuesta
     */
    public function rest_approve_expense($request) {
        $expense_id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        // Obtener el gasto existente
        $expense = $this->get_expense($expense_id);
        
        if (!$expense) {
            return new WP_Error(
                'not_found',
                __('Gasto no encontrado.', 'worker-portal'),
                array('status' => 404)
            );
        }
        
        // Verificar que el gasto está pendiente
        if ($expense['status'] != 'pending') {
            return new WP_Error(
                'invalid_status',
                __('Este gasto ya ha sido aprobado o rechazado.', 'worker-portal'),
                array('status' => 400)
            );
        }
        
        // Aprobar el gasto
        $result = $this->update_expense_status($expense_id, 'approved', $user_id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Notificar al usuario
        $this->send_approval_notification($expense_id, 'approved');
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Gasto aprobado correctamente.', 'worker-portal')
        ));
    }

    /**
     * Rechaza un gasto (endpoint REST)
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   WP_REST_Response                Respuesta
     */
    public function rest_reject_expense($request) {
        $expense_id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        // Obtener el gasto existente
        $expense = $this->get_expense($expense_id);
        
        if (!$expense) {
            return new WP_Error(
                'not_found',
                __('Gasto no encontrado.', 'worker-portal'),
                array('status' => 404)
            );
        }
        
        // Verificar que el gasto está pendiente
        if ($expense['status'] != 'pending') {
            return new WP_Error(
                'invalid_status',
                __('Este gasto ya ha sido aprobado o rechazado.', 'worker-portal'),
                array('status' => 400)
            );
        }
        
        // Rechazar el gasto
        $result = $this->update_expense_status($expense_id, 'rejected', $user_id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Notificar al usuario
        $this->send_approval_notification($expense_id, 'rejected');
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Gasto rechazado correctamente.', 'worker-portal')
        ));
    }

    /**
     * Procesa la subida de un ticket/recibo
     *
     * @since    1.0.0
     * @param    array    $file    Información del archivo subido
     * @return   string|WP_Error    Ruta del archivo o error
     */
    private function process_receipt_upload($file) {
        // Verificar que el archivo es una imagen
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file_type['ext'], array('jpg', 'jpeg', 'png', 'gif', 'pdf'))) {
            return new WP_Error(
                'invalid_file_type',
                __('El tipo de archivo no es válido. Solo se permiten imágenes (JPG, PNG, GIF) o PDF.', 'worker-portal')
            );
        }
        
        // Crear directorio para recibos si no existe
        $upload_dir = wp_upload_dir();
        $receipts_dir = $upload_dir['basedir'] . '/worker-portal/receipts';
        
        if (!file_exists($receipts_dir)) {
            wp_mkdir_p($receipts_dir);
            
            // Crear un archivo index.php para evitar navegación directa
            $index_file = $receipts_dir . '/index.php';
            file_put_contents($index_file, '<?php // Silence is golden');
        }
        
        // Generar nombre único para el archivo
        $filename = 'receipt-' . time() . '-' . wp_generate_password(8, false) . '.' . $file_type['ext'];
        $filepath = $receipts_dir . '/' . $filename;
        
        // Mover el archivo temporal al directorio de recibos
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return new WP_Error(
                'upload_error',
                __('Error al subir el archivo. Por favor, inténtalo de nuevo.', 'worker-portal')
            );
        }
        
        // Devolver la ruta relativa (para almacenar en la BD)
        return 'worker-portal/receipts/' . $filename;
    }

    /**
     * Inserta un nuevo gasto en la base de datos
     *
     * @since    1.0.0
     * @param    int       $user_id         ID del usuario
     * @param    string    $expense_date    Fecha del gasto
     * @param    string    $expense_type    Tipo de gasto
     * @param    string    $description     Descripción del gasto
     * @param    float     $amount          Importe del gasto
     * @param    bool      $has_receipt     Si tiene recibo
     * @param    string    $receipt_path    Ruta del recibo
     * @return   int|WP_Error               ID del gasto o error
     */
    private function insert_expense($user_id, $expense_date, $expense_type, $description, $amount, $has_receipt = false, $receipt_path = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'report_date' => current_time('mysql'),
                'expense_date' => $expense_date,
                'expense_type' => $expense_type,
                'description' => $description,
                'amount' => $amount,
                'has_receipt' => $has_receipt ? 1 : 0,
                'receipt_path' => $receipt_path,
                'status' => 'pending'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error(
                'db_error',
                __('Error al insertar el gasto en la base de datos.', 'worker-portal')
            );
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Actualiza un gasto existente en la base de datos
     *
     * @since    1.0.0
     * @param    int       $expense_id      ID del gasto
     * @param    string    $expense_date    Fecha del gasto
     * @param    string    $expense_type    Tipo de gasto
     * @param    string    $description     Descripción del gasto
     * @param    float     $amount          Importe del gasto
     * @return   bool|WP_Error              Éxito o error
     */
    private function update_expense($expense_id, $expense_date, $expense_type, $description, $amount) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'expense_date' => $expense_date,
                'expense_type' => $expense_type,
                'description' => $description,
                'amount' => $amount
            ),
            array('id' => $expense_id),
            array('%s', '%s', '%s', '%f'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error(
                'db_error',
                __('Error al actualizar el gasto en la base de datos.', 'worker-portal')
            );
        }
        
        return true;
    }

    /**
     * Actualiza el estado de un gasto en la base de datos
     *
     * @since    1.0.0
     * @param    int       $expense_id    ID del gasto
     * @param    string    $status        Nuevo estado (approved/rejected)
     * @param    int       $approver_id   ID del usuario que aprueba/rechaza
     * @return   bool|WP_Error            Éxito o error
     */
    private function update_expense_status($expense_id, $status, $approver_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => $status,
                'approved_by' => $approver_id,
                'approved_date' => current_time('mysql')
            ),
            array('id' => $expense_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error(
                'db_error',
                __('Error al actualizar el estado del gasto en la base de datos.', 'worker-portal')
            );
        }
        
        return true;
    }

    /**
     * Elimina un gasto de la base de datos
     *
     * @since    1.0.0
     * @param    int       $expense_id    ID del gasto
     * @return   bool|WP_Error            Éxito o error
     */
    private function delete_expense($expense_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        // Obtener el gasto para eliminar el recibo si existe
        $expense = $this->get_expense($expense_id);
        
        if ($expense && $expense['has_receipt'] && !empty($expense['receipt_path'])) {
            $upload_dir = wp_upload_dir();
            $receipt_file = $upload_dir['basedir'] . '/' . $expense['receipt_path'];
            
            if (file_exists($receipt_file)) {
                @unlink($receipt_file);
            }
        }
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $expense_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error(
                'db_error',
                __('Error al eliminar el gasto de la base de datos.', 'worker-portal')
            );
        }
        
        return true;
    }

    /**
     * Obtiene un gasto específico
     *
     * @since    1.0.0
     * @param    int       $expense_id    ID del gasto
     * @return   array|false              Datos del gasto o false si no existe
     */
    private function get_expense($expense_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $expense_id
            ),
            ARRAY_A
        );
    }

    /**
     * Envía una notificación por email de un nuevo gasto
     *
     * @since    1.0.0
     * @param    int       $expense_id    ID del gasto
     */
    private function send_expense_notification($expense_id) {
        $expense = $this->get_expense($expense_id);
        
        if (!$expense) {
            return;
        }
        
        $user = get_userdata($expense['user_id']);
        $admin_email = get_option('worker_portal_expense_notification_email', get_option('admin_email'));
        
        $subject = sprintf(
            __('[%s] Nuevo gasto registrado por %s', 'worker-portal'),
            get_bloginfo('name'),
            $user->display_name
        );
        
        $expense_types = get_option('worker_portal_expense_types', array());
        $expense_type_name = isset($expense_types[$expense['expense_type']]) 
            ? $expense_types[$expense['expense_type']] 
            : $expense['expense_type'];
        
        $message = sprintf(
            __('Se ha registrado un nuevo gasto:
Usuario: %s
Fecha del gasto: %s
Tipo: %s
Descripción: %s
Importe: %.2f
Ticket: %s
Para aprobar o rechazar este gasto, accede al panel de administración: %s', 'worker-portal'),
            $user->display_name,
            $expense['expense_date'],
            $expense_type_name,
            $expense['description'],
            $expense['amount'],
            $expense['has_receipt'] ? __('Sí', 'worker-portal') : __('No', 'worker-portal'),
            admin_url('admin.php?page=worker-portal-expenses')
        );
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Envía una notificación por email al usuario cuando su gasto es aprobado o rechazado
     *
     * @since    1.0.0
     * @param    int       $expense_id    ID del gasto
     * @param    string    $status        Estado del gasto (approved/rejected)
     */
    private function send_approval_notification($expense_id, $status) {
        $expense = $this->get_expense($expense_id);
        
        if (!$expense) {
            return;
        }
        
        $user = get_userdata($expense['user_id']);
        $approver = get_userdata($expense['approved_by']);
        
        $subject = sprintf(
            __('[%s] Tu gasto ha sido %s', 'worker-portal'),
            get_bloginfo('name'),
            $status === 'approved' ? __('aprobado', 'worker-portal') : __('rechazado', 'worker-portal')
        );
        
        $expense_types = get_option('worker_portal_expense_types', array());
        $expense_type_name = isset($expense_types[$expense['expense_type']]) 
            ? $expense_types[$expense['expense_type']] 
            : $expense['expense_type'];
        
        $message = sprintf(
            __('Hola %s,
Tu gasto ha sido %s por %s:
Fecha del gasto: %s
Tipo: %s
Descripción: %s
Importe: %.2f

Para ver todos tus gastos, accede al portal del trabajador: %s', 'worker-portal'),
            $user->display_name,
            $status === 'approved' ? __('aprobado', 'worker-portal') : __('rechazado', 'worker-portal'),
            $approver->display_name,
            $expense['expense_date'],
            $expense_type_name,
            $expense['description'],
            $expense['amount'],
            site_url('/portal-del-trabajador/')
        );
        
        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Maneja la petición AJAX para enviar un gasto
     *
     * @since    1.0.0
     */
    public function ajax_submit_expense() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_expenses_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para registrar un gasto.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        
        // Obtener datos del formulario
        $expense_date = isset($_POST['expense_date']) ? sanitize_text_field($_POST['expense_date']) : '';
        $expense_type = isset($_POST['expense_type']) ? sanitize_text_field($_POST['expense_type']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $has_receipt = isset($_POST['has_receipt']) && $_POST['has_receipt'] === 'yes';
        
        // Validar datos
        if (empty($expense_date) || empty($expense_type) || empty($description) || $amount <= 0) {
            wp_send_json_error(__('Faltan campos obligatorios o son inválidos.', 'worker-portal'));
        }
        
        // Procesar imagen/recibo si existe
        $receipt_path = '';
        
        if ($has_receipt && isset($_FILES['receipt']) && !empty($_FILES['receipt']['name'])) {
            $receipt_path = $this->process_receipt_upload($_FILES['receipt']);
            
            if (is_wp_error($receipt_path)) {
                wp_send_json_error($receipt_path->get_error_message());
            }
        }
        
        // Insertar el gasto en la base de datos
        $result = $this->insert_expense(
            $user_id,
            $expense_date,
            $expense_type,
            $description,
            $amount,
            $has_receipt,
            $receipt_path
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Enviar notificación
        $this->send_expense_notification($result);
        
        wp_send_json_success(array(
            'expense_id' => $result,
            'message' => __('Gasto registrado correctamente.', 'worker-portal')
        ));
    }

    /**
     * Maneja la petición AJAX para eliminar un gasto
     *
     * @since    1.0.0
     */
    public function ajax_delete_expense() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_expenses_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para eliminar un gasto.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        $expense_id = isset($_POST['expense_id']) ? intval($_POST['expense_id']) : 0;
        
        // Verificar que existe el gasto
        $expense = $this->get_expense($expense_id);
        
        if (!$expense) {
            wp_send_json_error(__('Gasto no encontrado.', 'worker-portal'));
        }
        
        // Verificar que el gasto pertenece al usuario
        if ($expense['user_id'] != $user_id) {
            wp_send_json_error(__('No tienes permiso para eliminar este gasto.', 'worker-portal'));
        }
        
        // Verificar que el gasto no ha sido aprobado o rechazado
        if ($expense['status'] != 'pending') {
            wp_send_json_error(__('No puedes eliminar un gasto que ya ha sido aprobado o rechazado.', 'worker-portal'));
        }
        
        // Eliminar el gasto
        $result = $this->delete_expense($expense_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Gasto eliminado correctamente.', 'worker-portal'));
    }

    /**
     * Maneja la petición AJAX para aprobar un gasto
     *
     * @since    1.0.0
     */
    public function ajax_approve_expense() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_expenses_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para aprobar un gasto.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        $expense_id = isset($_POST['expense_id']) ? intval($_POST['expense_id']) : 0;
        
        // Verificar que existe el gasto
        $expense = $this->get_expense($expense_id);
        
        if (!$expense) {
            wp_send_json_error(__('Gasto no encontrado.', 'worker-portal'));
        }
        
        // Verificar que el usuario tiene permisos para aprobar gastos
        $approvers = get_option('worker_portal_expense_approvers', array());
        
        if (!current_user_can('manage_options') && !in_array($user_id, $approvers)) {
            wp_send_json_error(__('No tienes permiso para aprobar gastos.', 'worker-portal'));
        }
        
        // Verificar que el gasto está pendiente
        if ($expense['status'] != 'pending') {
            wp_send_json_error(__('Este gasto ya ha sido aprobado o rechazado.', 'worker-portal'));
        }
        
        // Aprobar el gasto
        $result = $this->update_expense_status($expense_id, 'approved', $user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Notificar al usuario
        $this->send_approval_notification($expense_id, 'approved');
        
        wp_send_json_success(__('Gasto aprobado correctamente.', 'worker-portal'));
    }

    /**
     * Maneja la petición AJAX para rechazar un gasto
     *
     * @since    1.0.0
     */
    public function ajax_reject_expense() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_expenses_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para rechazar un gasto.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        $expense_id = isset($_POST['expense_id']) ? intval($_POST['expense_id']) : 0;
        
        // Verificar que existe el gasto
        $expense = $this->get_expense($expense_id);
        
        if (!$expense) {
            wp_send_json_error(__('Gasto no encontrado.', 'worker-portal'));
        }
        
        // Verificar que el usuario tiene permisos para rechazar gastos
        $approvers = get_option('worker_portal_expense_approvers', array());
        
        if (!current_user_can('manage_options') && !in_array($user_id, $approvers)) {
            wp_send_json_error(__('No tienes permiso para rechazar gastos.', 'worker-portal'));
        }
        
        // Verificar que el gasto está pendiente
        if ($expense['status'] != 'pending') {
            wp_send_json_error(__('Este gasto ya ha sido aprobado o rechazado.', 'worker-portal'));
        }
        
        // Rechazar el gasto
        $result = $this->update_expense_status($expense_id, 'rejected', $user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Notificar al usuario
        $this->send_approval_notification($expense_id, 'rejected');
        
        wp_send_json_success(__('Gasto rechazado correctamente.', 'worker-portal'));
    }

    /**
 * Estas funciones deben agregarse a la clase Worker_Portal_Module_Expenses 
 * en el archivo modules/expenses/class-expenses.php
 */

    /**
     * Maneja la petición AJAX para filtrar gastos
     *
     * @since    1.0.0
     */
    public function ajax_filter_expenses() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_expenses_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para ver tus gastos.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        
        // Obtener parámetros de filtrado
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        $expense_type = isset($_POST['expense_type']) ? sanitize_text_field($_POST['expense_type']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        // Página actual y elementos por página
        $current_page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 10;
        $offset = ($current_page - 1) * $per_page;
        
        // Obtener los gastos filtrados
        $expenses = $this->get_filtered_expenses(
            $user_id,
            $date_from,
            $date_to,
            $expense_type,
            $status,
            $search,
            $per_page,
            $offset
        );
        
        // Obtener el total de gastos para la paginación
        $total_items = $this->get_total_filtered_expenses(
            $user_id,
            $date_from,
            $date_to,
            $expense_type,
            $status,
            $search
        );
        
        $total_pages = ceil($total_items / $per_page);
        
        // Obtener los tipos de gastos disponibles
        $expense_types = get_option('worker_portal_expense_types', array(
            'km' => __('Kilometraje', 'worker-portal'),
            'hours' => __('Horas de desplazamiento', 'worker-portal'),
            'meal' => __('Dietas', 'worker-portal'),
            'other' => __('Otros', 'worker-portal')
        ));
        
        // Si no hay gastos
        if (empty($expenses)) {
            wp_send_json_success('<p class="worker-portal-no-data">' . __('No se encontraron gastos con los filtros seleccionados.', 'worker-portal') . '</p>');
            return;
        }
        
        // Generar HTML de la tabla
        ob_start();
        ?>
        <div class="worker-portal-table-responsive">
            <table class="worker-portal-table worker-portal-expenses-table">
                <thead>
                    <tr>
                        <th><?php _e('FECHA', 'worker-portal'); ?></th>
                        <th><?php _e('TIPO', 'worker-portal'); ?></th>
                        <th><?php _e('GASTO (motivo del gasto)', 'worker-portal'); ?></th>
                        <th><?php _e('Fecha del gasto', 'worker-portal'); ?></th>
                        <th><?php _e('Km / Horas / Euros', 'worker-portal'); ?></th>
                        <th><?php _e('TICKET', 'worker-portal'); ?></th>
                        <th><?php _e('VALIDACIÓN', 'worker-portal'); ?></th>
                        <th><?php _e('ACCIONES', 'worker-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $expense): ?>
                        <tr data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($expense['report_date'])); ?></td>
                            <td>
                                <?php 
                                echo isset($expense_types[$expense['expense_type']]) 
                                    ? esc_html($expense_types[$expense['expense_type']]) 
                                    : esc_html($expense['expense_type']); 
                                ?>
                            </td>
                            <td><?php echo esc_html($expense['description']); ?></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($expense['expense_date'])); ?></td>
                            <td>
                                <?php 
                                // Mostrar unidad según tipo de gasto
                                switch ($expense['expense_type']) {
                                    case 'km':
                                        echo esc_html($expense['amount']) . ' Km';
                                        break;
                                    case 'hours':
                                        echo esc_html($expense['amount']) . ' Horas';
                                        break;
                                    default:
                                        echo esc_html(number_format($expense['amount'], 2, ',', '.')) . ' Euros';
                                        break;
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($expense['has_receipt']): ?>
                                    <span class="worker-portal-badge worker-portal-badge-success"><?php _e('SI', 'worker-portal'); ?></span>
                                    <?php if (!empty($expense['receipt_path'])): ?>
                                        <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . $expense['receipt_path']); ?>" target="_blank" class="worker-portal-view-receipt">
                                            <i class="dashicons dashicons-visibility"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="worker-portal-badge worker-portal-badge-secondary"><?php _e('NO', 'worker-portal'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                switch ($expense['status']) {
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
                            <td>
                                <?php if ($expense['status'] === 'pending'): ?>
                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-delete-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                        <i class="dashicons dashicons-trash"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (!empty($expense['receipt_path'])): ?>
                                    <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . $expense['receipt_path']); ?>" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-view-receipt">
                                        <i class="dashicons dashicons-visibility"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="worker-portal-pagination">
            <?php if ($total_pages > 1): ?>
                <div class="worker-portal-pagination-info">
                    <?php
                    printf(
                        __('Mostrando %1$s - %2$s de %3$s gastos', 'worker-portal'),
                        (($current_page - 1) * $per_page) + 1,
                        min($current_page * $per_page, $total_items),
                        $total_items
                    );
                    ?>
                </div>
                
                <div class="worker-portal-pagination-links">
                    <?php 
                    // Botón anterior
                    if ($current_page > 1): ?>
                        <a href="#" class="worker-portal-pagination-prev" data-page="<?php echo $current_page - 1; ?>">&laquo; <?php _e('Anterior', 'worker-portal'); ?></a>
                    <?php endif; ?>
                    
                    <?php 
                    // Números de página
                    for ($i = 1; $i <= $total_pages; $i++):
                        $class = ($i === $current_page) ? 'worker-portal-pagination-current' : '';
                    ?>
                        <a href="#" class="worker-portal-pagination-number <?php echo $class; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php 
                    // Botón siguiente
                    if ($current_page < $total_pages): ?>
                        <a href="#" class="worker-portal-pagination-next" data-page="<?php echo $current_page + 1; ?>"><?php _e('Siguiente', 'worker-portal'); ?> &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        $html = ob_get_clean();
        wp_send_json_success($html);
    }

    /**
     * Obtiene gastos filtrados de un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario
     * @param    string    $date_from    Fecha inicial
     * @param    string    $date_to      Fecha final
     * @param    string    $expense_type Tipo de gasto
     * @param    string    $status       Estado del gasto
     * @param    string    $search       Término de búsqueda
     * @param    int       $limit        Límite de resultados
     * @param    int       $offset       Offset para paginación
     * @return   array                   Lista de gastos
     */
    private function get_filtered_expenses($user_id, $date_from = '', $date_to = '', $expense_type = '', $status = '', $search = '', $limit = 10, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        $query = "SELECT * FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        // Filtro por fecha desde
        if (!empty($date_from)) {
            $query .= " AND expense_date >= %s";
            $params[] = $date_from;
        }
        
        // Filtro por fecha hasta
        if (!empty($date_to)) {
            $query .= " AND expense_date <= %s";
            $params[] = $date_to;
        }
        
        // Filtro por tipo de gasto
        if (!empty($expense_type)) {
            $query .= " AND expense_type = %s";
            $params[] = $expense_type;
        }
        
        // Filtro por estado
        if (!empty($status)) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        // Filtro por término de búsqueda
        if (!empty($search)) {
            $query .= " AND (description LIKE %s OR amount LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $query .= " ORDER BY report_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }

    /**
     * Obtiene el total de gastos filtrados
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario
     * @param    string    $date_from    Fecha inicial
     * @param    string    $date_to      Fecha final
     * @param    string    $expense_type Tipo de gasto
     * @param    string    $status       Estado del gasto
     * @param    string    $search       Término de búsqueda
     * @return   int                     Total de gastos
     */
    private function get_total_filtered_expenses($user_id, $date_from = '', $date_to = '', $expense_type = '', $status = '', $search = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        $query = "SELECT COUNT(*) FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        // Filtro por fecha desde
        if (!empty($date_from)) {
            $query .= " AND expense_date >= %s";
            $params[] = $date_from;
        }
        
        // Filtro por fecha hasta
        if (!empty($date_to)) {
            $query .= " AND expense_date <= %s";
            $params[] = $date_to;
        }
        
        // Filtro por tipo de gasto
        if (!empty($expense_type)) {
            $query .= " AND expense_type = %s";
            $params[] = $expense_type;
        }
        
        // Filtro por estado
        if (!empty($status)) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        // Filtro por término de búsqueda
        if (!empty($search)) {
            $query .= " AND (description LIKE %s OR amount LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        return $wpdb->get_var($wpdb->prepare($query, $params));
    }

    /**
     * Maneja la petición AJAX para exportar gastos
     *
     * @since    1.0.0
     */
    public function ajax_export_expenses() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_expenses_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para exportar tus gastos.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        
        // Obtener parámetros de filtrado
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        $expense_type = isset($_POST['expense_type']) ? sanitize_text_field($_POST['expense_type']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        // Obtener todos los gastos filtrados (sin límite)
        $expenses = $this->get_filtered_expenses(
            $user_id,
            $date_from,
            $date_to,
            $expense_type,
            $status,
            $search,
            1000, // Límite más alto para exportación
            0
        );
        
        if (empty($expenses)) {
            wp_send_json_error(__('No hay gastos para exportar con los filtros seleccionados.', 'worker-portal'));
            return;
        }
        
        // Obtener los tipos de gastos disponibles
        $expense_types = get_option('worker_portal_expense_types', array(
            'km' => __('Kilometraje', 'worker-portal'),
            'hours' => __('Horas de desplazamiento', 'worker-portal'),
            'meal' => __('Dietas', 'worker-portal'),
            'other' => __('Otros', 'worker-portal')
        ));
        
        // Generar archivo CSV
        $filename = 'gastos_' . date('Y-m-d') . '.csv';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/worker-portal/exports/' . $filename;
        
        // Crear directorio si no existe
        if (!file_exists($upload_dir['basedir'] . '/worker-portal/exports/')) {
            wp_mkdir_p($upload_dir['basedir'] . '/worker-portal/exports/');
            
            // Añadir archivo index.php para proteger el directorio
            file_put_contents($upload_dir['basedir'] . '/worker-portal/exports/index.php', '<?php // Silence is golden');
        }
        
        // Abrir archivo para escritura
        $file = fopen($file_path, 'w');
        
        // Escribir BOM para UTF-8
        fputs($file, "\xEF\xBB\xBF");
        
        // Escribir cabecera
        fputcsv($file, array(
            __('Fecha de reporte', 'worker-portal'),
            __('Fecha del gasto', 'worker-portal'),
            __('Tipo', 'worker-portal'),
            __('Descripción', 'worker-portal'),
            __('Importe', 'worker-portal'),
            __('Unidad', 'worker-portal'),
            __('Ticket', 'worker-portal'),
            __('Estado', 'worker-portal')
        ));
        
        // Escribir datos
        foreach ($expenses as $expense) {
            // Determinar unidad según tipo de gasto
            $unit = '';
            switch ($expense['expense_type']) {
                case 'km':
                    $unit = 'Km';
                    break;
                case 'hours':
                    $unit = __('Horas', 'worker-portal');
                    break;
                default:
                    $unit = __('Euros', 'worker-portal');
                    break;
            }
            
            // Estado del gasto
            $status_text = '';
            switch ($expense['status']) {
                case 'pending':
                    $status_text = __('Pendiente', 'worker-portal');
                    break;
                case 'approved':
                    $status_text = __('Aprobado', 'worker-portal');
                    break;
                case 'rejected':
                    $status_text = __('Denegado', 'worker-portal');
                    break;
            }
            
            // Escribir fila
            fputcsv($file, array(
                date_i18n(get_option('date_format'), strtotime($expense['report_date'])),
                date_i18n(get_option('date_format'), strtotime($expense['expense_date'])),
                isset($expense_types[$expense['expense_type']]) ? $expense_types[$expense['expense_type']] : $expense['expense_type'],
                $expense['description'],
                $expense['amount'],
                $unit,
                $expense['has_receipt'] ? __('Sí', 'worker-portal') : __('No', 'worker-portal'),
                $status_text
            ));
        }
        
        // Cerrar archivo
        fclose($file);
        
        // Devolver la URL del archivo generado
        wp_send_json_success(array(
            'file_url' => $upload_dir['baseurl'] . '/worker-portal/exports/' . $filename,
            'filename' => $filename,
            'count' => count($expenses)
        ));
    }

    /**
 * Estas funciones deben agregarse a la clase Worker_Portal_Module_Expenses 
 * en el archivo modules/expenses/class-expenses.php
 */

    /**
     * Maneja la petición AJAX para acciones masivas sobre gastos
     *
     * @since    1.0.0
     */
    public function ajax_bulk_expense_action() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_expenses_bulk_action')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options') && !$this->is_expense_approver()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'worker-portal'));
        }
        
        // Obtener acción y IDs
        $bulk_action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $expense_ids = isset($_POST['expense_ids']) ? array_map('intval', $_POST['expense_ids']) : array();
        
        // Validar datos
        if (empty($bulk_action) || !in_array($bulk_action, array('approve', 'reject'))) {
            wp_send_json_error(__('Acción no válida.', 'worker-portal'));
        }
        
        if (empty($expense_ids)) {
            wp_send_json_error(__('No se han seleccionado gastos.', 'worker-portal'));
        }
        
        // Procesar gastos
        $processed = 0;
        $user_id = get_current_user_id();
        
        foreach ($expense_ids as $expense_id) {
            // Verificar que el gasto existe y está pendiente
            $expense = $this->get_expense($expense_id);
            
            if (!$expense || $expense['status'] !== 'pending') {
                continue;
            }
            
            // Actualizar estado
            $status = $bulk_action === 'approve' ? 'approved' : 'rejected';
            $result = $this->update_expense_status($expense_id, $status, $user_id);
            
            if (!is_wp_error($result)) {
                $processed++;
                
                // Enviar notificación
                $this->send_approval_notification($expense_id, $status);
            }
        }
        
        // Devolver resultado
        if ($processed > 0) {
            $message = $bulk_action === 'approve' 
                ? sprintf(_n('%d gasto aprobado correctamente.', '%d gastos aprobados correctamente.', $processed, 'worker-portal'), $processed)
                : sprintf(_n('%d gasto rechazado correctamente.', '%d gastos rechazados correctamente.', $processed, 'worker-portal'), $processed);
            
            wp_send_json_success(array(
                'message' => $message,
                'processed' => $processed
            ));
        } else {
            wp_send_json_error(__('No se ha podido procesar ningún gasto.', 'worker-portal'));
        }
    }

    /**
     * Comprueba si el usuario actual es un aprobador de gastos
     *
     * @since    1.0.0
     * @return   bool    Si el usuario es aprobador
     */
    private function is_expense_approver() {
        $user_id = get_current_user_id();
        $approvers = get_option('worker_portal_expense_approvers', array());
        
        return in_array($user_id, $approvers);
    }

    /**
     * Maneja la petición AJAX para exportar gastos desde el admin
     *
     * @since    1.0.0
     */
    public function ajax_admin_export_expenses() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_expenses_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options') && !$this->is_expense_approver()) {
            wp_send_json_error(__('No tienes permisos para exportar gastos.', 'worker-portal'));
        }
        
        // Obtener parámetros de filtrado
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $expense_type = isset($_POST['expense_type']) ? sanitize_text_field($_POST['expense_type']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // Obtener gastos filtrados para exportación
        $expenses = $this->get_admin_filtered_expenses(
            $user_id,
            $date_from,
            $date_to,
            $expense_type,
            $status,
            1000 // Límite alto para la exportación
        );
        
        if (empty($expenses)) {
            wp_send_json_error(__('No hay gastos para exportar con los filtros seleccionados.', 'worker-portal'));
            return;
        }
        
        // Obtener los tipos de gastos disponibles
        $expense_types = get_option('worker_portal_expense_types', array(
            'km' => __('Kilometraje', 'worker-portal'),
            'hours' => __('Horas de desplazamiento', 'worker-portal'),
            'meal' => __('Dietas', 'worker-portal'),
            'other' => __('Otros', 'worker-portal')
        ));
        
        // Generar archivo CSV
        $filename = 'gastos_' . date('Y-m-d') . '.csv';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/worker-portal/exports/' . $filename;
        
        // Crear directorio si no existe
        if (!file_exists($upload_dir['basedir'] . '/worker-portal/exports/')) {
            wp_mkdir_p($upload_dir['basedir'] . '/worker-portal/exports/');
            
            // Añadir archivo index.php para proteger el directorio
            file_put_contents($upload_dir['basedir'] . '/worker-portal/exports/index.php', '<?php // Silence is golden');
        }
        
        // Abrir archivo para escritura
        $file = fopen($file_path, 'w');
        
        // Escribir BOM para UTF-8
        fputs($file, "\xEF\xBB\xBF");
        
        // Escribir cabecera
        fputcsv($file, array(
            __('ID', 'worker-portal'),
            __('Usuario', 'worker-portal'),
            __('Fecha de reporte', 'worker-portal'),
            __('Fecha del gasto', 'worker-portal'),
            __('Tipo', 'worker-portal'),
            __('Descripción', 'worker-portal'),
            __('Importe', 'worker-portal'),
            __('Unidad', 'worker-portal'),
            __('Ticket', 'worker-portal'),
            __('Estado', 'worker-portal'),
            __('Aprobado por', 'worker-portal'),
            __('Fecha de aprobación', 'worker-portal')
        ));
        
        // Escribir datos
        foreach ($expenses as $expense) {
            // Determinar unidad según tipo de gasto
            $unit = '';
            switch ($expense['expense_type']) {
                case 'km':
                    $unit = 'Km';
                    break;
                case 'hours':
                    $unit = __('Horas', 'worker-portal');
                    break;
                default:
                    $unit = __('Euros', 'worker-portal');
                    break;
            }
            
            // Estado del gasto
            $status_text = '';
            switch ($expense['status']) {
                case 'pending':
                    $status_text = __('Pendiente', 'worker-portal');
                    break;
                case 'approved':
                    $status_text = __('Aprobado', 'worker-portal');
                    break;
                case 'rejected':
                    $status_text = __('Denegado', 'worker-portal');
                    break;
            }
            
            // Obtener nombre del usuario
            $user_info = get_userdata($expense['user_id']);
            $user_name = $user_info ? $user_info->display_name : __('Usuario desconocido', 'worker-portal');
            
            // Obtener nombre del aprobador
            $approver_name = '';
            if (!empty($expense['approved_by'])) {
                $approver_info = get_userdata($expense['approved_by']);
                $approver_name = $approver_info ? $approver_info->display_name : __('Usuario desconocido', 'worker-portal');
            }
            
            // Escribir fila
            fputcsv($file, array(
                $expense['id'],
                $user_name,
                date_i18n(get_option('date_format'), strtotime($expense['report_date'])),
                date_i18n(get_option('date_format'), strtotime($expense['expense_date'])),
                isset($expense_types[$expense['expense_type']]) ? $expense_types[$expense['expense_type']] : $expense['expense_type'],
                $expense['description'],
                $expense['amount'],
                $unit,
                $expense['has_receipt'] ? __('Sí', 'worker-portal') : __('No', 'worker-portal'),
                $status_text,
                $approver_name,
                !empty($expense['approved_date']) ? date_i18n(get_option('date_format'), strtotime($expense['approved_date'])) : ''
            ));
        }
        
        // Cerrar archivo
        fclose($file);
        
        // Devolver la URL del archivo generado
        wp_send_json_success(array(
            'file_url' => $upload_dir['baseurl'] . '/worker-portal/exports/' . $filename,
            'filename' => $filename,
            'count' => count($expenses)
        ));
    }

    /**
     * Obtiene los detalles de un gasto para mostrar en modal
     *
     * @since    1.0.0
     */
    public function ajax_get_expense_details() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_expenses_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options') && !$this->is_expense_approver()) {
            wp_send_json_error(__('No tienes permisos para ver estos detalles.', 'worker-portal'));
        }
        
        // Obtener ID del gasto
        $expense_id = isset($_POST['expense_id']) ? intval($_POST['expense_id']) : 0;
        
        if (empty($expense_id)) {
            wp_send_json_error(__('ID de gasto no válido.', 'worker-portal'));
        }
        
        // Obtener detalles del gasto
        $expense = $this->get_expense($expense_id);
        
        if (!$expense) {
            wp_send_json_error(__('Gasto no encontrado.', 'worker-portal'));
        }
        
        // Obtener información adicional
        $user_info = get_userdata($expense['user_id']);
        $user_name = $user_info ? $user_info->display_name : __('Usuario desconocido', 'worker-portal');
        
        $approver_name = '';
        if (!empty($expense['approved_by'])) {
            $approver_info = get_userdata($expense['approved_by']);
            $approver_name = $approver_info ? $approver_info->display_name : __('Usuario desconocido', 'worker-portal');
        }
        
        // Obtener tipo de gasto
        $expense_types = get_option('worker_portal_expense_types', array());
        $expense_type_name = isset($expense_types[$expense['expense_type']]) 
            ? $expense_types[$expense['expense_type']] 
            : $expense['expense_type'];
        
        // Generar HTML para los detalles
        ob_start();
        ?>
        <div class="worker-portal-expense-details">
            <table class="widefat fixed" style="margin-bottom: 20px;">
                <tbody>
                    <tr>
                        <th style="width: 200px;"><?php _e('ID del gasto:', 'worker-portal'); ?></th>
                        <td><?php echo esc_html($expense['id']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Usuario:', 'worker-portal'); ?></th>
                        <td><?php echo esc_html($user_name); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Fecha de comunicación:', 'worker-portal'); ?></th>
                        <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($expense['report_date'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Fecha del gasto:', 'worker-portal'); ?></th>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($expense['expense_date'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Tipo de gasto:', 'worker-portal'); ?></th>
                        <td><?php echo esc_html($expense_type_name); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Descripción:', 'worker-portal'); ?></th>
                        <td><?php echo esc_html($expense['description']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Importe:', 'worker-portal'); ?></th>
                        <td>
                            <?php 
                            // Mostrar unidad según tipo de gasto
                            switch ($expense['expense_type']) {
                                case 'km':
                                    echo esc_html($expense['amount']) . ' Km';
                                    break;
                                case 'hours':
                                    echo esc_html($expense['amount']) . ' ' . __('Horas', 'worker-portal');
                                    break;
                                default:
                                    echo esc_html(number_format($expense['amount'], 2, ',', '.')) . ' €';
                                    break;
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Justificante:', 'worker-portal'); ?></th>
                        <td>
                            <?php if ($expense['has_receipt']): ?>
                                <span class="worker-portal-status-yes"><?php _e('Sí', 'worker-portal'); ?></span>
                                <?php if (!empty($expense['receipt_path'])): ?>
                                    <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . $expense['receipt_path']); ?>" target="_blank" class="button view-receipt">
                                        <span class="dashicons dashicons-visibility"></span> <?php _e('Ver justificante', 'worker-portal'); ?>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="worker-portal-status-no"><?php _e('No', 'worker-portal'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Estado:', 'worker-portal'); ?></th>
                        <td>
                            <?php
                            switch ($expense['status']) {
                                case 'pending':
                                    echo '<span class="worker-portal-status-pending">' . __('Pendiente', 'worker-portal') . '</span>';
                                    break;
                                case 'approved':
                                    echo '<span class="worker-portal-status-approved">' . __('Aprobado', 'worker-portal') . '</span>';
                                    break;
                                case 'rejected':
                                    echo '<span class="worker-portal-status-rejected">' . __('Denegado', 'worker-portal') . '</span>';
                                    break;
                            }
                            ?>
                        </td>
                    </tr>
                    <?php if (!empty($approver_name)): ?>
                        <tr>
                            <th><?php _e('Aprobado/Denegado por:', 'worker-portal'); ?></th>
                            <td><?php echo esc_html($approver_name); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($expense['approved_date'])): ?>
                        <tr>
                            <th><?php _e('Fecha de aprobación:', 'worker-portal'); ?></th>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($expense['approved_date'])); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($expense['status'] === 'pending'): ?>
                <div class="worker-portal-expense-actions">
                    <button type="button" class="button button-primary approve-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                        <span class="dashicons dashicons-yes"></span> <?php _e('Aprobar este gasto', 'worker-portal'); ?>
                    </button>
                    <button type="button" class="button button-secondary reject-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                        <span class="dashicons dashicons-no"></span> <?php _e('Denegar este gasto', 'worker-portal'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        $html = ob_get_clean();
        wp_send_json_success($html);
    }

    /**
     * Obtiene gastos filtrados para administración
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario (0 para todos)
     * @param    string    $date_from    Fecha inicial
     * @param    string    $date_to      Fecha final
     * @param    string    $expense_type Tipo de gasto
     * @param    string    $status       Estado del gasto
     * @param    int       $limit        Límite de resultados
     * @param    int       $offset       Offset para paginación
     * @return   array                   Lista de gastos
     */
    private function get_admin_filtered_expenses($user_id = 0, $date_from = '', $date_to = '', $expense_type = '', $status = '', $limit = 20, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        $query = "SELECT e.*, u.display_name AS user_name 
                  FROM $table_name e
                  LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
                  WHERE 1=1";
        $params = array();
        
        // Filtro por usuario
        if (!empty($user_id)) {
            $query .= " AND e.user_id = %d";
            $params[] = $user_id;
        }
        
        // Filtro por fecha desde
        if (!empty($date_from)) {
            $query .= " AND e.expense_date >= %s";
            $params[] = $date_from;
        }
        
        // Filtro por fecha hasta
        if (!empty($date_to)) {
            $query .= " AND e.expense_date <= %s";
            $params[] = $date_to;
        }
        
        // Filtro por tipo de gasto
        if (!empty($expense_type)) {
            $query .= " AND e.expense_type = %s";
            $params[] = $expense_type;
        }
        
        // Filtro por estado
        if (!empty($status)) {
            $query .= " AND e.status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY e.report_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }

}