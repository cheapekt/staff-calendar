<?php
/**
 * AJAX Handler for Documents Module
 * 
 * Este archivo maneja todas las solicitudes AJAX para el módulo de documentos,
 * incluyendo tanto solicitudes de usuarios como de administradores.
 * 
 * @since      1.0.0
 */

// Si este archivo es llamado directamente, abortar
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Document_Ajax_Handler
 */
class Worker_Portal_Document_Ajax_Handler {
    
    /**
     * Constructor - Inicializa los hooks
     */
    public function __construct() {
        // Registrar manejadores AJAX para usuarios
        add_action('wp_ajax_filter_documents', array($this, 'ajax_filter_documents'));
        add_action('wp_ajax_download_document', array($this, 'ajax_download_document'));
        add_action('wp_ajax_get_document_details', array($this, 'ajax_get_document_details'));
        
        // Registrar manejadores AJAX para administradores
        add_action('wp_ajax_admin_upload_document', array($this, 'ajax_admin_upload_document'));
        add_action('wp_ajax_admin_delete_document', array($this, 'ajax_admin_delete_document'));
        add_action('wp_ajax_admin_get_document_details', array($this, 'ajax_admin_get_document_details'));
        add_action('wp_ajax_admin_save_document_settings', array($this, 'ajax_admin_save_document_settings'));
    }
    
