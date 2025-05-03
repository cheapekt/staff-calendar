<?php
/**
 * Funciones AJAX para el módulo de trabajadores
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar las funciones AJAX del módulo de trabajadores
 */
class Worker_Portal_Worker_Ajax_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Acciones AJAX para trabajadores
        add_action('wp_ajax_update_worker_profile', array($this, 'update_worker_profile'));
        add_action('wp_ajax_update_worker_password', array($this, 'update_worker_password'));
        
        // Acciones AJAX para administradores
        add_action('wp_ajax_add_new_worker', array($this, 'add_new_worker'));
        add_action('wp_ajax_get_worker_details', array($this, 'get_worker_details'));
        add_action('wp_ajax_get_worker_edit_form', array($this, 'get_worker_edit_form'));
        add_action('wp_ajax_update_worker', array($this, 'update_worker'));
        add_action('wp_ajax_change_worker_status', array($this, 'change_worker_status'));
        add_action('wp_ajax_save_worker_settings', array($this, 'save_worker_settings'));
        add_action('wp_ajax_export_workers', array($this, 'export_workers'));
    }
    
    /**
     * Actualiza el perfil del trabajador
     */
    public function update_worker_profile() {
        // Verificar nonce
        check_ajax_referer('worker_profile_nonce', 'nonce');
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('No estás autorizado para realizar esta acción.', 'worker-portal'));
        }
        
        // Obtener datos del usuario actual
        $user_id = get_current_user_id();
        
        // Obtener datos del formulario
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $address = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
        
        // Validar email
        if (!is_email($email)) {
            wp_send_json_error(__('El email no es válido.', 'worker-portal'));
        }
        
        // Actualizar email (verificando que no exista ya)
        $current_user = wp_get_current_user();
        if ($email !== $current_user->user_email) {
            if (email_exists($email)) {
                wp_send_json_error(__('Este email ya está siendo utilizado por otro usuario.', 'worker-portal'));
            }
            
            wp_update_user(array(
                'ID' => $user_id,
                'user_email' => $email
            ));
        }
        
        // Actualizar datos
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'phone', $phone);
        update_user_meta($user_id, 'address', $address);
        
        // Actualizar display_name
        $display_name = trim($first_name . ' ' . $last_name);
        if (!empty($display_name)) {
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $display_name
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Perfil actualizado correctamente.', 'worker-portal')
        ));
    }
    
    /**
     * Actualiza la contraseña del trabajador
     */
    public function update_worker_password() {
        // Verificar nonce
        check_ajax_referer('worker_password_nonce', 'nonce');
        
        // Verificar que el usuario está logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(__('No estás autorizado para realizar esta acción.', 'worker-portal'));
        }
        
        // Obtener datos del usuario actual
        $user_id = get_current_user_id();
        $user = get_user_by('id', $user_id);
        
        // Obtener datos del formulario
        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // Validar datos
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            wp_send_json_error(__('Todos los campos son obligatorios.', 'worker-portal'));
        }
        
        // Verificar contraseña actual
        if (!wp_check_password($current_password, $user->data->user_pass, $user_id)) {
            wp_send_json_error(__('La contraseña actual es incorrecta.', 'worker-portal'));
        }
        
        // Verificar que las contraseñas coinciden
        if ($new_password !== $confirm_password) {
            wp_send_json_error(__('Las contraseñas no coinciden.', 'worker-portal'));
        }
        
        // Validar fortaleza de contraseña si está habilitado
        if (get_option('worker_portal_enforce_strong_passwords', '1') === '1') {
            if (strlen($new_password) < 8 || 
                !preg_match('/[A-Z]/', $new_password) || 
                !preg_match('/[a-z]/', $new_password) || 
                !preg_match('/[0-9]/', $new_password) || 
                !preg_match('/[!,%,&,@,#,$,^,*,?,_,~]/', $new_password)) {
                
                wp_send_json_error(__('La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas, números y símbolos.', 'worker-portal'));
            }
        }
        
        // Actualizar contraseña
        wp_set_password($new_password, $user_id);
        
        // Enviar notificación si está habilitado
        if (get_option('worker_portal_notify_on_password_change', '1') === '1') {
            $subject = sprintf(__('[%s] Tu contraseña ha sido cambiada', 'worker-portal'), get_bloginfo('name'));
            
            $message = sprintf(
                __('Hola %s,

Tu contraseña ha sido cambiada correctamente.

Si no has solicitado este cambio, por favor contacta inmediatamente con el administrador.

Saludos,
%s', 'worker-portal'),
                $user->display_name,
                get_bloginfo('name')
            );
            
            wp_mail($user->user_email, $subject, $message);
        }
        
        // Registrar fecha de cambio de contraseña
        update_user_meta($user_id, 'password_changed_date', current_time('mysql'));
        
        wp_send_json_success(array(
            'message' => __('Contraseña actualizada correctamente.', 'worker-portal')
        ));
    }
    
    /**
     * Añade un nuevo trabajador
     */
    public function add_new_worker() {
        // Verificar nonce
        check_ajax_referer('worker_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'worker-portal'));
        }
        
        // Obtener datos del formulario
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $address = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : 'subscriber';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        $send_notification = isset($_POST['send_notification']) && $_POST['send_notification'] === '1';
        
        // Validar datos obligatorios
        if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($password)) {
            wp_send_json_error(__('Todos los campos marcados con * son obligatorios.', 'worker-portal'));
        }
        
        // Validar email
        if (!is_email($email)) {
            wp_send_json_error(__('El email no es válido.', 'worker-portal'));
        }
        
        // Verificar que las contraseñas coinciden
        if ($password !== $confirm_password) {
            wp_send_json_error(__('Las contraseñas no coinciden.', 'worker-portal'));
        }
        
        // Validar fortaleza de contraseña si está habilitado
        if (get_option('worker_portal_enforce_strong_passwords', '1') === '1') {
            if (strlen($password) < 8 || 
                !preg_match('/[A-Z]/', $password) || 
                !preg_match('/[a-z]/', $password) || 
                !preg_match('/[0-9]/', $password) || 
                !preg_match('/[!,%,&,@,#,$,^,*,?,_,~]/', $password)) {
                
                wp_send_json_error(__('La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas, números y símbolos.', 'worker-portal'));
            }
        }
        
        // Verificar que el usuario no exista
        if (username_exists($username)) {
            wp_send_json_error(__('Este nombre de usuario ya está registrado.', 'worker-portal'));
        }
        
        // Verificar que el email no exista
        if (email_exists($email)) {
            wp_send_json_error(__('Este email ya está registrado.', 'worker-portal'));
        }
        
