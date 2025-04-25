<?php
/**
 * Funcionalidad específica de administración del plugin
 *
 * @since      1.0.0
 */
class WP_Time_Clock_Admin {

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
     * Registra los estilos para el área de administración
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Solo cargar en las páginas de nuestro plugin
        $screen = get_current_screen();
        
        if (strpos($screen->id, 'wp-time-clock') !== false) {
            wp_enqueue_style(
                $this->plugin_name . '-admin',
                WP_TIME_CLOCK_URL . 'admin/css/admin-style.css',
                array(),
                $this->version,
                'all'
            );
        }
    }

    /**
     * Registra los scripts para el área de administración
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Solo cargar en las páginas de nuestro plugin
        $screen = get_current_screen();
        
        if (strpos($screen->id, 'wp-time-clock') !== false) {
            wp_enqueue_script(
                $this->plugin_name . '-admin',
                WP_TIME_CLOCK_URL . 'admin/js/admin-script.js',
                array('jquery'),
                $this->version,
                true
            );
            
            // Pasar variables a JavaScript
            wp_localize_script(
                $this->plugin_name . '-admin',
                'wpTimeClockAdmin',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'rest_url' => rest_url($this->plugin_name . '/v1'),
                    'nonce' => wp_create_nonce('wp_rest'),
                    'rest_nonce' => wp_create_nonce('wp_rest'),
                    'i18n' => array(
                        'error' => __('Error', 'wp-time-clock'),
                        'success' => __('Éxito', 'wp-time-clock'),
                        'confirm_delete' => __('¿Estás seguro de que deseas eliminar esta entrada?', 'wp-time-clock'),
                        'confirm_reset' => __('¿Estás seguro de que deseas restablecer esta configuración?', 'wp-time-clock'),
                        'loading' => __('Cargando...', 'wp-time-clock'),
                        'save_changes' => __('Guardar cambios', 'wp-time-clock'),
                        'cancel' => __('Cancelar', 'wp-time-clock'),
                        'edit' => __('Editar', 'wp-time-clock'),
                        'delete' => __('Eliminar', 'wp-time-clock')
                    )
                )
            );
        }
    }

    /**
     * Añade páginas de menú en el área de administración
     *
     * @since    1.0.0
     */
    public function add_menu_pages() {
        // Menú principal
        add_menu_page(
            __('Sistema de Fichajes', 'wp-time-clock'),
            __('Fichajes', 'wp-time-clock'),
            'manage_options',
            'wp-time-clock',
            array($this, 'display_dashboard_page'),
            'dashicons-clock',
            30
        );
        
        // Submenú: Dashboard
        add_submenu_page(
            'wp-time-clock',
            __('Panel de Control - Fichajes', 'wp-time-clock'),
            __('Panel de Control', 'wp-time-clock'),
            'manage_options',
            'wp-time-clock',
            array($this, 'display_dashboard_page')
        );
        
        // Submenú: Todas las entradas
        add_submenu_page(
            'wp-time-clock',
            __('Todas las Entradas - Fichajes', 'wp-time-clock'),
            __('Todas las Entradas', 'wp-time-clock'),
            'manage_options',
            'wp-time-clock-entries',
            array($this, 'display_entries_page')
        );
        
        // Submenú: Informes
        add_submenu_page(
            'wp-time-clock',
            __('Informes - Fichajes', 'wp-time-clock'),
            __('Informes', 'wp-time-clock'),
            'manage_options',
            'wp-time-clock-reports',
            array($this, 'display_reports_page')
        );
        
        // Submenú: Configuración
        add_submenu_page(
            'wp-time-clock',
            __('Configuración - Fichajes', 'wp-time-clock'),
            __('Configuración', 'wp-time-clock'),
            'manage_options',
            'wp-time-clock-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Añade enlaces de acción en la página de plugins
     *
     * @since    1.0.0
     * @param    array    $links    Enlaces existentes
     * @return   array              Enlaces modificados
     */
    public function add_plugin_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wp-time-clock-settings') . '">' . __('Configuración', 'wp-time-clock') . '</a>'
        );
        
        return array_merge($plugin_links, $links);
    }

    /**
     * Añade widgets al dashboard de WordPress
     *
     * @since    1.0.0
     */
    public function add_dashboard_widgets() {
        if (current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'wp_time_clock_status_widget',
                __('Estado de Fichajes', 'wp-time-clock'),
                array($this, 'render_dashboard_widget')
            );
        }
    }

    /**
     * Renderiza el widget del dashboard
     *
     * @since    1.0.0
     */
    public function render_dashboard_widget() {
        echo '<div class="wp-time-clock-widget">';
        echo '<p>' . __('Estado actual de los fichajes de los usuarios:', 'wp-time-clock') . '</p>';
        
        // Obtener usuarios activos
        $active_users = $this->get_active_users();
        
        if (empty($active_users)) {
            echo '<p class="wp-time-clock-no-data">' . __('No hay usuarios con fichajes activos.', 'wp-time-clock') . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped wp-time-clock-table">';
            echo '<thead><tr>';
            echo '<th>' . __('Usuario', 'wp-time-clock') . '</th>';
            echo '<th>' . __('Estado', 'wp-time-clock') . '</th>';
            echo '<th>' . __('Tiempo', 'wp-time-clock') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($active_users as $user) {
                echo '<tr>';
                echo '<td>' . esc_html($user['name']) . '</td>';
                echo '<td>' . esc_html($user['status']) . '</td>';
                echo '<td>' . esc_html($user['time']) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        }
        
        echo '<p class="wp-time-clock-widget-footer">';
        echo '<a href="' . admin_url('admin.php?page=wp-time-clock') . '">' . __('Ver panel completo', 'wp-time-clock') . ' &rarr;</a>';
        echo '</p>';
        echo '</div>';
    }

    /**
     * Obtiene usuarios con fichajes activos
     *
     * @since    1.0.0
     * @return   array    Información de usuarios activos
     */
    private function get_active_users() {
        global $wpdb;
        
        $table_entries = $wpdb->prefix . 'time_clock_entries';
        
        $results = $wpdb->get_results(
            "SELECT e.user_id, e.clock_in, u.display_name 
            FROM {$table_entries} e
            JOIN {$wpdb->users} u ON e.user_id = u.ID
            WHERE e.clock_out IS NULL
            ORDER BY e.clock_in DESC",
            ARRAY_A
        );
        
        $active_users = array();
        $clock_manager = new WP_Time_Clock_Manager();
        
        foreach ($results as $row) {
            $start_time = strtotime($row['clock_in']);
            $current_time = time();
            $elapsed = $current_time - $start_time;
            
            $active_users[] = array(
                'id' => $row['user_id'],
                'name' => $row['display_name'],
                'status' => __('Trabajando', 'wp-time-clock'),
                'time' => $clock_manager->format_time_worked($elapsed)
            );
        }
        
        return $active_users;
    }

    /**
     * Muestra la página del panel de control
     *
     * @since    1.0.0
     */
    public function display_dashboard_page() {
        include WP_TIME_CLOCK_PATH . 'admin/partials/dashboard-display.php';
    }

    /**
     * Muestra la página de todas las entradas
     *
     * @since    1.0.0
     */
    public function display_entries_page() {
        include WP_TIME_CLOCK_PATH . 'admin/partials/entries-display.php';
    }

    /**
     * Muestra la página de informes
     *
     * @since    1.0.0
     */
    public function display_reports_page() {
        include WP_TIME_CLOCK_PATH . 'admin/partials/reports-display.php';
    }

    /**
     * Muestra la página de configuración
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        include WP_TIME_CLOCK_PATH . 'admin/partials/settings-display.php';
    }
}
