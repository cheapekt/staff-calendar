<?php
/**
 * La clase pública del plugin
 *
 * @since      1.0.0
 */
class Worker_Portal_Public {

      // Añadir esta propiedad al inicio de la clase
    private $utils;

    // Modificar el constructor
    public function __construct() {
        // Cargar la clase de utilidades
        require_once WORKER_PORTAL_PATH . 'includes/class-utils.php';
    }

    /**
     * Carga de estilos para el frontend
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Cargar utilidades
        require_once WORKER_PORTAL_PATH . 'includes/class-utils.php';
        
        // Estilos generales del portal
        wp_enqueue_style(
            'worker-portal-public',
            WORKER_PORTAL_URL . 'public/css/public-style.css',
            array(),
            WORKER_PORTAL_VERSION,
            'all'
        );

        // Estilos específicos de gastos
        wp_enqueue_style(
            'worker-portal-expenses',
            WORKER_PORTAL_URL . 'modules/expenses/css/expenses.css',
            array(),
            WORKER_PORTAL_VERSION,
            'all'
        );
        
        // Si es administrador, cargar estilos adicionales
        if (Worker_Portal_Utils::is_portal_admin()) {
            wp_enqueue_style(
                'worker-portal-admin-frontend',
                WORKER_PORTAL_URL . 'public/css/admin-frontend-style.css',
                array('worker-portal-public'),
                WORKER_PORTAL_VERSION,
                'all'
            );
        }
    }

    /**
     * Carga de scripts para el frontend
     *
     * @since    1.0.0
     */
public function enqueue_scripts() {
    // Cargar utilidades
    require_once WORKER_PORTAL_PATH . 'includes/class-utils.php';
    
    // Cargar librería jQuery si no está cargada
    wp_enqueue_script('jquery');
    
    // Cargar dashicons
    wp_enqueue_style('dashicons');
    
    // Scripts generales del portal
    wp_enqueue_script(
        'worker-portal-public',
        WORKER_PORTAL_URL . 'public/js/public-script.js',
        array('jquery'),
        WORKER_PORTAL_VERSION,
        true
    );
    
    // Localizar script de portal
    wp_localize_script(
        'worker-portal-public',
        'worker_portal_params',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('worker_portal_ajax_nonce')
        )
    );
    
