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
                                $workers = get_users();
                                $workers = array_filter($workers, function($user) {
                                    return !user_can($user->ID, 'manage_options'); // Excluir administradores
                                });
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
            <div id="pending-expenses-list-container" class="worker-portal-admin-list-container">
                <!-- Esta sección se cargará vía AJAX -->
                <div class="worker-portal-loading">
                    <div class="worker-portal-spinner"></div>
                    <p><?php _e('Cargando gastos...', 'worker-portal'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Otras pestañas estarán disponibles en futuras versiones -->
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
                        $workers = get_users();
                        $workers = array_filter($workers, function($user) {
                            return !user_can($user->ID, 'manage_options'); // Excluir administradores
                        });
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
                        global $wpdb;
                        $projects = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}worker_projects WHERE status = 'active'", ARRAY_A);
                        foreach ($projects as $project): 
                        ?>
                            <option value="<?php echo esc_attr($project['id']); ?>"><?php echo esc_html($project['name']); ?></option>
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
    
    <!-- Lista de hojas de trabajo -->
    <div id="worksheets-list-container">
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

<script>
jQuery(document).ready(function($) {
    // Cargar hojas de trabajo mediante AJAX al hacer clic en la pestaña
    $('.worker-portal-tab-link[data-tab="worksheets"]').on('click', function() {
        loadWorksheets();
    });

    function loadWorksheets(page = 1) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'admin_load_worksheets',
                page: page,
                nonce: '<?php echo wp_create_nonce('worker_portal_ajax_nonce'); ?>'
            },
            beforeSend: function() {
                $('#worksheets-list-container').html(
                    '<div class="worker-portal-loading">' +
                    '<div class="worker-portal-spinner"></div>' +
                    '<p><?php _e('Cargando hojas de trabajo...', 'worker-portal'); ?></p>' +
                    '</div>'
                );
            },
            success: function(response) {
                if (response.success) {
                    $('#worksheets-list-container').html(response.data);
                } else {
                    $('#worksheets-list-container').html(
                        '<div class="worker-portal-error">' + response.data + '</div>'
                    );
                }
            },
            error: function() {
                $('#worksheets-list-container').html(
                    '<div class="worker-portal-error"><?php _e('Error al cargar las hojas de trabajo.', 'worker-portal'); ?></div>'
                );
            }
        });
    }
});
</script>
        
        <div id="tab-documents" class="worker-portal-tab-content">
            <h2><?php _e('Gestión de Documentos', 'worker-portal'); ?></h2>
            <div class="worker-portal-coming-soon">
                <p><?php _e('La funcionalidad de gestión de documentos estará disponible próximamente.', 'worker-portal'); ?></p>
            </div>
        </div>
        
        <div id="tab-incentives" class="worker-portal-tab-content">
            <h2><?php _e('Gestión de Incentivos', 'worker-portal'); ?></h2>
            <div class="worker-portal-coming-soon">
                <p><?php _e('La funcionalidad de gestión de incentivos estará disponible próximamente.', 'worker-portal'); ?></p>
            </div>
        </div>
        
        <div id="tab-workers" class="worker-portal-tab-content">
            <h2><?php _e('Gestión de Trabajadores', 'worker-portal'); ?></h2>
            <div class="worker-portal-coming-soon">
                <p><?php _e('La funcionalidad completa de gestión de trabajadores estará disponible próximamente.', 'worker-portal'); ?></p>
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

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Navegación entre pestañas
    $('.worker-portal-tab-link').on('click', function(e) {
        e.preventDefault();
        
        // Ocultar todas las pestañas
        $('.worker-portal-tab-content').removeClass('active');
        
        // Remover clase activa de todos los enlaces
        $('.worker-portal-tab-link').removeClass('active');
        
        // Mostrar pestaña seleccionada
        const tab = $(this).data('tab');
        $('#tab-' + tab).addClass('active');
        
        // Activar enlace
        $(this).addClass('active');
    });
    
    // Navegación entre sub-pestañas
    $('.worker-portal-subtab-link').on('click', function(e) {
        e.preventDefault();
        
        // Ocultar todas las sub-pestañas
        $('.worker-portal-subtab-content').removeClass('active');
        
        // Remover clase activa de todos los enlaces
        $('.worker-portal-subtab-link').removeClass('active');
        
        // Mostrar sub-pestaña seleccionada
        const subtab = $(this).data('subtab');
        $('#subtab-' + subtab).addClass('active');
        
        // Activar enlace
        $(this).addClass('active');
    });
    
    // Navegación desde enlaces de estadísticas
    $('.worker-portal-admin-stat-action').on('click', function(e) {
        e.preventDefault();
        
        const tab = $(this).data('tab');
        $('.worker-portal-tab-link[data-tab="' + tab + '"]').click();
    });
    
    // Enlaces para tabs desde botones
    $('.tab-nav-link').on('click', function(e) {
        e.preventDefault();
        
        const tab = $(this).data('tab');
        $('.worker-portal-tab-link[data-tab="' + tab + '"]').click();
    });
    
    // Cargar lista de gastos pendientes al hacer clic en la pestaña
    $('.worker-portal-tab-link[data-tab="pending-expenses"]').on('click', function() {
        loadPendingExpenses();
    });
    
    // Función para cargar gastos pendientes
    function loadPendingExpenses() {
        // Obtener valores de filtros
        const formData = new FormData($('#admin-expenses-filter-form')[0]);
        formData.append('action', 'admin_load_pending_expenses');
        formData.append('nonce', worker_portal_params.nonce);
        
        // Mostrar indicador de carga
        $('#pending-expenses-list-container').html(
            '<div class="worker-portal-loading">' +
            '<div class="worker-portal-spinner"></div>' +
            '<p><?php echo esc_js(__('Cargando gastos...', 'worker-portal')); ?></p>' +
            '</div>'
        );
        
        // Realizar petición AJAX
        $.ajax({
            url: worker_portal_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#pending-expenses-list-container').html(response.data);
                    initExpenseActions();
                } else {
                    $('#pending-expenses-list-container').html(
                        '<div class="worker-portal-error">' + response.data + '</div>'
                    );
                }
            },
            error: function() {
                $('#pending-expenses-list-container').html(
                    '<div class="worker-portal-error">' + 
                    '<?php echo esc_js(__('Error al cargar los gastos. Por favor, inténtalo de nuevo.', 'worker-portal')); ?>' + 
                    '</div>'
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
        if (!confirm('<?php echo esc_js(__('¿Estás seguro de aprobar este gasto?', 'worker-portal')); ?>')) {
            return;
        }
        
        $.ajax({
            url: worker_portal_params.ajax_url,
            type: 'POST',
            data: {
                action: 'admin_approve_expense',
                expense_id: expenseId,
                nonce: worker_portal_params.nonce
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
                alert('<?php echo esc_js(__('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal')); ?>');
            }
        });
    }
    
    // Rechazar un gasto individual
    function rejectExpense(expenseId) {
        if (!confirm('<?php echo esc_js(__('¿Estás seguro de denegar este gasto?', 'worker-portal')); ?>')) {
            return;
        }
        
        $.ajax({
            url: worker_portal_params.ajax_url,
            type: 'POST',
            data: {
                action: 'admin_reject_expense',
                expense_id: expenseId,
                nonce: worker_portal_params.nonce
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
                alert('<?php echo esc_js(__('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal')); ?>');
            }
        });
    }
    
    // Ver detalles de un gasto
    function viewExpenseDetails(expenseId) {
        $.ajax({
            url: worker_portal_params.ajax_url,
            type: 'POST',
            data: {
                action: 'admin_get_expense_details',
                expense_id: expenseId,
                nonce: worker_portal_params.nonce
            },
            beforeSend: function() {
                $('#expense-details-content').html(
                    '<div class="worker-portal-loading">' +
                    '<div<?php
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
                    <?php
                    // Obtener número de trabajadores
                    $workers_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}users WHERE role NOT IN ('administrator')");
                    if ($workers_count > 0):
                    ?>
                    <span class="worker-portal-pending-count"><?php echo esc_html($workers_count); ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
    </div>  
    <!-- Contenido de cada pestaña -->
    <div class="worker-portal-admin-tabs-content">
        <!-- Dashboard -->
        <div id="tab-dashboard" class="worker-portal-tab-content active">
            <h2><?php _e('Dashboard', 'worker-portal'); ?></h2>
            
            <div class="worker-portal-admin-statistics">
                <div class="worker-portal-admin-stat-boxes">
                    <!-- Estadísticas de Trabajadores -->
                    <?php 
                    $workers_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}users WHERE role NOT IN ('administrator')");
                    ?>
                    <div class="worker-portal-admin-stat-box worker-portal-stat-workers">
                        <div class="worker-portal-admin-stat-icon">
                            <i class="dashicons dashicons-groups"></i>
                        </div>
                        <div class="worker-portal-admin-stat-content">
                            <div class="worker-portal-admin-stat-value"><?php echo $workers_count; ?></div>
                            <div class="worker-portal-admin-stat-label"><?php _e('Trabajadores', 'worker-portal'); ?></div>
                            <a href="#" class="worker-portal-admin-stat-action" data-tab="workers">
                                <i class="dashicons dashicons-arrow-right-alt"></i>
                                <?php _e('Ver todos', 'worker-portal'); ?>
                            </a>
                        </div>
                    </div>
                    <!-- Estadísticas de Gastos -->
                    <?php
                    $expenses_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses");
                    $total_expenses = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}worker_expenses");
                    ?>  
                    <div class="worker-portal-admin-stat-box worker-portal-stat-expenses">
                        <div class="worker-portal-admin-stat-icon">
                            <i class="dashicons dashicons-money-alt"></i>
                        </div>
                        <div class="worker-portal-admin-stat-content">
                            <div class="worker-portal-admin-stat-value"><?php echo $expenses_count; ?></div>
                            <div class="worker-portal-admin-stat-label"><?php _e('Gastos', 'worker-portal'); ?></div>
                            <a href="#" class="worker-portal-admin-stat-action" data-tab="pending-expenses">
                                <i class="dashicons dashicons-arrow-right-alt"></i>
                                <?php _e('Ver todos', 'worker-portal'); ?>
                            </a>
                        </div>
                    </div>
                    <!-- Estadísticas de Hojas de Trabajo -->
                    <?php
                    $worksheets_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_worksheets");
                    $total_hours = $wpdb->get_var("SELECT SUM(hours) FROM {$wpdb->prefix}worker_worksheets");
                    ?>  
                    <div class="worker-portal-admin-stat-box worker-portal-stat-worksheets">
                        <div class="worker-portal-admin-stat-icon">
                            <i class="dashicons dashicons-clipboard"></i>
                        </div>
                        <div class="worker-portal-admin-stat-content">
                            <div class="worker-portal-admin-stat-value"><?php echo $worksheets_count; ?></div>
                            <div class="worker-portal-admin-stat-label"><?php _e('Hojas de Trabajo', 'worker-portal'); ?></div>
                            <a href="#" class="worker-portal-admin-stat-action" data-tab="worksheets">
                                <i class="dashicons dashicons-arrow-right-alt"></i>
                                <?php _e('Ver todos', 'worker-portal'); ?>
                            </a>
                        </div>
                    </div>
                    <!-- Estadísticas de Incentivos -->
                    <?php
                    $incentives_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_incentives");
                    $total_incentives = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}worker_incentives");
                    ?>  
                    <div class="worker-portal-admin-stat-box worker-portal-stat-incentives">
                        <div class="worker-portal-admin-stat-icon">
                            <i class="dashicons dashicons-star-filled"></i>
                        </div>
                        <div class="worker-portal-admin-stat-content">
                            <div class="worker-portal-admin-stat-value"><?php echo $incentives_count; ?></div>
                            <div class="worker-portal-admin-stat-label"><?php _e('Incentivos', 'worker-portal'); ?></div>
                            <a href="#" class="worker-portal-admin-stat-action" data-tab="incentives">
                                <i class="dashicons dashicons-arrow-right-alt"></i>
                                <?php _e('Ver todos', 'worker-portal'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="worker-portal-admin-stat-summary">
                    <h3><?php _e('Resumen de Gastos', 'worker-portal'); ?></h3>
                    <p><?php _e('Total de gastos: ', 'worker-portal') . $total_expenses; ?></p>
                    <p><?php _e('Total de horas trabajadas: ', 'worker-portal') . $total_hours; ?></p>
                    <p><?php _e('Total de incentivos: ', 'worker-portal') . $total_incentives; ?></p>
                </div>
            </div>
        </div>
        <!-- Gastos Pendientes -->  
        <div id="tab-expenses" class="worker-portal-tab-content">
            <h2><?php _e('Gastos Pendientes', 'worker-portal'); ?></h2>
            <div class="worker-portal-admin-table">
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('Fecha', 'worker-portal'); ?></th>
                            <th><?php _e('Descripción', 'worker-portal'); ?></th>
                            <th><?php _e('Monto', 'worker-portal'); ?></th>
                            <th><?php _e('Estado', 'worker-portal'); ?></th>
                            <th><?php _e('Acciones', 'worker-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $expenses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}worker_expenses WHERE status = 'pending'");
                        foreach ($expenses as $expense) {
                            $status_icon = ($expense->status == 'approved') ? 'dashicons-yes' : 'dashicons-no';
                            ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($expense->date)); ?></td>
                                <td><?php echo $expense->description; ?></td>
                                <td><?php echo $expense->amount; ?></td>
                                <td>
                                    <span class="worker-portal-badge worker-portal-badge-<?php echo $expense->status; ?>">
                                        <?php echo ucfirst($expense->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="worker-portal-button worker-portal-button-small approve-expense" data-expense-id="<?php echo $expense->id; ?>">
                                        <i class="dashicons <?php echo $status_icon; ?>"></i>
                                    </button>
                                    <button type="button" class="worker-portal-button worker-portal-button-small view-expense" data-expense-id="<?php echo $expense->id; ?>">
                                        <i class="dashicons dashicons-visibility"></i>
                                    </button>
                                    <button type="button" class="worker-portal-button worker-portal-button-small reject-expense" data-expense-id="<?php echo $expense->id; ?>">
                                        <i class="dashicons dashicons-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="worker-portal-admin-table-actions">
                <button type="button" class="worker-portal-button worker-portal-button-primary" id="approve-selected-expenses">
                    <?php _e('Aprobar seleccionados', 'worker-portal'); ?>
                </button>
                <button type="button" class="worker-portal-button worker-portal-button-secondary" id="reject-selected-expenses">
                    <?php _e('Rechazar seleccionados', 'worker-portal'); ?>
                </button>
                <button type="button" class="worker-portal-button worker-portal-button-link" id="clear-filters">
                    <?php _e('Limpiar filtros', 'worker-portal'); ?>
                </button>
            </div>
        </div>
        <!-- Documentos -->
        <div id="tab-documents" class="worker-portal-tab-content">
            <h2><?php _e('Documentos', 'worker-portal'); ?></h2>
            <table class="worker-portal-admin-table">
                <thead>
                    <tr>
                        <th><?php _e('Nombre', 'worker-portal'); ?></th>
                        <th><?php _e('Descripción', 'worker-portal'); ?></th>
                        <th><?php _e('Estado', 'worker-portal'); ?></th>
                        <th><?php _e('Acciones', 'worker-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $documents = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}worker_documents");
                    foreach ($documents as $document) {
                        ?>
                        <tr>
                            <td><?php echo esc_html($document->name); ?></td>
                            <td><?php echo esc_html($document->description); ?></td>
                            <td>
                                <span class="worker-portal-badge worker-portal-badge-<?php echo esc_attr($document->status); ?>">
                                    <?php echo ucfirst(esc_html($document->status)); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="worker-portal-button worker-portal-button-small view-document" data-document-id="<?php echo esc_attr($document->id); ?>">
                                    <i class="dashicons dashicons-visibility"></i>
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="worker-portal-admin-table-actions">
                <button type="button" class="worker-portal-button worker-portal-button-primary" id="upload-new-document">
                    <?php _e('Subir nuevo documento', 'worker-portal'); ?>
                </button>
                <button type="button" class="worker-portal-button worker-portal-button-secondary" id="delete-selected-documents">
                    <?php _e('Eliminar seleccionados', 'worker-portal'); ?>
                </button>
                <button type="button" class="worker-portal-button worker-portal-button-link" id="clear-filters">
                    <?php _e('Limpiar filtros', 'worker-portal'); ?>
                </button>
            </div>
        </div>
        <!-- Incentivos -->
        <div id="tab-incentives" class="worker-portal-tab-content">
            <h2><?php _e('Incentivos', 'worker-portal'); ?></h2>
            <table class="worker-portal-admin-table">
                <thead>
                    <tr>
                        <th><?php _e('Nombre', 'worker-portal'); ?></th>
                        <th><?php _e('Descripción', 'worker-portal'); ?></th>
                        <th><?php _e('Estado', 'worker-portal'); ?></th>
                        <th><?php _e('Acciones', 'worker-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $incentives = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}worker_incentives");
                    foreach ($incentives as $incentive) {
                        ?>
                        <tr>
                            <td><?php echo esc_html($incentive->name); ?></td>
                            <td><?php echo esc_html($incentive->description); ?></td>
                            <td>
                                <span class="worker-portal-badge worker-portal-badge-<?php echo esc_attr($incentive->status); ?>">
                                    <?php echo ucfirst(esc_html($incentive->status)); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="worker-portal-button worker-portal-button-small view-incentive" data-incentive-id="<?php echo esc_attr($incentive->id); ?>">
                                    <i class="dashicons dashicons-visibility"></i>
                                </button>
                                <button type="button" class="worker-portal-button worker-portal-button-small delete-incentive" data-incentive-id="<?php echo esc_attr($incentive->id); ?>">
                                    <i class="dashicons dashicons-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="worker-portal-admin-table-actions">
                <button type="button" class="worker-portal-button worker-portal-button-primary" id="add-new-incentive">
                    <?php _e('Agregar nuevo incentivo', 'worker-portal'); ?>
                </button>
                <button type="button" class="worker-portal-button worker-portal-button-secondary" id="delete-selected-incentives">
                    <?php _e('Eliminar seleccionados', 'worker-portal'); ?>
                </button>
                <button type="button" class="worker-portal-button worker-portal-button-link" id="clear-filters">
                    <?php _e('Limpiar filtros', 'worker-portal'); ?>
                </button>
            </div>
        </div>
        <!-- Trabajadores -->   
        <div id="tab-workers" class="worker-portal-tab-content">
            <h2><?php _e('Trabajadores', 'worker-portal'); ?></h2>
            <table class="worker-portal-admin-table">
                <thead>
                    <tr>
                        <th><?php _e('Nombre', 'worker-portal'); ?></th>
                        <th><?php _e('Correo electrónico', 'worker-portal'); ?></th>
                        <th><?php _e('Estado', 'worker-portal'); ?></th>
                        <th><?php _e('Acciones', 'worker-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $workers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}users WHERE role NOT IN ('administrator')");
                    foreach ($workers as $worker) {
                        ?>
                        <tr>
                            <td><?php echo esc_html($worker->display_name); ?></td>
                            <td><?php echo esc_html($worker->user_email); ?></td>
                            <td>
                                <span class="worker-portal-badge worker-portal-badge-<?php echo esc_attr($worker->status); ?>">
                                    <?php echo ucfirst(esc_html($worker->status)); ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="worker-portal-button worker-portal-button-small view-worker" data-worker-id="<?php echo esc_attr($worker->ID); ?>">
                                    <i class="dashicons dashicons-visibility"></i>
                                </button>
                                <button type="button" class="worker-portal-button worker-portal-button-small delete-worker" data-worker-id="<?php echo esc_attr($worker->ID); ?>">
                                    <i class="dashicons dashicons-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="worker-portal-admin-table-actions">
                <button type="button" class="worker-portal-button worker-portal-button-primary" id="add-new-worker">
                    <?php _e('Agregar nuevo trabajador', 'worker-portal'); ?>
                </button>
                <button type="button" class="worker-portal-button worker-portal-button-secondary" id="delete-selected-workers">
                    <?php _e('Eliminar seleccionados', 'worker-portal'); ?>
                </button>
                <button type="button" class="worker-portal-button worker-portal-button-link" id="clear-filters">
                    <?php _e('Limpiar filtros', 'worker-portal'); ?>
                </button>
            </div>
        </div>
    </div>
</div>  
                <div class="worker-portal-loading">
                    <div class="worker-portal-spinner"></div>
                    <p><?php _e('Cargando detalles del gasto...', 'worker-portal'); ?></p>
                </div>
            </div>
        </div>
    </div>
        success: function(response) {
            if (response.success) {
                $('#expense-details-content').html(response.data);
                $('#expense-details-modal').show();
            } else {
                alert(response.data);
            }
        },
        error: function() {
            alert('<?php echo esc_js(__('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal')); ?>');
        }
    });
    }
});
</script>