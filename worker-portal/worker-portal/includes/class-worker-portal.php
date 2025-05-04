<?php
/**
 * Clase principal del plugin Portal del Trabajador
 *
 * @since      1.0.0
 */
class Worker_Portal {

    /**
     * Cargador de hooks
     *
     * @since    1.0.0
     * @access   protected
     * @var      Worker_Portal_Loader    $loader    Gestiona los hooks del plugin
     */
    protected $loader;

    /**
     * Gestor de módulos
     *
     * @since    1.0.0
     * @access   protected
     * @var      Worker_Portal_Module_Manager    $module_manager    Gestiona los módulos del plugin
     */
    protected $module_manager;

    /**
     * Constructor de la clase
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_modules();
    }

    /**
     * Carga las dependencias del plugin
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Cargar clase de gestión de hooks
        require_once WORKER_PORTAL_PATH . 'includes/class-loader.php';
        $this->loader = new Worker_Portal_Loader();

        // Cargar clase de internacionalización
        require_once WORKER_PORTAL_PATH . 'includes/class-i18n.php';

        // Cargar clase de gestión de módulos
        require_once WORKER_PORTAL_PATH . 'includes/class-module-manager.php';
        $this->module_manager = new Worker_Portal_Module_Manager();

        // Registrar módulos
        $this->register_modules();
    }

    /**
     * Registra los módulos del plugin
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_modules() {
        // Registrar módulos del plugin
        $modules = array(
            'documents',
            'expenses',
            'worksheets',
            'incentives',
            'workers'
        );

        foreach ($modules as $module) {
            $this->module_manager->register_module($module);
        }
    }

    /**
     * Configura la internacionalización
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Worker_Portal_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Registra los hooks para el área de administración
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        require_once WORKER_PORTAL_PATH . 'admin/class-admin.php';
        $plugin_admin = new Worker_Portal_Admin();

        // Registrar menús de administración
        $this->loader->add_action('admin_menu', $plugin_admin, 'register_admin_menu');
        
        // Registrar estilos y scripts de administración
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    }

    /**
     * Registra los hooks para el frontend
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        require_once WORKER_PORTAL_PATH . 'public/class-public.php';
        $plugin_public = new Worker_Portal_Public();

        // Registrar estilos y scripts públicos
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Registrar shortcodes
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        
        // Registrar hooks de AJAX
        $this->loader->add_action('init', $plugin_public, 'add_ajax_hooks');
    }

    /**
     * Inicializa los módulos del plugin
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_modules() {
        // Inicializar módulos activos
        $this->module_manager->init_active_modules();
    }

    /**
     * Ejecuta el plugin
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Obtiene el cargador de hooks
     *
     * @since    1.0.0
     * @return   Worker_Portal_Loader    Cargador de hooks del plugin
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Obtiene el gestor de módulos
     *
     * @since    1.0.0
     * @return   Worker_Portal_Module_Manager    Gestor de módulos del plugin
     */
    public function get_module_manager() {
        return $this->module_manager;
    }
}