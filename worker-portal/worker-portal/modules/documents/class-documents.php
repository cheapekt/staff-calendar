<?php
/**
 * Módulo de Documentos
 *
 * @since      1.0.0
 */
class Worker_Portal_Module_Documents {

    /**
     * Opciones por defecto del módulo
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $default_options    Opciones por defecto
     */
    private $default_options = array();

    /**
     * Constructor
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Inicializar opciones por defecto
        $this->init_default_options();
    }

    /**
     * Inicializa las opciones por defecto
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_default_options() {
        $this->default_options = array(
            'document_categories' => array(
                'payroll' => 'Nóminas',
                'contract' => 'Contratos',
                'communication' => 'Comunicaciones',
                'other' => 'Otros'
            ),
            'document_notification_email' => get_option('admin_email')
        );
    }

    /**
     * Inicializa el módulo
     *
     * @since    1.0.0
     */
    public function init() {
        // Registrar hooks para frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Registrar shortcodes
        add_shortcode('worker_documents', array($this, 'render_documents_shortcode'));

        // Registrar endpoints de REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Registrar AJAX actions (ahora se maneja en documents-ajax-handler.php)
        require_once WORKER_PORTAL_PATH . 'modules/documents/documents-ajax-handler.php';
        new Worker_Portal_Document_Ajax_Handler();
    }

    /**
     * Carga los scripts y estilos necesarios
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Cargar estilos
        wp_enqueue_style(
            'worker-portal-documents',
            WORKER_PORTAL_URL . 'modules/documents/css/documents.css',
            array(),
            WORKER_PORTAL_VERSION,
            'all'
        );
        
        // Cargar scripts
        wp_enqueue_script(
            'worker-portal-documents',
            WORKER_PORTAL_URL . 'modules/documents/js/documents.js',
            array('jquery'),
            WORKER_PORTAL_VERSION,
            true
        );
        
        // Localizar script con variables necesarias
        wp_localize_script(
            'worker-portal-documents',
            'workerPortalDocuments',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('worker_portal_documents_nonce'),
                'common_nonce' => wp_create_nonce('worker_portal_ajax_nonce'),
                'is_admin' => Worker_Portal_Utils::is_portal_admin() ? 'true' : 'false',
                'i18n' => array(
                    'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'),
                    'success' => __('Operación completada con éxito.', 'worker-portal'),
                    'loading' => __('Cargando...', 'worker-portal'),
                    'no_documents' => __('No hay documentos disponibles.', 'worker-portal'),
                    'confirm_delete' => __('¿Estás seguro de eliminar este documento? Esta acción no se puede deshacer.', 'worker-portal')
                ),
                'debug' => WP_DEBUG
            )
        );
    }

    /**
     * Renderiza el shortcode para mostrar los documentos del usuario
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            HTML generado
     */
    public function render_documents_shortcode($atts) {
        // Si el usuario no está logueado, mostrar mensaje de error
        if (!is_user_logged_in()) {
            return '<div class="worker-portal-error">' . __('Debes iniciar sesión para ver tus documentos.', 'worker-portal') . '</div>';
        }
        
        // Atributos por defecto
        $atts = shortcode_atts(
            array(
                'limit' => 10,     // Número de documentos a mostrar
                'category' => ''   // Categoría de documentos a mostrar
            ),
            $atts,
            'worker_documents'
        );
        
        // Obtener el usuario actual
        $user_id = get_current_user_id();
        
        // Verificar si es administrador para mostrar vista diferente
        $is_admin = Worker_Portal_Utils::is_portal_admin();
        
        // Obtener los documentos del usuario si no es admin
        $documents = array();
        if (!$is_admin) {
            $documents = $this->get_user_documents($user_id, $atts['limit'], 0, $atts['category']);
        }
        
        // Obtener las categorías disponibles
        $categories = get_option('worker_portal_document_categories', $this->default_options['document_categories']);
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir plantilla según el rol
        if ($is_admin) {
            include(WORKER_PORTAL_PATH . 'modules/documents/templates/documents-admin-view.php');
        } else {
            include(WORKER_PORTAL_PATH . 'modules/documents/templates/documents-view.php');
        }
        
        // Retornar el contenido
        return ob_get_clean();
    }

