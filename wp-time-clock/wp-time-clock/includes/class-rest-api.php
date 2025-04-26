<?php
/**
 * Gestiona la API REST del plugin WP Time Clock
 *
 * @since      1.1.0
 */
class WP_Time_Clock_REST_API {

    /**
     * El identificador único de este plugin
     *
     * @since    1.1.0
     * @access   private
     * @var      string    $plugin_name    El nombre o identificador único de este plugin
     */
    private $plugin_name;

    /**
     * La versión actual del plugin
     *
     * @since    1.1.0
     * @access   private
     * @var      string    $version    La versión actual del plugin
     */
    private $version;

    /**
     * Namespace para la API REST
     *
     * @since    1.1.0
     * @access   private
     * @var      string    $namespace    Namespace para los endpoints
     */
    private $namespace;

    /**
     * Constructor
     *
     * @since    1.1.0
     * @param    string    $plugin_name    El nombre del plugin
     * @param    string    $version        La versión del plugin
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->namespace = 'wp-time-clock/v1';
    }

    /**
     * Registra las rutas de la API REST
     *
     * @since    1.1.0
     */
    public function register_routes() {
        // Registro de rutas principales
        $routes = [
            // Fichajes
            [
                'method' => 'POST',
                'path' => '/clock-in',
                'callback' => 'clock_in',
                'permission' => 'permissions_check'
            ],
            [
                'method' => 'POST',
                'path' => '/clock-out',
                'callback' => 'clock_out',
                'permission' => 'permissions_check'
            ],
            // Estado y entradas
            [
                'method' => 'GET',
                'path' => '/status',
                'callback' => 'get_status',
                'permission' => 'permissions_check'
            ],
            [
                'method' => 'GET',
                'path' => '/entries',
                'callback' => 'get_entries',
                'permission' => 'permissions_check'
            ],
            // Edición de entradas (requiere permisos de admin)
            [
                'method' => 'GET',
                'path' => '/edit-entry/(?P<id>\d+)',
                'callback' => 'get_entry_details',
                'permission' => 'admin_permissions_check',
                'args' => [
                    'id' => [
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ]
                ]
            ],
            [
                'method' => 'POST',
                'path' => '/edit-entry/(?P<id>\d+)',
                'callback' => 'edit_entry',
                'permission' => 'admin_permissions_check',
                'args' => [
                    'id' => [
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ]
                ]
            ],
            // Configuraciones
            [
                'method' => 'GET',
                'path' => '/settings',
                'callback' => 'get_settings',
                'permission' => 'admin_permissions_check'
            ],
            [
                'method' => 'POST',
                'path' => '/settings',
                'callback' => 'save_settings',
                'permission' => 'admin_permissions_check'
            ],
            // Restablecer configuraciones
            [
                'method' => 'POST',
                'path' => '/reset-settings',
                'callback' => 'reset_settings',
                'permission' => 'admin_permissions_check'
            ]
        ];

        // Registrar todas las rutas
        foreach ($routes as $route) {
            register_rest_route($this->namespace, $route['path'], [
                'methods' => $route['method'],
                'callback' => [$this, $route['callback']],
                'permission_callback' => [$this, $route['permission']],
                'args' => $route['args'] ?? []
            ]);
        }
    }

    /**
     * Verifica permisos básicos (usuario logueado)
     *
     * @since    1.1.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   bool|WP_Error                  True si el usuario tiene permiso, WP_Error en caso contrario
     */
    public function permissions_check($request) {
        // Verificar nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_forbidden', __('Acceso denegado (nonce inválido)', 'wp-time-clock'), ['status' => 401]);
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', __('Debes iniciar sesión', 'wp-time-clock'), ['status' => 401]);
        }
        
        // Verificar que el usuario puede fichar
        if (!current_user_can('time_clock_clock_in_out')) {
            return new WP_Error('rest_forbidden', __('No tienes permiso para fichar', 'wp-time-clock'), ['status' => 403]);
        }
        
