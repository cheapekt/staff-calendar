<?php
/**
 * Funciones utilitarias para el Portal del Trabajador
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

class Worker_Portal_Utils {

    /**
     * Verifica si un usuario es administrador o supervisor del portal
     *
     * @since    1.0.0
     * @param    int    $user_id    ID del usuario (opcional, usa current user si no se proporciona)
     * @return   bool               True si es admin/supervisor, false en caso contrario
     */
    public static function is_portal_admin($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return false;
            }
        }
        
        // Si es administrador de WordPress
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Si tiene capacidades específicas de supervisor
        if (user_can($user_id, 'wp_worker_approve_expenses') || 
            user_can($user_id, 'wp_worker_validate_worksheets')) {
            return true;
        }
        
        // Si está en la lista de aprobadores de gastos
        $expense_approvers = get_option('worker_portal_expense_approvers', array());
        if (in_array($user_id, $expense_approvers)) {
            return true;
        }
        
        return false;
    }

    /**
     * Obtiene los usuarios supervisados por un supervisor
     *
     * @since    1.0.0
     * @param    int      $supervisor_id    ID del supervisor (opcional, usa current user si no se proporciona)
     * @return   array                      Lista de IDs de usuarios supervisados
     */
    public static function get_supervised_users($supervisor_id = 0) {
        if (!$supervisor_id) {
            $supervisor_id = get_current_user_id();
            if (!$supervisor_id) {
                return array();
            }
        }
        
        // Si no es supervisor, devolver array vacío
        if (!self::is_portal_admin($supervisor_id)) {
            return array();
        }
        
        // Si es administrador, devolver todos los usuarios excepto admins
        if (user_can($supervisor_id, 'manage_options')) {
            $users = get_users(array(
                'role__not_in' => array('administrator'),
                'fields' => 'ID'
            ));
            return $users;
        }
        
        // Para otros supervisores, implementar lógica de asignación
        // Por ahora, devolver todos los usuarios no admin
        // Esta lógica puede personalizarse según necesidades específicas
        $users = get_users(array(
            'role__not_in' => array('administrator'),
            'fields' => 'ID'
        ));
        
        return $users;
    }

    /**
     * Obtiene los proyectos disponibles para un usuario
     *
     * @since    1.0.0
     * @param    int      $user_id    ID del usuario (opcional, usa current user si no se proporciona)
     * @param    bool     $active     Obtener solo proyectos activos
     * @return   array                Lista de proyectos
     */
    public static function get_user_projects($user_id = 0, $active = true) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return array();
            }
        }
        
        $project_table = $wpdb->prefix . 'worker_projects';
        
        // Si es administrador o supervisor, puede ver todos los proyectos
        if (self::is_portal_admin($user_id)) {
            $query = "SELECT * FROM $project_table";
            if ($active) {
                $query .= " WHERE status = 'active'";
            }
            $query .= " ORDER BY name ASC";
            
            return $wpdb->get_results($query, ARRAY_A);
        }
        
        // Para trabajadores normales, solo ver proyectos asignados
        // Esta lógica puede personalizarse según tus necesidades
        $query = "SELECT * FROM $project_table";
        if ($active) {
            $query .= " WHERE status = 'active'";
        }
        $query .= " ORDER BY name ASC";
        
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Formatea un importe con unidad según tipo de gasto
     *
     * @since    1.0.0
     * @param    float     $amount        Cantidad
     * @param    string    $expense_type  Tipo de gasto (km, hours, etc.)
     * @param    bool      $format        Si debe formatearse la cantidad (con separadores)
     * @return   string                   Cantidad formateada
     */
    public static function format_expense_amount($amount, $expense_type, $format = true) {
        switch ($expense_type) {
            case 'km':
                return ($format ? number_format($amount, 0, ',', '.') : $amount) . ' Km';
            case 'hours':
                return ($format ? number_format($amount, 1, ',', '.') : $amount) . ' ' . __('Horas', 'worker-portal');
            default:
                return ($format ? number_format($amount, 2, ',', '.') : $amount) . ' €';
        }
    }

    /**
     * Obtiene el nombre del estado de un gasto
     *
     * @since    1.0.0
     * @param    string    $status    Estado del gasto (pending, approved, rejected)
     * @return   string               Nombre localizado del estado
     */
    public static function get_expense_status_name($status) {
        switch ($status) {
            case 'pending':
                return __('Pendiente', 'worker-portal');
            case 'approved':
                return __('Aprobado', 'worker-portal');
            case 'rejected':
                return __('Denegado', 'worker-portal');
            default:
                return $status;
        }
    }

    /**
     * Obtiene la clase CSS para un estado de gasto
     *
     * @since    1.0.0
     * @param    string    $status    Estado del gasto (pending, approved, rejected)
     * @return   string               Clase CSS
     */
    public static function get_expense_status_class($status) {
        switch ($status) {
            case 'pending':
                return 'worker-portal-badge worker-portal-badge-warning';
            case 'approved':
                return 'worker-portal-badge worker-portal-badge-success';
            case 'rejected':
                return 'worker-portal-badge worker-portal-badge-danger';
            default:
                return 'worker-portal-badge worker-portal-badge-secondary';
        }
    }
}