<?php
/**
 * Plugin Name: Staff Calendar
 * Description: Calendario laboral para gestionar destinos de trabajo
 * Version: 1.1.3
 * Author: Carlos Reyes
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Clase principal del plugin
class StaffCalendar {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hooks de activación
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Acciones para el frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendScripts'));
        add_shortcode('staff_calendar', array($this, 'calendarShortcode'));
        
        // Ajax handlers
        add_action('wp_ajax_update_staff_destination_range', array($this, 'updateStaffDestinationRange'));
        add_action('wp_ajax_get_calendar_data', array($this, 'getCalendarData'));
        add_action('wp_ajax_nopriv_get_calendar_data', array($this, 'getCalendarData'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'staff_calendar';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            work_date date NOT NULL,
            destination varchar(255) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_date (user_id, work_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueueFrontendScripts() {
        $version = '1.0.' . time();
        
        wp_enqueue_style('staff-calendar-frontend', plugins_url('css/frontend-style.css', __FILE__), array(), $version);
        wp_enqueue_script('jquery');
        wp_enqueue_script('staff-calendar-frontend', plugins_url('js/frontend-script.js', __FILE__), array('jquery'), $version, true);
        
        $calendar_config = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('staff_calendar_nonce'),
            'isAdmin' => current_user_can('manage_options')
        );
        
        wp_localize_script('staff-calendar-frontend', 'staffCalendarConfig', $calendar_config);
    }

    public function updateStaffDestinationRange() {
        check_ajax_referer('staff_calendar_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_calendar';
        
        $user_id = intval($_POST['user_id']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $destination = sanitize_text_field($_POST['destination']);

        // Crear array de fechas entre start_date y end_date
        $current_date = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day');
        $interval = new DateInterval('P1D');
        $date_range = new DatePeriod($current_date, $interval, $end);

        $success = true;
        
        foreach ($date_range as $date) {
            $formatted_date = $date->format('Y-m-d');
            
            $result = $wpdb->replace(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'work_date' => $formatted_date,
                    'destination' => $destination
                ),
                array('%d', '%s', '%s')
            );

            if ($result === false) {
                $success = false;
                break;
            }
        }

        if ($success) {
            wp_send_json_success('Destinos actualizados correctamente');
        } else {
            wp_send_json_error('Error al actualizar los destinos: ' . $wpdb->last_error);
        }
    }

    public function getCalendarData() {
        check_ajax_referer('staff_calendar_nonce', 'nonce');
        
        $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'staff_calendar';
        
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, work_date, destination 
            FROM $table_name 
            WHERE work_date BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));
        
        wp_send_json_success($data);
    }

    public function calendarShortcode($atts) {
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/frontend-view.php';
        return ob_get_clean();
    }
}

// Inicializar el plugin
StaffCalendar::getInstance();