        return true;
    }

    /**
     * Verifica permisos de administrador
     *
     * @since    1.1.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   bool|WP_Error                  True si el usuario tiene permiso, WP_Error en caso contrario
     */
    public function admin_permissions_check($request) {
        // Verificar permisos básicos primero
        $basic_check = $this->permissions_check($request);
        if (is_wp_error($basic_check)) {
            return $basic_check;
        }
        
        // Verificar permisos de administración
        if (!current_user_can('time_clock_edit_entries') && !current_user_can('administrator')) {
            return new WP_Error('rest_forbidden', __('No tienes permiso para realizar esta acción', 'wp-time-clock'), ['status' => 403]);
        }
        
        return true;
    }
    /**
     * Endpoint para fichar entrada
     *
     * @since    1.1.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function clock_in($request) {
        $params = $request->get_params();
        $location = isset($params['location']) ? sanitize_text_field($params['location']) : '';
        $note = isset($params['note']) ? sanitize_textarea_field($params['note']) : '';
        
        $clock_manager = new WP_Time_Clock_Manager();
        $result = $clock_manager->clock_in(null, $location, $note);
        
        return new WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    /**
     * Endpoint para fichar salida
     *
     * @since    1.1.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function clock_out($request) {
        $params = $request->get_params();
        $location = isset($params['location']) ? sanitize_text_field($params['location']) : '';
        $note = isset($params['note']) ? sanitize_textarea_field($params['note']) : '';
        
        $clock_manager = new WP_Time_Clock_Manager();
        $result = $clock_manager->clock_out(null, $location, $note);
        
        return new WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    /**
     * Endpoint para obtener el estado de fichaje
     *
     * @since    1.1.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function get_status($request) {
        $params = $request->get_params();
        $user_id = isset($params['user_id']) ? intval($params['user_id']) : null;
        
        // Si se solicita estado de otro usuario, verificar permisos
        if ($user_id && $user_id !== get_current_user_id()) {
            if (!current_user_can('time_clock_view_all') && !current_user_can('administrator')) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => __('No tienes permiso para ver el estado de otros usuarios', 'wp-time-clock')
                ], 403);
            }
        }
        
        $clock_manager = new WP_Time_Clock_Manager();
        $status = $clock_manager->get_user_status($user_id);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $status
        ], 200);
    }

    /**
     * Endpoint para obtener entradas de fichaje
     *
     * @since    1.1.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function get_entries($request) {
        $params = $request->get_params();
        
        $user_id = isset($params['user_id']) ? intval($params['user_id']) : get_current_user_id();
        $start_date = isset($params['start_date']) ? sanitize_text_field($params['start_date']) : date('Y-m-01');
        $end_date = isset($params['end_date']) ? sanitize_text_field($params['end_date']) : date('Y-m-t');
        $status = isset($params['status']) ? sanitize_text_field($params['status']) : 'all';
        
        // Si se solicitan entradas de otro usuario, verificar permisos
        if ($user_id !== get_current_user_id()) {
            if (!current_user_can('time_clock_view_all') && !current_user_can('administrator')) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => __('No tienes permiso para ver las entradas de otros usuarios', 'wp-time-clock')
                ], 403);
            }
        }
        
        $clock_manager = new WP_Time_Clock_Manager();
        $entries = $clock_manager->get_user_entries($user_id, $start_date, $end_date, $status);
        
        // Calcular estadísticas
        $stats = $this->calculate_stats($entries);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $entries,
            'stats' => $stats
        ], 200);
    }

    /**
     * Calcula estadísticas para un conjunto de entradas
     *
     * @since    1.1.0
     * @param    array     $entries    Array de entradas
     * @return   array                 Estadísticas calculadas
     */
    private function calculate_stats($entries) {
        $total_seconds = 0;
        $count = 0;
        $days_worked = [];
        
        foreach ($entries as $entry) {
            if ($entry->clock_out) {
                $total_seconds += $entry->time_worked['total_seconds'];
                $count++;
                
                // Contar días únicos trabajados
                $day = date('Y-m-d', strtotime($entry->clock_in));
                $days_worked[$day] = true;
            }
        }
        
        $clock_manager = new WP_Time_Clock_Manager();
        
        return [
            'total_entries' => $count,
            'total_time' => $clock_manager->format_time_worked($total_seconds),
            'total_days' => count($days_worked),
            'average_per_day' => $count > 0 
                ? $clock_manager->format_time_worked($total_seconds / $count) 
                : '00:00:00'
        ];
    }

    /**
     * Endpoint para obtener detalles de una entrada específica
     *
     * @since    1.1.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function get_entry_details($request) {
        $entry_id = $request['id'];
        global $wpdb;
        
        $table_entries = $wpdb->prefix . 'time_clock_entries';
        $table_users = $wpdb->users;
        
        // Consultar detalles de la entrada con información del usuario
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, u.display_name as user_name 
            FROM {$table_entries} e
            JOIN {$table_users} u ON e.user_id = u.ID
            WHERE e.id = %d",
            $entry_id
        ), ARRAY_A);
        
        if (!$entry) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Entrada no encontrada', 'wp-time-clock')
            ], 404);
        }
        
        // Añadir información de usuario que editó (si aplica)
        if ($entry['edited_by']) {
            $editor = get_userdata($entry['edited_by']);
            $entry['editor_name'] = $editor ? $editor->display_name : __('Usuario desconocido', 'wp-time-clock');
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $entry
        ], 200);
    }

    /**
     * Endpoint para editar una entrada
     *
     * @since    1.1.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function edit_entry($request) {
        $entry_id = $request['id'];
        $params = $request->get_params();
        
        $data = [];
        
        // Campos permitidos para edición
        $allowed_fields = [
            'clock_in', 
            'clock_out', 
            'clock_in_note', 
            'clock_out_note', 
            'status'
        ];
        
        foreach ($allowed_fields as $field) {
            if (isset($params[$field])) {
                $data[$field] = $field === 'status' 
                    ? sanitize_text_field($params[$field])
                    : ($field === 'clock_in_note' || $field === 'clock_out_note' 
                        ? sanitize_textarea_field($params[$field]) 
                        : sanitize_text_field($params[$field]));
            }
        }
        
        $clock_manager = new WP_Time_Clock_Manager();
        $result = $clock_manager->edit_entry($entry_id, $data);
        
        return new WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    /**
     * Método de utilidad para sanitizar valores de checkbox
     *
     * @since    1.1.0
     * @param    mixed    $value    Valor a sanitizar
     * @return   string             'yes' o 'no'
     */
    private function sanitize_checkbox($value) {
        // Valores que representan verdadero
        $truthy_values = [true, 'true', '1', 'yes', 1];
        
        return in_array($value, $truthy_values, true) ? 'yes' : 'no';
    }

    // Los métodos de configuraciones (get_settings, save_settings, reset_settings)
    // seguirán en la misma línea de los métodos anteriores, con validaciones robustas
    
    /**
     * Endpoint para obtener configuraciones
     *
     * @since    1.1.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function get_settings($request) {
        $clock_manager = new WP_Time_Clock_Manager();
        
        // Lista de opciones a recuperar
        $options = [
            'working_hours_per_day',
            'allow_manual_entry',
            'require_approval',
            'geolocation_enabled',
            'clock_button_style',
            'notification_emails',
            'allow_clock_note',
            'display_clock_time',
            'enable_breaks',
            'auto_clock_out',
            'auto_clock_out_time',
            'weekend_days',
            'workday_start',
            'workday_end'
        ];
        
        $settings = [];
        
        foreach ($options as $option) {
            $settings[$option] = $clock_manager->get_setting($option);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $settings
        ], 200);
    }

    // Los métodos save_settings y reset_settings serían similares a los 
    // proporcionados en el mensaje anterior
}