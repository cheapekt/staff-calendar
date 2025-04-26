<?php
/**
 * Se ejecuta durante la activación del plugin
 *
 * @since      1.0.0
 */
class Worker_Portal_Activator {

    /**
     * Método principal de activación
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Crear tablas en la base de datos
        self::create_database_tables();
        
        // Crear roles y capacidades
        self::create_roles_and_capabilities();
        
        // Crear opciones por defecto
        self::create_default_options();
        
        // Limpiar la caché de reescritura
        flush_rewrite_rules();
    }
    
    /**
     * Crea las tablas necesarias en la base de datos
     *
     * @since    1.0.0
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla de documentos
        $table_documents = $wpdb->prefix . 'worker_documents';
        $sql_documents = "CREATE TABLE IF NOT EXISTS $table_documents (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            description text DEFAULT NULL,
            file_path varchar(255) NOT NULL,
            category varchar(50) DEFAULT NULL,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Tabla de gastos
        $table_expenses = $wpdb->prefix . 'worker_expenses';
        $sql_expenses = "CREATE TABLE IF NOT EXISTS $table_expenses (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            report_date datetime DEFAULT CURRENT_TIMESTAMP,
            expense_date date NOT NULL,
            expense_type varchar(50) NOT NULL,
            description text NOT NULL,
            amount decimal(10,2) NOT NULL,
            has_receipt tinyint(1) DEFAULT 0,
            receipt_path varchar(255) DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            approved_by bigint(20) DEFAULT NULL,
            approved_date datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Tabla de hojas de trabajo
        $table_worksheets = $wpdb->prefix . 'worker_worksheets';
        $sql_worksheets = "CREATE TABLE IF NOT EXISTS $table_worksheets (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            work_date date NOT NULL,
            project_id bigint(20) NOT NULL,
            difficulty varchar(20) DEFAULT 'normal',
            system_type varchar(100) DEFAULT NULL,
            unit_type varchar(20) DEFAULT NULL,
            quantity decimal(10,2) DEFAULT 0,
            hours decimal(5,2) DEFAULT 0,
            notes text DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            validated_by bigint(20) DEFAULT NULL,
            validated_date datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY project_id (project_id),
            KEY work_date (work_date),
            KEY status (status)
        ) $charset_collate;";
        
        // Tabla de incentivos
        $table_incentives = $wpdb->prefix . 'worker_incentives';
        $sql_incentives = "CREATE TABLE IF NOT EXISTS $table_incentives (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            worksheet_id bigint(20) DEFAULT NULL,
            calculation_date datetime DEFAULT CURRENT_TIMESTAMP,
            description text NOT NULL,
            amount decimal(10,2) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            approved_by bigint(20) DEFAULT NULL,
            approved_date datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY worksheet_id (worksheet_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Tabla de proyectos
        $table_projects = $wpdb->prefix . 'worker_projects';
        $sql_projects = "CREATE TABLE IF NOT EXISTS $table_projects (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            location varchar(255) DEFAULT NULL,
            start_date date DEFAULT NULL,
            end_date date DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charset_collate;";
        
        // Ejecutar las consultas SQL
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_documents);
        dbDelta($sql_expenses);
        dbDelta($sql_worksheets);
        dbDelta($sql_incentives);
        dbDelta($sql_projects);
    }
    
    /**
     * Crea roles y capacidades
     *
     * @since    1.0.0
     */
    private static function create_roles_and_capabilities() {
        // Capacidades para el portal del trabajador
        $capabilities = array(
            'wp_worker_view_own_documents' => __('Ver documentos propios', 'worker-portal'),
            'wp_worker_upload_documents' => __('Subir documentos', 'worker-portal'),
            'wp_worker_manage_expenses' => __('Gestionar gastos', 'worker-portal'),
            'wp_worker_approve_expenses' => __('Aprobar gastos', 'worker-portal'),
            'wp_worker_manage_worksheets' => __('Gestionar hojas de trabajo', 'worker-portal'),
            'wp_worker_validate_worksheets' => __('Validar hojas de trabajo', 'worker-portal'),
            'wp_worker_view_incentives' => __('Ver incentivos', 'worker-portal'),
            'wp_worker_manage_incentives' => __('Gestionar incentivos', 'worker-portal')
        );
        
        // Actualizar rol de administrador
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            foreach ($capabilities as $cap => $label) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Crear rol de Supervisor
        if (!get_role('supervisor')) {
            add_role(
                'supervisor', 
                __('Supervisor', 'worker-portal'),
                array(
                    'read' => true,
                    'wp_worker_view_own_documents' => true,
                    'wp_worker_upload_documents' => true,
                    'wp_worker_manage_expenses' => true,
                    'wp_worker_approve_expenses' => true,
                    'wp_worker_manage_worksheets' => true,
                    'wp_worker_validate_worksheets' => true,
                    'wp_worker_view_incentives' => true
                )
            );
        }
        
        // Actualizar rol de suscriptor para empleados normales
        $subscriber_role = get_role('subscriber');
        
        if ($subscriber_role) {
            $subscriber_role->add_cap('wp_worker_view_own_documents');
            $subscriber_role->add_cap('wp_worker_manage_expenses');
            $subscriber_role->add_cap('wp_worker_manage_worksheets');
            $subscriber_role->add_cap('wp_worker_view_incentives');
        }
    }
    