// Crear usuario
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }
        
        // Actualizar datos adicionales
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'phone', $phone);
        update_user_meta($user_id, 'address', $address);
        update_user_meta($user_id, 'nif', $username); // Guardar NIF/NIE como meta
        update_user_meta($user_id, 'registration_date', current_time('mysql'));
        update_user_meta($user_id, 'worker_status', 'active');
        
        // Actualizar display_name
        $display_name = trim($first_name . ' ' . $last_name);
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $display_name
        ));
        
        // Asignar rol
        $user = new WP_User($user_id);
        $user->set_role($role);
        
        // Enviar notificación al nuevo usuario
        if ($send_notification) {
            $subject = sprintf(__('[%s] Cuenta creada', 'worker-portal'), get_bloginfo('name'));
            
            $message = sprintf(
                __('Hola %s,

Te damos la bienvenida al Portal del Trabajador de %s.

Se ha creado una cuenta para ti con los siguientes detalles:

Usuario: %s
Contraseña: %s

Puedes acceder al portal en: %s

Por favor, cambia tu contraseña después del primer acceso.

Saludos,
%s', 'worker-portal'),
                $display_name,
                get_bloginfo('name'),
                $username,
                $password,
                site_url('/portal-del-trabajador/'),
                get_bloginfo('name')
            );
            
            wp_mail($email, $subject, $message);
        }
        
        // Notificar al administrador si está configurado
        if (get_option('worker_portal_notify_on_registration', '1') === '1') {
            $admin_email = get_option('worker_portal_admin_email', get_option('admin_email'));
            
            $subject = sprintf(__('[%s] Nuevo trabajador registrado', 'worker-portal'), get_bloginfo('name'));
            
            $message = sprintf(
                __('Hola,

Se ha registrado un nuevo trabajador en el Portal del Trabajador:

Nombre: %s
Email: %s
Usuario: %s
Rol: %s

Puedes gestionar a los trabajadores en el panel de administración.

Saludos,
%s', 'worker-portal'),
                $display_name,
                $email,
                $username,
                $role === 'supervisor' ? __('Supervisor', 'worker-portal') : __('Trabajador', 'worker-portal'),
                get_bloginfo('name')
            );
            
            wp_mail($admin_email, $subject, $message);
        }
        
        wp_send_json_success(array(
            'message' => __('Trabajador añadido correctamente.', 'worker-portal'),
            'user_id' => $user_id
        ));
    }
    
    /**
     * Obtiene los detalles de un trabajador
     */
    public function get_worker_details() {
        // Verificar nonce
        check_ajax_referer('worker_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'worker-portal'));
        }
        
        // Obtener ID del trabajador
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if ($user_id <= 0) {
            wp_send_json_error(__('ID de usuario no válido.', 'worker-portal'));
        }
        
        // Obtener datos del usuario
        $user = get_userdata($user_id);
        
        if (!$user) {
            wp_send_json_error(__('Usuario no encontrado.', 'worker-portal'));
        }
        
        // Obtener metadatos
        $phone = get_user_meta($user_id, 'phone', true);
        $address = get_user_meta($user_id, 'address', true);
        $nif = get_user_meta($user_id, 'nif', true);
        $registration_date = get_user_meta($user_id, 'registration_date', true);
        $status = get_user_meta($user_id, 'worker_status', true);
        $last_login = get_user_meta($user_id, 'last_login', true);
        
        if (empty($registration_date)) {
            $registration_date = $user->user_registered;
        }
        
        if (empty($status)) {
            $status = 'active';
        }
        
        // Obtener rol legible
        $role = '';
        if (in_array('supervisor', $user->roles)) {
            $role = __('Supervisor', 'worker-portal');
        } else {
            $role = __('Trabajador', 'worker-portal');
        }
        
        // Obtener estadísticas
        global $wpdb;
        
        // Contar documentos del usuario
        $documents_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}worker_documents WHERE user_id = %d",
            $user_id
        ));
        
        // Contar gastos del usuario
        $expenses_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses WHERE user_id = %d",
            $user_id
        ));
        
        // Contar gastos pendientes
        $pending_expenses = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses WHERE user_id = %d AND status = 'pending'",
            $user_id
        ));
        
        // Contar hojas de trabajo del usuario
        $worksheets_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}worker_worksheets WHERE user_id = %d",
            $user_id
        ));
        
        // Contar hojas pendientes
        $pending_worksheets = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}worker_worksheets WHERE user_id = %d AND status = 'pending'",
            $user_id
        ));
        
        // Calcular incentivos totales del usuario
        $incentives_amount = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}worker_incentives WHERE user_id = %d AND status = 'approved'",
            $user_id
        ));
        $incentives_amount = $incentives_amount ? $incentives_amount : 0;
        
        // Obtener fichajes recientes si existe la tabla
        $time_entries = array();
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}time_clock_entries'") == "{$wpdb->prefix}time_clock_entries") {
            $time_entries = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}time_clock_entries WHERE user_id = %d ORDER BY clock_in DESC LIMIT 5",
                $user_id
            ), ARRAY_A);
        }
        
        // Obtener actividad reciente
        $recent_activity = array();
        
        // Últimos gastos
        $recent_expenses = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}worker_expenses WHERE user_id = %d ORDER BY report_date DESC LIMIT 3",
            $user_id
        ), ARRAY_A);
        
        foreach ($recent_expenses as $expense) {
            $recent_activity[] = array(
                'type' => 'expense',
                'date' => $expense['report_date'],
                'title' => sprintf(__('Gasto: %s', 'worker-portal'), $expense['description']),
                'content' => sprintf(
                    __('Importe: %s. Estado: %s', 'worker-portal'), 
                    Worker_Portal_Utils::format_expense_amount($expense['amount'], $expense['expense_type']),
                    Worker_Portal_Utils::get_expense_status_name($expense['status'])
                ),
                'id' => $expense['id']
            );
        }
        
        // Últimas hojas de trabajo
        $recent_worksheets = $wpdb->get_results($wpdb->prepare(
            "SELECT w.*, p.name as project_name FROM {$wpdb->prefix}worker_worksheets w 
             LEFT JOIN {$wpdb->prefix}worker_projects p ON w.project_id = p.id 
             WHERE w.user_id = %d ORDER BY w.work_date DESC LIMIT 3",
            $user_id
        ), ARRAY_A);
        
        foreach ($recent_worksheets as $worksheet) {
            $recent_activity[] = array(
                'type' => 'worksheet',
                'date' => $worksheet['work_date'],
                'title' => sprintf(__('Hoja de Trabajo: %s', 'worker-portal'), $worksheet['project_name']),
                'content' => sprintf(
                    __('Horas: %s. Estado: %s', 'worker-portal'), 
                    $worksheet['hours'],
                    $worksheet['status'] === 'pending' ? __('Pendiente', 'worker-portal') : __('Validada', 'worker-portal')
                ),
                'id' => $worksheet['id']
            );
        }
        
        // Últimos incentivos
        $recent_incentives = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}worker_incentives WHERE user_id = %d ORDER BY calculation_date DESC LIMIT 3",
            $user_id
        ), ARRAY_A);
        
        foreach ($recent_incentives as $incentive) {
            $recent_activity[] = array(
                'type' => 'incentive',
                'date' => $incentive['calculation_date'],
                'title' => __('Incentivo', 'worker-portal'),
                'content' => sprintf(
                    __('Descripción: %s. Importe: %s. Estado: %s', 'worker-portal'), 
                    $incentive['description'],
                    number_format($incentive['amount'], 2, ',', '.') . ' €',
                    $incentive['status'] === 'pending' ? __('Pendiente', 'worker-portal') : 
                        ($incentive['status'] === 'approved' ? __('Aprobado', 'worker-portal') : __('Rechazado', 'worker-portal'))
                ),
                'id' => $incentive['id']
            );
        }
        
        // Ordenar actividad por fecha
        usort($recent_activity, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Generar HTML
        ob_start();
        ?>
        <div class="worker-portal-worker-detail-grid">
            <div class="worker-portal-worker-main-content">
                <!-- Información básica -->
                <div class="worker-portal-profile-card">
                    <div class="worker-portal-profile-header">
                        <div class="worker-portal-profile-avatar">
                            <?php echo get_avatar($user_id, 120); ?>
                        </div>
                        <div class="worker-portal-profile-info">
                            <h4><?php echo esc_html($user->display_name); ?></h4>
                            <p class="worker-portal-profile-role"><?php echo esc_html($role); ?></p>
                            <p class="worker-portal-profile-email">
                                <i class="dashicons dashicons-email"></i> <?php echo esc_html($user->user_email); ?>
                            </p>
                            <?php if (!empty($phone)): ?>
                                <p class="worker-portal-profile-phone">
                                    <i class="dashicons dashicons-phone"></i> <?php echo esc_html($phone); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="worker-portal-profile-details">
                        <div class="worker-portal-profile-detail-item">
                            <span class="worker-portal-profile-detail-label"><?php _e('Nombre:', 'worker-portal'); ?></span>
                            <span class="worker-portal-profile-detail-value"><?php echo esc_html($user->first_name); ?></span>
                        </div>
                        
                        <div class="worker-portal-profile-detail-item">
                            <span class="worker-portal-profile-detail-label"><?php _e('Apellidos:', 'worker-portal'); ?></span>
                            <span class="worker-portal-profile-detail-value"><?php echo esc_html($user->last_name); ?></span>
                        </div>
                        
                        <div class="worker-portal-profile-detail-item">
                            <span class="worker-portal-profile-detail-label"><?php _e('NIF/NIE:', 'worker-portal'); ?></span>
                            <span class="worker-portal-profile-detail-value"><?php echo esc_html($nif); ?></span>
                        </div>
                        
                        <div class="worker-portal-profile-detail-item">
                            <span class="worker-portal-profile-detail-label"><?php _e('Usuario:', 'worker-portal'); ?></span>
                            <span class="worker-portal-profile-detail-value"><?php echo esc_html($user->user_login); ?></span>
                        </div>
                        
                        <div class="worker-portal-profile-detail-item">
                            <span class="worker-portal-profile-detail-label"><?php _e('Dirección:', 'worker-portal'); ?></span>
                            <span class="worker-portal-profile-detail-value"><?php echo esc_html($address); ?></span>
                        </div>
                        
                        <div class="worker-portal-profile-detail-item">
                            <span class="worker-portal-profile-detail-label"><?php _e('Fecha de alta:', 'worker-portal'); ?></span>
                            <span class="worker-portal-profile-detail-value"><?php echo date_i18n(get_option('date_format'), strtotime($registration_date)); ?></span>
                        </div>
                        
                        <div class="worker-portal-profile-detail-item">
                            <span class="worker-portal-profile-detail-label"><?php _e('Último acceso:', 'worker-portal'); ?></span>
                            <span class="worker-portal-profile-detail-value">
                                <?php 
                                if (!empty($last_login)) {
                                    echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_login));
                                } else {
                                    echo __('No disponible', 'worker-portal');
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div class="worker-portal-profile-detail-item">
                            <span class="worker-portal-profile-detail-label"><?php _e('Estado:', 'worker-portal'); ?></span>
                            <span class="worker-portal-profile-detail-value">
                                <?php if ($status === 'active'): ?>
                                    <span class="worker-portal-badge worker-portal-badge-success"><?php _e('Activo', 'worker-portal'); ?></span>
                                <?php else: ?>
                                    <span class="worker-portal-badge worker-portal-badge-danger"><?php _e('Inactivo', 'worker-portal'); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="worker-portal-profile-actions">
                        <button type="button" class="worker-portal-button worker-portal-button-primary edit-worker" data-user-id="<?php echo esc_attr($user_id); ?>">
                            <i class="dashicons dashicons-edit"></i> <?php _e('Editar', 'worker-portal'); ?>
                        </button>
                        
                        <?php if ($status === 'active'): ?>
                            <button type="button" class="worker-portal-button worker-portal-button-danger deactivate-worker" data-user-id="<?php echo esc_attr($user_id); ?>">
                                <i class="dashicons dashicons-lock"></i> <?php _e('Desactivar', 'worker-portal'); ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="worker-portal-button worker-portal-button-success activate-worker" data-user-id="<?php echo esc_attr($user_id); ?>">
                                <i class="dashicons dashicons-unlock"></i> <?php _e('Activar', 'worker-portal'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="worker-portal-button worker-portal-button-secondary reset-password" data-user-id="<?php echo esc_attr($user_id); ?>">
                            <i class="dashicons dashicons-admin-network"></i> <?php _e('Restablecer contraseña', 'worker-portal'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Actividad reciente -->
                <div class="worker-portal-profile-section">
                    <h3><i class="dashicons dashicons-list-view"></i> <?php _e('Actividad Reciente', 'worker-portal'); ?></h3>
                    
                    <div class="worker-portal-profile-activity">
                        <?php if (empty($recent_activity)): ?>
                            <p class="worker-portal-no-data"><?php _e('No hay actividad reciente.', 'worker-portal'); ?></p>
                        <?php else: ?>
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="worker-portal-activity-card">
                                    <div class="worker-portal-activity-header">
                                        <h4 class="worker-portal-activity-title"><?php echo esc_html($activity['title']); ?></h4>
                                        <span class="worker-portal-activity-date"><?php echo date_i18n(get_option('date_format'), strtotime($activity['date'])); ?></span>
                                    </div>
                                    <div class="worker-portal-activity-content">
                                        <p><?php echo esc_html($activity['content']); ?></p>
                                    </div>
                                    <div class="worker-portal-activity-footer">
                                        <span class="worker-portal-activity-type">
                                            <?php 
                                            switch ($activity['type']) {
                                                case 'expense':
                                                    echo '<i class="dashicons dashicons-money-alt"></i> ' . __('Gasto', 'worker-portal');
                                                    break;
                                                case 'worksheet':
                                                    echo '<i class="dashicons dashicons-clipboard"></i> ' . __('Hoja de Trabajo', 'worker-portal');
                                                    break;
                                                case 'incentive':
                                                    echo '<i class="dashicons dashicons-star-filled"></i> ' . __('Incentivo', 'worker-portal');
                                                    break;
                                            }
                                            ?>
                                        </span>
                                        <a href="#" class="worker-portal-activity-link view-activity" 
                                           data-type="<?php echo esc_attr($activity['type']); ?>" 
                                           data-id="<?php echo esc_attr($activity['id']); ?>">
                                            <?php _e('Ver detalles', 'worker-portal'); ?> →
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Fichajes recientes -->
                <?php if (!empty($time_entries)): ?>
                <div class="worker-portal-profile-section">
                    <h3><i class="dashicons dashicons-clock"></i> <?php _e('Fichajes Recientes', 'worker-portal'); ?></h3>
                    
                    <div class="worker-portal-table-responsive">
                        <table class="worker-portal-table worker-portal-time-entries-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Fecha', 'worker-portal'); ?></th>
                                    <th><?php _e('Entrada', 'worker-portal'); ?></th>
                                    <th><?php _e('Salida', 'worker-portal'); ?></th>
                                    <th><?php _e('Duración', 'worker-portal'); ?></th>
                                    <th><?php _e('Estado', 'worker-portal'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($time_entries as $entry): 
                                    // Calcular duración
                                    $duration = '';
                                    if (!empty($entry['clock_out'])) {
                                        $start_time = strtotime($entry['clock_in']);
                                        $end_time = strtotime($entry['clock_out']);
                                        $diff = $end_time - $start_time;
                                        
                                        $hours = floor($diff / 3600);
                                        $minutes = floor(($diff % 3600) / 60);
                                        
                                        $duration = sprintf('%02d:%02d', $hours, $minutes);
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo date_i18n(get_option('date_format'), strtotime($entry['clock_in'])); ?></td>
                                        <td><?php echo date_i18n(get_option('time_format'), strtotime($entry['clock_in'])); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($entry['clock_out'])) {
                                                echo date_i18n(get_option('time_format'), strtotime($entry['clock_out']));
                                            } else {
                                                echo '<span class="worker-portal-badge worker-portal-badge-warning">' . __('Activo', 'worker-portal') . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo $duration ?: '-'; ?></td>
                                        <td>
                                            <?php 
                                            $status_class = 'worker-portal-badge ';
                                            $status_text = '';
                                            
                                            if (empty($entry['clock_out'])) {
                                                $status_class .= 'worker-portal-badge-warning';
                                                $status_text = __('Activo', 'worker-portal');
                                            } elseif ($entry['status'] === 'edited') {
                                                $status_class .= 'worker-portal-badge-secondary';
                                                $status_text = __('Editado', 'worker-portal');
                                            } else {
                                                $status_class .= 'worker-portal-badge-success';
                                                $status_text = __('Completado', 'worker-portal');
                                            }
                                            
                                            echo '<span class="' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span>';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="worker-portal-worker-sidebar">
                <!-- Estadísticas -->
                <div class="worker-portal-profile-section">
                    <h3><?php _e('Estadísticas', 'worker-portal'); ?></h3>
                    
                    <div class="worker-portal-profile-stats">
                        <div class="worker-portal-profile-stat">
                            <div class="worker-portal-profile-stat-value"><?php echo esc_html($documents_count); ?></div>
                            <div class="worker-portal-profile-stat-label"><?php _e('Documentos', 'worker-portal'); ?></div>
                        </div>
                        
                        <div class="worker-portal-profile-stat">
                            <div class="worker-portal-profile-stat-value"><?php echo esc_html($expenses_count); ?></div>
                            <div class="worker-portal-profile-stat-label"><?php _e('Gastos', 'worker-portal'); ?></div>
                        </div>
                        
                        <div class="worker-portal-profile-stat">
                            <div class="worker-portal-profile-stat-value"><?php echo esc_html($pending_expenses); ?></div>
                            <div class="worker-portal-profile-stat-label"><?php _e('Gastos Pendientes', 'worker-portal'); ?></div>
                        </div>
                        
                        <div class="worker-portal-profile-stat">
                            <div class="worker-portal-profile-stat-value"><?php echo esc_html($worksheets_count); ?></div>
                            <div class="worker-portal-profile-stat-label"><?php _e('Hojas de Trabajo', 'worker-portal'); ?></div>
                        </div>
                        
                        <div class="worker-portal-profile-stat">
                            <div class="worker-portal-profile-stat-value"><?php echo esc_html($pending_worksheets); ?></div>
                            <div class="worker-portal-profile-stat-label"><?php _e('Hojas Pendientes', 'worker-portal'); ?></div>
                        </div>
                        
                        <div class="worker-portal-profile-stat">
                            <div class="worker-portal-profile-stat-value"><?php echo number_format($incentives_amount, 2, ',', '.'); ?> €</div>
                            <div class="worker-portal-profile-stat-label"><?php _e('Incentivos Totales', 'worker-portal'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Accesos rápidos -->
                <div class="worker-portal-profile-section">
                    <h3><?php _e('Accesos Rápidos', 'worker-portal'); ?></h3>
                    
                    <div class="worker-portal-quick-links">
                        <a href="#" class="worker-portal-button worker-portal-button-outline view-worker-expenses" data-user-id="<?php echo esc_attr($user_id); ?>">
                            <i class="dashicons dashicons-money-alt"></i> <?php _e('Ver gastos', 'worker-portal'); ?>
                        </a>
                        
                        <a href="#" class="worker-portal-button worker-portal-button-outline view-worker-worksheets" data-user-id="<?php echo esc_attr($user_id); ?>">
                            <i class="dashicons dashicons-clipboard"></i> <?php _e('Ver hojas de trabajo', 'worker-portal'); ?>
                        </a>
                        
                        <a href="#" class="worker-portal-button worker-portal-button-outline view-worker-documents" data-user-id="<?php echo esc_attr($user_id); ?>">
                            <i class="dashicons dashicons-media-document"></i> <?php _e('Ver documentos', 'worker-portal'); ?>
                        </a>
                        
                        <a href="#" class="worker-portal-button worker-portal-button-outline view-worker-incentives" data-user-id="<?php echo esc_attr($user_id); ?>">
                            <i class="dashicons dashicons-star-filled"></i> <?php _e('Ver incentivos', 'worker-portal'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html
        ));
    }
    
    /**
     * Obtiene el formulario de edición de un trabajador
     */
    public function get_worker_edit_form() {
        // Verificar nonce
        check_ajax_referer('worker_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'worker-portal'));
        }
        
        // Obtener ID del trabajador
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if ($user_id <= 0) {
            wp_send_json_error(__('ID de usuario no válido.', 'worker-portal'));
        }
        
        // Obtener datos del usuario
        $user = get_userdata($user_id);
        
        if (!$user) {
            wp_send_json_error(__('Usuario no encontrado.', 'worker-portal'));
        }
        
        // Obtener metadatos
        $phone = get_user_meta($user_id, 'phone', true);
        $address = get_user_meta($user_id, 'address', true);
        $nif = get_user_meta($user_id, 'nif', true);
        
        // Generar HTML
        ob_start();
        ?>
        <form id="edit-worker-form" class="worker-portal-form">
            <div class="worker-portal-form-row">
                <div class="worker-portal-form-group">
                    <label for="edit-first-name"><?php _e('Nombre:', 'worker-portal'); ?></label>
                    <input type="text" id="edit-first-name" name="first_name" value="<?php echo esc_attr($user->first_name); ?>" required>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="edit-last-name"><?php _e('Apellidos:', 'worker-portal'); ?></label>
                    <input type="text" id="edit-last-name" name="last_name" value="<?php echo