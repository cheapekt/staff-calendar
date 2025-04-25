<?php
/**
 * Gestiona la API REST del plugin
 *
 * @since      1.0.0
 */
class WP_Time_Clock_REST_API {

    /**
     * El identificador único de este plugin
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    El nombre o identificador único de este plugin
     */
    private $plugin_name;

    /**
     * La versión actual del plugin
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    La versión actual del plugin
     */
    private $version;

    /**
     * Namespace para la API REST
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $namespace    Namespace para los endpoints
     */
    private $namespace;

    /**
     * Constructor
     *
     * @since    1.0.0
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
     * @since    1.0.0
     */
    public function register_routes() {
        // Ruta para fichar entrada
        register_rest_route($this->namespace, '/clock-in', array(
            'methods' => 'POST',
            'callback' => array($this, 'clock_in'),
            'permission_callback' => array($this, 'permissions_check')
        ));
        
        // Ruta para fichar salida
        register_rest_route($this->namespace, '/clock-out', array(
            'methods' => 'POST',
            'callback' => array($this, 'clock_out'),
            'permission_callback' => array($this, 'permissions_check')
        ));
        
        // Ruta para obtener estado de usuario
        register_rest_route($this->namespace, '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_status'),
            'permission_callback' => array($this, 'permissions_check')
        ));
        
        // Ruta para obtener entradas
        register_rest_route($this->namespace, '/entries', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_entries'),
            'permission_callback' => array($this, 'permissions_check')
        ));
        
        // Ruta para editar entrada
        register_rest_route($this->namespace, '/edit-entry/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'edit_entry'),
            'permission_callback' => array($this, 'admin_permissions_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Ruta para obtener configuraciones
        register_rest_route($this->namespace, '/settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_settings'),
            'permission_callback' => array($this, 'admin_permissions_check')
        ));
        
        // Ruta para guardar configuraciones
        register_rest_route($this->namespace, '/settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_settings'),
            'permission_callback' => array($this, 'admin_permissions_check')
        ));
    }

    /**
     * Verifica permisos básicos (usuario logueado)
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   bool|WP_Error                  True si el usuario tiene permiso, WP_Error en caso contrario
     */
    public function permissions_check($request) {
        // Verificar nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_forbidden', __('Acceso denegado (nonce inválido)', 'wp-time-clock'), array('status' => 401));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            return new WP_Error('rest_forbidden', __('Debes iniciar sesión', 'wp-time-clock'), array('status' => 401));
        }
        
        // Verificar que el usuario puede fichar
        if (!current_user_can('time_clock_clock_in_out')) {
            return new WP_Error('rest_forbidden', __('No tienes permiso para fichar', 'wp-time-clock'), array('status' => 403));
        }
        
        return true;
    }

    /**
     * Verifica permisos de administrador
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   bool|WP_Error                  True si el usuario tiene permiso, WP_Error en caso contrario
     */
    public function admin_permissions_check($request) {
        // Primero verificar permisos básicos
        $basic_check = $this->permissions_check($request);
        if (is_wp_error($basic_check)) {
            return $basic_check;
        }
        
        // Verificar permisos de administración
        if (!current_user_can('time_clock_edit_entries') && !current_user_can('administrator')) {
            return new WP_Error('rest_forbidden', __('No tienes permiso para realizar esta acción', 'wp-time-clock'), array('status' => 403));
        }
        
        return true;
    }

    /**
     * Endpoint para fichar entrada
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function clock_in($request) {
        $params = $request->get_params();
        $location = isset($params['location']) ? sanitize_text_field($params['location']) : '';
        $note = isset($params['note']) ? sanitize_textarea_field($params['note']) : '';
        
        $clock_manager = new WP_Time_Clock_Manager();
        $result = $clock_manager->clock_in(null, $location, $note);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_REST_Response($result, 400);
        }
    }

    /**
     * Endpoint para fichar salida
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function clock_out($request) {
        $params = $request->get_params();
        $location = isset($params['location']) ? sanitize_text_field($params['location']) : '';
        $note = isset($params['note']) ? sanitize_textarea_field($params['note']) : '';
        
        $clock_manager = new WP_Time_Clock_Manager();
        $result = $clock_manager->clock_out(null, $location, $note);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_REST_Response($result, 400);
        }
    }

    /**
     * Endpoint para obtener el estado de fichaje
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function get_status($request) {
        $params = $request->get_params();
        $user_id = isset($params['user_id']) ? intval($params['user_id']) : null;
        
        // Si se solicita estado de otro usuario, verificar permisos
        if ($user_id && $user_id !== get_current_user_id()) {
            if (!current_user_can('time_clock_view_all') && !current_user_can('administrator')) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => __('No tienes permiso para ver el estado de otros usuarios', 'wp-time-clock')
                ), 403);
            }
        }
        
        $clock_manager = new WP_Time_Clock_Manager();
        $status = $clock_manager->get_user_status($user_id);
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $status
        ), 200);
    }

    /**
     * Endpoint para obtener entradas de fichaje
     *
     * @since    1.0.0
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
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => __('No tienes permiso para ver las entradas de otros usuarios', 'wp-time-clock')
                ), 403);
            }
        }
        
        $clock_manager = new WP_Time_Clock_Manager();
        $entries = $clock_manager->get_user_entries($user_id, $start_date, $end_date, $status);
        
        // Calcular estadísticas
        $stats = $this->calculate_stats($entries);
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $entries,
            'stats' => $stats
        ), 200);
    }

    /**
     * Calcula estadísticas de un conjunto de entradas
     *
     * @since    1.0.0
     * @param    array     $entries    Array de entradas
     * @return   array                 Estadísticas
     */
    private function calculate_stats($entries) {
        $total_seconds = 0;
        $count = 0;
        $days_worked = array();
        
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
        
        return array(
            'total_entries' => $count,
            'total_time' => $clock_manager->format_time_worked($total_seconds),
            'total_days' => count($days_worked),
            'average_per_day' => $count > 0 ? $clock_manager->format_time_worked($total_seconds / count($days_worked)) : '00:00:00'
        );
    }

    /**
     * Endpoint para editar una entrada
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function edit_entry($request) {
        $entry_id = $request['id']; // De la URL
        $params = $request->get_params();
        
        $data = array();
        
        if (isset($params['clock_in'])) {
            $data['clock_in'] = sanitize_text_field($params['clock_in']);
        }
        
        if (isset($params['clock_out'])) {
            $data['clock_out'] = sanitize_text_field($params['clock_out']);
        }
        
        if (isset($params['clock_in_note'])) {
            $data['clock_in_note'] = sanitize_textarea_field($params['clock_in_note']);
        }
        
        if (isset($params['clock_out_note'])) {
            $data['clock_out_note'] = sanitize_textarea_field($params['clock_out_note']);
        }
        
        if (isset($params['status'])) {
            $data['status'] = sanitize_text_field($params['status']);
        }
        
        $clock_manager = new WP_Time_Clock_Manager();
        $result = $clock_manager->edit_entry($entry_id, $data);
        
        if ($result['success']) {
            return new WP_REST_Response($result, 200);
        } else {
            return new WP_REST_Response($result, 400);
        }
    }

    /**
     * Endpoint para obtener configuraciones
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function get_settings($request) {
        $clock_manager = new WP_Time_Clock_Manager();
        
        // Lista de opciones a recuperar
        $options = array(
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
        );
        
        $settings = array();
        
        foreach ($options as $option) {
            $settings[$option] = $clock_manager->get_setting($option);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $settings
        ), 200);
    }

    /**
     * Endpoint para guardar configuraciones
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    La petición REST
     * @return   WP_REST_Response               Respuesta al cliente
     */
    public function save_settings($request) {
        $params = $request->get_params();
        $clock_manager = new WP_Time_Clock_Manager();
        
        $allowed_settings = array(
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
        );
        
        $updated = 0;
        
        foreach ($params as $key => $value) {
            if (in_array($key, $allowed_settings)) {
                // Sanitizar según el tipo de campo
                if ($key === 'notification_emails') {
                    $value = sanitize_email($value);
                } elseif (in_array($key, array('allow_manual_entry', 'require_approval', 'geolocation_enabled', 'allow_clock_note', 'display_clock_time', 'enable_breaks', 'auto_clock_out'))) {
                    $value = ($value === 'yes' || $value === '1' || $value === 'true') ? 'yes' : 'no';
                } else {
                    $value = sanitize_text_field($value);
                }
                
                $result = $clock_manager->save_setting($key, $value);
                if ($result) {
                    $updated++;
                }
            }
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => sprintf(__('Se actualizaron %d configuraciones', 'wp-time-clock'), $updated),
            'updated_count' => $updated
        ), 200);
    }
}
