<?php
/**
 * Contenido específico para la vista de administrador
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}
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
            <li>
                <a href="#" class="worker-portal-tab-link" data-tab="calendar">
                    <i class="dashicons dashicons-calendar-alt"></i> 
                    <?php _e('Calendario', 'worker-portal'); ?>
                </a>
            </li>
            <li>
                <a href="#" class="worker-portal-tab-link" data-tab="timeclock">
                    <i class="dashicons dashicons-clock"></i> 
                    <?php _e('Fichajes', 'worker-portal'); ?>
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
                                    <a href="#" class="worker-portal-button worker-portal-button-outline tab-nav-link" data-tab="pending-expenses">
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
                                    <a href="#" class="worker-portal-button worker-portal-button-outline tab-nav-link" data-tab="worksheets">
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
                                $workers = get_users(array('role__not_in' => array('administrator')));
                                foreach ($workers as $worker): 
                                ?>
                                    <option value="<?php echo esc_attr($worker->ID); ?>"><?php echo esc_html($worker->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="worker-portal-admin-filter-group">
                            <label for="filter-type"><?php _e('Tipo:', 'worker-portal'); ?></label>
                            <select id="filter-type" name="expense_type">
                                <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                                <?php 
                                $expense_types = get_option('worker_portal_expense_types', array());
                                foreach ($expense_types as $key => $label): 
                                ?>
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
                        <button type="button" id="clear-filters-expenses" class="worker-portal-button worker-portal-button-link">
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
            <div id="pending-expenses-list-container" class="worker-portal-admin-list-container">
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
                                <?php 
                                $workers = get_users(array('role__not_in' => array('administrator')));
                                foreach ($workers as $worker): 
                                ?>
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
                                if ($projects):
                                    foreach ($projects as $project): 
                                ?>
                                    <option value="<?php echo esc_attr($project['id']); ?>"><?php echo esc_html($project['name']); ?></option>
                                <?php 
                                    endforeach;
                                endif;
                                ?>
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
            <div id="worksheets-list-container" data-nonce="<?php echo wp_create_nonce('worker_portal_ajax_nonce'); ?>">
                <div class="worker-portal-loading">
                    <div class="worker-portal-spinner"></div>
                    <p><?php _e('Cargando hojas de trabajo...', 'worker-portal'); ?></p>
                </div>
            </div>
            
            <!-- Acciones -->
            <div class="worker-portal-admin-actions">
                <button type="button" id="export-worksheets-button" class="worker-portal-button worker-portal-button-secondary">
                    <i class="dashicons dashicons-download"></i> <?php _e('Exportar a Excel', 'worker-portal'); ?>
                </button>
            </div>
        </div>
        
        <!-- Documentos -->
        <div id="tab-documents" class="worker-portal-tab-content">
            <h2><?php _e('Gestión de Documentos', 'worker-portal'); ?></h2>
            
            <!-- Pestañas de documentos -->
            <div class="worker-portal-admin-tabs-in-tabs">
                <ul class="worker-portal-admin-subtabs-nav">
                    <li><a href="#" class="worker-portal-subtab-link active" data-subtab="doc-list"><?php _e('Lista de Documentos', 'worker-portal'); ?></a></li>
                    <li><a href="#" class="worker-portal-subtab-link" data-subtab="doc-upload"><?php _e('Subir Documento', 'worker-portal'); ?></a></li>
                    <li><a href="#" class="worker-portal-subtab-link" data-subtab="doc-settings"><?php _e('Configuración', 'worker-portal'); ?></a></li>
                </ul>
                
                <div class="worker-portal-admin-subtabs-content">
                    <!-- Lista de documentos -->
                    <div id="subtab-doc-list" class="worker-portal-subtab-content active">
                        <!-- Filtros de documentos -->
                        <div class="worker-portal-admin-filters">
                            <form id="admin-documents-filter-form" class="worker-portal-admin-filter-form">
                                <div class="worker-portal-admin-filter-row">
                                    <div class="worker-portal-admin-filter-group">
                                        <label for="filter-category"><?php _e('Categoría:', 'worker-portal'); ?></label>
                                        <select id="filter-category" name="category">
                                            <option value=""><?php _e('Todas', 'worker-portal'); ?></option>
                                            <?php 
                                            $categories = get_option('worker_portal_document_categories', array(
                                                'payroll' => __('Nóminas', 'worker-portal'),
                                                'contract' => __('Contratos', 'worker-portal'),
                                                'communication' => __('Comunicaciones', 'worker-portal'),
                                                'other' => __('Otros', 'worker-portal')
                                            ));
                                            foreach ($categories as $key => $label): 
                                            ?>
                                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
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
                                    
                                    <div class="worker-portal-admin-filter-group">
                                        <label for="filter-date-from-doc"><?php _e('Desde:', 'worker-portal'); ?></label>
                                        <input type="date" id="filter-date-from-doc" name="date_from">
                                    </div>
                                    
                                    <div class="worker-portal-admin-filter-group">
                                        <label for="filter-date-to-doc"><?php _e('Hasta:', 'worker-portal'); ?></label>
                                        <input type="date" id="filter-date-to-doc" name="date_to">
                                    </div>
                                </div>
                                
                                <div class="worker-portal-admin-filter-actions">
                                    <button type="submit" class="worker-portal-button worker-portal-button-secondary">
                                        <i class="dashicons dashicons-search"></i> <?php _e('Filtrar', 'worker-portal'); ?>
                                    </button>
                                    <button type="button" id="clear-filters-doc" class="worker-portal-button worker-portal-button-link">
                                        <?php _e('Limpiar filtros', 'worker-portal'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <!-- Lista de documentos -->
                        <div id="documents-list-container" data-nonce="<?php echo wp_create_nonce('worker_portal_documents_nonce'); ?>">
                            <div class="worker-portal-loading">
                                <div class="worker-portal-spinner"></div>
                                <p><?php _e('Cargando documentos...', 'worker-portal'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Subir documento -->
                    <div id="subtab-doc-upload" class="worker-portal-subtab-content">
                        <h3><?php _e('Subir Nuevo Documento', 'worker-portal'); ?></h3>
                        
                        <form id="upload-document-form" class="worker-portal-form">
                            <div class="worker-portal-form-row">
                                <div class="worker-portal-form-group">
                                    <label for="document-title"><?php _e('Título:', 'worker-portal'); ?></label>
                                    <input type="text" id="document-title" name="title" required>
                                </div>
                                
                                <div class="worker-portal-form-group">
                                    <label for="document-category"><?php _e('Categoría:', 'worker-portal'); ?></label>
                                    <select id="document-category" name="category">
                                        <?php foreach ($categories as $key => $label): ?>
                                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="worker-portal-form-group">
                                <label for="document-description"><?php _e('Descripción:', 'worker-portal'); ?></label>
                                <textarea id="document-description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="worker-portal-form-group">
                                <label for="document-file"><?php _e('Archivo (PDF):', 'worker-portal'); ?></label>
                                <input type="file" id="document-file" name="document" accept="application/pdf" required>
                                <p class="description"><?php _e('Solo se aceptan archivos PDF.', 'worker-portal'); ?></p>
                            </div>
                            
                            <div class="worker-portal-form-group">
                                <label for="document-users"><?php _e('Destinatarios:', 'worker-portal'); ?></label>
                                <select id="document-users" name="users[]" multiple size="5" required>
                                    <option value="all"><?php _e('Todos los trabajadores', 'worker-portal'); ?></option>
                                    <?php foreach ($workers as $worker): ?>
                                        <option value="<?php echo esc_attr($worker->ID); ?>"><?php echo esc_html($worker->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Mantén presionada la tecla Ctrl (o Cmd en Mac) para seleccionar múltiples usuarios.', 'worker-portal'); ?></p>
                            </div>
                            
                            <div class="worker-portal-form-group">
                                <label for="document-notify">
                                    <input type="checkbox" id="document-notify" name="notify" value="1" checked>
                                    <?php _e('Enviar notificación por email a los usuarios', 'worker-portal'); ?>
                                </label>
                            </div>
                            
                            <div class="worker-portal-form-actions">
                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('worker_portal_admin_nonce'); ?>">
                                <button type="submit" class="worker-portal-button worker-portal-button-primary">
                                    <i class="dashicons dashicons-upload"></i> <?php _e('Subir Documento', 'worker-portal'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Configuración de documentos -->
                    <div id="subtab-doc-settings" class="worker-portal-subtab-content">
                        <h3><?php _e('Configuración de Documentos', 'worker-portal'); ?></h3>
                        
                        <form id="document-settings-form" class="worker-portal-form">
                            <h4><?php _e('Categorías de Documentos', 'worker-portal'); ?></h4>
                            <p class="description"><?php _e('Configura las categorías disponibles para clasificar documentos.', 'worker-portal'); ?></p>
                            
                            <table class="worker-portal-table categories-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Clave', 'worker-portal'); ?></th>
                                        <th><?php _e('Nombre', 'worker-portal'); ?></th>
                                        <th><?php _e('Acciones', 'worker-portal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="categories-list">
                                    <?php foreach ($categories as $key => $label): ?>
                                        <tr class="category-row">
                                            <td>
                                                <input type="text" name="categories[keys][]" value="<?php echo esc_attr($key); ?>" required>
                                            </td>
                                            <td>
                                                <input type="text" name="categories[labels][]" value="<?php echo esc_attr($label); ?>" required>
                                            </td>
                                            <td>
                                                <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline remove-category">
                                                    <i class="dashicons dashicons-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3">
                                            <button type="button" id="add-category" class="worker-portal-button worker-portal-button-secondary">
                                                <i class="dashicons dashicons-plus"></i> <?php _e('Añadir categoría', 'worker-portal'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <h4><?php _e('Notificaciones', 'worker-portal'); ?></h4>
                            
                            <div class="worker-portal-form-group">
                                <label for="notification-email"><?php _e('Email de notificación:', 'worker-portal'); ?></label>
                                <input type="email" id="notification-email" name="notification_email" value="<?php echo esc_attr(get_option('worker_portal_document_notification_email', get_option('admin_email'))); ?>" class="regular-text">
                                <p class="description"><?php _e('Email al que se enviarán las notificaciones cuando se suba un nuevo documento.', 'worker-portal'); ?></p>
                            </div>
                            
                            <div class="worker-portal-form-actions">
                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('worker_portal_admin_nonce'); ?>">
                                <input type="hidden" name="action" value="admin_save_document_settings">
                                <button type="submit" class="worker-portal-button worker-portal-button-primary">
                                    <?php _e('Guardar Cambios', 'worker-portal'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
<!-- Content for tab-incentives in portal-content-admin.php -->
<div id="tab-incentives" class="worker-portal-tab-content">
    <h2><?php _e('Gestión de Incentivos', 'worker-portal'); ?></h2>
    
    <!-- Formulario para añadir incentivo manualmente -->
    <div class="worker-portal-add-incentive-form">
        <h3><?php _e('Añadir Incentivo', 'worker-portal'); ?></h3>
        
        <form id="add-incentive-form" class="worker-portal-form">
            <div class="worker-portal-form-row">
                <div class="worker-portal-form-group">
                    <label for="incentive-user-id"><?php _e('Trabajador:', 'worker-portal'); ?></label>
                    <select id="incentive-user-id" name="user_id" required>
                        <option value=""><?php _e('Seleccionar trabajador', 'worker-portal'); ?></option>
                        <?php 
                        $workers = get_users(array('role__not_in' => array('administrator')));
                        foreach ($workers as $worker): 
                        ?>
                            <option value="<?php echo esc_attr($worker->ID); ?>"><?php echo esc_html($worker->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="incentive-type"><?php _e('Tipo de incentivo:', 'worker-portal'); ?></label>
                    <select id="incentive-type" name="incentive_type">
                        <?php 
                        $incentive_types = get_option('worker_portal_incentive_types', array(
                            'excess_meters' => __('Plus de productividad por exceso de metros ejecutados', 'worker-portal'),
                            'quality' => __('Plus de calidad', 'worker-portal'),
                            'efficiency' => __('Plus de eficiencia', 'worker-portal'),
                            'other' => __('Otros', 'worker-portal')
                        ));
                        foreach ($incentive_types as $key => $label): 
                        ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="worker-portal-form-row">
                <div class="worker-portal-form-group">
                    <label for="incentive-description"><?php _e('Descripción:', 'worker-portal'); ?></label>
                    <textarea id="incentive-description" name="description" required></textarea>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="incentive-amount"><?php _e('Importe (€):', 'worker-portal'); ?></label>
                    <input type="number" id="incentive-amount" name="amount" min="0.01" step="0.01" required>
                </div>
            </div>
            
            <input type="hidden" id="incentive-worksheet-id" name="worksheet_id" value="0">
            
            <div class="worker-portal-form-actions">
                <button type="submit" class="worker-portal-button worker-portal-button-primary">
                    <i class="dashicons dashicons-plus"></i> <?php _e('Añadir Incentivo', 'worker-portal'); ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Filtros de incentivos -->
    <div class="worker-portal-admin-filters">
        <form id="admin-incentives-filter-form" class="worker-portal-admin-filter-form">
            <div class="worker-portal-admin-filter-row">
                <div class="worker-portal-admin-filter-group">
                    <label for="filter-worker-inc"><?php _e('Trabajador:', 'worker-portal'); ?></label>
                    <select id="filter-worker-inc" name="user_id">
                        <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                        <?php foreach ($workers as $worker): ?>
                            <option value="<?php echo esc_attr($worker->ID); ?>"><?php echo esc_html($worker->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="worker-portal-admin-filter-group">
                    <label for="filter-status-inc"><?php _e('Estado:', 'worker-portal'); ?></label>
                    <select id="filter-status-inc" name="status">
                        <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                        <option value="pending"><?php _e('Pendientes', 'worker-portal'); ?></option>
                        <option value="approved"><?php _e('Aprobados', 'worker-portal'); ?></option>
                        <option value="rejected"><?php _e('Rechazados', 'worker-portal'); ?></option>
                    </select>
                </div>
                
                <div class="worker-portal-admin-filter-group">
                    <label for="filter-date-from-inc"><?php _e('Desde:', 'worker-portal'); ?></label>
                    <input type="date" id="filter-date-from-inc" name="date_from">
                </div>
                
                <div class="worker-portal-admin-filter-group">
                    <label for="filter-date-to-inc"><?php _e('Hasta:', 'worker-portal'); ?></label>
                    <input type="date" id="filter-date-to-inc" name="date_to">
                </div>
            </div>
            
            <div class="worker-portal-admin-filter-actions">
                <button type="submit" class="worker-portal-button worker-portal-button-secondary">
                    <i class="dashicons dashicons-search"></i> <?php _e('Filtrar', 'worker-portal'); ?>
                </button>
                <button type="button" id="clear-filters-inc" class="worker-portal-button worker-portal-button-link">
                    <?php _e('Limpiar filtros', 'worker-portal'); ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Lista de incentivos (se cargará vía AJAX) -->
    <div id="incentives-list-container" data-nonce="<?php echo wp_create_nonce('worker_portal_admin_nonce'); ?>">
        <div class="worker-portal-loading">
            <div class="worker-portal-spinner"></div>
            <p><?php _e('Cargando incentivos...', 'worker-portal'); ?></p>
        </div>
    </div>
</div>
        
        <div id="tab-workers" class="worker-portal-tab-content">
            <?php 
            // Usando el shortcode definido
            echo do_shortcode('[worker_admin_panel]'); 
            ?>
        </div>
        <!-- Calendario -->
        <div id="tab-calendar" class="worker-portal-tab-content">
            <h2><?php _e('Gestión de Calendario', 'worker-portal'); ?></h2>
            <div class="worker-portal-admin-calendar-container">
                <?php echo do_shortcode('[staff_calendar]'); ?>
            </div>
        </div>

        <!-- Pestaña de Fichajes - Mejora para el panel de administrador -->
<div id="tab-timeclock" class="worker-portal-tab-content">
    <h2><?php _e('Gestión de Fichajes', 'worker-portal'); ?></h2>
    
    <!-- Filtros para fichajes -->
    <div class="worker-portal-admin-filters">
        <form id="admin-timeclock-filter-form" class="worker-portal-admin-filter-form">
            <div class="worker-portal-admin-filter-row">
                <div class="worker-portal-admin-filter-group">
                    <label for="filter-worker-timeclock"><?php _e('Trabajador:', 'worker-portal'); ?></label>
                    <select id="filter-worker-timeclock" name="user_id">
                        <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                        <?php 
                        $workers = get_users(array('role__not_in' => array('administrator')));
                        foreach ($workers as $worker): 
                        ?>
                            <option value="<?php echo esc_attr($worker->ID); ?>"><?php echo esc_html($worker->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="worker-portal-admin-filter-group">
                    <label for="filter-status-timeclock"><?php _e('Estado:', 'worker-portal'); ?></label>
                    <select id="filter-status-timeclock" name="status">
                        <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                        <option value="active"><?php _e('Activo', 'worker-portal'); ?></option>
                        <option value="completed"><?php _e('Completado', 'worker-portal'); ?></option>
                        <option value="edited"><?php _e('Editado', 'worker-portal'); ?></option>
                    </select>
                </div>
                
                <div class="worker-portal-admin-filter-group">
                    <label for="filter-date-from-timeclock"><?php _e('Desde:', 'worker-portal'); ?></label>
                    <input type="date" id="filter-date-from-timeclock" name="date_from">
                </div>
                
                <div class="worker-portal-admin-filter-group">
                    <label for="filter-date-to-timeclock"><?php _e('Hasta:', 'worker-portal'); ?></label>
                    <input type="date" id="filter-date-to-timeclock" name="date_to">
                </div>
            </div>
            
            <div class="worker-portal-admin-filter-actions">
                <button type="submit" class="worker-portal-button worker-portal-button-secondary">
                    <i class="dashicons dashicons-search"></i> <?php _e('Filtrar', 'worker-portal'); ?>
                </button>
                <button type="button" id="clear-filters-timeclock" class="worker-portal-button worker-portal-button-link">
                    <?php _e('Limpiar filtros', 'worker-portal'); ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Panel de acciones rápidas -->
    <div class="worker-portal-admin-action-panel">
        <h3><?php _e('Acciones Rápidas', 'worker-portal'); ?></h3>
        <div class="worker-portal-admin-action-buttons">
            <button type="button" id="register-all-exits" class="worker-portal-button worker-portal-button-danger">
                <i class="dashicons dashicons-exit"></i> <?php _e('Registrar salida para todos los activos', 'worker-portal'); ?>
            </button>
            <button type="button" id="export-timeclock-data" class="worker-portal-button worker-portal-button-secondary">
                <i class="dashicons dashicons-download"></i> <?php _e('Exportar a Excel', 'worker-portal'); ?>
            </button>
        </div>
    </div>

    <!-- Resumen de Estadísticas -->
    <div class="worker-portal-admin-stats worker-portal-timeclock-stats">
        <div class="worker-portal-admin-stats-grid">
            <!-- Estadísticas actuales -->
            <div class="worker-portal-admin-stat-box worker-portal-stat-active">
                <div class="worker-portal-admin-stat-icon">
                    <i class="dashicons dashicons-marker"></i>
                </div>
                <div class="worker-portal-admin-stat-content">
                    <div class="worker-portal-admin-stat-value" id="active-entries-count">
                        <?php 
                        global $wpdb;
                        $active_count = $wpdb->get_var(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}time_clock_entries 
                             WHERE clock_out IS NULL"
                        );
                        echo esc_html($active_count);
                        ?>
                    </div>
                    <div class="worker-portal-admin-stat-label"><?php _e('Fichajes Activos', 'worker-portal'); ?></div>
                </div>
            </div>
            
            <!-- Fichajes de hoy -->
            <div class="worker-portal-admin-stat-box worker-portal-stat-today">
                <div class="worker-portal-admin-stat-icon">
                    <i class="dashicons dashicons-calendar-alt"></i>
                </div>
                <div class="worker-portal-admin-stat-content">
                    <div class="worker-portal-admin-stat-value" id="today-entries-count">
                        <?php 
                        $today = date('Y-m-d');
                        $today_count = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}time_clock_entries 
                                 WHERE DATE(clock_in) = %s",
                                $today
                            )
                        );
                        echo esc_html($today_count);
                        ?>
                    </div>
                    <div class="worker-portal-admin-stat-label"><?php _e('Fichajes Hoy', 'worker-portal'); ?></div>
                </div>
            </div>
            
            <!-- Total horas este mes -->
            <div class="worker-portal-admin-stat-box worker-portal-stat-hours">
                <div class="worker-portal-admin-stat-icon">
                    <i class="dashicons dashicons-clock"></i>
                </div>
                <div class="worker-portal-admin-stat-content">
                    <div class="worker-portal-admin-stat-value" id="month-hours-count">
                        <?php 
                        $first_day = date('Y-m-01');
                        $last_day = date('Y-m-t');
                        
                        $total_seconds = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT SUM(TIMESTAMPDIFF(SECOND, clock_in, clock_out)) 
                                 FROM {$wpdb->prefix}time_clock_entries 
                                 WHERE clock_out IS NOT NULL 
                                 AND clock_in BETWEEN %s AND %s",
                                $first_day,
                                $last_day
                            )
                        );
                        
                        $hours = floor($total_seconds / 3600);
                        $minutes = floor(($total_seconds % 3600) / 60);
                        echo sprintf('%d:%02d', $hours, $minutes);
                        ?>
                    </div>
                    <div class="worker-portal-admin-stat-label"><?php _e('Horas este mes', 'worker-portal'); ?></div>
                </div>
            </div>
        </div>
    </div>
    
            <!-- Contenedor de entradas de fichaje -->
            <div id="timeclock-entries-container" class="worker-portal-admin-list-container">
                <div class="worker-portal-loading">
                    <div class="worker-portal-spinner"></div>
                    <p><?php _e('Cargando fichajes...', 'worker-portal'); ?></p>
                </div>
            </div>
        </div>

        <!-- Modal para editar entrada de fichaje -->
        <div id="timeclock-entry-details-modal" class="worker-portal-modal">
            <div class="worker-portal-modal-content">
                <div class="worker-portal-modal-header">
                    <h3><?php _e('Detalles del Fichaje', 'worker-portal'); ?></h3>
                    <button type="button" class="worker-portal-modal-close">&times;</button>
                </div>
                <div class="worker-portal-modal-body">
                    <div id="timeclock-entry-details-content">
                        <!-- Contenido cargado por AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales para la interfaz de administrador -->
<div id="expense-details-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Detalles del Gasto', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="expense-details-content">
                <!-- Contenido cargado por AJAX -->
            </div>
        </div>
    </div>
</div>

<div id="receipt-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Justificante', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="receipt-modal-content">
                <!-- Contenido cargado por AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles de documento -->
<div id="document-details-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Detalles del Documento', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="document-details-content">
                <!-- Contenido cargado por AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver documentos -->
<div id="document-view-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content worker-portal-modal-large">
        <div class="worker-portal-modal-header">
            <h3 id="document-modal-title"><?php _e('Documento', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="document-modal-content">
                <!-- Contenido cargado por AJAX -->
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="admin_nonce" value="<?php echo wp_create_nonce('worker_portal_ajax_nonce'); ?>">
<input type="hidden" id="worker_portal_nonce" value="<?php echo wp_create_nonce('worker_portal_ajax_nonce'); ?>">

<script type="text/javascript">
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

jQuery(document).ready(function($) {
    // Navegación entre pestañas
    $('.worker-portal-tab-link').on('click', function(e) {
        e.preventDefault();
        
        // Ocultar todas las pestañas
        $('.worker-portal-tab-content').removeClass('active');
        
        // Remover clase activa de todos los enlaces
        $('.worker-portal-tab-link').removeClass('active');
        
        // Mostrar pestaña seleccionada
        var tab = $(this).data('tab');
        $('#tab-' + tab).addClass('active');
        
        // Activar enlace
        $(this).addClass('active');
        
        // Cargar contenido específico según la pestaña
        if (tab === 'pending-expenses') {
            loadPendingExpenses();
        } else if (tab === 'worksheets') {
            loadWorksheets();
        } else if (tab === 'documents') {
            // Cargar documentos si estamos en la subpestaña de lista
            if ($('#subtab-doc-list').hasClass('active')) {
                loadDocuments();
            }
        }
    });
    
    // Navegación entre sub-pestañas
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
        
        // Si seleccionamos la lista de documentos, cargarlos
        if (subtab === 'doc-list') {
            loadDocuments();
        }
    });
    
    // Navegación desde enlaces de estadísticas
    $('.worker-portal-admin-stat-action').on('click', function(e) {
        e.preventDefault();
        
        var tab = $(this).data('tab');
        $('.worker-portal-tab-link[data-tab="' + tab + '"]').click();
    });
    
    // Enlaces para tabs desde botones
    $(document).on('click', '.tab-nav-link', function(e) {
        e.preventDefault();
        
        var tab = $(this).data('tab');
        $('.worker-portal-tab-link[data-tab="' + tab + '"]').click();
    });
    
    // Función para cargar gastos pendientes
    function loadPendingExpenses() {
        console.log('Cargando gastos pendientes...');
        
        // Obtener valores de filtros
        var formData = new FormData(document.getElementById('admin-expenses-filter-form'));
        formData.append('action', 'admin_load_pending_expenses');
        formData.append('nonce', $('#admin_nonce').val());
        
        // Mostrar indicador de carga
        $('#pending-expenses-list-container').html(
            '<div class="worker-portal-loading">' +
            '<div class="worker-portal-spinner"></div>' +
            '<p>Cargando gastos...</p>' +
            '</div>'
        );
        
        // Realizar petición AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Respuesta de gastos recibida:', response);
                if (response.success) {
                    $('#pending-expenses-list-container').html(response.data);
                    initExpenseActions();
                } else {
                    $('#pending-expenses-list-container').html(
                        '<div class="worker-portal-error">' + response.data + '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', status, error);
                $('#pending-expenses-list-container').html(
                    '<div class="worker-portal-error">Error al cargar los gastos. Por favor, inténtalo de nuevo.</div>'
                );
            }
        });
    }
    
    // Función para cargar hojas de trabajo
    function loadWorksheets() {
        console.log('Cargando hojas de trabajo...');
        
        // Mostrar indicador de carga
        $('#worksheets-list-container').html(
            '<div class="worker-portal-loading">' +
            '<div class="worker-portal-spinner"></div>' +
            '<p>Cargando hojas de trabajo...</p>' +
            '</div>'
        );
        
        // Obtener datos del formulario
        var formData = new FormData();
        formData.append('action', 'admin_load_worksheets');
        formData.append('nonce', $('#admin_nonce').val());
        
        // Añadir filtros si existen
        if ($('#filter-worker-ws').length) {
            formData.append('user_id', $('#filter-worker-ws').val() || '');
        }
        
        if ($('#filter-project').length) {
            formData.append('project_id', $('#filter-project').val() || '');
        }
        
        if ($('#filter-date-from-ws').length) {
            formData.append('date_from', $('#filter-date-from-ws').val() || '');
        }
        
        if ($('#filter-date-to-ws').length) {
            formData.append('date_to', $('#filter-date-to-ws').val() || '');
        }
        
        // Realizar petición AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Respuesta de hojas recibida:', response);
                if (response.success) {
                    $('#worksheets-list-container').html(response.data.html || '<p>No hay datos para mostrar</p>');
                } else {
                    $('#worksheets-list-container').html(
                        '<div class="worker-portal-error">Error al cargar datos: ' + 
                        (response.data || 'Error desconocido') + '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la solicitud AJAX:', status, error);
                $('#worksheets-list-container').html(
                    '<div class="worker-portal-error">Error de comunicación con el servidor: ' + error + '</div>'
                );
            }
        });
    }
    
    // Función para cargar documentos
    function loadDocuments() {
        console.log('Cargando documentos...');
        
        // Mostrar indicador de carga
        $('#documents-list-container').html(
            '<div class="worker-portal-loading">' +
            '<div class="worker-portal-spinner"></div>' +
            '<p>Cargando documentos...</p>' +
            '</div>'
        );
        
        // Obtener datos del formulario
        var formData = new FormData();
        formData.append('action', 'filter_documents');
        formData.append('nonce', $('#documents-list-container').data('nonce'));
        
        // Añadir filtros si existen
        if ($('#filter-category').length) {
            formData.append('category', $('#filter-category').val() || '');
        }
        
        if ($('#filter-worker-doc').length) {
            formData.append('user_id', $('#filter-worker-doc').val() || '');
        }
        
        if ($('#filter-date-from-doc').length) {
            formData.append('date_from', $('#filter-date-from-doc').val() || '');
        }
        
        if ($('#filter-date-to-doc').length) {
            formData.append('date_to', $('#filter-date-to-doc').val() || '');
        }
        
        // Realizar petición AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Respuesta de documentos recibida:', response);
                if (response.success) {
                    $('#documents-list-container').html(response.data.html || '<p>No hay documentos para mostrar</p>');
                    initDocumentActions();
                } else {
                    $('#documents-list-container').html(
                        '<div class="worker-portal-error">' + (response.data || 'Error al cargar documentos') + '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la solicitud AJAX:', status, error);
                $('#documents-list-container').html(
                    '<div class="worker-portal-error">Error de comunicación con el servidor: ' + error + '</div>'
                );
            }
        });
    }
    
    // Inicializar acciones de gastos
    function initExpenseActions() {
        // Seleccionar/deseleccionar todos los gastos
        $("#select-all-expenses").on("click", function() {
            $(".expense-checkbox").prop("checked", $(this).prop("checked"));
            checkBulkSelection();
        });
        
        // Actualizar estado del botón de acción masiva
        $(".expense-checkbox").on("change", checkBulkSelection);
        
        // Acción de aprobar gasto
        $(".approve-expense").on("click", function() {
            approveExpense($(this).data("expense-id"));
        });
        
        // Acción de rechazar gasto
        $(".reject-expense").on("click", function() {
            rejectExpense($(this).data("expense-id"));
        });
        
        // Acción de ver gasto
        $(".view-expense").on("click", function() {
            viewExpenseDetails($(this).data("expense-id"));
        });
    }
    
    // Inicializar acciones de documentos
    function initDocumentActions() {
        // Acción de ver documento
        $(".worker-portal-view-document").on("click", function() {
            viewDocument($(this).data("document-id"));
        });
        
        // Acción de descargar documento
        $(".worker-portal-download-document").on("click", function(e) {
            e.preventDefault();
            downloadDocument($(this).data("document-id"));
        });
        
        // Acción de ver detalles de documento
        $(".worker-portal-document-details").on("click", function() {
            viewDocumentDetails($(this).data("document-id"));
        });
        
        // Acción de eliminar documento
        $(".worker-portal-delete-document").on("click", function() {
            deleteDocument($(this).data("document-id"));
        });
    }
    
    // Comprobar selección de gastos para acciones masivas
    function checkBulkSelection() {
        if ($(".expense-checkbox:checked").length > 0) {
            $("#apply-bulk-action").prop("disabled", false);
        } else {
            $("#apply-bulk-action").prop("disabled", true);
        }
    }
    
    // Aprobar un gasto individual
    function approveExpense(expenseId) {
        if (!confirm('¿Estás seguro de aprobar este gasto?')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'admin_approve_expense',
                expense_id: expenseId,
                nonce: $('#admin_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    loadPendingExpenses();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Ha ocurrido un error. Por favor, inténtalo de nuevo.');
            }
        });
    }
    
    // Rechazar un gasto individual
    function rejectExpense(expenseId) {
        if (!confirm('¿Estás seguro de denegar este gasto?')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'admin_reject_expense',
                expense_id: expenseId,
                nonce: $('#admin_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    loadPendingExpenses();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Ha ocurrido un error. Por favor, inténtalo de nuevo.');
            }
        });
    }
    
    // Ver detalles de un gasto
    function viewExpenseDetails(expenseId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'admin_get_expense_details',
                expense_id: expenseId,
                nonce: $('#admin_nonce').val()
            },
            beforeSend: function() {
                $('#expense-details-content').html(
                    '<div class="worker-portal-loading">' +
                    '<div class="worker-portal-spinner"></div>' +
                    '<p>Cargando detalles del gasto...</p>' +
                    '</div>'
                );
                $('#expense-details-modal').show();
            },
            success: function(response) {
                if (response.success) {
                    $('#expense-details-content').html(response.data);
                } else {
                    $('#expense-details-content').html(
                        '<div class="worker-portal-error">' + response.data + '</div>'
                    );
                }
            },
            error: function() {
                $('#expense-details-content').html(
                    '<div class="worker-portal-error">Ha ocurrido un error. Por favor, inténtalo de nuevo.</div>'
                );
            }
        });
    }
    
    // Ver un documento
    function viewDocument(documentId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'download_document',
                nonce: $('#documents-list-container').data('nonce'),
                document_id: documentId
            },
            beforeSend: function() {
                $('#document-modal-content').html(
                    '<div class="worker-portal-loading">' +
                    '<div class="worker-portal-spinner"></div>' +
                    '<p>Cargando documento...</p>' +
                    '</div>'
                );
                $('#document-view-modal').show();
            },
            success: function(response) {
                if (response.success) {
                    // Mostrar PDF en iframe
                    var html = '<iframe src="' + response.data.download_url + '" style="width:100%; height:500px; border:none;"></iframe>';
                    $('#document-modal-content').html(html);
                    
                    // Actualizar título
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'admin_get_document_details',
                            nonce: $('#documents-list-container').data('nonce'),
                            document_id: documentId
                        },
                        success: function(detailsResponse) {
                            if (detailsResponse.success) {
                                $('#document-modal-title').text(detailsResponse.data.title);
                            }
                        }
                    });
                } else {
                    $('#document-modal-content').html(
                        '<div class="worker-portal-error">' + response.data + '</div>'
                    );
                }
            },
            error: function() {
                $('#document-modal-content').html(
                    '<div class="worker-portal-error">Ha ocurrido un error al cargar el documento.</div>'
                );
            }
        });
    }
    
    // Descargar un documento
    function downloadDocument(documentId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'download_document',
                nonce: $('#documents-list-container').data('nonce'),
                document_id: documentId
            },
            success: function(response) {
                if (response.success) {
                    // Crear enlace de descarga
                    var link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Ha ocurrido un error. Por favor, inténtalo de nuevo.');
            }
        });
    }
    
    // Ver detalles de un documento
    function viewDocumentDetails(documentId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'admin_get_document_details',
                nonce: $('#documents-list-container').data('nonce'),
                document_id: documentId
            },
            beforeSend: function() {
                $('#document-details-content').html(
                    '<div class="worker-portal-loading">' +
                    '<div class="worker-portal-spinner"></div>' +
                    '<p>Cargando detalles del documento...</p>' +
                    '</div>'
                );
                $('#document-details-modal').show();
            },
            success: function(response) {
                if (response.success) {
                    var document = response.data;
                    
                    var html = 
                        '<table class="worker-portal-details-table">' +
                            '<tr>' +
                                '<th><?php _e('ID:', 'worker-portal'); ?></th>' +
                                '<td>' + document.id + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th><?php _e('Título:', 'worker-portal'); ?></th>' +
                                '<td>' + document.title + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th><?php _e('Categoría:', 'worker-portal'); ?></th>' +
                                '<td>' + document.category_name + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th><?php _e('Descripción:', 'worker-portal'); ?></th>' +
                                '<td>' + (document.description || '<?php _e('Sin descripción', 'worker-portal'); ?>') + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th><?php _e('Usuario:', 'worker-portal'); ?></th>' +
                                '<td>' + document.user_name + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th><?php _e('Fecha de subida:', 'worker-portal'); ?></th>' +
                                '<td>' + document.upload_date + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th><?php _e('Archivo:', 'worker-portal'); ?></th>' +
                                '<td>' +
                                    '<a href="' + document.download_url + '" target="_blank" class="worker-portal-button worker-portal-button-small worker-portal-button-outline">' +
                                        '<i class="dashicons dashicons-visibility"></i> <?php _e('Ver documento', 'worker-portal'); ?>' +
                                    '</a>' +
                                '</td>' +
                            '</tr>' +
                        '</table>' +
                        
                        '<div class="worker-portal-document-actions" style="margin-top: 20px;">' +
                            '<button type="button" class="worker-portal-button worker-portal-button-danger worker-portal-delete-document" data-document-id="' + document.id + '">' +
                                '<i class="dashicons dashicons-trash"></i> <?php _e('Eliminar documento', 'worker-portal'); ?>' +
                            '</button>' +
                        '</div>';
                    
                    $('#document-details-content').html(html);
                    
                    // Reinicializar el botón de eliminar
                    $('#document-details-modal .worker-portal-delete-document').on('click', function() {
                        deleteDocument($(this).data('document-id'));
                    });
                } else {
                    $('#document-details-content').html(
                        '<div class="worker-portal-error">' + response.data + '</div>'
                    );
                }
            },
            error: function() {
                $('#document-details-content').html(
                    '<div class="worker-portal-error">Ha ocurrido un error. Por favor, inténtalo de nuevo.</div>'
                );
            }
        });
    }
    
    // Eliminar un documento
    function deleteDocument(documentId) {
        if (!confirm('¿Estás seguro de eliminar este documento? Esta acción no se puede deshacer.')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'admin_delete_document',
                nonce: $('#documents-list-container').data('nonce'),
                document_id: documentId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    
                    // Cerrar modal si está abierto
                    $('#document-details-modal').hide();
                    
                    // Recargar lista de documentos
                    loadDocuments();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Ha ocurrido un error. Por favor, inténtalo de nuevo.');
            }
        });
    }
    
    // Manejar el envío del formulario de filtros de gastos
    $('#admin-expenses-filter-form').on('submit', function(e) {
        e.preventDefault();
        loadPendingExpenses();
    });
    
    // Manejar el envío del formulario de filtros de hojas de trabajo
    $('#admin-worksheets-filter-form').on('submit', function(e) {
        e.preventDefault();
        loadWorksheets();
    });
    
    // Manejar el envío del formulario de filtros de documentos
    $('#admin-documents-filter-form').on('submit', function(e) {
        e.preventDefault();
        loadDocuments();
    });
    
    // Limpiar filtros de gastos
    $('#clear-filters-expenses').on('click', function() {
        $('#admin-expenses-filter-form')[0].reset();
        loadPendingExpenses();
    });
    
    // Limpiar filtros de hojas de trabajo
    $('#clear-filters-ws').on('click', function() {
        $('#admin-worksheets-filter-form')[0].reset();
        loadWorksheets();
    });
    
    // Limpiar filtros de documentos
    $('#clear-filters-doc').on('click', function() {
        $('#admin-documents-filter-form')[0].reset();
        loadDocuments();
    });
    
    // Subir documento
    $('#upload-document-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'admin_upload_document');
        
        // Verificar si se ha seleccionado un archivo
        if (!$('#document-file')[0].files.length) {
            alert('<?php _e('Por favor, selecciona un archivo PDF.', 'worker-portal'); ?>');
            return;
        }
        
        // Verificar que se ha seleccionado al menos un usuario
        if (!$('#document-users').val() || $('#document-users').val().length === 0) {
            alert('<?php _e('Por favor, selecciona al menos un destinatario.', 'worker-portal'); ?>');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(this).find('button[type=submit]').prop('disabled', true).html(
                    '<i class="dashicons dashicons-update-alt spinning"></i> <?php _e('Subiendo...', 'worker-portal'); ?>'
                );
            }.bind(this),
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    this.reset();
                    
                    // Cambiar a la pestaña de lista de documentos y cargarlos
                    $('.worker-portal-subtab-link[data-subtab="doc-list"]').click();
                } else {
                    alert(response.data);
                }
            }.bind(this),
            error: function() {
                alert('<?php _e('Ha ocurrido un error al subir el documento. Por favor, inténtalo de nuevo.', 'worker-portal'); ?>');
            },
            complete: function() {
                $(this).find('button[type=submit]').prop('disabled', false).html(
                    '<i class="dashicons dashicons-upload"></i> <?php _e('Subir Documento', 'worker-portal'); ?>'
                );
            }.bind(this)
        });
    });
    
    // Gestión de categorías
    $('#add-category').on('click', function() {
        var newRow = 
            '<tr class="category-row">' +
                '<td>' +
                    '<input type="text" name="categories[keys][]" required>' +
                '</td>' +
                '<td>' +
                    '<input type="text" name="categories[labels][]" required>' +
                '</td>' +
                '<td>' +
                    '<button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline remove-category">' +
                        '<i class="dashicons dashicons-trash"></i>' +
                    '</button>' +
                '</td>' +
            '</tr>';
        
        $('#categories-list').append(newRow);
    });
    
    $(document).on('click', '.remove-category', function() {
        // Si solo queda una categoría, mostrar mensaje
        if ($('.category-row').length <= 1) {
            alert('<?php _e('Debe existir al menos una categoría.', 'worker-portal'); ?>');
            return;
        }
        
        $(this).closest('tr').remove();
    });
    
    // Guardar configuración de documentos
    $('#document-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(this).find('button[type=submit]').prop('disabled', true).html('<?php _e('Guardando...', 'worker-portal'); ?>');
            }.bind(this),
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Ha ocurrido un error al guardar la configuración. Por favor, inténtalo de nuevo.', 'worker-portal'); ?>');
            },
            complete: function() {
                $(this).find('button[type=submit]').prop('disabled', false).html('<?php _e('Guardar Cambios', 'worker-portal'); ?>');
            }.bind(this)
        });
    });
    
    // Manejar acciones masivas
    $('#bulk-approve-form').on('submit', function(e) {
        e.preventDefault();
        
        var action = $('#bulk-action').val();
        if (!action) {
            alert('Por favor, selecciona una acción.');
            return;
        }
        
        var selectedIds = [];
        $('.expense-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            alert('Por favor, selecciona al menos un gasto.');
            return;
        }
        
        if (!confirm('¿Estás seguro de realizar esta acción? No se puede deshacer.')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'admin_bulk_expense_action',
                bulk_action: action,
                expense_ids: selectedIds,
                nonce: $('#admin_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    loadPendingExpenses();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('Ha ocurrido un error. Por favor, inténtalo de nuevo.');
            }
        });
    });
    
    // Exportar hojas de trabajo
    $('#export-worksheets-button').on('click', function() {
        var formData = new FormData();
        formData.append('action', 'admin_export_worksheets');
        formData.append('nonce', $('#admin_nonce').val());
        
        // Añadir filtros si existen
        if ($('#filter-worker-ws').length) {
            formData.append('user_id', $('#filter-worker-ws').val() || '');
        }
        
        if ($('#filter-project').length) {
            formData.append('project_id', $('#filter-project').val() || '');
        }
        
        if ($('#filter-date-from-ws').length) {
            formData.append('date_from', $('#filter-date-from-ws').val() || '');
        }
        
        if ($('#filter-date-to-ws').length) {
            formData.append('date_to', $('#filter-date-to-ws').val() || '');
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $(this).prop('disabled', true).html('<i class="dashicons dashicons-update-alt spinning"></i> Exportando...');
            }.bind(this),
            success: function(response) {
                if (response.success && response.data.file_url) {
                    // Crear y hacer clic en un enlace de descarga
                    var link = document.createElement('a');
                    link.href = response.data.file_url;
                    link.download = response.data.filename || 'hojas-trabajo.xlsx';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert(response.data || 'Error al exportar las hojas de trabajo.');
                }
            },
            error: function() {
                alert('Ha ocurrido un error durante la exportación. Por favor, inténtalo de nuevo.');
            },
            complete: function() {
                $(this).prop('disabled', false).html('<i class="dashicons dashicons-download"></i> Exportar a Excel');
            }.bind(this)
        });
    });
    
    // Cerrar modales
    $('.worker-portal-modal-close').on('click', function() {
        $(this).closest('.worker-portal-modal').hide();
    });
    
    // Cerrar modal haciendo clic fuera
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('worker-portal-modal')) {
            $('.worker-portal-modal').hide();
        }
    });
});
</script>

<script>
// Después de inicializar todo, cargamos los datos si estamos en la pestaña de fichajes
jQuery(document).ready(function($) {
    // Si estamos en la pestaña de fichajes, cargamos los datos
    if ($('#tab-timeclock').hasClass('active')) {
        // Cargamos los datos de fichajes
        if (typeof WorkerPortalAdminFrontend !== 'undefined') {
            WorkerPortalAdminFrontend.loadTimeclockEntries();
        } else {
            // Si WorkerPortalAdminFrontend no está disponible, cargar manualmente
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'admin_load_timeclock_entries',
                    nonce: $('#admin_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        $('#timeclock-entries-container').html(response.data);
                    }
                }
            });
        }
    }
    
    // Cada vez que cambiamos a la pestaña de fichajes, cargamos los datos
    $('.worker-portal-tab-link[data-tab="timeclock"]').on('click', function() {
        if (typeof WorkerPortalAdminFrontend !== 'undefined') {
            WorkerPortalAdminFrontend.loadTimeclockEntries();
        } else {
            // Si WorkerPortalAdminFrontend no está disponible, cargar manualmente
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'admin_load_timeclock_entries',
                    nonce: $('#admin_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        $('#timeclock-entries-container').html(response.data);
                    }
                }
            });
        }
    });
});
</script>
<script type="text/javascript">
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
</script>