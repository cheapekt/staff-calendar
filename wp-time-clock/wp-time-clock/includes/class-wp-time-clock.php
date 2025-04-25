<?php
/**
 * La clase principal del plugin
 *
 * @since      1.0.0
 */
class WP_Time_Clock {

    /**
     * Instancia única de esta clase
     *
     * @since    1.0.0
     * @access   private
     * @var      WP_Time_Clock    $instance    La instancia única de esta clase
     */
    private static $instance = null;

    /**
     * El cargador de hooks que une todas las funcionalidades del plugin
     *
     * @since    1.0.0
     * @access   protected
     * @var      object    $loader    El cargador de hooks del plugin
     */
    protected $loader;

    /**
     * El identificador único de este plugin
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    Identificador único del plugin
     */
    protected $plugin_name;

    /**
     * La versión actual del plugin
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    La versión actual del plugin
     */
    protected $version;

    /**
     * El gestor de fichajes
     *
     * @since    1.0.0
     * @access   protected
     * @var      object    $clock_manager    Clase que gestiona los fichajes
     */
    protected $clock_manager;

    /**
     * API REST del plugin
     *
     * @since    1.0.0
     * @access   protected
     * @var      object    $rest_api    La API REST del plugin
     */
    protected $rest_api;

    /**
     * Definir la funcionalidad central del plugin
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = WP_TIME_CLOCK_VERSION;
        $this->plugin_name = 'wp-time-clock';
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_rest_api();
    }

    /**
     * Obtener la instancia única de esta clase
     *
     * @since     1.0.0
     * @return    WP_Time_Clock    La instancia única de esta clase
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Cargar las dependencias necesarias para este plugin
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Clase para manejar hooks y filtros
        require_once WP_TIME_CLOCK_PATH . 'includes/class-loader.php';
        $this->loader = new WP_Time_Clock_Loader();

        // Clase para internacionalización
        require_once WP_TIME_CLOCK_PATH . 'includes/class-i18n.php';

        // Clase para gestión de fichajes
        require_once WP_TIME_CLOCK_PATH . 'includes/class-clock-manager.php';
        $this->clock_manager = new WP_Time_Clock_Manager();

        // Clase para gestión de ubicaciones
        require_once WP_TIME_CLOCK_PATH . 'includes/class-location-manager.php';

        // Clases Admin y Public
        require_once WP_TIME_CLOCK_PATH . 'admin/class-admin.php';
        require_once WP_TIME_CLOCK_PATH . 'public/class-public.php';

        // API REST
        require_once WP_TIME_CLOCK_PATH . 'includes/class-rest-api.php';
        $this->rest_api = new WP_Time_Clock_REST_API($this->get_plugin_name(), $this->get_version());
    }

    /**
     * Definir la configuración regional del plugin
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new WP_Time_Clock_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Registrar los hooks relacionados con la funcionalidad de administración del plugin
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new WP_Time_Clock_Admin($this->get_plugin_name(), $this->get_version());

        // Agregar menú en el admin
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_pages');
        
        // Registrar estilos y scripts
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Agregar enlaces en la página de plugins
        $this->loader->add_filter('plugin_action_links_' . WP_TIME_CLOCK_BASENAME, $plugin_admin, 'add_plugin_links');
        
        // Agregar posibles metaboxes en el dashboard
        $this->loader->add_action('wp_dashboard_setup', $plugin_admin, 'add_dashboard_widgets');
    }

    /**
     * Registrar los hooks relacionados con la funcionalidad del frontend
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new WP_Time_Clock_Public($this->get_plugin_name(), $this->get_version());

        // Registrar estilos y scripts
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Agregar shortcodes adicionales si los hay
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
    }

    /**
     * Configurar la API REST
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_rest_api() {
        $this->loader->add_action('rest_api_init', $this->rest_api, 'register_routes');
    }

    /**
     * Ejecutar el cargador para ejecutar todos los hooks con WordPress
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * El nombre del plugin usado para identificarlo en el contexto de WordPress
     *
     * @since     1.0.0
     * @return    string    El nombre del plugin
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * La referencia al cargador de hooks que coordina los hooks del plugin
     *
     * @since     1.0.0
     * @return    object    Coordina los hooks del plugin
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Obtiene la versión del plugin
     *
     * @since     1.0.0
     * @return    string    La versión del plugin
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Obtiene el gestor de fichajes
     *
     * @since     1.0.0
     * @return    object    El gestor de fichajes
     */
    public function get_clock_manager() {
        return $this->clock_manager;
    }

}
