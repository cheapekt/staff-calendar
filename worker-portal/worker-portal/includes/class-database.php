<?php
/**
 * Gestiona las operaciones de base de datos
 *
 * @since      1.0.0
 */
class Worker_Portal_Database {

    /**
     * Instancia única de esta clase
     *
     * @since    1.0.0
     * @access   private
     * @var      Worker_Portal_Database    $instance    La instancia única de esta clase
     */
    private static $instance = null;

    /**
     * Método para obtener la instancia única
     *
     * @since    1.0.0
     * @return   Worker_Portal_Database    La instancia única de esta clase
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    private function __construct() {
        // Nada por ahora
    }

    /**
     * Obtiene los documentos de un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id    ID del usuario
     * @param    int       $limit      Límite de resultados
     * @param    int       $offset     Desplazamiento para paginación
     * @param    string    $category   Categoría de los documentos (opcional)
     * @return   array                 Lista de documentos
     */
    public function get_user_documents($user_id, $limit = 10, $offset = 0, $category = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_documents';
        
        $query = "SELECT * FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        if (!empty($category)) {
            $query .= " AND category = %s";
            $params[] = $category;
        }
        
        $query .= " ORDER BY upload_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }

    /**
     * Obtiene los gastos de un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id    ID del usuario
     * @param    int       $limit      Límite de resultados
     * @param    int       $offset     Desplazamiento para paginación
     * @param    string    $status     Estado de los gastos (opcional)
     * @return   array                 Lista de gastos
     */
    public function get_user_expenses($user_id, $limit = 10, $offset = 0, $status = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        $query = "SELECT * FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        if (!empty($status) && $status !== 'all') {
            $query .= " AND status = %s";
            $params[] = $status;
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
     * Inserta un nuevo gasto
     *
     * @since    1.0.0
     * @param    array     $data    Datos del gasto
     * @return   int|false          ID del gasto insertado o false si hubo error
     */
    public function insert_expense($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        $result = $wpdb->insert(
            $table_name,
            $data,
            array('%d', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Actualiza un gasto existente
     *
     * @since    1.0.0
     * @param    int       $expense_id    ID del gasto
     * @param    array     $data          Datos a actualizar
     * @return   bool                     True si se actualizó correctamente, false en caso contrario
     */
    public function update_expense($expense_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        return $wpdb->update(
            $table_name,
            $data,
            array('id' => $expense_id),
            null, // Formato determinado por los tipos de datos
            array('%d')
        ) !== false;
    }

    /**
     * Elimina un gasto
     *
     * @since    1.0.0
     * @param    int       $expense_id    ID del gasto
     * @return   bool                     True si se eliminó correctamente, false en caso contrario
     */
    public function delete_expense($expense_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_expenses';
        
        return $wpdb->delete(
            $table_name,
            array('id' => $expense_id),
            array('%d')
        ) !== false;
    }

    /**
     * Obtiene un gasto específico
     *
     * @since    1.0.0
     * @param    int       $expense_id    ID del gasto
     * @return   array|null               Datos del gasto o null si no existe
     */
    public function get_expense($expense_id) {
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
     * Obtiene las hojas de trabajo de un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario
     * @param    int       $limit        Límite de resultados
     * @param    int       $offset       Desplazamiento para paginación
     * @param    string    $status       Estado de las hojas (opcional)
     * @param    string    $date_start   Fecha de inicio (opcional)
     * @param    string    $date_end     Fecha de fin (opcional)
     * @return   array                   Lista de hojas de trabajo
     */
    public function get_user_worksheets($user_id, $limit = 10, $offset = 0, $status = '', $date_start = '', $date_end = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_worksheets';
        $projects_table = $wpdb->prefix . 'worker_projects';
        
        $query = "SELECT w.*, p.name as project_name, p.location as project_location
                  FROM $table_name w
                  LEFT JOIN $projects_table p ON w.project_id = p.id
                  WHERE w.user_id = %d";
        $params = array($user_id);
        
        if (!empty($status) && $status !== 'all') {
            $query .= " AND w.status = %s";
            $params[] = $status;
        }
        
        if (!empty($date_start)) {
            $query .= " AND w.work_date >= %s";
            $params[] = $date_start;
        }
        
        if (!empty($date_end)) {
            $query .= " AND w.work_date <= %s";
            $params[] = $date_end;
        }
        
        $query .= " ORDER BY w.work_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }

    /**
     * Inserta una nueva hoja de trabajo
     *
     * @since    1.0.0
     * @param    array     $data    Datos de la hoja de trabajo
     * @return   int|false          ID de la hoja insertada o false si hubo error
     */
    public function insert_worksheet($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_worksheets';
        
        $result = $wpdb->insert(
            $table_name,
            $data,
            null // Formato determinado por los tipos de datos
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Actualiza una hoja de trabajo existente
     *
     * @since    1.0.0
     * @param    int       $worksheet_id    ID de la hoja de trabajo
     * @param    array     $data            Datos a actualizar
     * @return   bool                       True si se actualizó correctamente, false en caso contrario
     */
    public function update_worksheet($worksheet_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_worksheets';
        
        return $wpdb->update(
            $table_name,
            $data,
            array('id' => $worksheet_id),
            null, // Formato determinado por los tipos de datos
            array('%d')
        ) !== false;
    }

    /**
     * Obtiene una hoja de trabajo específica
     *
     * @since    1.0.0
     * @param    int       $worksheet_id    ID de la hoja de trabajo
     * @return   array|null                 Datos de la hoja o null si no existe
     */
    public function get_worksheet($worksheet_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_worksheets';
        $projects_table = $wpdb->prefix . 'worker_projects';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT w.*, p.name as project_name, p.location as project_location
                FROM $table_name w
                LEFT JOIN $projects_table p ON w.project_id = p.id
                WHERE w.id = %d",
                $worksheet_id
            ),
            ARRAY_A
        );
    }

    /**
     * Obtiene los incentivos de un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id    ID del usuario
     * @param    int       $limit      Límite de resultados
     * @param    int       $offset     Desplazamiento para paginación
     * @param    string    $status     Estado de los incentivos (opcional)
     * @return   array                 Lista de incentivos
     */
    public function get_user_incentives($user_id, $limit = 10, $offset = 0, $status = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_incentives';
        
        $query = "SELECT * FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        if (!empty($status) && $status !== 'all') {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY calculation_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }

    /**
     * Inserta un nuevo incentivo
     *
     * @since    1.0.0
     * @param    array     $data    Datos del incentivo
     * @return   int|false          ID del incentivo insertado o false si hubo error
     */
    public function insert_incentive($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_incentives';
        
        $result = $wpdb->insert(
            $table_name,
            $data,
            null // Formato determinado por los tipos de datos
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Obtiene los proyectos activos
     *
     * @since    1.0.0
     * @param    bool      $active_only    Solo proyectos activos
     * @param    int       $limit          Límite de resultados
     * @param    int       $offset         Desplazamiento para paginación
     * @return   array                     Lista de proyectos
     */
    public function get_projects($active_only = true, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_projects';
        
        $query = "SELECT * FROM $table_name";
        $params = array();
        
        if ($active_only) {
            $query .= " WHERE status = 'active'";
        }
        
        $query .= " ORDER BY name ASC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }

    /**
     * Inserta un nuevo proyecto
     *
     * @since    1.0.0
     * @param    array     $data    Datos del proyecto
     * @return   int|false          ID del proyecto insertado o false si hubo error
     */
    public function insert_project($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_projects';
        
        $result = $wpdb->insert(
            $table_name,
            $data,
            null // Formato determinado por los tipos de datos
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Actualiza un proyecto existente
     *
     * @since    1.0.0
     * @param    int       $project_id    ID del proyecto
     * @param    array     $data          Datos a actualizar
     * @return   bool                     True si se actualizó correctamente, false en caso contrario
     */
    public function update_project($project_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_projects';
        
        return $wpdb->update(
            $table_name,
            $data,
            array('id' => $project_id),
            null, // Formato determinado por los tipos de datos
            array('%d')
        ) !== false;
    }

    /**
     * Obtiene un proyecto específico
     *
     * @since    1.0.0
     * @param    int       $project_id    ID del proyecto
     * @return   array|null               Datos del proyecto o null si no existe
     */
    public function get_project($project_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_projects';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $project_id
            ),
            ARRAY_A
        );
    }

    /**
     * Obtiene el total de elementos de una consulta
     *
     * @since    1.0.0
     * @param    string    $table     Nombre de la tabla
     * @param    string    $where     Condición WHERE (sin la palabra WHERE)
     * @param    array     $params    Parámetros para la consulta preparada
     * @return   int                  Número total de elementos
     */
    public function get_total_items($table, $where = '', $params = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $table;
        
        $query = "SELECT COUNT(*) FROM $table_name";
        
        if (!empty($where)) {
            $query .= " WHERE $where";
        }
        
        if (empty($params)) {
            return $wpdb->get_var($query);
        } else {
            return $wpdb->get_var($wpdb->prepare($query, $params));
        }
    }
}