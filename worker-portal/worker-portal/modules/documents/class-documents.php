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

        // Registrar AJAX actions para frontend
        $this->register_frontend_ajax_actions();
        
        // Registrar AJAX actions para admin
        $this->register_admin_ajax_actions();
    }

    /**
     * Registra los endpoints de acciones AJAX para el frontend
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_frontend_ajax_actions() {
        // Acciones AJAX para usuarios normales
        add_action('wp_ajax_get_user_documents', array($this, 'ajax_get_user_documents'));
        add_action('wp_ajax_filter_documents', array($this, 'ajax_filter_documents'));
        add_action('wp_ajax_download_document', array($this, 'ajax_download_document'));
    }

    /**
     * Registra los endpoints de acciones AJAX para el admin
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_admin_ajax_actions() {
        // Acciones AJAX para administradores
        add_action('wp_ajax_admin_upload_document', array($this, 'ajax_admin_upload_document'));
        add_action('wp_ajax_admin_delete_document', array($this, 'ajax_admin_delete_document'));
        add_action('wp_ajax_admin_get_document_details', array($this, 'ajax_admin_get_document_details'));
        add_action('wp_ajax_admin_load_documents', array($this, 'ajax_admin_load_documents'));
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
                'i18n' => array(
                    'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'),
                    'success' => __('Operación completada con éxito.', 'worker-portal'),
                    'loading' => __('Cargando...', 'worker-portal'),
                    'no_documents' => __('No hay documentos disponibles.', 'worker-portal')
                )
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
        
        // Obtener los documentos del usuario
        $documents = $this->get_user_documents($user_id, $atts['limit'], 0, $atts['category']);
        
        // Obtener las categorías disponibles
        $categories = get_option('worker_portal_document_categories', $this->default_options['document_categories']);
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir plantilla
        include(WORKER_PORTAL_PATH . 'modules/documents/templates/documents-view.php');
        
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
     * Maneja la petición AJAX para obtener documentos del usuario
     *
     * @since    1.0.0
     */
    public function ajax_get_user_documents() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para ver tus documentos.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        
        // Parámetros de paginación
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $offset = ($page - 1) * $per_page;
        
        // Obtener categoría si existe
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        
        // Obtener documentos
        $documents = $this->get_user_documents($user_id, $per_page, $offset, $category);
        
        // Obtener total para paginación
        global $wpdb;
        $table_name = $wpdb->prefix . 'worker_documents';
        
        $count_query = "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = 'active'";
        $count_params = array($user_id);
        
        if (!empty($category)) {
            $count_query .= " AND category = %s";
            $count_params[] = $category;
        }
        
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, $count_params));
        $total_pages = ceil($total_items / $per_page);
        
        // Generar HTML de la tabla
        $html = $this->generate_documents_table($documents, $page, $per_page, $total_items, $total_pages);
        
        wp_send_json_success(array(
            'html' => $html,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'current_page' => $page
        ));
    }

    /**
     * Genera el HTML para la tabla de documentos
     *
     * @since    1.0.0
     * @param    array    $documents     Lista de documentos
     * @param    int      $current_page  Página actual
     * @param    int      $per_page      Elementos por página
     * @param    int      $total_items   Total de elementos
     * @param    int      $total_pages   Total de páginas
     * @return   string                  HTML generado
     */
    private function generate_documents_table($documents, $current_page, $per_page, $total_items, $total_pages) {
        // Obtener categorías
        $categories = get_option('worker_portal_document_categories', $this->default_options['document_categories']);
        
        ob_start();
        
        if (empty($documents)) {
            echo '<p class="worker-portal-no-data">' . __('No hay documentos disponibles.', 'worker-portal') . '</p>';
        } else {
            ?>
            <div class="worker-portal-table-responsive">
                <table class="worker-portal-table worker-portal-documents-table">
                    <thead>
                        <tr>
                            <th><?php _e('Fecha', 'worker-portal'); ?></th>
                            <th><?php _e('Título', 'worker-portal'); ?></th>
                            <th><?php _e('Categoría', 'worker-portal'); ?></th>
                            <th><?php _e('Acciones', 'worker-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr data-document-id="<?php echo esc_attr($document['id']); ?>">
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($document['upload_date'])); ?></td>
                                <td><?php echo esc_html($document['title']); ?></td>
                                <td>
                                    <?php 
                                    echo isset($categories[$document['category']]) 
                                        ? esc_html($categories[$document['category']]) 
                                        : esc_html(ucfirst($document['category'])); 
                                    ?>
                                </td>
                                <td>
                                    <a href="#" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-download-document" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                        <i class="dashicons dashicons-download"></i> <?php _e('Descargar', 'worker-portal'); ?>
                                    </a>
                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-view-document" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                        <i class="dashicons dashicons-visibility"></i> <?php _e('Ver', 'worker-portal'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="worker-portal-pagination">
                    <div class="worker-portal-pagination-info">
                        <?php
                        printf(
                            __('Mostrando %1$s - %2$s de %3$s documentos', 'worker-portal'),
                            (($current_page - 1) * $per_page) + 1,
                            min($current_page * $per_page, $total_items),
                            $total_items
                        );
                        ?>
                    </div>
                    
                    <div class="worker-portal-pagination-links">
                        <?php if ($current_page > 1): ?>
                            <a href="#" class="worker-portal-pagination-prev" data-page="<?php echo $current_page - 1; ?>">
                                &laquo; <?php _e('Anterior', 'worker-portal'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php 
                        // Mostrar números de página
                        for ($i = 1; $i <= $total_pages; $i++):
                            $class = ($i === $current_page) ? 'worker-portal-pagination-current' : '';
                        ?>
                            <a href="#" class="worker-portal-pagination-number <?php echo $class; ?>" data-page="<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a href="#" class="worker-portal-pagination-next" data-page="<?php echo $current_page + 1; ?>">
                                <?php _e('Siguiente', 'worker-portal'); ?> &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php
        }
        
        return ob_get_clean();
    }

    /**
     * Maneja la petición AJAX para filtrar documentos
     *
     * @since    1.0.0
     */
    public function ajax_filter_documents() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para ver tus documentos.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        
        // Parámetros de paginación
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $offset = ($page - 1) * $per_page;
        
        // Parámetros de filtrado
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // Obtener documentos filtrados
        $documents = $this->get_filtered_documents($user_id, $category, $search, $date_from, $date_to, $per_page, $offset);
        
        // Obtener total para paginación
        $total_items = $this->get_total_filtered_documents($user_id, $category, $search, $date_from, $date_to);
        $total_pages = ceil($total_items / $per_page);
        
        // Generar HTML de la tabla
        $html = $this->generate_documents_table($documents, $page, $per_page, $total_items, $total_pages);
        
        wp_send_json_success(array(
            'html' => $html,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'current_page' => $page
        ));
    }

    /**
     * Obtiene documentos filtrados de un usuario
     *
     * @since    1.0.0
     * @param    int       $user_id      ID del usuario
     * @param    string    $category     Categoría de documentos
     * @param    string    $search       Término de búsqueda
     * @param    string    $date_from    Fecha de inicio
     * @param    string    $date_to      Fecha de fin
     * @param    int       $limit        Límite de resultados
     * @param    int       $offset       Offset para paginación
     * @return   array                   Lista de documentos
     */
    private function get_filtered_documents($user_id, $category = '', $search = '', $date_from = '', $date_to = '', $limit = 10, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_documents';
        
        $query = "SELECT * FROM $table_name WHERE user_id = %d AND status = 'active'";
        $params = array($user_id);
        
        // Aplicar filtros
        if (!empty($category)) {
            $query .= " AND category = %s";
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $query .= " AND (title LIKE %s OR description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($date_from)) {
            $query .= " AND upload_date >= %s";
            $params[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $query .= " AND upload_date <= %s";
            $params[] = $date_to . ' 23:59:59';
        }
        
        // Ordenar y limitar resultados
        $query .= " ORDER BY upload_date DESC LIMIT %d OFFSET %d";
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
     * @param    int       $user_id      ID del usuario
     * @param    string    $category     Categoría de documentos
     * @param    string    $search       Término de búsqueda
     * @param    string    $date_from    Fecha de inicio
     * @param    string    $date_to      Fecha de fin
     * @return   int                     Total de documentos
     */
    private function get_total_filtered_documents($user_id, $category = '', $search = '', $date_from = '', $date_to = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_documents';
        
        $query = "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = 'active'";
        $params = array($user_id);
        
        // Aplicar filtros
        if (!empty($category)) {
            $query .= " AND category = %s";
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $query .= " AND (title LIKE %s OR description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($date_from)) {
            $query .= " AND upload_date >= %s";
            $params[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $query .= " AND upload_date <= %s";
            $params[] = $date_to . ' 23:59:59';
        }
        
        return $wpdb->get_var($wpdb->prepare($query, $params));
    }

    /**
     * Maneja la petición AJAX para descargar un documento
     *
     * @since    1.0.0
     */
    public function ajax_download_document() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para descargar documentos.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if ($document_id <= 0) {
            wp_send_json_error(__('ID de documento no válido', 'worker-portal'));
        }
        
        // Obtener detalles del documento
        $document = $this->get_document_details($document_id);
        
        if (empty($document)) {
            wp_send_json_error(__('El documento no existe', 'worker-portal'));
        }
        
        // Verificar que el documento pertenece al usuario o es admin
        if ($document['user_id'] != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permiso para descargar este documento', 'worker-portal'));
        }
        
        // Verificar que el archivo existe
        $file_path = wp_upload_dir()['basedir'] . '/' . $document['file_path'];
        
        if (!file_exists($file_path)) {
            wp_send_json_error(__('El archivo no existe en el servidor', 'worker-portal'));
        }
        
        // Devolver URL de descarga
        $download_url = wp_upload_dir()['baseurl'] . '/' . $document['file_path'];
        
        wp_send_json_success(array(
            'download_url' => $download_url,
            'filename' => basename($document['file_path'])
        ));
    }

    /**
     * Obtiene los detalles de un documento
     *
     * @since    1.0.0
     * @param    int       $document_id    ID del documento
     * @return   array|null               Detalles del documento o null si no existe
     */
    private function get_document_details($document_id) {
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
     * Maneja la petición AJAX para subir un documento (admin)
     *
     * @since    1.0.0
     */
    public function ajax_admin_upload_document() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar permisos de administrador
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Verificar que se ha enviado un archivo
        if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('No se ha seleccionado un archivo válido', 'worker-portal'));
        }
        
        // Verificar tipo de archivo (solo PDF)
        $file_type = wp_check_filetype(basename($_FILES['document']['name']), array('pdf' => 'application/pdf'));
        
        if ($file_type['type'] !== 'application/pdf') {
            wp_send_json_error(__('Solo se permiten archivos PDF', 'worker-portal'));
        }
        
        // Obtener datos del formulario
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'other';
        $users = isset($_POST['users']) ? array_map('intval', (array)$_POST['users']) : array();
        
        // Verificar datos mínimos
        if (empty($title) || empty($users)) {
            wp_send_json_error(__('Faltan campos obligatorios (título y destinatarios)', 'worker-portal'));
        }
        
        // Procesar subida de archivo
        $upload = $this->upload_document_file($_FILES['document']);
        
        if (is_wp_error($upload)) {
            wp_send_json_error($upload->get_error_message());
        }
        
        // Guardar documento en la base de datos para cada usuario
        $uploaded_documents = array();
        
        // Si se seleccionaron todos los usuarios
        if (in_array('all', $users)) {
            // Obtener todos los usuarios no administradores
            $all_users = get_users(array(
                'role__not_in' => array('administrator'),
                'fields' => 'ID'
            ));
            
            $users = $all_users;
        }
        
        foreach ($users as $user_id) {
            $document_id = $this->save_document($user_id, $title, $description, $category, $upload['file_path']);
            
            if ($document_id) {
                $uploaded_documents[] = $document_id;
                
                // Enviar notificación al usuario
                $this->send_document_notification($document_id);
            }
        }
        
        if (empty($uploaded_documents)) {
            wp_send_json_error(__('Error al guardar el documento en la base de datos', 'worker-portal'));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                _n(
                    'Documento subido correctamente para %d usuario',
                    'Documento subido correctamente para %d usuarios',
                    count($uploaded_documents),
                    'worker-portal'
                ),
                count($uploaded_documents)
            ),
            'document_ids' => $uploaded_documents
        ));
    }

    /**
     * Sube un archivo de documento al servidor
     *
     * @since    1.0.0
     * @param    array     $file     Datos del archivo
     * @return   array|WP_Error     Información del archivo subido o error
     */
    private function upload_document_file($file) {
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
    private function save_document($user_id, $title, $description, $category, $file_path) {
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
    private function send_document_notification($document_id) {
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
     * Maneja la petición AJAX para eliminar un documento (admin)
     *
     * @since    1.0.0
     */
    public function ajax_admin_delete_document() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar permisos de administrador
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if ($document_id <= 0) {
            wp_send_json_error(__('ID de documento no válido', 'worker-portal'));
        }
        
        // Obtener detalles del documento
        $document = $this->get_document_details($document_id);
        
        if (empty($document)) {
            wp_send_json_error(__('El documento no existe', 'worker-portal'));
        }
        
        // Marcar documento como inactivo (eliminación lógica)
        global $wpdb;
        $table_name = $wpdb->prefix . 'worker_documents';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 'deleted'
            ),
            array('id' => $document_id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(__('Error al eliminar el documento', 'worker-portal'));
        }
        
        wp_send_json_success(__('Documento eliminado correctamente', 'worker-portal'));
    }

    /**
     * Maneja la petición AJAX para obtener detalles de un documento (admin)
     *
     * @since    1.0.0
     */
    public function ajax_admin_get_document_details() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar permisos de administrador
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if ($document_id <= 0) {
            wp_send_json_error(__('ID de documento no válido', 'worker-portal'));
        }
        
        // Obtener detalles del documento
        $document = $this->get_document_details($document_id);
        
        if (empty($document)) {
            wp_send_json_error(__('El documento no existe', 'worker-portal'));
        }
        
        // Obtener información adicional
        $user = get_userdata($document['user_id']);
        $categories = get_option('worker_portal_document_categories', $this->default_options['document_categories']);
        
        $document_data = array(
            'id' => $document['id'],
            'title' => $document['title'],
            'description' => $document['description'],
            'category' => $document['category'],
            'category_name' => isset($categories[$document['category']]) 
                ? $categories[$document['category']] 
                : ucfirst($document['category']),
            'upload_date' => date_i18n(get_option('date_format'), strtotime($document['upload_date'])),
            'user_id' => $document['user_id'],
            'user_name' => $user ? $user->display_name : __('Usuario desconocido', 'worker-portal'),
            'file_path' => $document['file_path'],
            'download_url' => wp_upload_dir()['baseurl'] . '/' . $document['file_path'],
            'status' => $document['status']
        );
        
        wp_send_json_success($document_data);
    }

    /**
     * Maneja la petición AJAX para cargar documentos (admin)
     *
     * @since    1.0.0
     */
    public function ajax_admin_load_documents() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar permisos de administrador
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Parámetros de filtrado
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        
        // Parámetros de paginación
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Obtener documentos
        $documents = $this->get_admin_documents($user_id, $category, $per_page, $offset);
        
        // Obtener total para paginación
        $total_items = $this->get_admin_total_documents($user_id, $category);
        $total_pages = ceil($total_items / $per_page);
        
        // Generar HTML
        $html = $this->generate_admin_documents_html($documents, $page, $per_page, $total_items, $total_pages);
        
        wp_send_json_success(array(
            'html' => $html,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'current_page' => $page
        ));
    }

    /**
     * Obtiene documentos para el administrador
     *
     * @since    1.0.0
     * @param    int       $user_id     ID del usuario (0 para todos)
     * @param    string    $category    Categoría de documentos
     * @param    int       $limit       Límite de resultados
     * @param    int       $offset      Desplazamiento para paginación
     * @return   array                  Lista de documentos
     */
    private function get_admin_documents($user_id = 0, $category = '', $limit = 20, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_documents';
        $users_table = $wpdb->users;
        
        $query = "SELECT d.*, u.display_name as user_name
                FROM $table_name d
                LEFT JOIN $users_table u ON d.user_id = u.ID
                WHERE d.status = 'active'";
        $params = array();
        
        // Filtrar por usuario
        if ($user_id > 0) {
            $query .= " AND d.user_id = %d";
            $params[] = $user_id;
        }
        
        // Filtrar por categoría
        if (!empty($category)) {
            $query .= " AND d.category = %s";
            $params[] = $category;
        }
        
        // Ordenar y limitar resultados
        $query .= " ORDER BY d.upload_date DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        return $wpdb->get_results(
            empty($params) ? $query : $wpdb->prepare($query, $params),
            ARRAY_A
        );
    }

    /**
     * Obtiene el total de documentos para el administrador
     *
     * @since    1.0.0
     * @param    int       $user_id     ID del usuario (0 para todos)
     * @param    string    $category    Categoría de documentos
     * @return   int                    Total de documentos
     */
    private function get_admin_total_documents($user_id = 0, $category = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'worker_documents';
        
        $query = "SELECT COUNT(*) FROM $table_name WHERE status = 'active'";
        $params = array();
        
        // Filtrar por usuario
        if ($user_id > 0) {
            $query .= " AND user_id = %d";
            $params[] = $user_id;
        }
        
        // Filtrar por categoría
        if (!empty($category)) {
            $query .= " AND category = %s";
            $params[] = $category;
        }
        
        return $wpdb->get_var(
            empty($params) ? $query : $wpdb->prepare($query, $params)
        );
    }

    /**
     * Genera el HTML para la tabla de documentos del administrador
     *
     * @since    1.0.0
     * @param    array     $documents     Lista de documentos
     * @param    int       $current_page  Página actual
     * @param    int       $per_page      Elementos por página
     * @param    int       $total_items   Total de elementos
     * @param    int       $total_pages   Total de páginas
     * @return   string                   HTML generado
     */
    private function generate_admin_documents_html($documents, $current_page, $per_page, $total_items, $total_pages) {
        // Obtener categorías
        $categories = get_option('worker_portal_document_categories', $this->default_options['document_categories']);
        
        ob_start();
        ?>
        <div class="worker-portal-admin-table-container">
            <?php if (empty($documents)): ?>
                <p><?php _e('No se encontraron documentos.', 'worker-portal'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'worker-portal'); ?></th>
                            <th><?php _e('Título', 'worker-portal'); ?></th>
                            <th><?php _e('Categoría', 'worker-portal'); ?></th>
                            <th><?php _e('Usuario', 'worker-portal'); ?></th>
                            <th><?php _e('Fecha', 'worker-portal'); ?></th>
                            <th><?php _e('Acciones', 'worker-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr>
                                <td><?php echo esc_html($document['id']); ?></td>
                                <td><?php echo esc_html($document['title']); ?></td>
                                <td>
                                    <?php 
                                    echo isset($categories[$document['category']]) 
                                        ? esc_html($categories[$document['category']]) 
                                        : esc_html(ucfirst($document['category'])); 
                                    ?>
                                </td>
                                <td><?php echo esc_html($document['user_name']); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($document['upload_date'])); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . $document['file_path']); ?>" class="button button-small" target="_blank">
                                        <?php _e('Ver', 'worker-portal'); ?>
                                    </a>
                                    <button type="button" class="button button-small view-document-details" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                        <?php _e('Detalles', 'worker-portal'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-link-delete delete-document" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                        <?php _e('Eliminar', 'worker-portal'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php echo sprintf(_n('%s elemento', '%s elementos', $total_items, 'worker-portal'), number_format_i18n($total_items)); ?>
                        </span>
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page,
                            'type' => 'plain'
                        ));
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Registra los endpoints de la API REST para futura implementación
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