    /**
     * Obtiene los documentos de un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id     ID del usuario
     * @param    int       $limit       Número máximo de documentos a obtener
     * @param    int       $offset      Desplazamiento para paginación
     * @param    string    $category    Categoría de documentos (opcional)
     * @return   array                  Lista de documentos
     */
    public function get_user_documents($user_id, $limit = 10, $offset = 0, $category = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_documents';
        
        $query = "SELECT * FROM $table_name WHERE user_id = %d AND status = 'active'";
        $params = array($user_id);
        
        if (!empty($category)) {
            $query .= " AND category = %s";
            $params[] = $category;
        }
        
        $query .= " ORDER BY upload_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        $documents = $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
        
        return $documents;
    }

    /**
     * Obtiene documentos filtrados de un usuario o todos los usuarios (admin)
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario (0 para todos)
     * @param    string    $category     Categoría de documentos
     * @param    string    $search       Término de búsqueda
     * @param    string    $date_from    Fecha de inicio
     * @param    string    $date_to      Fecha de fin
     * @param    int       $limit        Límite de resultados
     * @param    int       $offset       Offset para paginación
     * @return   array                   Lista de documentos
     */
    public function get_filtered_documents($user_id, $category = '', $search = '', $date_from = '', $date_to = '', $limit = 10, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_documents';
        $users_table = $wpdb->users;
        
        $query = "SELECT d.*, u.display_name 
                 FROM $table_name d 
                 LEFT JOIN $users_table u ON d.user_id = u.ID 
                 WHERE d.status = 'active'";
        $params = array();
        
        // Filtrar por usuario específico si no es admin o si el admin filtró por usuario
        if ($user_id > 0) {
            $query .= " AND d.user_id = %d";
            $params[] = $user_id;
        }
        
        // Aplicar filtros
        if (!empty($category)) {
            $query .= " AND d.category = %s";
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $query .= " AND (d.title LIKE %s OR d.description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($date_from)) {
            $query .= " AND d.upload_date >= %s";
            $params[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $query .= " AND d.upload_date <= %s";
            $params[] = $date_to . ' 23:59:59';
        }
        
        // Ordenar y limitar resultados
        $query .= " ORDER BY d.upload_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }

    /**
     * Obtiene el total de documentos filtrados
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario (0 para todos)
     * @param    string    $category     Categoría de documentos
     * @param    string    $search       Término de búsqueda
     * @param    string    $date_from    Fecha de inicio
     * @param    string    $date_to      Fecha de fin
     * @return   int                     Total de documentos
     */
    public function get_total_filtered_documents($user_id, $category = '', $search = '', $date_from = '', $date_to = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_documents';
        $users_table = $wpdb->users;
        
        $query = "SELECT COUNT(*) FROM $table_name d LEFT JOIN $users_table u ON d.user_id = u.ID WHERE d.status = 'active'";
        $params = array();
        
        // Filtrar por usuario específico si no es admin o si el admin filtró por usuario
        if ($user_id > 0) {
            $query .= " AND d.user_id = %d";
            $params[] = $user_id;
        }
        
        // Aplicar filtros
        if (!empty($category)) {
            $query .= " AND d.category = %s";
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $query .= " AND (d.title LIKE %s OR d.description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($date_from)) {
            $query .= " AND d.upload_date >= %s";
            $params[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $query .= " AND d.upload_date <= %s";
            $params[] = $date_to . ' 23:59:59';
        }
        
        return $wpdb->get_var($wpdb->prepare($query, $params));
    }

    /**
     * Obtiene los detalles de un documento
     *
     * @since    1.0.0
     * @param    int       $document_id    ID del documento
     * @return   array|null               Detalles del documento o null si no existe
     */
    public function get_document_details($document_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_documents';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $document_id
            ),
            ARRAY_A
        );
    }

    /**
     * Sube un archivo de documento al servidor
     *
     * @since    1.0.0
     * @param    array     $file     Datos del archivo
     * @return   array|WP_Error     Información del archivo subido o error
     */
    public function upload_document_file($file) {
        // Crear directorio de subida si no existe
        $upload_dir = wp_upload_dir();
        $documents_dir = 'worker-portal/documents/' . date('Y/m');
        $full_path = $upload_dir['basedir'] . '/' . $documents_dir;
        
        if (!file_exists($full_path)) {
            wp_mkdir_p($full_path);
            
            // Añadir archivos de protección
            file_put_contents($full_path . '/index.php', '<?php // Silence is golden');
            file_put_contents($upload_dir['basedir'] . '/worker-portal/documents/.htaccess', 'Deny from all');
        }
        
        // Generar nombre único para el archivo
        $filename = wp_unique_filename($full_path, $file['name']);
        $new_file = $full_path . '/' . $filename;
        
        // Mover el archivo
        if (!move_uploaded_file($file['tmp_name'], $new_file)) {
            return new WP_Error('upload_error', __('Error al subir el archivo al servidor', 'worker-portal'));
        }
        
        // Devolver información del archivo
        return array(
            'file_path' => $documents_dir . '/' . $filename,
            'file_name' => $filename,
            'file_type' => $file['type'],
            'file_size' => $file['size']
        );
    }

    /**
     * Guarda un documento en la base de datos
     *
     * @since    1.0.0
     * @param    int       $user_id       ID del usuario
     * @param    string    $title         Título del documento
     * @param    string    $description   Descripción del documento
     * @param    string    $category      Categoría del documento
     * @param    string    $file_path     Ruta del archivo
     * @return   int|false                ID del documento insertado o false si hubo error
     */
    public function save_document($user_id, $title, $description, $category, $file_path) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_documents';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'title' => $title,
                'description' => $description,
                'file_path' => $file_path,
                'category' => $category,
                'upload_date' => current_time('mysql'),
                'status' => 'active'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Envía una notificación al usuario sobre un nuevo documento
     *
     * @since    1.0.0
     * @param    int       $document_id    ID del documento
     */
    public function send_document_notification($document_id) {
        // Obtener detalles del documento
        $document = $this->get_document_details($document_id);
        
        if (empty($document)) {
            return;
        }
        
        // Obtener datos del usuario
        $user = get_userdata($document['user_id']);
        
        if (!$user || !is_email($user->user_email)) {
            return;
        }
        
        // Categorías disponibles
        $categories = get_option('worker_portal_document_categories', $this->default_options['document_categories']);
        $category_name = isset($categories[$document['category']]) 
            ? $categories[$document['category']] 
            : ucfirst($document['category']);
        
        // Configurar asunto del email
        $subject = sprintf(
            __('[%s] Nuevo documento disponible: %s', 'worker-portal'),
            get_bloginfo('name'),
            $document['title']
        );
        
        // Texto del mensaje
        $message = sprintf(
            __('Hola %s,

Se ha añadido un nuevo documento a tu portal:

Título: %s
Categoría: %s
Fecha: %s

Puedes acceder a este documento desde el Portal del Trabajador.

Saludos,
%s', 'worker-portal'),
            $user->display_name,
            $document['title'],
            $category_name,
            date_i18n(get_option('date_format'), strtotime($document['upload_date'])),
            get_bloginfo('name')
        );
        
        // Enviar email
        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Registra los endpoints de la API REST
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route('worker-portal/v1', '/documents', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'rest_get_documents'),
                'permission_callback' => array($this, 'rest_permissions_check')
            )
        ));
        
        register_rest_route('worker-portal/v1', '/documents/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'rest_get_document'),
                'permission_callback' => array($this, 'rest_permissions_check')
            )
        ));
    }

    /**
     * Verifica los permisos para las peticiones a la API REST
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   bool                           Si tiene permisos
     */
    public function rest_permissions_check($request) {
        return is_user_logged_in();
    }

    /**
     * Método para el endpoint REST de listado de documentos
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   WP_REST_Response               Respuesta REST
     */
    public function rest_get_documents($request) {
        // Para implementación futura
        return new WP_REST_Response(array('message' => 'Not implemented yet'), 501);
    }

    /**
     * Método para el endpoint REST de detalle de documento
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    Petición REST
     * @return   WP_REST_Response               Respuesta REST
     */
    public function rest_get_document($request) {
        // Para implementación futura
        return new WP_REST_Response(array('message' => 'Not implemented yet'), 501);
    }
}