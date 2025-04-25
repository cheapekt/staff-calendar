<?php
/**
 * Registra todos los hooks del plugin
 *
 * @since      1.0.0
 */
class WP_Time_Clock_Loader {

    /**
     * Array de acciones registradas con WordPress
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    Las acciones registradas con WordPress para ejecutarse cuando se carga el plugin
     */
    protected $actions;

    /**
     * Array de filtros registrados con WordPress
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    Los filtros registrados con WordPress para ejecutarse cuando se carga el plugin
     */
    protected $filters;

    /**
     * Inicializar las colecciones utilizadas para mantener las acciones y filtros
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Añadir una nueva acción al array de acciones
     *
     * @since    1.0.0
     * @param    string               $hook             El nombre de la acción de WordPress que se está registrando
     * @param    object               $component        Una referencia a la instancia del objeto en donde está definida la acción
     * @param    string               $callback         El nombre de la función que define la acción
     * @param    int                  $priority         Opcional. La prioridad en la que se debe ejecutar la función. Por defecto es 10
     * @param    int                  $accepted_args    Opcional. El número de argumentos que debería aceptar la función. Por defecto es 1
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Añadir un nuevo filtro al array de filtros
     *
     * @since    1.0.0
     * @param    string               $hook             El nombre del filtro de WordPress que se está registrando
     * @param    object               $component        Una referencia a la instancia del objeto en donde está definido el filtro
     * @param    string               $callback         El nombre de la función que define el filtro
     * @param    int                  $priority         Opcional. La prioridad en la que se debe ejecutar la función. Por defecto es 10
     * @param    int                  $accepted_args    Opcional. El número de argumentos que debería aceptar la función. Por defecto es 1
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Método de utilidad que se utiliza para registrar las acciones y hooks en una sola iteración
     *
     * @since    1.0.0
     * @access   private
     * @param    array                $hooks            La colección de hooks que se está registrando (acción o filtro)
     * @param    string               $hook             El nombre del hook de WordPress que se está registrando
     * @param    object               $component        Una referencia a la instancia del objeto en donde está definido el hook
     * @param    string               $callback         El nombre de la función que define el hook
     * @param    int                  $priority         La prioridad en la que se debe ejecutar la función
     * @param    int                  $accepted_args    El número de argumentos que debería aceptar la función
     * @return   array                La colección de hooks que se registró con WordPress
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Registra los filtros y acciones con WordPress
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}
