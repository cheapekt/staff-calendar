<?php
/**
 * Funcionalidad específica de frontend del plugin
 *
 * @since      1.0.0
 */
class WP_Time_Clock_Public {

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
     * Constructor
     *
     * @since    1.0.0
     * @param    string    $plugin_name    El nombre del plugin
     * @param    string    $version        La versión del plugin
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Registra los estilos para el frontend
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            WP_TIME_CLOCK_URL . 'public/css/public-style.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Registra los scripts para el frontend
     *
     * @since    1.0.0
     */
public function enqueue_scripts() {
    // Get the absolute URL to the script file
    $script_url = plugins_url('public/js/public-script.js', dirname(__FILE__));
    
    // Debug the URL
    error_log('WP Time Clock script URL: ' . $script_url);
    
    // Enqueue the script
    wp_enqueue_script(
        $this->plugin_name,
        $script_url,
        array('jquery'),
        $this->version . '.' . time(), // Add timestamp to prevent caching
        true
    );
    
    // Rest of your code...
    $clock_manager = new WP_Time_Clock_Manager();
    $geolocation_enabled = $clock_manager->get_setting('geolocation_enabled', 'yes');
    
    // Pasar variables a JavaScript
    wp_localize_script(
        $this->plugin_name,
        'wpTimeClock',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url($this->plugin_name . '/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'user_id' => get_current_user_id(),
            'geolocation_enabled' => ($geolocation_enabled === 'yes'),
            'i18n' => array(
                'error' => __('Error', 'wp-time-clock'),
                'success' => __('Éxito', 'wp-time-clock'),
                'confirm_clockout' => __('¿Estás seguro de que deseas registrar la salida?', 'wp-time-clock'),
                'location_error' => __('No se pudo obtener tu ubicación. Por favor, verifica los permisos de ubicación en tu navegador.', 'wp-time-clock'),
                'location_wait' => __('Obteniendo ubicación...', 'wp-time-clock'),
                'loading' => __('Cargando...', 'wp-time-clock'),
                'save' => __('Guardar', 'wp-time-clock'),
                'cancel' => __('Cancelar', 'wp-time-clock')
            )
        )
    );
}

    /**
     * Registra los shortcodes adicionales
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('wp_time_clock_history', array($this, 'history_shortcode'));
        // El shortcode principal 'wp_time_clock' ya está registrado en el archivo principal
    }

    /**
     * Implementa el shortcode para mostrar el historial de fichajes del usuario
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            HTML renderizado
     */
    public function history_shortcode($atts) {
        // Si el usuario no está logueado
        if (!is_user_logged_in()) {
            return sprintf(
                '<div class="wp-time-clock-message">%s</div>',
                __('Debes iniciar sesión para ver tu historial de fichajes', 'wp-time-clock')
            );
        }
        
        $atts = shortcode_atts(array(
            'days' => 30,
            'show_notes' => 'yes',
            'show_times' => 'yes'
        ), $atts, 'wp_time_clock_history');
        
        // Preparar variables para la plantilla
        $user_id = get_current_user_id();
        $days = intval($atts['days']);
        $show_notes = ($atts['show_notes'] === 'yes');
        $show_times = ($atts['show_times'] === 'yes');
        
        // Calcular fechas
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        // Obtener entradas
        $clock_manager = new WP_Time_Clock_Manager();
        $entries = $clock_manager->get_user_entries($user_id, $start_date, $end_date);
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir la plantilla
        include WP_TIME_CLOCK_PATH . 'public/partials/history-display.php';
        
        return ob_get_clean();
    }
}