    /**
     * Filtrar documentos vía AJAX
     * Esta función maneja las solicitudes tanto de usuarios como de administradores
     */
    public function ajax_filter_documents() {
        // Registrar evento para depuración
        if (WP_DEBUG) {
            error_log('filter_documents AJAX recibido: ' . print_r($_POST, true));
        }
        
        // Verificar nonce con mayor flexibilidad
        $nonce_verified = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
                $nonce_verified = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'worker_portal_ajax_nonce')) {
                $nonce_verified = true;
            }
        }
        
        if (!$nonce_verified) {
            if (WP_DEBUG) {
                error_log('Verificación de nonce fallida en filter_documents');
            }
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
            return;
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para ver tus documentos.', 'worker-portal'));
            return;
        }
        
        // Determinar si el usuario es administrador
        $is_admin = Worker_Portal_Utils::is_portal_admin();
        
        // Parámetros de paginación
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $offset = ($page - 1) * $per_page;
        
        // Parámetros de filtrado
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // Obtener user_id (para usuarios normales será su propio ID, para admin puede ser cualquiera)
        $user_id = get_current_user_id();
        if ($is_admin && isset($_POST['user_id']) && !empty($_POST['user_id'])) {
            $user_id = intval($_POST['user_id']);
        } elseif ($is_admin && (!isset($_POST['user_id']) || empty($_POST['user_id']))) {
            $user_id = 0; // Admin puede ver todos los documentos
        }
        
        // Crear instancia de la clase principal
        require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
        $documents_module = new Worker_Portal_Module_Documents();
        
        // Obtener documentos filtrados
        $documents = $documents_module->get_filtered_documents(
            $user_id, $category, $search, $date_from, $date_to, $per_page, $offset
        );
        
        // Obtener total para paginación
        $total_items = $documents_module->get_total_filtered_documents(
            $user_id, $category, $search, $date_from, $date_to
        );
        $total_pages = ceil($total_items / $per_page);
        
        // Generar HTML
        ob_start();
        
        // Obtener categorías de documentos
        $categories = get_option('worker_portal_document_categories', array(
            'payroll' => __('Nóminas', 'worker-portal'),
            'contract' => __('Contratos', 'worker-portal'),
            'communication' => __('Comunicaciones', 'worker-portal'),
            'other' => __('Otros', 'worker-portal')
        ));
        
        if (empty($documents)): ?>
            <p class="worker-portal-no-data"><?php _e('No hay documentos disponibles con los criterios seleccionados.', 'worker-portal'); ?></p>
        <?php else: ?>
            <div class="worker-portal-table-responsive">
                <table class="worker-portal-table worker-portal-documents-table">
                    <thead>
                        <tr>
                            <?php if ($is_admin): ?>
                                <th><?php _e('ID', 'worker-portal'); ?></th>
                            <?php endif; ?>
                            <th><?php _e('Fecha', 'worker-portal'); ?></th>
                            <th><?php _e('Título', 'worker-portal'); ?></th>
                            <th><?php _e('Categoría', 'worker-portal'); ?></th>
                            <?php if ($is_admin): ?>
                                <th><?php _e('Usuario', 'worker-portal'); ?></th>
                            <?php endif; ?>
                            <th><?php _e('Acciones', 'worker-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr data-document-id="<?php echo esc_attr($document['id']); ?>">
                                <?php if ($is_admin): ?>
                                    <td><?php echo esc_html($document['id']); ?></td>
                                <?php endif; ?>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($document['upload_date'])); ?></td>
                                <td><?php echo esc_html($document['title']); ?></td>
                                <td>
                                    <?php 
                                    echo isset($categories[$document['category']]) 
                                        ? esc_html($categories[$document['category']]) 
                                        : esc_html(ucfirst($document['category'])); 
                                    ?>
                                </td>
                                <?php if ($is_admin): ?>
                                    <td><?php echo esc_html($document['display_name']); ?></td>
                                <?php endif; ?>
                                <td>
                                    <a href="#" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-download-document" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                        <i class="dashicons dashicons-download"></i> <?php _e('Descargar', 'worker-portal'); ?>
                                    </a>
                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-view-document" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                        <i class="dashicons dashicons-visibility"></i> <?php _e('Ver', 'worker-portal'); ?>
                                    </button>
                                    <?php if ($is_admin): ?>
                                        <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-document-details" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                            <i class="dashicons dashicons-info"></i> <?php _e('Detalles', 'worker-portal'); ?>
                                        </button>
                                        <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-delete-document" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                            <i class="dashicons dashicons-trash"></i>
                                        </button>
                                    <?php endif; ?>
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
                            (($page - 1) * $per_page) + 1,
                            min($page * $per_page, $total_items),
                            $total_items
                        );
                        ?>
                    </div>
                    
                    <div class="worker-portal-pagination-links">
                        <?php if ($page > 1): ?>
                            <a href="#" class="worker-portal-pagination-prev" data-page="<?php echo $page - 1; ?>">
                                &laquo; <?php _e('Anterior', 'worker-portal'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php 
                        // Mostrar números de página
                        for ($i = 1; $i <= $total_pages; $i++):
                            $class = ($i === $page) ? 'worker-portal-pagination-current' : '';
                        ?>
                            <a href="#" class="worker-portal-pagination-number <?php echo $class; ?>" data-page="<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="#" class="worker-portal-pagination-next" data-page="<?php echo $page + 1; ?>">
                                <?php _e('Siguiente', 'worker-portal'); ?> &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif;
        
        $html = ob_get_clean();
        
        // Devolver respuesta
        wp_send_json_success(array(
            'html' => $html,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'current_page' => $page
        ));
    }
    
    /**
     * Descargar documento vía AJAX
     */
    public function ajax_download_document() {
        // Verificar nonce con mayor flexibilidad
        $nonce_verified = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
                $nonce_verified = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'worker_portal_ajax_nonce')) {
                $nonce_verified = true;
            }
        }
        
        if (!$nonce_verified) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
            return;
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para descargar documentos.', 'worker-portal'));
            return;
        }
        
        $user_id = get_current_user_id();
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if ($document_id <= 0) {
            wp_send_json_error(__('ID de documento no válido', 'worker-portal'));
            return;
        }
        
        // Cargar módulo de documentos
        require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
        $documents_module = new Worker_Portal_Module_Documents();
        
        // Obtener detalles del documento
        $document = $documents_module->get_document_details($document_id);
        
        if (empty($document)) {
            wp_send_json_error(__('El documento no existe', 'worker-portal'));
            return;
        }
        
        // Comprobar permisos (usuario propietario o administrador)
        if ($document['user_id'] != $user_id && !Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permiso para descargar este documento', 'worker-portal'));
            return;
        }
        
        // Comprobar que el archivo existe
        $file_path = wp_upload_dir()['basedir'] . '/' . $document['file_path'];
        
        if (!file_exists($file_path)) {
            wp_send_json_error(__('El archivo no existe en el servidor', 'worker-portal'));
            return;
        }
        
        // Devolver URL de descarga
        $download_url = wp_upload_dir()['baseurl'] . '/' . $document['file_path'];
        
        wp_send_json_success(array(
            'download_url' => $download_url,
            'filename' => basename($document['file_path'])
        ));
    }
    
    /**
     * Obtener detalles de un documento vía AJAX
     */
    public function ajax_get_document_details() {
        // Verificar nonce con mayor flexibilidad
        $nonce_verified = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
                $nonce_verified = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'worker_portal_ajax_nonce')) {
                $nonce_verified = true;
            }
        }
        
        if (!$nonce_verified) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
            return;
        }
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para ver detalles de documentos.', 'worker-portal'));
            return;
        }
        
        $user_id = get_current_user_id();
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if ($document_id <= 0) {
            wp_send_json_error(__('ID de documento no válido', 'worker-portal'));
            return;
        }
        
        // Cargar módulo de documentos
        require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
        $documents_module = new Worker_Portal_Module_Documents();
        
        // Obtener detalles del documento
        $document = $documents_module->get_document_details($document_id);
        
        if (empty($document)) {
            wp_send_json_error(__('El documento no existe', 'worker-portal'));
            return;
        }
        
        // Comprobar permisos (usuario propietario o administrador)
        if ($document['user_id'] != $user_id && !Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permiso para ver este documento', 'worker-portal'));
            return;
        }
        
        // Devolver detalles del documento
        wp_send_json_success(array(
            'id' => $document['id'],
            'title' => $document['title'],
            'description' => $document['description']
        ));
    }
    
    /**
     * Admin: Obtener detalles de un documento vía AJAX
     */
    public function ajax_admin_get_document_details() {
        // Verificar nonce con mayor flexibilidad
        $nonce_verified = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
                $nonce_verified = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'worker_portal_ajax_nonce')) {
                $nonce_verified = true;
            }
        }
        
        if (!$nonce_verified) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
            return;
        }
        
        // Verificar que el usuario es administrador
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
            return;
        }
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if ($document_id <= 0) {
            wp_send_json_error(__('ID de documento no válido', 'worker-portal'));
            return;
        }
        
        // Cargar módulo de documentos
        require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
        $documents_module = new Worker_Portal_Module_Documents();
        
        // Obtener detalles del documento
        $document = $documents_module->get_document_details($document_id);
        
        if (empty($document)) {
            wp_send_json_error(__('El documento no existe', 'worker-portal'));
            return;
        }
        
        // Obtener información adicional
        $user = get_userdata($document['user_id']);
        $categories = get_option('worker_portal_document_categories', array(
            'payroll' => __('Nóminas', 'worker-portal'),
            'contract' => __('Contratos', 'worker-portal'),
            'communication' => __('Comunicaciones', 'worker-portal'),
            'other' => __('Otros', 'worker-portal')
        ));
        
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
     * Admin: Eliminar un documento vía AJAX
     */
    public function ajax_admin_delete_document() {
        // Verificar nonce con mayor flexibilidad
        $nonce_verified = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
                $nonce_verified = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'worker_portal_ajax_nonce')) {
                $nonce_verified = true;
            }
        }
        
        if (!$nonce_verified) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
            return;
        }
        
        // Verificar que el usuario es administrador
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
            return;
        }
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if ($document_id <= 0) {
            wp_send_json_error(__('ID de documento no válido', 'worker-portal'));
            return;
        }
        
        // Cargar módulo de documentos
        require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
        $documents_module = new Worker_Portal_Module_Documents();
        
        // Obtener detalles del documento
        $document = $documents_module->get_document_details($document_id);
        
        if (empty($document)) {
            wp_send_json_error(__('El documento no existe', 'worker-portal'));
            return;
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
            return;
        }
        
        wp_send_json_success(__('Documento eliminado correctamente', 'worker-portal'));
    }
    
    /**
     * Admin: Subir documento vía AJAX
     */
    public function ajax_admin_upload_document() {
        // Verificar nonce con mayor flexibilidad
        $nonce_verified = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce')) {
                $nonce_verified = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'worker_portal_ajax_nonce')) {
                $nonce_verified = true;
            }
        }
        
        if (!$nonce_verified) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
            return;
        }
        
        // Verificar que el usuario es administrador
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
            return;
        }
        
        // Verificar que se ha enviado un archivo
        if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('No se ha seleccionado un archivo válido', 'worker-portal'));
            return;
        }
        
        // Verificar tipo de archivo (solo PDF)
        $file_type = wp_check_filetype(basename($_FILES['document']['name']), array('pdf' => 'application/pdf'));
        
        if ($file_type['type'] !== 'application/pdf') {
            wp_send_json_error(__('Solo se permiten archivos PDF', 'worker-portal'));
            return;
        }
        
        // Obtener datos del formulario
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'other';
        $users = isset($_POST['users']) ? array_map('intval', (array)$_POST['users']) : array();
        
        // Verificar datos mínimos
        if (empty($title) || empty($users)) {
            wp_send_json_error(__('Faltan campos obligatorios (título y destinatarios)', 'worker-portal'));
            return;
        }
        
        // Cargar módulo de documentos
        require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
        $documents_module = new Worker_Portal_Module_Documents();
        
        // Procesar subida de archivo
        $upload = $documents_module->upload_document_file($_FILES['document']);
        
        if (is_wp_error($upload)) {
            wp_send_json_error($upload->get_error_message());
            return;
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
            $document_id = $documents_module->save_document($user_id, $title, $description, $category, $upload['file_path']);
            
            if ($document_id) {
                $uploaded_documents[] = $document_id;
                
                // Enviar notificación al usuario si se solicitó
                if (isset($_POST['notify']) && $_POST['notify'] == 1) {
                    $documents_module->send_document_notification($document_id);
                }
            }
        }
        
        if (empty($uploaded_documents)) {
            wp_send_json_error(__('Error al guardar el documento en la base de datos', 'worker-portal'));
            return;
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
     * Admin: Guardar configuración de documentos vía AJAX
     */
    public function ajax_admin_save_document_settings() {
        // Verificar nonce con mayor flexibilidad
        $nonce_verified = false;
        if (isset($_POST['nonce'])) {
            if (wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce')) {
                $nonce_verified = true;
            } elseif (wp_verify_nonce($_POST['nonce'], 'worker_portal_ajax_nonce')) {
                $nonce_verified = true;
            }
        }
        
        if (!$nonce_verified) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
            return;
        }
        
        // Verificar que el usuario es administrador
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
            return;
        }
        
        // Procesar datos de categorías
        if (isset($_POST['categories']) && is_array($_POST['categories'])) {
            $keys = isset($_POST['categories']['keys']) ? array_map('sanitize_key', $_POST['categories']['keys']) : array();
            $labels = isset($_POST['categories']['labels']) ? array_map('sanitize_text_field', $_POST['categories']['labels']) : array();
            
            if (count($keys) !== count($labels)) {
                wp_send_json_error(__('Error en los datos de categorías', 'worker-portal'));
                return;
            }
            
            // Asegurar que hay al menos una categoría
            if (empty($keys)) {
                wp_send_json_error(__('Debe existir al menos una categoría', 'worker-portal'));
                return;
            }
            
            // Combinar claves y etiquetas
            $categories = array();
            foreach ($keys as $index => $key) {
                if (!empty($key)) {
                    $categories[$key] = $labels[$index];
                }
            }
            
            // Actualizar opción
            update_option('worker_portal_document_categories', $categories);
        }
        
        // Guardar email de notificación
        if (isset($_POST['notification_email']) && is_email($_POST['notification_email'])) {
            update_option('worker_portal_document_notification_email', sanitize_email($_POST['notification_email']));
        }
        
        wp_send_json_success(array(
            'message' => __('Configuración guardada correctamente', 'worker-portal')
        ));
    }
}