    /**
     * Crea las opciones por defecto
     *
     * @since    1.0.0
     */
    private static function create_default_options() {
        // Módulos activos (por defecto, todos)
        if (!get_option('worker_portal_active_modules')) {
            update_option('worker_portal_active_modules', array(
                'documents',
                'expenses',
                'worksheets',
                'incentives'
            ));
        }
        
        // Tipos de gastos por defecto
        if (!get_option('worker_portal_expense_types')) {
            update_option('worker_portal_expense_types', array(
                'km' => __('Kilometraje', 'worker-portal'),
                'hours' => __('Horas de desplazamiento', 'worker-portal'),
                'meal' => __('Dietas', 'worker-portal'),
                'other' => __('Otros', 'worker-portal')
            ));
        }
        
        // Email de notificación por defecto
        if (!get_option('worker_portal_expense_notification_email')) {
            update_option('worker_portal_expense_notification_email', get_option('admin_email'));
        }
        
        // Tipos de sistemas para hojas de trabajo
        if (!get_option('worker_portal_system_types')) {
            update_option('worker_portal_system_types', array(
                'estructura_techo' => __('Estructura en techo continuo de PYL', 'worker-portal'),
                'estructura_tabique' => __('Estructura en tabique o trasdosado', 'worker-portal'),
                'aplacado_simple' => __('Aplacado 1 placa en tabique/trasdosado', 'worker-portal'),
                'aplacado_doble' => __('Aplacado 2 placas en tabique/trasdosado', 'worker-portal'),
                'horas_ayuda' => __('Horas de ayudas, descargas, etc.', 'worker-portal')
            ));
        }
        
        // Tipos de unidades para hojas de trabajo
        if (!get_option('worker_portal_unit_types')) {
            update_option('worker_portal_unit_types', array(
                'm2' => __('Metros cuadrados', 'worker-portal'),
                'h' => __('Horas', 'worker-portal')
            ));
        }
        
        // Niveles de dificultad para hojas de trabajo
        if (!get_option('worker_portal_difficulty_levels')) {
            update_option('worker_portal_difficulty_levels', array(
                'baja' => __('Baja', 'worker-portal'),
                'media' => __('Media', 'worker-portal'),
                'alta' => __('Alta', 'worker-portal')
            ));
        }
        
        // Crear las páginas necesarias si no existen
        if (!get_page_by_path('portal-del-trabajador')) {
            // Crear página para el portal
            $portal_page = array(
                'post_title'    => __('Portal del Trabajador', 'worker-portal'),
                'post_content'  => '[worker_portal]',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_name'     => 'portal-del-trabajador'
            );
            
            wp_insert_post($portal_page);
        }
    }
}