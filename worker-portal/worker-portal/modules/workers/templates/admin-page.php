<?php
/**
 * Plantilla para mostrar la gestión de trabajadores en el panel de administración
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos del usuario
if (!Worker_Portal_Utils::is_portal_admin()) {
    echo '<div class="worker-portal-error">' . 
        __('No tienes permiso para gestionar trabajadores.', 'worker-portal') . 
        '</div>';
    return;
}

// Obtener todos los usuarios trabajadores (excluyendo administradores)
$workers = get_users(array(
    'role__not_in' => array('administrator'),
    'orderby' => 'display_name',
    'order' => 'ASC'
));

// Obtener estadísticas
global $wpdb;
$active_workers_count = count($workers);
$pending_expenses = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses WHERE status = 'pending'");
$pending_worksheets = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_worksheets WHERE status = 'pending'");

// Comprobar si existe la tabla de fichajes
$active_time_entries = 0;
if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}time_clock_entries'") == "{$wpdb->prefix}time_clock_entries") {
    $active_time_entries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}time_clock_entries WHERE clock_out IS NULL");
}
?>

<div class="worker-portal-admin-workers">
    <h2><?php _e('Gestión de Trabajadores', 'worker-portal'); ?></h2>
    
    <!-- Resumen de estadísticas -->
    <div class="worker-portal-admin-stats">
        <div class="worker-portal-admin-stats-grid">
            <div class="worker-portal-admin-stat-box worker-portal-stat-workers">
                <div class="worker-portal-admin-stat-icon">
                    <i class="dashicons dashicons-groups"></i>
                </div>
                <div class="worker-portal-admin-stat-content">
                    <div class="worker-portal-admin-stat-value"><?php echo esc_html($active_workers_count); ?></div>
                    <div class="worker-portal-admin-stat-label"><?php _e('Trabajadores Activos', 'worker-portal'); ?></div>
                </div>
            </div>
            
            <div class="worker-portal-admin-stat-box worker-portal-stat-expenses">
                <div class="worker-portal-admin-stat-icon">
                    <i class="dashicons dashicons-money-alt"></i>
                </div>
                <div class="worker-portal-admin-stat-content">
                    <div class="worker-portal-admin-stat-value"><?php echo esc_html($pending_expenses); ?></div>
                    <div class="worker-portal-admin-stat-label"><?php _e('Gastos Pendientes', 'worker-portal'); ?></div>
                    <?php if ($pending_expenses > 0): ?>
                        <a href="#" class="worker-portal-admin-stat-action tab-nav-link" data-tab="pending-expenses">
                            <?php _e('Ver gastos', 'worker-portal'); ?> →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="worker-portal-admin-stat-box worker-portal-stat-worksheets">
                <div class="worker-portal-admin-stat-icon">
                    <i class="dashicons dashicons-clipboard"></i>
                </div>
                <div class="worker-portal-admin-stat-content">
                    <div class="worker-portal-admin-stat-value"><?php echo esc_html($pending_worksheets); ?></div>
                    <div class="worker-portal-admin-stat-label"><?php _e('Hojas Pendientes', 'worker-portal'); ?></div>
                    <?php if ($pending_worksheets > 0): ?>
                        <a href="#" class="worker-portal-admin-stat-action tab-nav-link" data-tab="worksheets">
                            <?php _e('Ver hojas', 'worker-portal'); ?> →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}time_clock_entries'") == "{$wpdb->prefix}time_clock_entries"): ?>
            <div class="worker-portal-admin-stat-box worker-portal-stat-active">
                <div class="worker-portal-admin-stat-icon">
                    <i class="dashicons dashicons-clock"></i>
                </div>
                <div class="worker-portal-admin-stat-content">
                    <div class="worker-portal-admin-stat-value"><?php echo esc_html($active_time_entries); ?></div>
                    <div class="worker-portal-admin-stat-label"><?php _e('Fichajes Activos', 'worker-portal'); ?></div>
                    <?php if ($active_time_entries > 0): ?>
                        <a href="#" class="worker-portal-admin-stat-action tab-nav-link" data-tab="timeclock">
                            <?php _e('Ver fichajes', 'worker-portal'); ?> →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pestañas de gestión de trabajadores -->
    <div class="worker-portal-admin-tabs-in-tabs">
        <ul class="worker-portal-admin-subtabs-nav">
            <li><a href="#" class="worker-portal-subtab-link active" data-subtab="workers-list"><?php _e('Lista de Trabajadores', 'worker-portal'); ?></a></li>
            <li><a href="#" class="worker-portal-subtab-link" data-subtab="add-worker"><?php _e('Añadir Trabajador', 'worker-portal'); ?></a></li>
            <li><a href="#" class="worker-portal-subtab-link" data-subtab="worker-settings"><?php _e('Configuración', 'worker-portal'); ?></a></li>
        </ul>
        
        <div class="worker-portal-admin-subtabs-content">
            <!-- Lista de trabajadores -->
            <div id="subtab-workers-list" class="worker-portal-subtab-content active">
                <!-- Filtros de búsqueda -->
                <div class="worker-portal-admin-filters">
                    <form id="workers-filter-form" class="worker-portal-admin-filter-form">
                        <div class="worker-portal-admin-filter-row">
                            <div class="worker-portal-admin-filter-group">
                                <label for="filter-worker-name"><?php _e('Buscar:', 'worker-portal'); ?></label>
                                <input type="text" id="filter-worker-name" name="search" placeholder="<?php _e('Buscar por nombre, email...', 'worker-portal'); ?>">
                            </div>
                            
                            <div class="worker-portal-admin-filter-group">
                                <label for="filter-worker-role"><?php _e('Rol:', 'worker-portal'); ?></label>
                                <select id="filter-worker-role" name="role">
                                    <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                                    <option value="supervisor"><?php _e('Supervisor', 'worker-portal'); ?></option>
                                    <option value="subscriber"><?php _e('Trabajador', 'worker-portal'); ?></option>
                                </select>
                            </div>
                            
                            <div class="worker-portal-admin-filter-group">
                                <label for="filter-worker-status"><?php _e('Estado:', 'worker-portal'); ?></label>
                                <select id="filter-worker-status" name="status">
                                    <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                                    <option value="active"><?php _e('Activo', 'worker-portal'); ?></option>
                                    <option value="inactive"><?php _e('Inactivo', 'worker-portal'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="worker-portal-admin-filter-actions">
                            <button type="submit" class="worker-portal-button worker-portal-button-secondary">
                                <i class="dashicons dashicons-search"></i> <?php _e('Filtrar', 'worker-portal'); ?>
                            </button>
                            <button type="button" id="clear-workers-filters" class="worker-portal-button worker-portal-button-link">
                                <?php _e('Limpiar filtros', 'worker-portal'); ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Tabla de trabajadores -->
                <div class="worker-portal-table-responsive">
                    <table class="worker-portal-admin-table" id="workers-table">
                        <thead>
                            <tr>
                                <th><?php _e('Trabajador', 'worker-portal'); ?></th>
                                <th><?php _e('Email', 'worker-portal'); ?></th>
                                <th><?php _e('Rol', 'worker-portal'); ?></th>
                                <th><?php _e('Teléfono', 'worker-portal'); ?></th>
                                <th><?php _e('Fecha de alta', 'worker-portal'); ?></th>
                                <th><?php _e('Estado', 'worker-portal'); ?></th>
                                <th><?php _e('Actividad', 'worker-portal'); ?></th>
                                <th><?php _e('Acciones', 'worker-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($workers)): ?>
                                <tr>
                                    <td colspan="8" class="worker-portal-no-data"><?php _e('No hay trabajadores registrados.', 'worker-portal'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($workers as $worker):
                                    // Obtener metadatos del usuario
                                    $phone = get_user_meta($worker->ID, 'phone', true);
                                    $status = get_user_meta($worker->ID, 'worker_status', true);
                                    if (empty($status)) {
                                        $status = 'active'; // Por defecto activo
                                    }
                                    
                                    // Obtener rol legible
                                    $role = '';
                                    if (in_array('supervisor', $worker->roles)) {
                                        $role = __('Supervisor', 'worker-portal');
                                    } else {
                                        $role = __('Trabajador', 'worker-portal');
                                    }
                                    
                                    // Obtener fecha de registro
                                    $registration_date = get_user_meta($worker->ID, 'registration_date', true);
                                    if (empty($registration_date)) {
                                        $registration_date = $worker->user_registered;
                                    }
                                    
                                    // Obtener última actividad (último login o última hoja de trabajo)
                                    $last_login = get_user_meta($worker->ID, 'last_login', true);
                                    $last_worksheet = $wpdb->get_var($wpdb->prepare(
                                        "SELECT work_date FROM {$wpdb->prefix}worker_worksheets 
                                         WHERE user_id = %d 
                                         ORDER BY work_date DESC 
                                         LIMIT 1",
                                        $worker->ID
                                    ));
                                    
                                    $last_activity = $last_login;
                                    if ($last_worksheet && strtotime($last_worksheet) > strtotime($last_login)) {
                                        $last_activity = $last_worksheet;
                                    }
                                ?>
                                    <tr data-user-id="<?php echo esc_attr($worker->ID); ?>" class="<?php echo $status === 'inactive' ? 'worker-inactive' : ''; ?>">
                                        <td class="worker-portal-worker-info">
                                            <?php echo get_avatar($worker->ID, 40); ?>
                                            <div class="worker-portal-worker-name">
                                                <strong><?php echo esc_html($worker->display_name); ?></strong>
                                                <?php if (!empty($worker->first_name) || !empty($worker->last_name)): ?>
                                                    <div class="worker-portal-worker-fullname">
                                                        <?php echo esc_html(trim($worker->first_name . ' ' . $worker->last_name)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo esc_html($worker->user_email); ?></td>
                                        <td><?php echo esc_html($role); ?></td>
                                        <td><?php echo esc_html($phone); ?></td>
                                        <td><?php echo date_i18n(get_option('date_format'), strtotime($registration_date)); ?></td>
                                        <td>
                                            <?php if ($status === 'active'): ?>
                                                <span class="worker-portal-badge worker-portal-badge-success"><?php _e('Activo', 'worker-portal'); ?></span>
                                            <?php else: ?>
                                                <span class="worker-portal-badge worker-portal-badge-danger"><?php _e('Inactivo', 'worker-portal'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($last_activity)) {
                                                echo date_i18n(get_option('date_format'), strtotime($last_activity));
                                            } else {
                                                echo '<span class="worker-portal-text-muted">' . __('Sin actividad', 'worker-portal') . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="worker-portal-actions">
                                            <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-secondary edit-worker" data-user-id="<?php echo esc_attr($worker->ID); ?>">
                                                <i class="dashicons dashicons-edit"></i>
                                            </button>
                                            <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-primary view-worker" data-user-id="<?php echo esc_attr($worker->ID); ?>">
                                                <i class="dashicons dashicons-visibility"></i>
                                            </button>
                                            <?php if ($status === 'active'): ?>
                                                <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-danger deactivate-worker" data-user-id="<?php echo esc_attr($worker->ID); ?>">
                                                    <i class="dashicons dashicons-lock"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-success activate-worker" data-user-id="<?php echo esc_attr($worker->ID); ?>">
                                                    <i class="dashicons dashicons-unlock"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Exportar trabajadores -->
                <div class="worker-portal-admin-actions">
                    <button type="button" id="export-workers-button" class="worker-portal-button worker-portal-button-secondary">
                        <i class="dashicons dashicons-download"></i> <?php _e('Exportar a Excel', 'worker-portal'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Añadir trabajador -->
            <div id="subtab-add-worker" class="worker-portal-subtab-content">
                <form id="add-worker-form" class="worker-portal-form">
                    <div class="worker-portal-form-row">
                        <div class="worker-portal-form-group">
                            <label for="worker-username"><?php _e('Usuario (NIF/NIE):', 'worker-portal'); ?> <span class="required">*</span></label>
                            <input type="text" id="worker-username" name="username" required>
                            <p class="description"><?php _e('Introduce el NIF o NIE sin espacios ni puntos.', 'worker-portal'); ?></p>
                        </div>
                        
                        <div class="worker-portal-form-group">
                            <label for="worker-email"><?php _e('Email:', 'worker-portal'); ?> <span class="required">*</span></label>
                            <input type="email" id="worker-email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="worker-portal-form-row">
                        <div class="worker-portal-form-group">
                            <label for="worker-first-name"><?php _e('Nombre:', 'worker-portal'); ?> <span class="required">*</span></label>
                            <input type="text" id="worker-first-name" name="first_name" required>
                        </div>
                        
                        <div class="worker-portal-form-group">
                            <label for="worker-last-name"><?php _e('Apellidos:', 'worker-portal'); ?> <span class="required">*</span></label>
                            <input type="text" id="worker-last-name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="worker-portal-form-row">
                        <div class="worker-portal-form-group">
                            <label for="worker-phone"><?php _e('Teléfono:', 'worker-portal'); ?></label>
                            <input type="tel" id="worker-phone" name="phone">
                        </div>
                        
                        <div class="worker-portal-form-group">
                            <label for="worker-role"><?php _e('Rol:', 'worker-portal'); ?></label>
                            <select id="worker-role" name="role">
                                <option value="subscriber"><?php _e('Trabajador', 'worker-portal'); ?></option>
                                <option value="supervisor"><?php _e('Supervisor', 'worker-portal'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="worker-portal-form-group">
                        <label for="worker-address"><?php _e('Dirección:', 'worker-portal'); ?></label>
                        <textarea id="worker-address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="worker-portal-form-row">
                        <div class="worker-portal-form-group">
                            <label for="worker-password"><?php _e('Contraseña:', 'worker-portal'); ?> <span class="required">*</span></label>
                            <input type="password" id="worker-password" name="password" required>
                            <div class="password-strength-meter"></div>
                        </div>
                        
                        <div class="worker-portal-form-group">
                            <label for="worker-confirm-password"><?php _e('Confirmar contraseña:', 'worker-portal'); ?> <span class="required">*</span></label>
                            <input type="password" id="worker-confirm-password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="worker-portal-form-group">
                        <label>
                            <input type="checkbox" name="send_notification" value="1" checked>
                            <?php _e('Enviar email de bienvenida al nuevo trabajador', 'worker-portal'); ?>
                        </label>
                    </div>
                    
                    <div class="worker-portal-form-actions">
                        <input type="hidden" name="action" value="add_new_worker">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('worker_admin_nonce'); ?>">
                        <button type="submit" class="worker-portal-button worker-portal-button-primary">
                            <i class="dashicons dashicons-plus-alt"></i> <?php _e('Añadir Trabajador', 'worker-portal'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Configuración de trabajadores -->
            <div id="subtab-worker-settings" class="worker-portal-subtab-content">
                <form id="worker-settings-form" class="worker-portal-form">
                    <h4><?php _e('Roles y Permisos', 'worker-portal'); ?></h4>
                    
                    <div class="worker-portal-form-group">
                        <label for="default-worker-role"><?php _e('Rol por defecto para nuevos trabajadores:', 'worker-portal'); ?></label>
                        <select id="default-worker-role" name="default_role">
                            <option value="subscriber" <?php selected(get_option('worker_portal_default_role', 'subscriber'), 'subscriber'); ?>><?php _e('Trabajador', 'worker-portal'); ?></option>
                            <option value="supervisor" <?php selected(get_option('worker_portal_default_role', 'subscriber'), 'supervisor'); ?>><?php _e('Supervisor', 'worker-portal'); ?></option>
                        </select>
                    </div>
                    
                    <h4><?php _e('Notificaciones', 'worker-portal'); ?></h4>
                    
                    <div class="worker-portal-form-group">
                        <label for="admin-notification-email"><?php _e('Email para notificaciones de administración:', 'worker-portal'); ?></label>
                        <input type="email" id="admin-notification-email" name="admin_email" value="<?php echo esc_attr(get_option('worker_portal_admin_email', get_option('admin_email'))); ?>">
                        <p class="description"><?php _e('Email al que se enviarán las notificaciones administrativas.', 'worker-portal'); ?></p>
                    </div>
                    
                    <div class="worker-portal-form-group">
                        <label>
                            <input type="checkbox" name="notify_on_registration" value="1" <?php checked(get_option('worker_portal_notify_on_registration', '1'), '1'); ?>>
                            <?php _e('Notificar al administrador cuando se registra un nuevo trabajador', 'worker-portal'); ?>
                        </label>
                    </div>
                    
                    <div class="worker-portal-form-group">
                        <label>
                            <input type="checkbox" name="notify_on_password_change" value="1" <?php checked(get_option('worker_portal_notify_on_password_change', '1'), '1'); ?>>
                            <?php _e('Notificar al trabajador cuando se cambie su contraseña', 'worker-portal'); ?>
                        </label>
                    </div>
                    
                    <h4><?php _e('Seguridad', 'worker-portal'); ?></h4>
                    
                    <div class="worker-portal-form-group">
                        <label>
                            <input type="checkbox" name="enforce_strong_passwords" value="1" <?php checked(get_option('worker_portal_enforce_strong_passwords', '1'), '1'); ?>>
                            <?php _e('Exigir contraseñas seguras', 'worker-portal'); ?>
                        </label>
                        <p class="description"><?php _e('Requiere que las contraseñas tengan al menos 8 caracteres, incluyan mayúsculas, minúsculas, números y símbolos.', 'worker-portal'); ?></p>
                    </div>
                    
                    <div class="worker-portal-form-group">
                        <label for="password-expiry">
                            <?php _e('Caducidad de contraseñas (días):', 'worker-portal'); ?>
                        </label>
                        <input type="number" id="password-expiry" name="password_expiry" min="0" step="1" value="<?php echo esc_attr(get_option('worker_portal_password_expiry', '90')); ?>">
                        <p class="description"><?php _e('Número de días antes de que se solicite al usuario cambiar su contraseña. 0 para desactivar.', 'worker-portal'); ?></p>
                    </div>
                    
                    <div class="worker-portal-form-actions">
                        <input type="hidden" name="action" value="save_worker_settings">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('worker_admin_nonce'); ?>">
                        <button type="submit" class="worker-portal-button worker-portal-button-primary">
                            <i class="dashicons dashicons-yes"></i> <?php _e('Guardar Configuración', 'worker-portal'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de trabajador -->
<div id="worker-details-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content worker-portal-modal-large">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Detalles del Trabajador', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="worker-details-content">
                <!-- Contenido cargado por AJAX -->
                <div class="worker-portal-loading">
                    <div class="worker-portal-spinner"></div>
                    <p><?php _e('Cargando detalles...', 'worker-portal'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar trabajador -->
<div id="edit-worker-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Editar Trabajador', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="edit-worker-content">
                <!-- Contenido cargado por AJAX -->
                <div class="worker-portal-loading">
                    <div class="worker-portal-spinner"></div>
                    <p><?php _e('Cargando formulario...', 'worker-portal'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Navegación entre pestañas
    $('.worker-portal-subtab-link').on('click', function(e) {
        e.preventDefault();
        
        // Ocultar todas las sub-pestañas
        $('.worker-portal-subtab-content').removeClass('active');
        
        // Remover clase activa de todos los enlaces
        $('.worker-portal-subtab-link').removeClass('active');
        
        // Mostrar sub-pestaña seleccionada
        var subtab = $(this).data('subtab');
        $('#subtab-' + subtab).addClass('active');
        
        // Activar enlace
        $(this).addClass('active');
    });
    
    // Filtrar trabajadores
    $('#workers-filter-form').on('submit', function(e) {
        e.preventDefault();
        filterWorkers();
    });
    
    // Limpiar filtros
    $('#clear-workers-filters').on('click', function() {
        $('#workers-filter-form')[0].reset();
        filterWorkers();
    });
    
    // Función para filtrar trabajadores en la tabla
    function filterWorkers() {
        var search = $('#filter-worker-name').val().toLowerCase();
        var role = $('#filter-worker-role').val();
        var status = $('#filter-worker-status').val();
        
        $('#workers-table tbody tr').each(function() {
            var $row = $(this);
            var showRow = true;
            
            // Filtrar por nombre/email
            if (search) {
                var text = $row.text().toLowerCase();
                if (text.indexOf(search) === -1) {
                    showRow = false;
                }
            }
            
            // Filtrar por rol
            if (role && showRow) {
                var workerRole = '';
                if (role === 'supervisor') {
                    workerRole = '<?php _e('Supervisor', 'worker-portal'); ?>';
                } else {
                    workerRole = '<?php _e('Trabajador', 'worker-portal'); ?>';
                }
                
                if ($row.find('td:nth-child(3)').text() !== workerRole) {
                    showRow = false;
                }
            }
            
            // Filtrar por estado
            if (status && showRow) {
                var isActive = !$row.hasClass('worker-inactive');
                if ((status === 'active' && !isActive) || (status === 'inactive' && isActive)) {
                    showRow = false;
                }
            }
            
            // Mostrar u ocultar fila
            $row.toggle(showRow);
        });
        
        // Mostrar mensaje si no hay resultados
        var visibleRows = $('#workers-table tbody tr:visible').length;
        if (visibleRows === 0) {
            if ($('#no-results-row').length === 0) {
                $('#workers-table tbody').append(
                    '<tr id="no-results-row"><td colspan="8" class="worker-portal-no-data">' +
                    '<?php _e('No se encontraron trabajadores con los criterios seleccionados.', 'worker-portal'); ?>' +
                    '</td></tr>'
                );
            }
        } else {
            $('#no-results-row').remove();
        }
    }
    
// Exportar trabajadores a Excel
    $('#export-workers-button').on('click', function() {
        // Mostrar indicador de carga
        $(this).prop('disabled', true).html(
            '<i class="dashicons dashicons-update-alt spinning"></i> <?php _e('Exportando...', 'worker-portal'); ?>'
        );
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_workers',
                nonce: $('#worker-settings-form input[name="nonce"]').val(),
                search: $('#filter-worker-name').val(),
                role: $('#filter-worker-role').val(),
                status: $('#filter-worker-status').val()
            },
            success: function(response) {
                if (response.success) {
                    // Crear enlace para descargar
                    var link = document.createElement('a');
                    link.href = response.data.file_url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert(response.data || '<?php _e('Error al exportar trabajadores', 'worker-portal'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'); ?>');
            },
            complete: function() {
                // Restaurar botón
                $('#export-workers-button').prop('disabled', false).html(
                    '<i class="dashicons dashicons-download"></i> <?php _e('Exportar a Excel', 'worker-portal'); ?>'
                );
            }
        });
    });
    
    // Añadir nuevo trabajador
    $('#add-worker-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validar contraseñas
        var password = $('#worker-password').val();
        var confirmPassword = $('#worker-confirm-password').val();
        
        if (password !== confirmPassword) {
            alert('<?php _e('Las contraseñas no coinciden.', 'worker-portal'); ?>');
            return;
        }
        
        // Validar fortaleza de contraseña
        if ($('#enforce-strong-passwords').is(':checked')) {
            var strongRegex = new RegExp("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})");
            if (!strongRegex.test(password)) {
                alert('<?php _e('La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas, números y símbolos.', 'worker-portal'); ?>');
                return;
            }
        }
        
        var formData = new FormData(this);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#add-worker-form button[type="submit"]').prop('disabled', true).html(
                    '<i class="dashicons dashicons-update-alt spinning"></i> <?php _e('Guardando...', 'worker-portal'); ?>'
                );
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#add-worker-form')[0].reset();
                    
                    // Recargar la página para mostrar el nuevo trabajador
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'); ?>');
            },
            complete: function() {
                $('#add-worker-form button[type="submit"]').prop('disabled', false).html(
                    '<i class="dashicons dashicons-plus-alt"></i> <?php _e('Añadir Trabajador', 'worker-portal'); ?>'
                );
            }
        });
    });
    
    // Guardar configuración
    $('#worker-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#worker-settings-form button[type="submit"]').prop('disabled', true).html(
                    '<i class="dashicons dashicons-update-alt spinning"></i> <?php _e('Guardando...', 'worker-portal'); ?>'
                );
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'); ?>');
            },
            complete: function() {
                $('#worker-settings-form button[type="submit"]').prop('disabled', false).html(
                    '<i class="dashicons dashicons-yes"></i> <?php _e('Guardar Configuración', 'worker-portal'); ?>'
                );
            }
        });
    });
    
    // Ver detalles de trabajador
    $(document).on('click', '.view-worker', function() {
        var userId = $(this).data('user-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_worker_details',
                user_id: userId,
                nonce: $('#worker-settings-form input[name="nonce"]').val()
            },
            beforeSend: function() {
                $('#worker-details-content').html(
                    '<div class="worker-portal-loading">' +
                    '<div class="worker-portal-spinner"></div>' +
                    '<p><?php _e('Cargando detalles...', 'worker-portal'); ?></p>' +
                    '</div>'
                );
                $('#worker-details-modal').fadeIn();
            },
            success: function(response) {
                if (response.success) {
                    $('#worker-details-content').html(response.data.html);
                } else {
                    $('#worker-details-content').html(
                        '<div class="worker-portal-error">' + 
                        (response.data || '<?php _e('Error al cargar detalles del trabajador', 'worker-portal'); ?>') + 
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#worker-details-content').html(
                    '<div class="worker-portal-error">' + 
                    '<?php _e('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'); ?>' + 
                    '</div>'
                );
            }
        });
    });
    
    // Editar trabajador
    $(document).on('click', '.edit-worker', function() {
        var userId = $(this).data('user-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_worker_edit_form',
                user_id: userId,
                nonce: $('#worker-settings-form input[name="nonce"]').val()
            },
            beforeSend: function() {
                $('#edit-worker-content').html(
                    '<div class="worker-portal-loading">' +
                    '<div class="worker-portal-spinner"></div>' +
                    '<p><?php _e('Cargando formulario...', 'worker-portal'); ?></p>' +
                    '</div>'
                );
                $('#edit-worker-modal').fadeIn();
            },
            success: function(response) {
                if (response.success) {
                    $('#edit-worker-content').html(response.data.html);
                    
                    // Inicializar el formulario de edición
                    initEditWorkerForm();
                } else {
                    $('#edit-worker-content').html(
                        '<div class="worker-portal-error">' + 
                        (response.data || '<?php _e('Error al cargar formulario de edición', 'worker-portal'); ?>') + 
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#edit-worker-content').html(
                    '<div class="worker-portal-error">' + 
                    '<?php _e('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'); ?>' + 
                    '</div>'
                );
            }
        });
    });
    
    // Inicializar formulario de edición de trabajador
    function initEditWorkerForm() {
        $('#edit-worker-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#edit-worker-form button[type="submit"]').prop('disabled', true).html(
                        '<i class="dashicons dashicons-update-alt spinning"></i> <?php _e('Guardando...', 'worker-portal'); ?>'
                    );
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#edit-worker-modal').fadeOut();
                        
                        // Recargar la página para mostrar los cambios
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('<?php _e('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'); ?>');
                },
                complete: function() {
                    $('#edit-worker-form button[type="submit"]').prop('disabled', false).html(
                        '<i class="dashicons dashicons-yes"></i> <?php _e('Guardar Cambios', 'worker-portal'); ?>'
                    );
                }
            });
        });
    }
    
    // Activar/Desactivar trabajador
    $(document).on('click', '.activate-worker, .deactivate-worker', function() {
        var userId = $(this).data('user-id');
        var action = $(this).hasClass('activate-worker') ? 'activate' : 'deactivate';
        var confirmMsg = action === 'activate' 
            ? '<?php _e('¿Estás seguro de que deseas activar a este trabajador?', 'worker-portal'); ?>'
            : '<?php _e('¿Estás seguro de que deseas desactivar a este trabajador?', 'worker-portal'); ?>';
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'change_worker_status',
                user_id: userId,
                status: action,
                nonce: $('#worker-settings-form input[name="nonce"]').val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    
                    // Recargar la página para mostrar los cambios
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'); ?>');
            }
        });
    });
    
    // Medidor de fortaleza de contraseña
    $('#worker-password').on('keyup', function() {
        var password = $(this).val();
        var strength = 0;
        
        // Si la contraseña es mayor a 6 caracteres, sumar puntos
        if (password.length >= 6) strength += 1;
        
        // Si la contraseña tiene letras minúsculas y mayúsculas, sumar puntos
        if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 1;
        
        // Si la contraseña tiene números, sumar puntos
        if (password.match(/([0-9])/)) strength += 1;
        
        // Si la contraseña tiene caracteres especiales, sumar puntos
        if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1;
        
        // Mostrar el indicador de fuerza
        var strengthMeter = $('.password-strength-meter');
        
        if (strength < 2) {
            strengthMeter.html('<?php _e('Débil', 'worker-portal'); ?>').css('color', 'red');
        } else if (strength === 2) {
            strengthMeter.html('<?php _e('Regular', 'worker-portal'); ?>').css('color', 'orange');
        } else if (strength === 3) {
            strengthMeter.html('<?php _e('Buena', 'worker-portal'); ?>').css('color', 'yellowgreen');
        } else {
            strengthMeter.html('<?php _e('Fuerte', 'worker-portal'); ?>').css('color', 'green');
        }
    });
    
    // Cerrar modales
    $('.worker-portal-modal-close').on('click', function() {
        $(this).closest('.worker-portal-modal').fadeOut();
    });
    
    // Cerrar modales al hacer clic fuera
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('worker-portal-modal')) {
            $('.worker-portal-modal').fadeOut();
        }
    });
    
    // Cerrar modales con Escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.worker-portal-modal').fadeOut();
        }
    });
});
</script>