    // Scripts específicos según el rol
    if (Worker_Portal_Utils::is_portal_admin()) {
        // Scripts para la interfaz de administrador
        wp_enqueue_script(
            'worker-portal-admin-frontend',
            WORKER_PORTAL_URL . 'public/js/admin-frontend-script.js',
            array('jquery', 'worker-portal-public'),
            WORKER_PORTAL_VERSION,
            true
        );
        
        // MOVER A AQUÍ: Localizar script de admin frontend (después de registrarlo)
        wp_localize_script(
            'worker-portal-admin-frontend',
            'worker_portal_params',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('worker_portal_ajax_nonce')
            )
        );
    } else {
        // Scripts específicos para trabajadores
        wp_enqueue_script(
            'worker-portal-expenses',
            WORKER_PORTAL_URL . 'modules/expenses/js/expenses.js',
            array('jquery'),
            WORKER_PORTAL_VERSION,
            true
        );
        
        // Localizar script de gastos
        wp_localize_script(
            'worker-portal-expenses',
            'workerPortalExpenses',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('worker_portal_expenses_nonce'),
                'i18n' => array(
                    'confirm_delete' => __('¿Estás seguro de que deseas eliminar este gasto?', 'worker-portal'),
                    'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'),
                    'success' => __('Operación completada con éxito.', 'worker-portal')
                )
            )
        );
    }
    
    // Si estamos en la página del portal y el módulo de documentos está activo
    if (is_page('portal-del-trabajador') || has_shortcode(get_the_content(), 'worker_portal')) {
        // Registrar estilos y scripts de documentos
        wp_enqueue_style(
            'worker-portal-documents',
            WORKER_PORTAL_URL . 'modules/documents/css/documents.css',
            array('worker-portal-public'),
            WORKER_PORTAL_VERSION
        );
        
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
                'is_admin' => Worker_Portal_Utils::is_portal_admin() ? 'true' : 'false',
                'i18n' => array(
                    'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'),
                    'success' => __('Operación completada con éxito.', 'worker-portal'),
                    'loading' => __('Cargando...', 'worker-portal'),
                    'no_documents' => __('No hay documentos disponibles.', 'worker-portal'),
                    'confirm_delete' => __('¿Estás seguro de eliminar este documento? Esta acción no se puede deshacer.', 'worker-portal')
                )
            )
        );
    }
}

    /**
     * Registra shortcodes para el portal
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('worker_portal', array($this, 'render_portal_shortcode'));
        add_shortcode('worker_expenses', array($this, 'render_expenses_shortcode'));
        add_shortcode('worker_documents', array($this, 'render_documents_shortcode'));
        add_shortcode('worker_worksheets', array($this, 'render_worksheets_shortcode'));
        add_shortcode('worker_incentives', array($this, 'render_incentives_shortcode'));
    }

    /**
     * Añade hooks de AJAX
     *
     * @since    1.0.0
     */
    public function add_ajax_hooks() {
        // Hook para cargar secciones del portal
        add_action('wp_ajax_load_portal_section', array($this, 'ajax_load_portal_section'));
        add_action('wp_ajax_nopriv_load_portal_section', array($this, 'ajax_load_portal_section'));
        
        // Hooks para acciones de administrador en frontend
        add_action('wp_ajax_admin_load_pending_expenses', array($this, 'ajax_admin_load_pending_expenses'));
        add_action('wp_ajax_admin_approve_expense', array($this, 'ajax_admin_approve_expense'));
        add_action('wp_ajax_admin_reject_expense', array($this, 'ajax_admin_reject_expense'));
        add_action('wp_ajax_admin_bulk_expense_action', array($this, 'ajax_admin_bulk_expense_action'));
        add_action('wp_ajax_admin_get_expense_details', array($this, 'ajax_admin_get_expense_details'));

        // Registrar el manejador AJAX para documentos
        require_once WORKER_PORTAL_PATH . 'modules/documents/documents-ajax-handler.php';
        new Worker_Portal_Document_Ajax_Handler();
    }

    /**
     * Carga dinámica de secciones del portal
     *
     * @since    1.0.0
     */
    public function ajax_load_portal_section() {
        // Verificar nonce
        check_ajax_referer('worker_portal_ajax_nonce', 'nonce');

        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'worker-portal'));
        }

        // Obtener la sección solicitada
        $section = isset($_POST['section']) ? sanitize_text_field($_POST['section']) : '';

        // Contenido de la sección
        $content = '';

        // Generar contenido según la sección
        switch ($section) {
            case 'expenses':
                $content = do_shortcode('[worker_expenses]');
                break;
            case 'documents':
                $content = do_shortcode('[worker_documents]');
                break;
            case 'worksheets':
                $content = do_shortcode('[worker_worksheets]');
                break;
            case 'incentives':
                $content = do_shortcode('[worker_incentives]');
                break;
            default:
                wp_send_json_error(__('Sección no válida', 'worker-portal'));
        }

        // Enviar respuesta
        wp_send_json_success($content);
    }
    
    /**
     * Carga gastos pendientes para el panel de administración
     *
     * @since    1.0.0
     */
    public function ajax_admin_load_pending_expenses() {
        // Cargar la clase de utilidades
        require_once WORKER_PORTAL_PATH . 'includes/class-utils.php';
        
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'worker_portal_ajax_nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'worker-portal'));
        }
        
        // Verificar que el usuario está logueado y es administrador
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener parámetros de filtrado
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $expense_type = isset($_POST['expense_type']) ? sanitize_text_field($_POST['expense_type']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // Obtener gastos pendientes
        global $wpdb;
        
        $query = "SELECT e.*, u.display_name 
                FROM {$wpdb->prefix}worker_expenses e 
                LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID 
                WHERE e.status = 'pending'";
        $params = array();
        
        // Filtro por usuario
        if ($user_id > 0) {
            $query .= " AND e.user_id = %d";
            $params[] = $user_id;
        }
        
        // Filtro por tipo
        if (!empty($expense_type)) {
            $query .= " AND e.expense_type = %s";
            $params[] = $expense_type;
        }
        
        // Filtro por fecha desde
        if (!empty($date_from)) {
            $query .= " AND e.expense_date >= %s";
            $params[] = $date_from;
        }
        
        // Filtro por fecha hasta
        if (!empty($date_to)) {
            $query .= " AND e.expense_date <= %s";
            $params[] = $date_to;
        }
        
        // Ordenar
        $query .= " ORDER BY e.report_date DESC";
        
        // Ejecutar consulta
        $expenses = empty($params) ?
            $wpdb->get_results($query, ARRAY_A) :
            $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        
        // Obtener tipos de gastos
        $expense_types = get_option('worker_portal_expense_types', array(
            'km' => __('Kilometraje', 'worker-portal'),
            'hours' => __('Horas de desplazamiento', 'worker-portal'),
            'meal' => __('Dietas', 'worker-portal'),
            'other' => __('Otros', 'worker-portal')
        ));
        
        // Generar HTML de respuesta
        ob_start();
        
        if (empty($expenses)):
        ?>
            <div class="worker-portal-no-items">
                <p><?php _e('No hay gastos pendientes con los criterios seleccionados.', 'worker-portal'); ?></p>
            </div>
        <?php else: ?>
            <form id="expenses-list-form">
                <table class="worker-portal-admin-table">
                    <thead>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" id="select-all-expenses">
                            </th>
                            <th><?php _e('Fecha', 'worker-portal'); ?></th>
                            <th><?php _e('Trabajador', 'worker-portal'); ?></th>
                            <th><?php _e('Tipo', 'worker-portal'); ?></th>
                            <th><?php _e('Descripción', 'worker-portal'); ?></th>
                            <th><?php _e('Importe', 'worker-portal'); ?></th>
                            <th><?php _e('Ticket', 'worker-portal'); ?></th>
                            <th><?php _e('Acciones', 'worker-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td class="check-column">
                                    <input type="checkbox" name="expense_ids[]" value="<?php echo esc_attr($expense['id']); ?>" class="expense-checkbox">
                                </td>
                                <td>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($expense['report_date'])); ?><br>
                                    <small><?php echo date_i18n(get_option('date_format'), strtotime($expense['expense_date'])); ?></small>
                                </td>
                                <td><?php echo esc_html($expense['display_name']); ?></td>
                                <td>
                                    <?php 
                                    echo isset($expense_types[$expense['expense_type']]) 
                                        ? esc_html($expense_types[$expense['expense_type']]) 
                                        : esc_html($expense['expense_type']); 
                                    ?>
                                </td>
                                <td><?php echo esc_html($expense['description']); ?></td>
                                <td>
                                    <?php 
                                    // Mostrar importe con formato según tipo de gasto
                                    switch ($expense['expense_type']) {
                                        case 'km':
                                            echo esc_html($expense['amount']) . ' Km';
                                            break;
                                        case 'hours':
                                            echo esc_html($expense['amount']) . ' ' . __('Horas', 'worker-portal');
                                            break;
                                        default:
                                            echo esc_html(number_format((float)$expense['amount'], 2, ',', '.')) . ' €';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($expense['has_receipt']): ?>
                                        <span class="worker-portal-badge worker-portal-badge-success"><?php _e('SI', 'worker-portal'); ?></span>
                                        <?php if (!empty($expense['receipt_path'])): ?>
                                            <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . $expense['receipt_path']); ?>" class="worker-portal-button worker-portal-button-small worker-portal-button-outline view-receipt">
                                                <i class="dashicons dashicons-visibility"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="worker-portal-badge worker-portal-badge-secondary"><?php _e('NO', 'worker-portal'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-primary approve-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                        <i class="dashicons dashicons-yes"></i>
                                    </button>
                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-danger reject-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                        <i class="dashicons dashicons-no"></i>
                                    </button>
                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-secondary view-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                        <i class="dashicons dashicons-visibility"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        <?php endif;
        
        $html = ob_get_clean();
        wp_send_json_success($html);
    }
        
    /**
     * Aprobar un gasto desde el panel de administración
     *
     * @since    1.0.0
     */
    public function ajax_admin_approve_expense() {
        // Verificar nonce
        check_ajax_referer('worker_portal_ajax_nonce', 'nonce');
        
        // Verificar que el usuario está logueado y es administrador
        if (!is_user_logged_in() || !Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener ID del gasto
        $expense_id = isset($_POST['expense_id']) ? intval($_POST['expense_id']) : 0;
        
        if ($expense_id <= 0) {
            wp_send_json_error(__('ID de gasto no válido', 'worker-portal'));
        }
        
        // Verificar que el gasto existe y está pendiente
        global $wpdb;
        
        $expense = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}worker_expenses WHERE id = %d",
                $expense_id
            ),
            ARRAY_A
        );
        
        if (!$expense) {
            wp_send_json_error(__('El gasto no existe', 'worker-portal'));
        }
        
        if ($expense['status'] !== 'pending') {
            wp_send_json_error(__('El gasto ya ha sido procesado', 'worker-portal'));
        }
        
        // Aprobar gasto
        $updated = $wpdb->update(
            $wpdb->prefix . 'worker_expenses',
            array(
                'status' => 'approved',
                'approved_by' => get_current_user_id(),
                'approved_date' => current_time('mysql')
            ),
            array('id' => $expense_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        if ($updated === false) {
            wp_send_json_error(__('Error al aprobar el gasto. Por favor, inténtalo de nuevo.', 'worker-portal'));
        }
        
        // Enviar notificación al trabajador
        $user = get_userdata($expense['user_id']);
        $approver = wp_get_current_user();
        
        if ($user) {
            $subject = sprintf(__('[%s] Tu gasto ha sido aprobado', 'worker-portal'), get_bloginfo('name'));
            
            $message = sprintf(
                __('Hola %s,

Tu gasto comunicado el %s ha sido aprobado por %s.

Detalles del gasto:
- Tipo: %s
- Importe: %s
- Descripción: %s

Puedes ver todos tus gastos en el Portal del Trabajador.

Saludos,
%s', 'worker-portal'),
                $user->display_name,
                date_i18n(get_option('date_format'), strtotime($expense['report_date'])),
                $approver->display_name,
                isset($expense_types[$expense['expense_type']]) ? $expense_types[$expense['expense_type']] : $expense['expense_type'],
                Worker_Portal_Utils::format_expense_amount($expense['amount'], $expense['expense_type']),
                $expense['description'],
                get_bloginfo('name')
            );
            
            wp_mail($user->user_email, $subject, $message);
        }
        
        // Respuesta exitosa
        wp_send_json_success(array(
            'message' => __('Gasto aprobado correctamente', 'worker-portal')
        ));
    }
    
    /**
     * Rechazar un gasto desde el panel de administración
     *
     * @since    1.0.0
     */
    public function ajax_admin_reject_expense() {
        // Verificar nonce
        check_ajax_referer('worker_portal_ajax_nonce', 'nonce');
        
        // Verificar que el usuario está logueado y es administrador
        if (!is_user_logged_in() || !Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener ID del gasto
        $expense_id = isset($_POST['expense_id']) ? intval($_POST['expense_id']) : 0;
        
        if ($expense_id <= 0) {
            wp_send_json_error(__('ID de gasto no válido', 'worker-portal'));
        }
        
        // Verificar que el gasto existe y está pendiente
        global $wpdb;
        
        $expense = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}worker_expenses WHERE id = %d",
                $expense_id
            ),
            ARRAY_A
        );
        
        if (!$expense) {
            wp_send_json_error(__('El gasto no existe', 'worker-portal'));
        }
        
        if ($expense['status'] !== 'pending') {
            wp_send_json_error(__('El gasto ya ha sido procesado', 'worker-portal'));
        }
        
        // Rechazar gasto
        $updated = $wpdb->update(
            $wpdb->prefix . 'worker_expenses',
            array(
                'status' => 'rejected',
                'approved_by' => get_current_user_id(),
                'approved_date' => current_time('mysql')
            ),
            array('id' => $expense_id),
            array('%s', '%d', '%s'),
            array('%d')
        );
        
        if ($updated === false) {
            wp_send_json_error(__('Error al rechazar el gasto. Por favor, inténtalo de nuevo.', 'worker-portal'));
        }
        
        // Enviar notificación al trabajador
        $user = get_userdata($expense['user_id']);
        $approver = wp_get_current_user();
        
        if ($user) {
            $subject = sprintf(__('[%s] Tu gasto ha sido denegado', 'worker-portal'), get_bloginfo('name'));
            
            $message = sprintf(
                __('Hola %s,

Tu gasto comunicado el %s ha sido denegado por %s.

Detalles del gasto:
- Tipo: %s
- Importe: %s
- Descripción: %s

Si tienes alguna duda, contacta con tu responsable.

Saludos,
%s', 'worker-portal'),
                $user->display_name,
                date_i18n(get_option('date_format'), strtotime($expense['report_date'])),
                $approver->display_name,
                isset($expense_types[$expense['expense_type']]) ? $expense_types[$expense['expense_type']] : $expense['expense_type'],
                Worker_Portal_Utils::format_expense_amount($expense['amount'], $expense['expense_type']),
                $expense['description'],
                get_bloginfo('name')
            );
            
            wp_mail($user->user_email, $subject, $message);
        }
        
        // Respuesta exitosa
        wp_send_json_success(array(
            'message' => __('Gasto denegado correctamente', 'worker-portal')
        ));
    }
    
    /**
     * Procesar acciones masivas de gastos
     *
     * @since    1.0.0
     */
    public function ajax_admin_bulk_expense_action() {
        // Verificar nonce
        check_ajax_referer('worker_portal_ajax_nonce', 'nonce');
        
        // Verificar que el usuario está logueado y es administrador
        if (!is_user_logged_in() || !Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener acción y gastos seleccionados
        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $expense_ids = isset($_POST['expense_ids']) ? array_map('intval', $_POST['expense_ids']) : array();
        
        if (empty($action) || !in_array($action, array('approve', 'reject'))) {
            wp_send_json_error(__('Acción no válida', 'worker-portal'));
        }
        
        if (empty($expense_ids)) {
            wp_send_json_error(__('No se han seleccionado gastos', 'worker-portal'));
        }
        
        // Procesar los gastos seleccionados
        global $wpdb;
        $processed = 0;
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $user_id = get_current_user_id();
        $current_time = current_time('mysql');
        
        foreach ($expense_ids as $expense_id) {
            // Verificar que el gasto existe y está pendiente
            $expense = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}worker_expenses WHERE id = %d AND status = 'pending'",
                    $expense_id
                ),
                ARRAY_A
            );
            
            if (!$expense) {
                continue;
            }
            
            // Actualizar estado
            $updated = $wpdb->update(
                $wpdb->prefix . 'worker_expenses',
                array(
                    'status' => $status,
                    'approved_by' => $user_id,
                    'approved_date' => $current_time
                ),
                array('id' => $expense_id),
                array('%s', '%d', '%s'),
                array('%d')
            );
            
            if ($updated !== false) {
                $processed++;
            }
        }
        
      if ($processed === 0) {
            wp_send_json_error(__('No se ha podido procesar ningún gasto', 'worker-portal'));
        }
        
        // Respuesta exitosa
        wp_send_json_success(array(
            'message' => sprintf(
                _n(
                    '%d gasto %s correctamente', 
                    '%d gastos %s correctamente', 
                    $processed, 
                    'worker-portal'
                ),
                $processed,
                $action === 'approve' ? __('aprobado', 'worker-portal') : __('denegado', 'worker-portal')
            ),
            'processed' => $processed
        ));
    }
    
    /**
     * Obtener detalles de un gasto para mostrar en modal
     *
     * @since    1.0.0
     */
    public function ajax_admin_get_expense_details() {
        // Verificar nonce
        check_ajax_referer('worker_portal_ajax_nonce', 'nonce');
        
        // Verificar que el usuario está logueado y es administrador
        if (!is_user_logged_in() || !Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'worker-portal'));
        }
        
        // Obtener ID del gasto
        $expense_id = isset($_POST['expense_id']) ? intval($_POST['expense_id']) : 0;
        
        if ($expense_id <= 0) {
            wp_send_json_error(__('ID de gasto no válido', 'worker-portal'));
        }
        
        // Obtener detalles del gasto
        global $wpdb;
        
        $expense = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT e.*, u.display_name as user_name 
                 FROM {$wpdb->prefix}worker_expenses e 
                 LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID 
                 WHERE e.id = %d",
                $expense_id
            ),
            ARRAY_A
        );
        
        if (!$expense) {
            wp_send_json_error(__('El gasto no existe', 'worker-portal'));
        }
        
        // Obtener información del aprobador si existe
        $approver_name = '';
        if (!empty($expense['approved_by'])) {
            $approver = get_userdata($expense['approved_by']);
            if ($approver) {
                $approver_name = $approver->display_name;
            }
        }
        
        // Obtener tipos de gastos
        $expense_types = get_option('worker_portal_expense_types', array());
        
        // Generar HTML de detalles
        ob_start();
        ?>
        <div class="worker-portal-expense-details">
            <table class="worker-portal-details-table">
                <tr>
                    <th><?php _e('ID:', 'worker-portal'); ?></th>
                    <td><?php echo esc_html($expense['id']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Trabajador:', 'worker-portal'); ?></th>
                    <td><?php echo esc_html($expense['user_name']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Fecha de comunicación:', 'worker-portal'); ?></th>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($expense['report_date'])); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Fecha del gasto:', 'worker-portal'); ?></th>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($expense['expense_date'])); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Tipo:', 'worker-portal'); ?></th>
                    <td>
                        <?php 
                        echo isset($expense_types[$expense['expense_type']]) 
                            ? esc_html($expense_types[$expense['expense_type']]) 
                            : esc_html($expense['expense_type']); 
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Descripción:', 'worker-portal'); ?></th>
                    <td><?php echo esc_html($expense['description']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Importe:', 'worker-portal'); ?></th>
                    <td><?php echo Worker_Portal_Utils::format_expense_amount($expense['amount'], $expense['expense_type']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Justificante:', 'worker-portal'); ?></th>
                    <td>
                        <?php if ($expense['has_receipt']): ?>
                            <span class="worker-portal-badge worker-portal-badge-success"><?php _e('SI', 'worker-portal'); ?></span>
                            <?php if (!empty($expense['receipt_path'])): ?>
                                <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . $expense['receipt_path']); ?>" target="_blank" class="worker-portal-button worker-portal-button-small worker-portal-button-outline view-receipt">
                                    <i class="dashicons dashicons-visibility"></i> <?php _e('Ver justificante', 'worker-portal'); ?>
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="worker-portal-badge worker-portal-badge-secondary"><?php _e('NO', 'worker-portal'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Estado:', 'worker-portal'); ?></th>
                    <td>
                        <span class="<?php echo Worker_Portal_Utils::get_expense_status_class($expense['status']); ?>">
                            <?php echo Worker_Portal_Utils::get_expense_status_name($expense['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php if (!empty($approver_name)): ?>
                    <tr>
                        <th><?php _e('Procesado por:', 'worker-portal'); ?></th>
                        <td><?php echo esc_html($approver_name); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Fecha de procesamiento:', 'worker-portal'); ?></th>
                        <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($expense['approved_date'])); ?></td>
                    </tr>
                <?php endif; ?>
            </table>
            
            <?php if ($expense['status'] === 'pending'): ?>
                <div class="worker-portal-expense-actions">
                    <button type="button" class="worker-portal-button worker-portal-button-primary approve-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                        <i class="dashicons dashicons-yes"></i> <?php _e('Aprobar', 'worker-portal'); ?>
                    </button>
                    <button type="button" class="worker-portal-button worker-portal-button-danger reject-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                        <i class="dashicons dashicons-no"></i> <?php _e('Denegar', 'worker-portal'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success($html);
    }

    /**
     * Renderiza shortcode de portal
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            HTML generado
     */
    public function render_portal_shortcode($atts) {
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            return '<div class="worker-portal-login-required">' . 
                __('Debes iniciar sesión para acceder al Portal del Trabajador.', 'worker-portal') . 
                ' <a href="' . wp_login_url(get_permalink()) . '">' . 
                __('Iniciar sesión', 'worker-portal') . 
                '</a></div>';
        }
        
        // Cargar utilidades
        require_once WORKER_PORTAL_PATH . 'includes/class-utils.php';
        
        // Determinar vista según el rol
        if (Worker_Portal_Utils::is_portal_admin()) {
            // Incluir plantilla de administrador
            include(WORKER_PORTAL_PATH . 'public/partials/portal-page-admin.php');
        } else {
            // Incluir plantilla de trabajador
            include(WORKER_PORTAL_PATH . 'public/partials/portal-page.php');
        }
        
        // Capturar la salida
        $content = ob_get_clean();
        return $content;
    }

    /**
     * Renderiza shortcode de gastos
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            HTML generado
     */
    public function render_expenses_shortcode($atts) {
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            return '<div class="worker-portal-login-required">' . 
                __('Debes iniciar sesión para ver tus gastos.', 'worker-portal') . 
                ' <a href="' . wp_login_url(get_permalink()) . '">' . 
                __('Iniciar sesión', 'worker-portal') . 
                '</a></div>';
        }
        
        // Atributos por defecto del shortcode
        $atts = shortcode_atts(
            array(
                'limit' => 10,  // Número de gastos a mostrar
                'show_form' => 'yes'  // Mostrar formulario para añadir gastos
            ),
            $atts,
            'worker_expenses'
        );
        
        // Obtener el usuario actual
        $user_id = get_current_user_id();
        
        // Obtener los gastos del usuario
        global $wpdb;
        $expenses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}worker_expenses 
                 WHERE user_id = %d 
                 ORDER BY report_date DESC 
                 LIMIT %d",
                $user_id,
                intval($atts['limit'])
            ),
            ARRAY_A
        );
        
        // Obtener los tipos de gastos disponibles
        $expense_types = get_option('worker_portal_expense_types', array(
            'km' => __('Kilometraje', 'worker-portal'),
            'hours' => __('Horas de desplazamiento', 'worker-portal'),
            'meal' => __('Dietas', 'worker-portal'),
            'other' => __('Otros', 'worker-portal')
        ));
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir plantilla de gastos
        include(WORKER_PORTAL_PATH . 'public/partials/expenses-view.php');
        
        // Devolver contenido
        return ob_get_clean();
    }

    /**
     * Renderiza shortcode de documentos
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            HTML generado
     */
