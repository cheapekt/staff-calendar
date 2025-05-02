<?php
/**
 * AJAX Handler for Documents Module
 * 
 * This file handles all AJAX requests for the documents module,
 * including both worker and admin requests.
 * 
 * @since      1.0.0
 */

// If this file is called directly, abort
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Document_Ajax_Handler
 */
class Worker_Portal_Document_Ajax_Handler {
    
    /**
     * Constructor - Initialize hooks
     */
    public function __construct() {
        // Register AJAX handlers for workers
        add_action('wp_ajax_filter_documents', array($this, 'ajax_filter_documents'));
        add_action('wp_ajax_download_document', array($this, 'ajax_download_document'));
        add_action('wp_ajax_get_document_details', array($this, 'ajax_get_document_details'));
        
        // Register AJAX handlers for admins
        add_action('wp_ajax_admin_upload_document', array($this, 'ajax_admin_upload_document'));
        add_action('wp_ajax_admin_delete_document', array($this, 'ajax_admin_delete_document'));
        add_action('wp_ajax_admin_get_document_details', array($this, 'ajax_admin_get_document_details'));
        add_action('wp_ajax_admin_save_document_settings', array($this, 'ajax_admin_save_document_settings'));
        
        // Register form handler for document uploads
        add_action('admin_post_admin_upload_document', array($this, 'handle_document_upload'));
    }
    
