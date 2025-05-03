<?php
/**
 * Se activa durante la activación del plugin
 *
 * @since      1.0.0
 */
class WP_Time_Clock_Activator {

    /**
     * Método ejecutado durante la activación del plugin
     *
     * Crea las tablas necesarias en la base de datos y configura valores iniciales
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla de entradas de fichaje
        $table_entries = $wpdb->prefix . 'time_clock_entries';
        $sql_entries = "CREATE TABLE IF NOT EXISTS $table_entries (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            clock_in datetime NOT NULL,
            clock_out datetime DEFAULT NULL,
            clock_in_location text DEFAULT NULL,
            clock_out_location text DEFAULT NULL,
            clock_in_note text DEFAULT NULL,
            clock_out_note text DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'active',
            edited_by bigint(20) DEFAULT NULL,
            edited_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            admin_note text DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Tabla de configuraciones
        $table_settings = $wpdb->prefix . 'time_clock_settings';
        $sql_settings = "CREATE TABLE IF NOT EXISTS $table_settings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            option_name varchar(191) NOT NULL,
            option_value text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY option_name (option_name)
        ) $charset_collate;";
        
        // Crear las tablas
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_entries);
        dbDelta($sql_settings);
        
        // Verificar si las tablas se crearon correctamente
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_entries'") != $table_entries) {
            wp_die('No se pudo crear la tabla de fichajes. Por favor, comprueba los permisos de la base de datos.');
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_settings'") != $table_settings) {
            wp_die('No se pudo crear la tabla de configuraciones. Por favor, comprueba los permisos de la base de datos.');
        }
        
        // Insertar configuraciones por defecto
        self::set_default_settings($table_settings);
        
        // Crear capacidades (capabilities) para los roles
        self::add_capabilities();
        
        // Guardar versión en la base de datos para futuras actualizaciones
        update_option('wp_time_clock_version', WP_TIME_CLOCK_VERSION);
        
        // Vaciar caché de reescritura
        flush_rewrite_rules();
    }
    
    /**
     * Configura valores por defecto en la tabla de configuraciones
     *
     * @since    1.0.0
     * @param    string    $table_name    Nombre de la tabla de configuraciones
     */
    private static function set_default_settings($table_name) {
        global $wpdb;
        
        $default_settings = array(
            'working_hours_per_day' => '8',
            'allow_manual_entry' => 'yes',
            'require_approval' => 'no',
            'geolocation_enabled' => 'yes',
            'clock_button_style' => 'default',
            'notification_emails' => get_option('admin_email'),
            'allow_clock_note' => 'yes',
            'display_clock_time' => 'yes',
            'enable_breaks' => 'no',
            'auto_clock_out' => 'no',
            'auto_clock_out_time' => '23:59:59',
            'weekend_days' => '0,6', // 0 (domingo) y 6 (sábado)
            'workday_start' => '09:00:00',
            'workday_end' => '18:00:00'
        );
        
        foreach ($default_settings as $option => $value) {
            $wpdb->replace(
                $table_name,
                array(
                    'option_name' => $option,
                    'option_value' => $value
                ),
                array('%s', '%s')
            );
        }
    }
    
    /**
     * Añade capacidades (capabilities) a los roles de WordPress
     *
     * @since    1.0.0
     */
    private static function add_capabilities() {
        // Capacidad para fichar (todos los usuarios registrados pueden fichar)
        $subscriber = get_role('subscriber');
        if ($subscriber) {
            $subscriber->add_cap('time_clock_clock_in_out');
        }
        
        // Crear capacidades para editores (pueden ver fichajes pero no editar)
        $editor = get_role('editor');
        if ($editor) {
            $editor->add_cap('time_clock_clock_in_out');
            $editor->add_cap('time_clock_view_own');
            $editor->add_cap('time_clock_view_reports');
        }
        
        // Capacidades para administradores
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('time_clock_clock_in_out');
            $admin->add_cap('time_clock_view_own');
            $admin->add_cap('time_clock_view_reports');
            $admin->add_cap('time_clock_edit_entries');
            $admin->add_cap('time_clock_manage_settings');
            $admin->add_cap('time_clock_export_data');
            $admin->add_cap('time_clock_view_all');
        }
    }
}