public function render_documents_shortcode($atts) {
    // Verificar que el usuario está logueado
    if (!is_user_logged_in()) {
        return '<div class="worker-portal-login-required">' . 
            __('Debes iniciar sesión para ver tus documentos.', 'worker-portal') . 
            ' <a href="' . wp_login_url(get_permalink()) . '">' . 
            __('Iniciar sesión', 'worker-portal') . 
            '</a></div>';
    }
    
    // Atributos por defecto del shortcode
    $atts = shortcode_atts(
        array(
            'limit' => 10,     // Número de documentos a mostrar
            'category' => ''   // Categoría de documentos a mostrar
        ),
        $atts,
        'worker_documents'
    );
    
    // Iniciar buffer de salida
    ob_start();
    
    // Incluir plantilla mejorada (reemplaza la ruta si la ubicación es diferente)
    include(WORKER_PORTAL_PATH . 'modules/documents/templates/enhanced-documents-view.php');
    
    // Retornar el contenido
    return ob_get_clean();
}

    /**
     * Renderiza shortcode de hojas de trabajo
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            HTML generado
     */
    public function render_worksheets_shortcode($atts) {
        // Si el usuario no está logueado, mostrar mensaje de error
        if (!is_user_logged_in()) {
            return '<div class="worker-portal-error">' . __('Debes iniciar sesión para ver tus hojas de trabajo.', 'worker-portal') . '</div>';
        }
        
        // Cargar módulo de hojas de trabajo
        require_once WORKER_PORTAL_PATH . 'modules/worksheets/class-worksheets.php';
        $worksheets_module = new Worker_Portal_Module_Worksheets();
        
        // Atributos por defecto
        $atts = shortcode_atts(
            array(
                'limit' => 10,     // Número de hojas a mostrar
                'show_form' => 'yes'  // Mostrar formulario para añadir hojas
            ),
            $atts,
            'worker_worksheets'
        );
        
        // Obtener el usuario actual
        $user_id = get_current_user_id();
        
        // Obtener las hojas de trabajo del usuario
        $worksheets = $worksheets_module->get_user_worksheets($user_id, $atts['limit']);
        
        // Obtener proyectos disponibles
        $projects = $worksheets_module->get_available_projects();
        
        // Obtener configuración
        $system_types = get_option('worker_portal_system_types', array(
            'estructura_techo' => __('Estructura en techo continuo de PYL', 'worker-portal'),
            'estructura_tabique' => __('Estructura en tabique o trasdosado', 'worker-portal'),
            'aplacado_simple' => __('Aplacado 1 placa en tabique/trasdosado', 'worker-portal'),
            'aplacado_doble' => __('Aplacado 2 placas en tabique/trasdosado', 'worker-portal'),
            'horas_ayuda' => __('Horas de ayudas, descargas, etc.', 'worker-portal')
        ));
        
        $unit_types = get_option('worker_portal_unit_types', array(
            'm2' => __('Metros cuadrados', 'worker-portal'),
            'h' => __('Horas', 'worker-portal')
        ));
        
        $difficulty_levels = get_option('worker_portal_difficulty_levels', array(
            'baja' => __('Baja', 'worker-portal'),
            'media' => __('Media', 'worker-portal'),
            'alta' => __('Alta', 'worker-portal')
        ));
        
        // Iniciar buffer de salida
        ob_start();
        
        // Incluir plantilla
        include(WORKER_PORTAL_PATH . 'modules/worksheets/templates/worksheets-view.php');
        
        // Retornar el contenido
        return ob_get_clean();
    }

    /**
     * Renderiza shortcode de incentivos
     *
     * @since    1.0.0
     * @param    array    $atts    Atributos del shortcode
     * @return   string            HTML generado
     */
    public function render_incentives_shortcode($atts) {
        return '<div class="worker-portal-section-placeholder">' . 
            __('Sección de Incentivos (Próximamente)', 'worker-portal') . 
            '</div>';
    }
}