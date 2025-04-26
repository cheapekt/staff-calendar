<?php
/**
 * Registra y gestiona los hooks del plugin
 *
 * @since      1.0.0
 */
class Worker_Portal_Loader {

    /**
     * Acciones registradas
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    Lista de acciones
     */
    protected $actions;

    /**
     * Filtros registrados
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    Lista de filtros
     */
    protected $filters;

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Añade una acción al sistema
     *
     * @since    1.0.0
     * @param    string         $hook           Nombre del hook
     * @param    object         $component      Objeto que contiene el método
     * @param    string         $callback       Nombre del método
     * @param    int            $priority       Prioridad del hook
     * @param    int            $accepted_args  Número de argumentos aceptados
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Añade un filtro al sistema
     *
     * @since    1.0.0
     * @param    string         $hook           Nombre del hook
     * @param    object         $component      Objeto que contiene el método
     * @param    string         $callback       Nombre del método
     * @param    int            $priority       Prioridad del hook
     * @param    int            $accepted_args  Número de argumentos aceptados
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Método interno para añadir hooks
     *
     * @since    1.0.0
     * @access   private
     * @param    array          $hooks          Lista de hooks
     * @param    string         $hook           Nombre del hook
     * @param    object         $component      Objeto que contiene el método
     * @param    string         $callback       Nombre del método
     * @param    int            $priority       Prioridad del hook
     * @param    int            $accepted_args  Número de argumentos aceptados
     * @return   array                          Lista de hooks actualizada
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
     * Ejecuta todos los hooks registrados
     *
     * @since    1.0.0
     */
    public function run() {
        // Ejecutar filtros
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Ejecutar acciones
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}