    /**
     * Filter documents via AJAX
     */
    public function ajax_filter_documents() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para ver tus documentos.', 'worker-portal'));
        }
        
        // Check if user is admin
        $is_admin = Worker_Portal_Utils::is_portal_admin();
        
        // Get parameters
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $offset = ($page - 1) * $per_page;
        
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // Get user ID - if admin and user_id is provided, use that
        $user_id = get_current_user_id();
        if ($is_admin && isset($_POST['user_id']) && !empty($_POST['user_id'])) {
            $user_id = intval($_POST['user_id']);
        } elseif ($is_admin && (!isset($_POST['user_id']) || empty($_POST['user_id']))) {
            $user_id = 0; // Admin can see all documents
        }
        
        // Load database class
        if (!class_exists('Worker_Portal_Database')) {
            require_once WORKER_PORTAL_PATH . 'includes/class-database.php';
        }
        $database = Worker_Portal_Database::get_instance();
        
        // Get documents
        if (empty($search) && empty($date_from) && empty($date_to)) {
            $documents = $database->get_user_documents($user_id, $per_page, $offset, $category);
            $total_items = $database->get_total_items('worker_documents', 
                           ($user_id > 0 ? 'user_id = %d AND ' : '') . 'status = "active"' . 
                           ($category ? ' AND category = %s' : ''),
                           array_filter([$user_id, $category]));
        } else {
            // Load documents module to use filtering methods
            require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
            $documents_module = new Worker_Portal_Module_Documents();
            
            // Use the module's filtering method
            $documents = $documents_module->get_filtered_documents(
                $user_id, $category, $search, $date_from, $date_to, $per_page, $offset
            );
            
            $total_items = $documents_module->get_total_filtered_documents(
                $user_id, $category, $search, $date_from, $date_to
            );
        }
        
        $total_pages = ceil($total_items / $per_page);
        
        // Generate HTML
        ob_start();
        
        // Get document categories
        $categories = get_option('worker_portal_document_categories', array(
            'payroll' => __('Nóminas', 'worker-portal'),
            'contract' => __('Contratos', 'worker-portal'),
            'communication' => __('Comunicaciones', 'worker-portal'),
            'other' => __('Otros', 'worker-portal')
        ));
        
        if (empty($documents)): ?>
            <p class="worker-portal-no-data"><?php _e('No hay documentos disponibles.', 'worker-portal'); ?></p>
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
                        <?php foreach ($documents as $document): 
                            // Get username for admin view
                            $username = '';
                            if ($is_admin) {
                                $user = get_userdata($document['user_id']);
                                $username = $user ? $user->display_name : __('Usuario desconocido', 'worker-portal');
                            }
                        ?>
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
                                    <td><?php echo esc_html($username); ?></td>
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
                        // Show page numbers
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
        
        // Return response
        wp_send_json_success(array(
            'html' => $html,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'current_page' => $page
        ));
    }
    
    /**
     * Download document via AJAX
     */
    public function ajax_download_document() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para descargar documentos.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if ($document_id <= 0) {
            wp_send_json_error(__('ID de documento no válido', 'worker-portal'));
        }
        
        // Load documents module
        require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
        $documents_module = new Worker_Portal_Module_Documents();
        
        // Get document details
        $document = $documents_module->get_document_details($document_id);
        
        if (empty($document)) {
            wp_send_json_error(__('El documento no existe', 'worker-portal'));
        }
        
        // Check if user has permission (admin or document owner)
        if ($document['user_id'] != $user_id && !Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permiso para descargar este documento', 'worker-portal'));
        }
        
        // Check if file exists
        $file_path = wp_upload_dir()['basedir'] . '/' . $document['file_path'];
        
        if (!file_exists($file_path)) {
            wp_send_json_error(__('El archivo no existe en el servidor', 'worker-portal'));
        }
        
        // Return download URL
        $download_url = wp_upload_dir()['baseurl'] . '/' . $document['file_path'];
        
        wp_send_json_success(array(
            'download_url' => $download_url,
            'filename' => basename($document['file_path'])
        ));
    }
    
    /**
     * Get document details via AJAX
     */
    public function ajax_get_document_details() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión para ver detalles de documentos.', 'worker-portal'));
        }
        
        $user_id = get_current_user_id();
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if ($document_id <= 0) {
            wp_send_json_error(__('ID de documento no válido', 'worker-portal'));
        }
        
        // Load documents module
        require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
        $documents_module = new Worker_Portal_Module_Documents();
        
        // Get document details
        $document = $documents_module->get_document_details($document_id);
        
        if (empty($document)) {
            wp_send_json_error(__('El documento no existe', 'worker-portal'));
        }
        
        // Check if user has permission (admin or document owner)
        if ($document['user_id'] != $user_id && !Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permiso para ver este documento', 'worker-portal'));
        }
        
        // Return document details
        wp_send_json_success(array(
            'id' => $document['id'],
            'title' => $document['title'],
            'description' => $document['description']
        ));
    }
    
    /**
     * Admin: Get document details via AJAX
     */
    public function ajax_admin_get_document_details() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verify user has admin permission
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if ($document_id <= 0) {
            wp_send_json_error(__('ID de documento no válido', 'worker-portal'));
        }
        
        // Load documents module
        require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
        $documents_module = new Worker_Portal_Module_Documents();
        
        // Get document details
        $document = $documents_module->get_document_details($document_id);
        
        if (empty($document)) {
            wp_send_json_error(__('El documento no existe', 'worker-portal'));
        }
        
        // Get additional information
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
     * Admin: Delete document via AJAX
     */
    public function ajax_admin_delete_document() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_documents_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verify user has admin permission
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        $document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;
        
        if ($document_id <= 0) {
            wp_send_json_error(__('ID de documento no válido', 'worker-portal'));
        }
        
        // Load documents module
        require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
        $documents_module = new Worker_Portal_Module_Documents();
        
        // Get document details
        $document = $documents_module->get_document_details($document_id);
        
        if (empty($document)) {
            wp_send_json_error(__('El documento no existe', 'worker-portal'));
        }
        
        // Update document status to 'deleted'
        global $wpdb;
        $table_name = $wpdb->prefix . 'worker_documents';
        
        $result = $wpdb->update(
            $table_name,
            array('status' => 'deleted'),
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
     * Admin: Upload document via AJAX
     */
    public function ajax_admin_upload_document() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verify user has admin permission
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Check if file has been uploaded
        if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('No se ha seleccionado un archivo válido', 'worker-portal'));
        }
        
        // Verify file type (PDF only)
        $file_type = wp_check_filetype(basename($_FILES['document']['name']), array('pdf' => 'application/pdf'));
        
        if ($file_type['type'] !== 'application/pdf') {
            wp_send_json_error(__('Solo se permiten archivos PDF', 'worker-portal'));
        }
        
        // Get form data
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'other';
        $users = isset($_POST['users']) ? array_map('intval', (array)$_POST['users']) : array();
        
        // Verify minimum data
        if (empty($title) || empty($users)) {
            wp_send_json_error(__('Faltan campos obligatorios (título y destinatarios)', 'worker-portal'));
        }
        
        // Load documents module
        require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
        $documents_module = new Worker_Portal_Module_Documents();
        
        // Upload file
        $upload = $documents_module->upload_document_file($_FILES['document']);
        
        if (is_wp_error($upload)) {
            wp_send_json_error($upload->get_error_message());
        }
        
        // Save document in database for each user
        $uploaded_documents = array();
        
        // If all users selected
        if (in_array('all', $users)) {
            // Get all non-admin users
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
                
                // Send notification to user if requested
                if (isset($_POST['notify']) && $_POST['notify'] == 1) {
                    $documents_module->send_document_notification($document_id);
                }
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
     * Admin: Save document settings via AJAX
     */
    public function ajax_admin_save_document_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verify user has admin permission
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Process categories data
        if (isset($_POST['categories']) && is_array($_POST['categories'])) {
            $keys = isset($_POST['categories']['keys']) ? array_map('sanitize_key', $_POST['categories']['keys']) : array();
            $labels = isset($_POST['categories']['labels']) ? array_map('sanitize_text_field', $_POST['categories']['labels']) : array();
            
            if (count($keys) !== count($labels)) {
                wp_send_json_error(__('Error en los datos de categorías', 'worker-portal'));
            }
            
            // Ensure at least one category
            if (empty($keys)) {
                wp_send_json_error(__('Debe existir al menos una categoría', 'worker-portal'));
            }
            
            // Combine keys and labels
            $categories = array();
            foreach ($keys as $index => $key) {
                if (!empty($key)) {
                    $categories[$key] = $labels[$index];
                }
            }
            
            // Update option
            update_option('worker_portal_document_categories', $categories);
        }
        
        // Save notification email
        if (isset($_POST['notification_email']) && is_email($_POST['notification_email'])) {
            update_option('worker_portal_document_notification_email', sanitize_email($_POST['notification_email']));
        }
        
        wp_send_json_success(array(
            'message' => __('Configuración guardada correctamente', 'worker-portal')
        ));
    }
    
    /**
     * Handle form submission for document upload
     * This is for non-AJAX form submission
     */
    public function handle_document_upload() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_admin_nonce')) {
            wp_die(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_die(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Check if file has been uploaded
        if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            wp_die(__('No se ha seleccionado un archivo válido', 'worker-portal'));
        }
        
        // Verify file type (PDF only)
        $file_type = wp_check_filetype(basename($_FILES['document']['name']), array('pdf' => 'application/pdf'));
        
        if ($file_type['type'] !== 'application/pdf') {
            wp_die(__('Solo se permiten archivos PDF', 'worker-portal'));
        }
        
        // Get form data
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'other';
        $users = isset($_POST['users']) ? array_map('intval', (array)$_POST['users']) : array();
        
        // Verify minimum data
        if (empty($title) || empty($users)) {
            wp_die(__('Faltan campos obligatorios (título y destinatarios)', 'worker-portal'));
        }
        
        // Load documents module
        require_once WORKER_PORTAL_PATH . 'modules/documents/class-documents.php';
        $documents_module = new Worker_Portal_Module_Documents();
        
        // Upload file
        $upload = $documents_module->upload_document_file($_FILES['document']);
        
        if (is_wp_error($upload)) {
            wp_die($upload->get_error_message());
        }
        
        // Save document in database for each user
        $uploaded_documents = array();
        
        // If all users selected
        if (in_array('all', $users)) {
            // Get all non-admin users
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
                
                // Send notification to user if requested
                if (isset($_POST['notify']) && $_POST['notify'] == 1) {
                    $documents_module->send_document_notification($document_id);
                }
            }
        }
        
        if (empty($uploaded_documents)) {
            wp_die(__('Error al guardar el documento en la base de datos', 'worker-portal'));
        }
        
        $message = sprintf(
            _n(
                'Documento subido correctamente para %d usuario',
                'Documento subido correctamente para %d usuarios',
                count($uploaded_documents),
                'worker-portal'
            ),
            count($uploaded_documents)
        );
        
        // Redirect back to documents page with success message
        wp_redirect(add_query_arg(
            array(
                'page' => 'worker-portal-documents',
                'tab' => 'all',
                'message' => urlencode($message)
            ),
            admin_url('admin.php')
        ));
        exit;
    }
}

// Initialize the handler
new Worker_Portal_Document_Ajax_Handler();