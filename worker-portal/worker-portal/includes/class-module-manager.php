<?php
/**
 * Gestor de módulos para el plugin
 *
 * @since      1.0.0
 */
class Worker_Portal_Module_Manager {

    /**
     * Módulos registrados
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $registered_modules    Lista de módulos registrados
     */
    private $registered_modules = array();

    /**
     * Módulos activos
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $active_modules    Lista de instancias de módulos activos
     */
    private $active_modules = array();

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Nada por ahora
    }

    /**
     * Registra un módulo en el sistema
     *
     * @since    1.0.0
     * @param    string    $module_id    Identificador único del módulo
     * @return   bool                    Éxito del registro
     */
    public function register_module($module_id) {
        // Verificar que el módulo no esté ya registrado
        if (isset($this->registered_modules[$module_id])) {
            return false;
        }
        
        // Verificar que existe el directorio del módulo
        $module_dir = WORKER_PORTAL_PATH . 'modules/' . $module_id;
        if (!is_dir($module_dir)) {
            return false;
        }
        
        // Verificar que existe el archivo principal de la clase
        $module_class_file = $module_dir . '/class-' . $module_id . '.php';
        if (!file_exists($module_class_file)) {
            return false;
        }
        
        // Registrar el módulo
        $this->registered_modules[$module_id] = array(
            'id' => $module_id,
            'path' => $module_dir,
            'class_file' => $module_class_file,
            'class_name' => $this->get_class_name_from_id($module_id),
            'active' => $this->is_module_active($module_id),
        );
        
        return true;
    }

    /**
     * Inicializa todos los módulos activos
     *
     * @since    1.0.0
     */
    public function init_active_modules() {
        foreach ($this->registered_modules as $module_id => $module_info) {
            if ($module_info['active']) {
                $this->init_module($module_id);
            }
        }
    }

    /**
     * Inicializa un módulo específico
     *
     * @since    1.0.0
     * @param    string    $module_id    Identificador único del módulo
     * @return   bool                    Éxito de la inicialización
     */
    public function init_module($module_id) {
        // Verificar que el módulo está registrado
        if (!isset($this->registered_modules[$module_id])) {
            return false;
        }
        
        // Verificar que el módulo no está ya inicializado
        if (isset($this->active_modules[$module_id])) {
            return true;
        }
        
        $module_info = $this->registered_modules[$module_id];
        
        // Cargar la clase del módulo
        require_once $module_info['class_file'];
        
        // Crear instancia del módulo
        $class_name = $module_info['class_name'];
        
        if (!class_exists($class_name)) {
            return false;
        }
        
        // Inicializar el módulo
        $module_instance = new $class_name();
        $this->active_modules[$module_id] = $module_instance;
        
        // Llamar al método de inicialización del módulo
        if (method_exists($module_instance, 'init')) {
            $module_instance->init();
        }
        
        return true;
    }

    /**
     * Verifica si un módulo está activo
     *
     * @since    1.0.0
     * @param    string    $module_id    Identificador único del módulo
     * @return   bool                    Si el módulo está activo
     */
    public function is_module_active($module_id) {
        // Por defecto, todos los módulos están activos
        // En el futuro, esto podría verificar una opción en la base de datos
        $active_modules = get_option('worker_portal_active_modules', array());
        
        // Si la opción no existe o está vacía, todos los módulos están activos
        if (empty($active_modules)) {
            return true;
        }
        
        return in_array($module_id, $active_modules);
    }

    /**
     * Obtiene una instancia de un módulo activo
     *
     * @since    1.0.0
     * @param    string    $module_id    Identificador único del módulo
     * @return   object|null             Instancia del módulo o null si no está activo
     */
    public function get_module($module_id) {
        return isset($this->active_modules[$module_id]) ? $this->active_modules[$module_id] : null;
    }

    /**
     * Obtiene todos los módulos registrados
     *
     * @since    1.0.0
     * @return   array    Lista de información de módulos registrados
     */
    public function get_registered_modules() {
        return $this->registered_modules;
    }

    /**
     * Obtiene todos los módulos activos
     *
     * @since    1.0.0
     * @return   array    Lista de instancias de módulos activos
     */
    public function get_active_modules() {
        return $this->active_modules;
    }

    /**
     * Obtiene el nombre de la clase a partir del ID del módulo
     *
     * @since    1.0.0
     * @param    string    $module_id    Identificador único del módulo
     * @return   string                  Nombre de la clase del módulo
     */
    private function get_class_name_from_id($module_id) {
        // Convertir module_id a Worker_Portal_Module_{ModuleId}
        $parts = explode('_', $module_id);
        $parts = array_map('ucfirst', $parts);
        $module_name = implode('_', $parts);
        
        return 'Worker_Portal_Module_' . $module_name;
    }

    /**
     * Activa un módulo
     *
     * @since    1.0.0
     * @param    string    $module_id    Identificador único del módulo
     * @return   bool                    Éxito de la activación
     */
    public function activate_module($module_id) {
        // Verificar que el módulo está registrado
        if (!isset($this->registered_modules[$module_id])) {
            return false;
        }
        
        // Obtener módulos activos
        $active_modules = get_option('worker_portal_active_modules', array());
        
        // Verificar si ya está activo
        if (in_array($module_id, $active_modules)) {
            return true;
        }
        
        // Activar el módulo
        $active_modules[] = $module_id;
        update_option('worker_portal_active_modules', $active_modules);
        
        // Actualizar estado en el registro
        $this->registered_modules[$module_id]['active'] = true;
        
        // Inicializar el módulo
        $this->init_module($module_id);
        
        return true;
    }

    /**
     * Desactiva un módulo
     *
     * @since    1.0.0
     * @param    string    $module_id    Identificador único del módulo
     * @return   bool                    Éxito de la desactivación
     */
    public function deactivate_module($module_id) {
        // Verificar que el módulo está registrado
        if (!isset($this->registered_modules[$module_id])) {
            return false;
        }
        
        // Obtener módulos activos
        $active_modules = get_option('worker_portal_active_modules', array());
        
        // Verificar si ya está inactivo
        if (!in_array($module_id, $active_modules)) {
            return true;
        }
        
        // Desactivar el módulo
        $active_modules = array_diff($active_modules, array($module_id));
        update_option('worker_portal_active_modules', $active_modules);
        
        // Actualizar estado en el registro
        $this->registered_modules[$module_id]['active'] = false;
        
        // Remover de los módulos activos
        if (isset($this->active_modules[$module_id])) {
            unset($this->active_modules[$module_id]);
        }
        
        return true;
    }
}