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
        add_action('wp_ajax_reset_worker_password', array($this, 'reset_worker_password'));
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
        include(plugin_dir_path(dirname(dirname(__FILE__))) . 'modules/workers/templates/worker-details.php');
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
        include(plugin_dir_path(dirname(dirname(__FILE__))) . 'modules/workers/templates/worker-edit-form.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html
        ));
    }
    
    /**
     * Actualiza los datos de un trabajador
     */
    public function update_worker() {
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
        
        // Obtener datos del formulario
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $address = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : 'subscriber';
        $reset_password = isset($_POST['reset_password']) && $_POST['reset_password'] === '1';
        $notify_user = isset($_POST['notify_user']) && $_POST['notify_user'] === '1';
        
        // Validar email
        if (!is_email($email)) {
            wp_send_json_error(__('El email no es válido.', 'worker-portal'));
        }
        
        // Actualizar email (verificando que no exista ya)
        if ($email !== $user->user_email) {
            if (email_exists($email) && email_exists($email) != $user_id) {
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
        
        // Actualizar rol
        $user_obj = new WP_User($user_id);
        
        // Verificar si el rol actual es diferente al seleccionado
        $current_roles = $user_obj->roles;
        $current_role = in_array('supervisor', $current_roles) ? 'supervisor' : 'subscriber';
        
        if ($role !== $current_role) {
            // Eliminar roles actuales
            foreach ($current_roles as $role_name) {
                $user_obj->remove_role($role_name);
            }
            
            // Asignar nuevo rol
            $user_obj->add_role($role);
        }
        
        // Actualizar contraseña si se ha marcado
        if ($reset_password) {
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            if (empty($password)) {
                wp_send_json_error(__('La nueva contraseña no puede estar vacía.', 'worker-portal'));
            }
            
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
            
            // Actualizar contraseña
            wp_set_password($password, $user_id);
            
            // Enviar notificación al usuario si está habilitado
            if ($notify_user && get_option('worker_portal_notify_on_password_change', '1') === '1') {
                $subject = sprintf(__('[%s] Tu contraseña ha sido cambiada', 'worker-portal'), get_bloginfo('name'));
                
                $message = sprintf(
                    __('Hola %s,

Un administrador ha cambiado tu contraseña en el Portal del Trabajador.

Tu nueva contraseña es: %s

Te recomendamos cambiarla por una de tu elección la próxima vez que inicies sesión.

Saludos,
%s', 'worker-portal'),
                    $display_name,
                    $password,
                    get_bloginfo('name')
                );
                
                wp_mail($email, $subject, $message);
            }
        }
 // Enviar notificación al administrador si está habilitado
        if ($notify_user && get_option('worker_portal_notify_on_profile_update', '1') === '1') {
            $admin_email = get_option('worker_portal_admin_email', get_option('admin_email'));
            
            $subject = sprintf(__('[%s] Perfil de trabajador actualizado', 'worker-portal'), get_bloginfo('name'));
            
            $message = sprintf(
                __('Hola,

Un administrador ha actualizado el perfil del trabajador %s (%s).

Datos actualizados:
- Nombre: %s %s
- Email: %s
- Teléfono: %s
- Rol: %s

Saludos,
%s', 'worker-portal'),
                $user->display_name,
                $email,
                $first_name,
                $last_name,
                $email,
                $phone,
                $role === 'supervisor' ? __('Supervisor', 'worker-portal') : __('Trabajador', 'worker-portal'),
                get_bloginfo('name')
            );
            
            wp_mail($admin_email, $subject, $message);
        }
        
        wp_send_json_success(array(
            'message' => __('Datos del trabajador actualizados correctamente.', 'worker-portal')
        ));
    }
    
    /**
     * Cambia el estado de un trabajador (activo/inactivo)
     */
    public function change_worker_status() {
        // Verificar nonce
        check_ajax_referer('worker_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'worker-portal'));
        }
        
        // Obtener datos
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if ($user_id <= 0) {
            wp_send_json_error(__('ID de usuario no válido.', 'worker-portal'));
        }
        
        if (!in_array($status, array('activate', 'deactivate'))) {
            wp_send_json_error(__('Estado no válido.', 'worker-portal'));
        }
        
        // Obtener datos del usuario
        $user = get_userdata($user_id);
        
        if (!$user) {
            wp_send_json_error(__('Usuario no encontrado.', 'worker-portal'));
        }
        
        // Cambiar estado
        $new_status = $status === 'activate' ? 'active' : 'inactive';
        update_user_meta($user_id, 'worker_status', $new_status);
        
        // Enviar notificación al usuario
        $subject = sprintf(
            __('[%s] Tu cuenta ha sido %s', 'worker-portal'),
            get_bloginfo('name'),
            $new_status === 'active' ? __('activada', 'worker-portal') : __('desactivada', 'worker-portal')
        );
        
        $message = sprintf(
            __('Hola %s,

Tu cuenta en el Portal del Trabajador ha sido %s por un administrador.

%s

Saludos,
%s', 'worker-portal'),
            $user->display_name,
            $new_status === 'active' ? __('activada', 'worker-portal') : __('desactivada', 'worker-portal'),
            $new_status === 'active' ? 
                __('Ahora puedes acceder normalmente al portal.', 'worker-portal') : 
                __('Ya no podrás acceder al portal hasta que tu cuenta sea reactivada.', 'worker-portal'),
            get_bloginfo('name')
        );
        
        wp_mail($user->user_email, $subject, $message);
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('El trabajador ha sido %s correctamente.', 'worker-portal'),
                $new_status === 'active' ? __('activado', 'worker-portal') : __('desactivado', 'worker-portal')
            )
        ));
    }
    
    /**
     * Guarda la configuración de trabajadores
     */
    public function save_worker_settings() {
        // Verificar nonce
        check_ajax_referer('worker_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'worker-portal'));
        }
        
        // Obtener y guardar datos
        $default_role = isset($_POST['default_role']) ? sanitize_text_field($_POST['default_role']) : 'subscriber';
        $admin_email = isset($_POST['admin_email']) ? sanitize_email($_POST['admin_email']) : '';
        $notify_on_registration = isset($_POST['notify_on_registration']) && $_POST['notify_on_registration'] === '1' ? '1' : '0';
        $notify_on_password_change = isset($_POST['notify_on_password_change']) && $_POST['notify_on_password_change'] === '1' ? '1' : '0';
        $enforce_strong_passwords = isset($_POST['enforce_strong_passwords']) && $_POST['enforce_strong_passwords'] === '1' ? '1' : '0';
        $password_expiry = isset($_POST['password_expiry']) ? intval($_POST['password_expiry']) : 90;
        
        // Validar email
        if (!empty($admin_email) && !is_email($admin_email)) {
            wp_send_json_error(__('El email de notificación no es válido.', 'worker-portal'));
        }
        
        // Actualizar opciones
        update_option('worker_portal_default_role', $default_role);
        update_option('worker_portal_admin_email', $admin_email);
        update_option('worker_portal_notify_on_registration', $notify_on_registration);
        update_option('worker_portal_notify_on_password_change', $notify_on_password_change);
        update_option('worker_portal_enforce_strong_passwords', $enforce_strong_passwords);
        update_option('worker_portal_password_expiry', $password_expiry);
        
        wp_send_json_success(array(
            'message' => __('Configuración guardada correctamente.', 'worker-portal')
        ));
    }
    
    /**
     * Exporta datos de trabajadores a Excel
     */
    public function export_workers() {
        // Verificar nonce
        check_ajax_referer('worker_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'worker-portal'));
        }
        
        // Obtener filtros
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        // Obtener trabajadores
        $args = array(
            'role__not_in' => array('administrator'),
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => 'all_with_meta'
        );
        
        // Aplicar filtro de rol
        if (!empty($role)) {
            $args['role'] = $role;
        }
        
        $workers = get_users($args);
        
        // Filtrar por búsqueda y estado
        if (!empty($search) || !empty($status)) {
            foreach ($workers as $key => $worker) {
                $worker_data = array(
                    'username' => $worker->user_login,
                    'email' => $worker->user_email,
                    'display_name' => $worker->display_name,
                    'first_name' => $worker->first_name,
                    'last_name' => $worker->last_name
                );
                
                // Filtrar por búsqueda
                if (!empty($search)) {
                    $found = false;
                    foreach ($worker_data as $field) {
                        if (stripos($field, $search) !== false) {
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        unset($workers[$key]);
                        continue;
                    }
                }
                
                // Filtrar por estado
                if (!empty($status)) {
                    $worker_status = get_user_meta($worker->ID, 'worker_status', true);
                    if (empty($worker_status)) {
                        $worker_status = 'active'; // Por defecto activo
                    }
                    
                    if (($status === 'active' && $worker_status !== 'active') ||
                        ($status === 'inactive' && $worker_status !== 'inactive')) {
                        unset($workers[$key]);
                    }
                }
            }
        }
        
        // Si no hay trabajadores, mostrar error
        if (empty($workers)) {
            wp_send_json_error(__('No hay trabajadores con los criterios seleccionados.', 'worker-portal'));
        }
        
        // Crear archivo CSV
        $filename = 'trabajadores_' . date('Y-m-d') . '.csv';
        $upload_dir = wp_upload_dir();
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        $fp = fopen($filepath, 'w');
        
        // Encabezados
        fputcsv($fp, array(
            __('NIF/NIE', 'worker-portal'),
            __('Nombre', 'worker-portal'),
            __('Apellidos', 'worker-portal'),
            __('Email', 'worker-portal'),
            __('Teléfono', 'worker-portal'),
            __('Dirección', 'worker-portal'),
            __('Rol', 'worker-portal'),
            __('Estado', 'worker-portal'),
            __('Fecha de registro', 'worker-portal'),
            __('Último acceso', 'worker-portal')
        ));
        
        // Datos
        foreach ($workers as $worker) {
            $status = get_user_meta($worker->ID, 'worker_status', true);
            if (empty($status)) {
                $status = 'active'; // Por defecto activo
            }
            
            $role = in_array('supervisor', $worker->roles) ? __('Supervisor', 'worker-portal') : __('Trabajador', 'worker-portal');
            
            $registration_date = get_user_meta($worker->ID, 'registration_date', true);
            if (empty($registration_date)) {
                $registration_date = $worker->user_registered;
            }
            
            $last_login = get_user_meta($worker->ID, 'last_login', true);
            
            fputcsv($fp, array(
                get_user_meta($worker->ID, 'nif', true),
                $worker->first_name,
                $worker->last_name,
                $worker->user_email,
                get_user_meta($worker->ID, 'phone', true),
                get_user_meta($worker->ID, 'address', true),
                $role,
                $status === 'active' ? __('Activo', 'worker-portal') : __('Inactivo', 'worker-portal'),
                $registration_date,
                $last_login
            ));
        }
        
        fclose($fp);
        
        // Devolver URL del archivo
        wp_send_json_success(array(
            'file_url' => $upload_dir['url'] . '/' . $filename,
            'filename' => $filename
        ));
    }
    
    /**
     * Restablece la contraseña de un trabajador
     */
    public function reset_worker_password() {
        // Verificar nonce
        check_ajax_referer('worker_admin_nonce', 'nonce');
        
        // Verificar permisos
        if (!Worker_Portal_Utils::is_portal_admin()) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'worker-portal'));
        }
        
        // Obtener datos
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if ($user_id <= 0) {
            wp_send_json_error(__('ID de usuario no válido.', 'worker-portal'));
        }
        
        if (empty($password)) {
            wp_send_json_error(__('La contraseña no puede estar vacía.', 'worker-portal'));
        }
        
        // Obtener datos del usuario
        $user = get_userdata($user_id);
        
        if (!$user) {
            wp_send_json_error(__('Usuario no encontrado.', 'worker-portal'));
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
        
        // Actualizar contraseña
        wp_set_password($password, $user_id);
        
        // Enviar notificación al usuario si está habilitado
        if (get_option('worker_portal_notify_on_password_change', '1') === '1') {
            $subject = sprintf(__('[%s] Tu contraseña ha sido restablecida', 'worker-portal'), get_bloginfo('name'));
            
            $message = sprintf(
                __('Hola %s,

Un administrador ha restablecido tu contraseña en el Portal del Trabajador.

Tu nueva contraseña es: %s

Te recomendamos cambiarla por una de tu elección la próxima vez que inicies sesión.

Saludos,
%s', 'worker-portal'),
                $user->display_name,
                $password,
                get_bloginfo('name')
            );
            
            wp_mail($user->user_email, $subject, $message);
        }
        
        // Registrar fecha de cambio de contraseña
        update_user_meta($user_id, 'password_changed_date', current_time('mysql'));
        
        wp_send_json_success(array(
            'message' => __('Contraseña restablecida correctamente.', 'worker-portal')
        ));
    }
}       