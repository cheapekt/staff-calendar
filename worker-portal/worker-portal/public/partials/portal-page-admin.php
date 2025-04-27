<?php
/**
 * Plantilla principal del portal del trabajador (vista de administrador)
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Verificar que el usuario tiene permisos de administrador
if (!Worker_Portal_Utils::is_portal_admin()) {
    wp_die(__('No tienes permisos para acceder a esta página.', 'worker-portal'));
}

// Definir contenido específico para esta vista
ob_start();
?>

<div class="worker-portal-admin-dashboard">
    <!-- Pestañas de navegación de administrador -->
    <div class="worker-portal-admin-tabs">
        <ul class="worker-portal-admin-tabs-nav">
            <li>
                <a href="#" class="worker-portal-tab-link active" data-tab="dashboard">
                    <i class="dashicons dashicons-dashboard"></i> 
                    <?php _e('Dashboard', 'worker-portal'); ?>
                </a>
            </li>
            <li>
                <a href="#" class="worker-portal-tab-link" data-tab="pending-expenses">
                    <i class="dashicons dashicons-money-alt"></i> 
                    <?php _e('Gastos Pendientes', 'worker-portal'); ?>
                    <?php 
                    // Obtener número de gastos pendientes
                    global $wpdb;
                    $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses WHERE status = 'pending'");
                    if ($pending_count > 0): 
                    ?>
                    <span class="worker-portal-pending-count"><?php echo esc_html($pending_count); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="#" class="worker-portal-tab-link" data-tab="worksheets">
                    <i class="dashicons dashicons-clipboard"></i> 
                    <?php _e('Hojas de Trabajo', 'worker-portal'); ?>
                    <?php 
                    // Obtener número de hojas pendientes
                    $worksheets_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_worksheets WHERE status = 'pending'");
                    if ($worksheets_count > 0): 
                    ?>
                    <span class="worker-portal-pending-count"><?php echo esc_html($worksheets_count); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="#" class="worker-portal-tab-link" data-tab="documents">
                    <i class="dashicons dashicons-media-document"></i> 
                    <?php _e('Documentos', 'worker-portal'); ?>
                </a>
            </li>
            <li>
                <a href="#" class="worker-portal-tab-link" data-tab="incentives">
                    <i class="dashicons dashicons-star-filled"></i> 
                    <?php _e('Incentivos', 'worker-portal'); ?>
                </a>
            </li>
            <li>
                <a href="#" class="worker-portal-tab-link" data-tab="workers">
                    <i class="dashicons dashicons-groups"></i> 
                    <?php _e('Trabajadores', 'worker-portal'); ?>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Contenido de cada pestaña -->
    <div class="worker-portal-admin-tabs-content">
        <!-- Dashboard -->
        <div id="tab-dashboard" class="worker-portal-tab-content active">
            <h2><?php _e('Dashboard', 'worker-portal'); ?></h2>
            
            <div class="worker-portal-admin-stats">
                <div class="worker-portal-admin-stats-grid">
                    <!-- Estadísticas de Gastos -->
                    <div class="worker-portal-admin-stat-box worker-portal-stat-expenses">
                        <div class="worker-portal-admin-stat-icon">
                            <i class="dashicons dashicons-money-alt"></i>
                        </div>
                        <div class="worker-portal-admin-stat-content">
                            <div class="worker-portal-admin-stat-value"><?php echo $pending_count; ?></div>
                            <div class="worker-portal-admin-stat-label"><?php _e('Gastos Pendientes', 'worker-portal'); ?></div>
                            <?php if ($pending_count > 0): ?>
                                <a href="#" class="worker-portal-admin-stat-action" data-tab="pending-expenses">
                                    <?php _e('Ver gastos', 'worker-portal'); ?> →
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Estadísticas de Hojas de Trabajo -->
                    <div class="worker-portal-admin-stat-box worker-portal-stat-worksheets">
                        <div class="worker-portal-admin-stat-icon">
                            <i class="dashicons dashicons-clipboard"></i>
                        </div>
                        <div class="worker-portal-admin-stat-content">
                            <div class="worker-portal-admin-stat-value"><?php echo $worksheets_count; ?></div>
                            <div class="worker-portal-admin-stat-label"><?php _e('Hojas Pendientes', 'worker-portal'); ?></div>
                            <?php if ($worksheets_count > 0): ?>
                                <a href="#" class="worker-portal-admin-stat-action" data-tab="worksheets">
                                    <?php _e('Ver hojas', 'worker-portal'); ?> →
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Estadísticas de Trabajadores -->
                    <?php 
                    $workers_count = count(Worker_Portal_Utils::get_supervised_users());
                    ?>
                    <div class="worker-portal-admin-stat-box worker-portal-stat-workers">
                        <div class="worker-portal-admin-stat-icon">
                            <i class="dashicons dashicons-groups"></i>
                        </div>
                        <div class="worker-portal-admin-stat-content">
                            <div class="worker-portal-admin-stat-value"><?php echo $workers_count; ?></div>
                            <div class="worker-portal-admin-stat-label"><?php _e('Trabajadores', 'worker-portal'); ?></div>
                            <a href="#" class="worker-portal-admin-stat-action" data-tab="workers">
                                <?php _e('Ver trabajadores', 'worker-portal'); ?> →
                            </a>
                        </div>
                    </div>
                    
                    <!-- Estadísticas de Documentos -->
                    <?php 
                    $documents_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_documents");
                    ?>
                    <div class="worker-portal-admin-stat-box worker-portal-stat-documents">
                        <div class="worker-portal-admin-stat-icon">
                            <i class="dashicons dashicons-media-document"></i>
                        </div>
                        <div class="worker-portal-admin-stat-content">
                            <div class="worker-portal-admin-stat-value"><?php echo $documents_count; ?></div>
                            <div class="worker-portal-admin-stat-label"><?php _e('Documentos', 'worker-portal'); ?></div>
                            <a href="#" class="worker-portal-admin-stat-action" data-tab="documents">
                                <?php _e('Ver documentos', 'worker-portal'); ?> →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actividad reciente -->
            <div class="worker-portal-admin-recent-activity">
                <h3><?php _e('Actividad Reciente', 'worker-portal'); ?></h3>
                
                <div class="worker-portal-admin-tabs-in-tabs">
                    <ul class="worker-portal-admin-subtabs-nav">
                        <li><a href="#" class="worker-portal-subtab-link active" data-subtab="recent-expenses"><?php _e('Gastos', 'worker-portal'); ?></a></li>
                        <li><a href="#" class="worker-portal-subtab-link" data-subtab="recent-worksheets"><?php _e('Hojas de Trabajo', 'worker-portal'); ?></a></li>
                    </ul>
                    
                    <div class="worker-portal-admin-subtabs-content">
                        <!-- Gastos recientes -->
                        <div id="subtab-recent-expenses" class="worker-portal-subtab-content active">
                            <?php
                            // Obtener gastos recientes
                            $recent_expenses = $wpdb->get_results(
                                "SELECT e.*, u.display_name 
                                 FROM {$wpdb->prefix}worker_expenses e 
                                 LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID 
                                 ORDER BY e.report_date DESC LIMIT 5",
                                ARRAY_A
                            );
                            
                            if (empty($recent_expenses)):
                            ?>
                                <p class="worker-portal-no-items"><?php _e('No hay gastos recientes.', 'worker-portal'); ?></p>
                            <?php else: ?>
                                <table class="worker-portal-admin-table">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Fecha', 'worker-portal'); ?></th>
                                            <th><?php _e('Trabajador', 'worker-portal'); ?></th>
                                            <th><?php _e('Tipo', 'worker-portal'); ?></th>
                                            <th><?php _e('Importe', 'worker-portal'); ?></th>
                                            <th><?php _e('Estado', 'worker-portal'); ?></th>
                                            <th><?php _e('Acciones', 'worker-portal'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_expenses as $expense): ?>
                                            <tr>
                                                <td><?php echo date_i18n(get_option('date_format'), strtotime($expense['report_date'])); ?></td>
                                                <td><?php echo esc_html($expense['display_name']); ?></td>
                                                <td>
                                                    <?php 
                                                    $expense_types = get_option('worker_portal_expense_types', array());
                                                    echo isset($expense_types[$expense['expense_type']]) 
                                                        ? esc_html($expense_types[$expense['expense_type']]) 
                                                        : esc_html($expense['expense_type']); 
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php echo Worker_Portal_Utils::format_expense_amount($expense['amount'], $expense['expense_type']); ?>
                                                </td>
                                                <td>
                                                    <span class="<?php echo Worker_Portal_Utils::get_expense_status_class($expense['status']); ?>">
                                                        <?php echo Worker_Portal_Utils::get_expense_status_name($expense['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($expense['status'] === 'pending'): ?>
                                                        <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-primary approve-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                                            <i class="dashicons dashicons-yes"></i>
                                                        </button>
                                                        <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-danger reject-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                                            <i class="dashicons dashicons-no"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-secondary view-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                                        <i class="dashicons dashicons-visibility"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <p class="worker-portal-view-all">
                                    <a href="#" class="worker-portal-button worker-portal-button-outline" data-tab="pending-expenses">
                                        <?php _e('Ver todos los gastos', 'worker-portal'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Hojas de trabajo recientes -->
                        <div id="subtab-recent-worksheets" class="worker-portal-subtab-content">
                            <?php
                            // Obtener hojas de trabajo recientes
                            $recent_worksheets = $wpdb->get_results(
                                "SELECT w.*, u.display_name, p.name as project_name 
                                 FROM {$wpdb->prefix}worker_worksheets w 
                                 LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID 
                                 LEFT JOIN {$wpdb->prefix}worker_projects p ON w.project_id = p.id
                                 ORDER BY w.work_date DESC LIMIT 5",
                                ARRAY_A
                            );
                            
                            if (empty($recent_worksheets)):
                            ?>
                                <p class="worker-portal-no-items"><?php _e('No hay hojas de trabajo recientes.', 'worker-portal'); ?></p>
                            <?php else: ?>
                                <table class="worker-portal-admin-table">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Fecha', 'worker-portal'); ?></th>
                                            <th><?php _e('Trabajador', 'worker-portal'); ?></th>
                                            <th><?php _e('Proyecto', 'worker-portal'); ?></th>
                                            <th><?php _e('Horas', 'worker-portal'); ?></th>
                                            <th><?php _e('Estado', 'worker-portal'); ?></th>
                                            <th><?php _e('Acciones', 'worker-portal'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_worksheets as $worksheet): ?>
                                            <tr>
                                                <td><?php echo date_i18n(get_option('date_format'), strtotime($worksheet['work_date'])); ?></td>
                                                <td><?php echo esc_html($worksheet['display_name']); ?></td>
                                                <td><?php echo esc_html($worksheet['project_name']); ?></td>
                                                <td><?php echo esc_html($worksheet['hours']); ?> <?php _e('h', 'worker-portal'); ?></td>
                                                <td>
                                                    <span class="<?php echo $worksheet['status'] === 'pending' ? 'worker-portal-badge worker-portal-badge-warning' : 'worker-portal-badge worker-portal-badge-success'; ?>">
                                                        <?php echo $worksheet['status'] === 'pending' ? __('Pendiente', 'worker-portal') : __('Validada', 'worker-portal'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($worksheet['status'] === 'pending'): ?>
                                                        <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-primary validate-worksheet" data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                                                            <i class="dashicons dashicons-yes"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-secondary view-worksheet" data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                                                        <i class="dashicons dashicons-visibility"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <p class="worker-portal-view-all">
                                    <a href="#" class="worker-portal-button worker-portal-button-outline" data-tab="worksheets">
                                        <?php _e('Ver todas las hojas de trabajo', 'worker-portal'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gastos Pendientes -->
        <div id="tab-pending-expenses" class="worker-portal-tab-content">
            <h2><?php _e('Gastos Pendientes', 'worker-portal'); ?></h2>
            
            <!-- Filtros de gastos -->
            <div class="worker-portal-admin-filters">
                <form id="admin-expenses-filter-form" class="worker-portal-admin-filter-form">
                    <div class="worker-portal-admin-filter-row">
                        <div class="worker-portal-admin-filter-group">
                            <label for="filter-worker"><?php _e('Trabajador:', 'worker-portal'); ?></label>
                            <select id="filter-worker" name="user_id">
                                <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                                <?php 
                                $workers = get_users();
                                $workers = array_filter($workers, function($user) {
                                    return !user_can($user->ID, 'manage_options'); // Excluir administradores
                                });                                foreach ($workers as $worker): 
                                ?>
                                    <option value="<?php echo esc_attr($worker->ID); ?>"><?php echo esc_html($worker->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="worker-portal-admin-filter-group">
                            <label for="filter-type"><?php _e('Tipo:', 'worker-portal'); ?></label>
                            <select id="filter-type" name="expense_type">
                                <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                                <?php foreach ($expense_types as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="worker-portal-admin-filter-group">
                            <label for="filter-date-from"><?php _e('Desde:', 'worker-portal'); ?></label>
                            <input type="date" id="filter-date-from" name="date_from">
                        </div>
                        
                        <div class="worker-portal-admin-filter-group">
                            <label for="filter-date-to"><?php _e('Hasta:', 'worker-portal'); ?></label>
                            <input type="date" id="filter-date-to" name="date_to">
                        </div>
                    </div>
                    
                    <div class="worker-portal-admin-filter-actions">
                        <button type="submit" class="worker-portal-button worker-portal-button-secondary">
                            <i class="dashicons dashicons-search"></i> <?php _e('Filtrar', 'worker-portal'); ?>
                        </button>
                        <button type="button" id="clear-filters" class="worker-portal-button worker-portal-button-link">
                            <?php _e('Limpiar filtros', 'worker-portal'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Acciones masivas -->
            <div class="worker-portal-admin-bulk-actions">
                <form id="bulk-approve-form">
                    <div class="worker-portal-admin-bulk-actions-row">
                        <div class="worker-portal-admin-bulk-action-select">
                            <label for="bulk-action"><?php _e('Acción masiva:', 'worker-portal'); ?></label>
                            <select id="bulk-action" name="bulk_action">
                                <option value=""><?php _e('Seleccionar acción', 'worker-portal'); ?></option>
                                <option value="approve"><?php _e('Aprobar seleccionados', 'worker-portal'); ?></option>
                                <option value="reject"><?php _e('Denegar seleccionados', 'worker-portal'); ?></option>
                            </select>
                        </div>
                        
                        <div class="worker-portal-admin-bulk-action-apply">
                            <button type="submit" class="worker-portal-button worker-portal-button-primary" id="apply-bulk-action" disabled>
                                <?php _e('Aplicar', 'worker-portal'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Lista de gastos pendientes -->
            <div id="pending-expenses-list-container">
                <!-- Esta sección se cargará vía AJAX -->
                <div class="worker-portal-loading">
                    <div class="worker-portal-spinner"></div>
                    <p><?php _e('Cargando gastos...', 'worker-portal'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Hojas de Trabajo -->
        <div id="tab-worksheets" class="worker-portal-tab-content">
            <h2><?php _e('Hojas de Trabajo', 'worker-portal'); ?></h2>
            
            <!-- Filtros de hojas de trabajo -->
            <div class="worker-portal-admin-filters">
                <form id="admin-worksheets-filter-form" class="worker-portal-admin-filter-form">
                    <div class="worker-portal-admin-filter-row">
                        <div class="worker-portal-admin-filter-group">
                            <label for="filter-worker-ws"><?php _e('Trabajador:', 'worker-portal'); ?></label>
                            <select id="filter-worker-ws" name="user_id">
                                <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                                <?php foreach ($workers as $worker): ?>
                                    <option value="<?php echo esc_attr($worker->ID); ?>"><?php echo esc_html($worker->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="worker-portal-admin-filter-group">
                            <label for="filter-project"><?php _e('Proyecto:', 'worker-portal'); ?></label>
                            <select id="filter-project" name="project_id">
                                <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                                <?php 
                                $projects = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}worker_projects WHERE status = 'active'", ARRAY_A);
                                foreach ($projects as $project): 
                                ?>
                                    <option value="<?php echo esc_attr($project['id']); ?>"><?php echo esc_html($project['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="worker-portal-admin-filter-group">
                            <label for="filter-status-ws"><?php _e('Estado:', 'worker-portal'); ?></label>
                            <select id="filter-status-ws" name="status">
                                <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                                <option value="pending"><?php _e('Pendiente', 'worker-portal'); ?></option>
                                <option value="validated"><?php _e('Validada', 'worker-portal'); ?></option>
                            </select>
                        </div>
                        
                        <div class="worker-portal-admin-filter-group">
                            <label for="filter-date-from-ws"><?php _e('Desde:', 'worker-portal'); ?></label>
                            <input type="date" id="filter-date-from-ws" name="date_from">
                        </div>
                        
                        <div class="worker-portal-admin-filter-group">
                            <label for="filter-date-to-ws"><?php _e('Hasta:', 'worker-portal'); ?></label>
                            <input type="date" id="filter-date-to-ws" name="date_to">
                        </div>
                    </div>
                    
                    <div class="worker-portal-admin-filter-actions">
                        <button type="submit" class="worker-portal-button worker-portal-button-secondary">
                            <i class="dashicons dashicons-search"></i> <?php _e('Filtrar', 'worker-portal'); ?>
                        </button>
                        <button type="button" id="clear-filters-ws" class="worker-portal-button worker-portal-button-link">
                            <?php _e('Limpiar filtros', 'worker-portal'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Lista de hojas de trabajo -->
            <div id="worksheets-list-container">
                <!-- Esta sección se cargará vía AJAX -->
                <div class="worker-portal-loading">
                    <div class="worker-portal-spinner"></div>
                    <p><?php _e('Cargando hojas de trabajo...3', 'worker-portal'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Documentos -->
        <div id="tab-documents" class="worker-portal-tab-content">
            <h2><?php _e('Gestión de Documentos', 'worker-portal'); ?></h2>
            
            <!-- Subir nuevo documento -->
            <div class="worker-portal-admin-upload-document">
                <h3><?php _e('Subir Nuevo Documento', 'worker-portal'); ?></h3>
                
                <form id="admin-upload-document-form" class="worker-portal-form" enctype="multipart/form-data">
                    <div class="worker-portal-form-row">
                        <div class="worker-portal-form-group">
                            <label for="document-title"><?php _e('Título:', 'worker-portal'); ?></label>
                            <input type="text" id="document-title" name="title" required>
                        </div>
                        
                        <div class="worker-portal-form-group">
                            <label for="document-category"><?php _e('Categoría:', 'worker-portal'); ?></label>
                            <select id="document-category" name="category">
                                <option value="payroll"><?php _e('Nómina', 'worker-portal'); ?></option>
                                <option value="contract"><?php _e('Contrato', 'worker-portal'); ?></option>
                                <option value="communication"><?php _e('Comunicación', 'worker-portal'); ?></option>
                                <option value="other"><?php _e('Otro', 'worker-portal'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="worker-portal-form-row">
                        <div class="worker-portal-form-group">
                            <label for="document-users"><?php _e('Destinatarios:', 'worker-portal'); ?></label>
                            <select id="document-users" name="users[]" multiple>
                                <option value="all"><?php _e('Todos los trabajadores', 'worker-portal'); ?></option>
                                <?php foreach ($workers as $worker): ?>
                                    <option value="<?php echo esc_attr($worker->ID); ?>"><?php echo esc_html($worker->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Mantén presionada la tecla Ctrl (o Cmd en Mac) para seleccionar múltiples usuarios.', 'worker-portal'); ?></p>
                        </div>
                        
                        <div class="worker-portal-form-group">
                            <label for="document-file"><?php _e('Archivo (PDF):', 'worker-portal'); ?></label>
                            <input type="file" id="document-file" name="document" accept=".pdf" required>
                        </div>
                    </div>
                    
                    <div class="worker-portal-form-group">
                        <label for="document-description"><?php _e('Descripción:', 'worker-portal'); ?></label>
                        <textarea id="document-description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="worker-portal-form-actions">
                        <button type="submit" class="worker-portal-button worker-portal-button-primary">
                            <i class="dashicons dashicons-upload"></i> <?php _e('Subir Documento', 'worker-portal'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Lista de documentos -->
            <div class="worker-portal-admin-documents-list">
                <h3><?php _e('Documentos Subidos', 'worker-portal'); ?></h3>
                
                <!-- Filtros de documentos -->
                <div class="worker-portal-admin-filters">
                    <form id="admin-documents-filter-form" class="worker-portal-admin-filter-form">
                        <div class="worker-portal-admin-filter-row">
                            <div class="worker-portal-admin-filter-group">
                                <label for="filter-category"><?php _e('Categoría:', 'worker-portal'); ?></label>
                                <select id="filter-category" name="category">
                                    <option value=""><?php _e('Todas', 'worker-portal'); ?></option>
                                    <option value="payroll"><?php _e('Nómina', 'worker-portal'); ?></option>
                                    <option value="contract"><?php _e('Contrato', 'worker-portal'); ?></option>
                                    <option value="communication"><?php _e('Comunicación', 'worker-portal'); ?></option>
                                    <option value="other"><?php _e('Otro', 'worker-portal'); ?></option>
                                </select>
                            </div>
                            
                            <div class="worker-portal-admin-filter-group">
                                <label for="filter-worker-doc"><?php _e('Trabajador:', 'worker-portal'); ?></label>
                                <select id="filter-worker-doc" name="user_id">
                                    <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                                    <?php foreach ($workers as $worker): ?>
                                        <option value="<?php echo esc_attr($worker->ID); ?>"><?php echo esc_html($worker->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="worker-portal-admin-filter-actions">
                                <button type="submit" class="worker-portal-button worker-portal-button-secondary">
                                    <i class="dashicons dashicons-search"></i> <?php _e('Filtrar', 'worker-portal'); ?>
                                </button>
                                <button type="button" id="clear-filters-doc" class="worker-portal-button worker-portal-button-link">
                                    <?php _e('Limpiar filtros', 'worker-portal'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div id="documents-list-container">
                    <!-- Esta sección se cargará vía AJAX -->
                    <div class="worker-portal-loading">
                        <div class="worker-portal-spinner"></div>
                        <p><?php _e('Cargando documentos...', 'worker-portal'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Incentivos -->
        <div id="tab-incentives" class="worker-portal-tab-content">
            <h2><?php _e('Gestión de Incentivos', 'worker-portal'); ?></h2>
            
            <!-- Esta sección se implementará en el futuro -->
            <div class="worker-portal-coming-soon">
                <p><?php _e('La funcionalidad de gestión de incentivos estará disponible próximamente.', 'worker-portal'); ?></p>
            </div>
        </div>
        
        <!-- Trabajadores -->
        <div id="tab-workers" class="worker-portal-tab-content">
            <h2><?php _e('Gestión de Trabajadores', 'worker-portal'); ?></h2>
            
            <!-- Lista de trabajadores -->
            <div class="worker-portal-admin-workers-list">
                <h3><?php _e('Trabajadores', 'worker-portal'); ?></h3>
                
                <?php if (empty($workers)): ?>
                    <p class="worker-portal-no-items"><?php _e('No hay trabajadores registrados.', 'worker-portal'); ?></p>
                <?php else: ?>
                    <table class="worker-portal-admin-table">
                        <thead>
                            <tr>
                                <th><?php _e('Nombre', 'worker-portal'); ?></th>
                                <th><?php _e('Email', 'worker-portal'); ?></th>
                                <th><?php _e('Últimos Gastos', 'worker-portal'); ?></th>
                                <th><?php _e('Últimas Hojas', 'worker-portal'); ?></th>
                                <th><?php _e('Acciones', 'worker-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workers as $worker): 
                                // Contar gastos pendientes
                                $pending_expenses = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses 
                                     WHERE user_id = %d AND status = 'pending'",
                                    $worker->ID
                                ));
                                
                                // Contar hojas pendientes
                                $pending_worksheets = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}worker_worksheets 
                                     WHERE user_id = %d AND status = 'pending'",
                                    $worker->ID
                                ));
                            ?>
                                <tr>
                                    <td><?php echo esc_html($worker->display_name); ?></td>
                                    <td><?php echo esc_html($worker->user_email); ?></td>
                                    <td>
                                        <?php if ($pending_expenses > 0): ?>
                                            <span class="worker-portal-badge worker-portal-badge-warning">
                                                <?php echo sprintf(_n('%d pendiente', '%d pendientes', $pending_expenses, 'worker-portal'), $pending_expenses); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="worker-portal-badge worker-portal-badge-success">
                                                <?php _e('Al día', 'worker-portal'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($pending_worksheets > 0): ?>
                                            <span class="worker-portal-badge worker-portal-badge-warning">
                                                <?php echo sprintf(_n('%d pendiente', '%d pendientes', $pending_worksheets, 'worker-portal'), $pending_worksheets); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="worker-portal-badge worker-portal-badge-success">
                                                <?php _e('Al día', 'worker-portal'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-secondary view-worker" data-user-id="<?php echo esc_attr($worker->ID); ?>">
                                            <i class="dashicons dashicons-visibility"></i> <?php _e('Ver detalles', 'worker-portal'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Definir la ubicación del contenido
$content_template = 'public/partials/portal-content-admin.php';

// Guardar el contenido en un archivo temporal
if (!file_exists(WORKER_PORTAL_PATH . $content_template)) {
    file_put_contents(WORKER_PORTAL_PATH . $content_template, $content);
}

// Incluir la plantilla base
include(WORKER_PORTAL_PATH . 'public/partials/portal-base.php');