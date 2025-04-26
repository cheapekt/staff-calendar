<?php
/**
 * Plantilla para la página de administración de gastos
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Cargar dependencias de base de datos
require_once WORKER_PORTAL_PATH . 'includes/class-database.php';
$database = Worker_Portal_Database::get_instance();

// Obtener ajustes actuales
$expense_types = get_option('worker_portal_expense_types', array(
    'km' => __('Kilometraje', 'worker-portal'),
    'hours' => __('Horas de desplazamiento', 'worker-portal'),
    'meal' => __('Dietas', 'worker-portal'),
    'other' => __('Otros', 'worker-portal')
));

$expense_approvers = get_option('worker_portal_expense_approvers', array());
$notification_email = get_option('worker_portal_expense_notification_email', get_option('admin_email'));

// Pestañas de navegación
$tabs = array(
    'dashboard' => __('Dashboard', 'worker-portal'),
    'pending' => __('Gastos Pendientes', 'worker-portal'),
    'all' => __('Todos los Gastos', 'worker-portal'),
    'settings' => __('Configuración', 'worker-portal')
);

$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
if (!array_key_exists($current_tab, $tabs)) {
    $current_tab = 'dashboard';
}

// Obtener estadísticas para el dashboard
$total_expenses = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses");
$pending_expenses = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses WHERE status = 'pending'");
$approved_expenses = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses WHERE status = 'approved'");
$rejected_expenses = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses WHERE status = 'rejected'");

// Obtener los gastos más recientes para el dashboard
$recent_expenses = $wpdb->get_results(
    "SELECT e.*, u.display_name AS user_name
     FROM {$wpdb->prefix}worker_expenses e
     LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
     ORDER BY e.report_date DESC LIMIT 5",
    ARRAY_A
);

// Obtener usuarios para el selector de aprobadores
$users = get_users(array(
    'role__in' => array('administrator', 'editor', 'supervisor'),
    'orderby' => 'display_name'
));

// Obtener gastos según la pestaña actual
$status_filter = '';
$page_title = '';

switch ($current_tab) {
    case 'dashboard':
        $page_title = __('Dashboard de Gastos', 'worker-portal');
        break;
    case 'pending':
        $status_filter = 'pending';
        $page_title = __('Gastos Pendientes de Aprobación', 'worker-portal');
        break;
    case 'all':
        $page_title = __('Todos los Gastos', 'worker-portal');
        break;
    case 'settings':
        $page_title = __('Configuración de Gastos', 'worker-portal');
        break;
}

// Paginación para listados de gastos
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($current_page - 1) * $per_page;

// Obtener gastos para las pestañas de listado
if ($current_tab === 'pending' || $current_tab === 'all') {
    // Construir consulta base
    $query = "SELECT e.*, u.display_name AS user_name
              FROM {$wpdb->prefix}worker_expenses e
              LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID";
    
    $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses";
    
    // Añadir filtro de estado si es necesario
    if ($status_filter) {
        $query .= " WHERE e.status = '$status_filter'";
        $count_query .= " WHERE status = '$status_filter'";
    }
    
    // Ordenar y limitar
    $query .= " ORDER BY e.report_date DESC LIMIT $per_page OFFSET $offset";
    
    // Ejecutar consultas
    $expenses = $wpdb->get_results($query, ARRAY_A);
    $total_items = $wpdb->get_var($count_query);
    
    // Calcular total de páginas
    $total_pages = ceil($total_items / $per_page);
}
?>

<div class="wrap worker-portal-admin">
    <h1><?php echo esc_html($page_title); ?></h1>
    
    <!-- Pestañas de navegación -->
    <nav class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($tabs as $tab_key => $tab_label): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-expenses&tab=' . $tab_key)); ?>" class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_label); ?>
                <?php if ($tab_key === 'pending' && $pending_expenses > 0): ?>
                    <span class="pending-count"><?php echo esc_html($pending_expenses); ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="worker-portal-admin-content">
        <?php if ($current_tab === 'dashboard'): ?>
            <!-- Dashboard -->
            <div class="worker-portal-dashboard">
                <div class="worker-portal-dashboard-widgets">
                    <!-- Estadísticas generales -->
                    <div class="worker-portal-widget worker-portal-stats-widget">
                        <h2><?php _e('Resumen de Gastos', 'worker-portal'); ?></h2>
                        <div class="worker-portal-stats-grid">
                            <div class="worker-portal-stat-box worker-portal-stat-total">
                                <div class="worker-portal-stat-value"><?php echo esc_html($total_expenses); ?></div>
                                <div class="worker-portal-stat-label"><?php _e('Total de Gastos', 'worker-portal'); ?></div>
                            </div>
                            
                            <div class="worker-portal-stat-box worker-portal-stat-pending">
                                <div class="worker-portal-stat-value"><?php echo esc_html($pending_expenses); ?></div>
                                <div class="worker-portal-stat-label"><?php _e('Pendientes', 'worker-portal'); ?></div>
                                <?php if ($pending_expenses > 0): ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-expenses&tab=pending')); ?>" class="worker-portal-stat-action">
                                        <?php _e('Ver Pendientes', 'worker-portal'); ?> →
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="worker-portal-stat-box worker-portal-stat-approved">
                                <div class="worker-portal-stat-value"><?php echo esc_html($approved_expenses); ?></div>
                                <div class="worker-portal-stat-label"><?php _e('Aprobados', 'worker-portal'); ?></div>
                            </div>
                            
                            <div class="worker-portal-stat-box worker-portal-stat-rejected">
                                <div class="worker-portal-stat-value"><?php echo esc_html($rejected_expenses); ?></div>
                                <div class="worker-portal-stat-label"><?php _e('Rechazados', 'worker-portal'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gastos recientes -->
                    <div class="worker-portal-widget worker-portal-recent-widget">
                        <h2><?php _e('Gastos Recientes', 'worker-portal'); ?></h2>
                        
                        <?php if (empty($recent_expenses)): ?>
                            <p class="worker-portal-no-items"><?php _e('No hay gastos registrados.', 'worker-portal'); ?></p>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Fecha', 'worker-portal'); ?></th>
                                        <th><?php _e('Usuario', 'worker-portal'); ?></th>
                                        <th><?php _e('Tipo', 'worker-portal'); ?></th>
                                        <th><?php _e('Importe', 'worker-portal'); ?></th>
                                        <th><?php _e('Estado', 'worker-portal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_expenses as $expense): ?>
                                        <tr>
                                            <td>
                                                <?php echo date_i18n(get_option('date_format'), strtotime($expense['report_date'])); ?>
                                            </td>
                                            <td><?php echo esc_html($expense['user_name']); ?></td>
                                            <td>
                                                <?php 
                                                echo isset($expense_types[$expense['expense_type']]) 
                                                    ? esc_html($expense_types[$expense['expense_type']]) 
                                                    : esc_html($expense['expense_type']); 
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                // Mostrar importe con formato según tipo
                                                switch ($expense['expense_type']) {
                                                    case 'km':
                                                        echo esc_html($expense['amount']) . ' Km';
                                                        break;
                                                    case 'hours':
                                                        echo esc_html($expense['amount']) . ' ' . __('Horas', 'worker-portal');
                                                        break;
                                                    default:
                                                        echo esc_html(number_format($expense['amount'], 2, ',', '.')) . ' €';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                switch ($expense['status']) {
                                                    case 'pending':
                                                        echo '<span class="worker-portal-status-pending">' . __('Pendiente', 'worker-portal') . '</span>';
                                                        break;
                                                    case 'approved':
                                                        echo '<span class="worker-portal-status-approved">' . __('Aprobado', 'worker-portal') . '</span>';
                                                        break;
                                                    case 'rejected':
                                                        echo '<span class="worker-portal-status-rejected">' . __('Denegado', 'worker-portal') . '</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <p class="worker-portal-view-all">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-expenses&tab=all')); ?>" class="button-secondary">
                                    <?php _e('Ver Todos los Gastos', 'worker-portal'); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        <?php elseif ($current_tab === 'pending' || $current_tab === 'all'): ?>
            <!-- Listado de gastos -->
            <div class="worker-portal-expenses-list">
                <!-- Filtros -->
                <div class="worker-portal-admin-filters">
                    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                        <input type="hidden" name="page" value="worker-portal-expenses">
                        <input type="hidden" name="tab" value="<?php echo esc_attr($current_tab); ?>">
                        
                        <div class="worker-portal-admin-filters-row">
                            <div class="worker-portal-admin-filter">
                                <label for="filter-user"><?php _e('Usuario:', 'worker-portal'); ?></label>
                                <select id="filter-user" name="user_id">
                                    <option value=""><?php _e('Todos los usuarios', 'worker-portal'); ?></option>
                                    <?php foreach (get_users() as $user): ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected(isset($_GET['user_id']) ? $_GET['user_id'] : '', $user->ID); ?>>
                                            <?php echo esc_html($user->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="worker-portal-admin-filter">
                                <label for="filter-type"><?php _e('Tipo:', 'worker-portal'); ?></label>
                                <select id="filter-type" name="expense_type">
                                    <option value=""><?php _e('Todos los tipos', 'worker-portal'); ?></option>
                                    <?php foreach ($expense_types as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected(isset($_GET['expense_type']) ? $_GET['expense_type'] : '', $key); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <?php if ($current_tab === 'all'): ?>
                                <div class="worker-portal-admin-filter">
                                    <label for="filter-status"><?php _e('Estado:', 'worker-portal'); ?></label>
                                    <select id="filter-status" name="status">
                                        <option value=""><?php _e('Todos los estados', 'worker-portal'); ?></option>
                                        <option value="pending" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'pending'); ?>><?php _e('Pendiente', 'worker-portal'); ?></option>
                                        <option value="approved" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'approved'); ?>><?php _e('Aprobado', 'worker-portal'); ?></option>
                                        <option value="rejected" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'rejected'); ?>><?php _e('Denegado', 'worker-portal'); ?></option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <div class="worker-portal-admin-filter">
                                <label for="filter-date-from"><?php _e('Desde:', 'worker-portal'); ?></label>
                                <input type="date" id="filter-date-from" name="date_from" value="<?php echo isset($_GET['date_from']) ? esc_attr($_GET['date_from']) : ''; ?>">
                            </div>
                            
                            <div class="worker-portal-admin-filter">
                                <label for="filter-date-to"><?php _e('Hasta:', 'worker-portal'); ?></label>
                                <input type="date" id="filter-date-to" name="date_to" value="<?php echo isset($_GET['date_to']) ? esc_attr($_GET['date_to']) : ''; ?>">
                            </div>
                            
                            <div class="worker-portal-admin-filter-actions">
                                <button type="submit" class="button button-secondary">
                                    <span class="dashicons dashicons-search"></span> <?php _e('Filtrar', 'worker-portal'); ?>
                                </button>
                                
                                <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-expenses&tab=' . $current_tab)); ?>" class="button button-link">
                                    <?php _e('Limpiar filtros', 'worker-portal'); ?>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if (empty($expenses)): ?>
                    <div class="worker-portal-no-items-message">
                        <p><?php _e('No se encontraron gastos con los criterios seleccionados.', 'worker-portal'); ?></p>
                    </div>
                <?php else: ?>
                    <!-- Acciones masivas si es la pestaña de pendientes -->
                    <?php if ($current_tab === 'pending'): ?>
                        <div class="worker-portal-bulk-actions">
                            <form id="bulk-approve-form" method="post">
                                <div class="worker-portal-bulk-actions-row">
                                    <div class="worker-portal-bulk-action-select">
                                        <label for="bulk-action"><?php _e('Acción masiva:', 'worker-portal'); ?></label>
                                        <select id="bulk-action" name="bulk_action">
                                            <option value=""><?php _e('Seleccionar acción', 'worker-portal'); ?></option>
                                            <option value="approve"><?php _e('Aprobar seleccionados', 'worker-portal'); ?></option>
                                            <option value="reject"><?php _e('Denegar seleccionados', 'worker-portal'); ?></option>
                                        </select>
                                    </div>
                                    
                                    <div class="worker-portal-bulk-action-apply">
                                        <button type="submit" class="button button-primary" id="apply-bulk-action">
                                            <?php _e('Aplicar', 'worker-portal'); ?>
                                        </button>
                                    </div>
                                </div>
                                
                                <input type="hidden" name="expenses_nonce" value="<?php echo wp_create_nonce('worker_portal_expenses_bulk_action'); ?>">
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tabla de gastos -->
                    <form id="expenses-list-form">
                        <table class="wp-list-table widefat fixed striped expenses-table">
                            <thead>
                                <tr>
                                    <?php if ($current_tab === 'pending'): ?>
                                        <th class="check-column">
                                            <input type="checkbox" id="select-all-expenses">
                                        </th>
                                    <?php endif; ?>
                                    <th><?php _e('ID', 'worker-portal'); ?></th>
                                    <th><?php _e('Fecha', 'worker-portal'); ?></th>
                                    <th><?php _e('Usuario', 'worker-portal'); ?></th>
                                    <th><?php _e('Tipo', 'worker-portal'); ?></th>
                                    <th><?php _e('Descripción', 'worker-portal'); ?></th>
                                    <th><?php _e('Importe', 'worker-portal'); ?></th>
                                    <th><?php _e('Ticket', 'worker-portal'); ?></th>
                                    <th><?php _e('Estado', 'worker-portal'); ?></th>
                                    <th><?php _e('Acciones', 'worker-portal'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <?php if ($current_tab === 'pending'): ?>
                                            <td class="check-column">
                                                <input type="checkbox" name="expense_ids[]" value="<?php echo esc_attr($expense['id']); ?>" class="expense-checkbox">
                                            </td>
                                        <?php endif; ?>
                                        <td><?php echo esc_html($expense['id']); ?></td>
                                        <td>
                                            <?php echo date_i18n(get_option('date_format'), strtotime($expense['report_date'])); ?><br>
                                            <small><?php echo date_i18n(get_option('date_format'), strtotime($expense['expense_date'])); ?></small>
                                        </td>
                                        <td><?php echo esc_html($expense['user_name']); ?></td>
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
                                            // Mostrar importe con formato según tipo
                                            switch ($expense['expense_type']) {
                                                case 'km':
                                                    echo esc_html($expense['amount']) . ' Km';
                                                    break;
                                                case 'hours':
                                                    echo esc_html($expense['amount']) . ' ' . __('Horas', 'worker-portal');
                                                    break;
                                                default:
                                                    echo esc_html(number_format($expense['amount'], 2, ',', '.')) . ' €';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($expense['has_receipt']): ?>
                                                <span class="worker-portal-status-yes"><?php _e('Sí', 'worker-portal'); ?></span>
                                                <?php if (!empty($expense['receipt_path'])): ?>
                                                    <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . $expense['receipt_path']); ?>" target="_blank" class="button button-small view-receipt">
                                                        <span class="dashicons dashicons-visibility"></span>
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="worker-portal-status-no"><?php _e('No', 'worker-portal'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            switch ($expense['status']) {
                                                case 'pending':
                                                    echo '<span class="worker-portal-status-pending">' . __('Pendiente', 'worker-portal') . '</span>';
                                                    break;
                                                case 'approved':
                                                    echo '<span class="worker-portal-status-approved">' . __('Aprobado', 'worker-portal') . '</span>';
                                                    if (!empty($expense['approved_by'])) {
                                                        $approver = get_userdata($expense['approved_by']);
                                                        if ($approver) {
                                                            echo '<br><small>' . sprintf(__('por %s', 'worker-portal'), $approver->display_name) . '</small>';
                                                        }
                                                    }
                                                    break;
                                                case 'rejected':
                                                    echo '<span class="worker-portal-status-rejected">' . __('Denegado', 'worker-portal') . '</span>';
                                                    if (!empty($expense['approved_by'])) {
                                                        $approver = get_userdata($expense['approved_by']);
                                                        if ($approver) {
                                                            echo '<br><small>' . sprintf(__('por %s', 'worker-portal'), $approver->display_name) . '</small>';
                                                        }
                                                    }
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td class="expense-actions">
                                            <?php if ($expense['status'] === 'pending'): ?>
                                                <button type="button" class="button button-small approve-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                                    <span class="dashicons dashicons-yes"></span> <?php _e('Aprobar', 'worker-portal'); ?>
                                                </button>
                                                <button type="button" class="button button-small reject-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                                    <span class="dashicons dashicons-no"></span> <?php _e('Denegar', 'worker-portal'); ?>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="button button-small view-expense-details" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                                    <span class="dashicons dashicons-info"></span> <?php _e('Detalles', 'worker-portal'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                    
                    <!-- Paginación -->
                    <?php if ($total_pages > 1): ?>
                        <div class="tablenav">
                            <div class="tablenav-pages">
                                <span class="displaying-num">
                                    <?php printf(_n('%s elemento', '%s elementos', $total_items, 'worker-portal'), number_format_i18n($total_items)); ?>
                                </span>
                                
                                <span class="pagination-links">
                                    <?php
                                    // Enlace a primera página
                                    if ($current_page > 1) {
                                        echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1)) . '"><span class="screen-reader-text">' . __('Primera página', 'worker-portal') . '</span><span aria-hidden="true">&laquo;</span></a>';
                                    } else {
                                        echo '<span class="first-page button disabled"><span class="screen-reader-text">' . __('Primera página', 'worker-portal') . '</span><span aria-hidden="true">&laquo;</span></span>';
                                    }
                                    
                                    // Enlace a página anterior
                                    if ($current_page > 1) {
                                        echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $current_page - 1)) . '"><span class="screen-reader-text">' . __('Página anterior', 'worker-portal') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                                    } else {
                                        echo '<span class="prev-page button disabled"><span class="screen-reader-text">' . __('Página anterior', 'worker-portal') . '</span><span aria-hidden="true">&lsaquo;</span></span>';
                                    }
                                    
                                    // Información de página actual
                                    echo '<span class="paging-input">' . $current_page . ' ' . __('de', 'worker-portal') . ' <span class="total-pages">' . $total_pages . '</span></span>';
                                    
                                    // Enlace a página siguiente
                                    if ($current_page < $total_pages) {
                                        echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $current_page + 1)) . '"><span class="screen-reader-text">' . __('Página siguiente', 'worker-portal') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                                    } else {
                                        echo '<span class="next-page button disabled"><span class="screen-reader-text">' . __('Página siguiente', 'worker-portal') . '</span><span aria-hidden="true">&rsaquo;</span></span>';
                                    }
                                    
                                    // Enlace a última página
                                    if ($current_page < $total_pages) {
                                        echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages)) . '"><span class="screen-reader-text">' . __('Última página', 'worker-portal') . '</span><span aria-hidden="true">&raquo;</span></a>';
                                    } else {
                                        echo '<span class="last-page button disabled"><span class="screen-reader-text">' . __('Última página', 'worker-portal') . '</span><span aria-hidden="true">&raquo;</span></span>';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Exportar gastos -->
                    <div class="worker-portal-admin-actions">
                        <button type="button" id="export-expenses-button" class="button button-secondary">
                            <span class="dashicons dashicons-download"></span> <?php _e('Exportar a Excel', 'worker-portal'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php elseif ($current_tab === 'settings'): ?>
            <!-- Configuración de gastos -->
            <div class="worker-portal-admin-settings">
                <form method="post" action="options.php" class="worker-portal-settings-form">
                    <?php
                    settings_fields('worker_portal_expenses');
                    do_settings_sections('worker_portal_expenses');
                    ?>
                    
                    <h2><?php _e('Tipos de Gastos', 'worker-portal'); ?></h2>
                    <p class="description"><?php _e('Configura los tipos de gastos que los empleados pueden reportar.', 'worker-portal'); ?></p>
                    
                    <table class="form-table expense-types-table" role="presentation">
                        <thead>
                            <tr>
                                <th><?php _e('Clave', 'worker-portal'); ?></th>
                                <th><?php _e('Nombre', 'worker-portal'); ?></th>
                                <th><?php _e('Acciones', 'worker-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="expense-types-list">
                            <?php foreach ($expense_types as $key => $label): ?>
                                <tr class="expense-type-row">
                                    <td>
                                        <input type="text" name="worker_portal_expense_types[keys][]" value="<?php echo esc_attr($key); ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" name="worker_portal_expense_types[labels][]" value="<?php echo esc_attr($label); ?>" required>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small remove-expense-type">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">
                                    <button type="button" id="add-expense-type" class="button button-secondary">
                                        <span class="dashicons dashicons-plus"></span> <?php _e('Añadir tipo de gasto', 'worker-portal'); ?>
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <h2><?php _e('Aprobadores de Gastos', 'worker-portal'); ?></h2>
                    <p class="description"><?php _e('Selecciona los usuarios que pueden aprobar gastos además de los administradores.', 'worker-portal'); ?></p>
                    
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php _e('Aprobadores', 'worker-portal'); ?></th>
                            <td>
                                <select name="worker_portal_expense_approvers[]" multiple size="6">
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected(in_array($user->ID, $expense_approvers)); ?>>
                                            <?php echo esc_html($user->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Mantén presionada la tecla Ctrl (o Cmd en Mac) para seleccionar múltiples usuarios.', 'worker-portal'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <h2><?php _e('Notificaciones', 'worker-portal'); ?></h2>
                    <p class="description"><?php _e('Configura las opciones de notificación por email.', 'worker-portal'); ?></p>
                    
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php _e('Email para notificaciones', 'worker-portal'); ?></th>
                            <td>
                                <input type="email" name="worker_portal_expense_notification_email" value="<?php echo esc_attr($notification_email); ?>" class="regular-text">
                                <p class="description"><?php _e('Email que recibirá las notificaciones de nuevos gastos.', 'worker-portal'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para visualizar recibos -->
<div id="receipt-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Justificante', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="receipt-modal-content"></div>
        </div>
    </div>
</div>

<!-- Modal para detalles de gasto -->
<div id="expense-details-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Detalles del Gasto', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="expense-details-content"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Seleccionar/deseleccionar todos los gastos
    $("#select-all-expenses").on("click", function() {
        $(".expense-checkbox").prop("checked", $(this).prop("checked"));
    });
    
    // Comprobar selección para habilitar/deshabilitar botón de acción masiva
    function checkBulkSelection() {
        if ($(".expense-checkbox:checked").length > 0) {
            $("#apply-bulk-action").prop("disabled", false);
        } else {
            $("#apply-bulk-action").prop("disabled", true);
        }
    }
    
    $(".expense-checkbox").on("change", checkBulkSelection);
    $("#select-all-expenses").on("change", checkBulkSelection);
    
    // Inicialmente desactivar botón de acción masiva
    $("#apply-bulk-action").prop("disabled", true);
    
    // Enviar formulario de acciones masivas
    $("#bulk-approve-form").on("submit", function(e) {
        e.preventDefault();
        
        const action = $("#bulk-action").val();
        if (!action) {
            alert("<?php echo esc_js(__('Por favor, selecciona una acción.', 'worker-portal')); ?>");
            return;
        }
        
        const checkedExpenses = $(".expense-checkbox:checked");
        if (checkedExpenses.length === 0) {
            alert("<?php echo esc_js(__('Por favor, selecciona al menos un gasto.', 'worker-portal')); ?>");
            return;
        }
        
        // Confirmar acción
        if (!confirm("<?php echo esc_js(__('¿Estás seguro? Esta acción no se puede deshacer.', 'worker-portal')); ?>")) {
            return;
        }
        
        // Recoger IDs de gastos seleccionados
        const expenseIds = [];
        checkedExpenses.each(function() {
            expenseIds.push($(this).val());
        });
        
        // Enviar solicitud AJAX
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "bulk_expense_action",
                nonce: "<?php echo wp_create_nonce('worker_portal_expenses_bulk_action'); ?>",
                bulk_action: action,
                expense_ids: expenseIds
            },
            beforeSend: function() {
                $("#apply-bulk-action").prop("disabled", true).text("<?php echo esc_js(__('Procesando...', 'worker-portal')); ?>");
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.reload();
                } else {
                    alert(response.data);
                    $("#apply-bulk-action").prop("disabled", false).text("<?php echo esc_js(__('Aplicar', 'worker-portal')); ?>");
                }
            },
            error: function() {
                alert("<?php echo esc_js(__('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal')); ?>");
                $("#apply-bulk-action").prop("disabled", false).text("<?php echo esc_js(__('Aplicar', 'worker-portal')); ?>");
            }
        });
    });
    
    // Aprobar un gasto individual
    $(".approve-expense").on("click", function() {
        const expenseId = $(this).data("expense-id");
        
        // Confirmar acción
        if (!confirm("<?php echo esc_js(__('¿Estás seguro de aprobar este gasto?', 'worker-portal')); ?>")) {
            return;
        }
        
        // Enviar solicitud AJAX
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "approve_expense",
                nonce: "<?php echo wp_create_nonce('worker_portal_expenses_nonce'); ?>",
                expense_id: expenseId
            },
            beforeSend: function() {
                $(this).prop("disabled", true).text("<?php echo esc_js(__('Procesando...', 'worker-portal')); ?>");
            }.bind(this),
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    window.location.reload();
                } else {
                    alert(response.data);
                    $(this).prop("disabled", false).html('<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Aprobar', 'worker-portal')); ?>');
                }
            }.bind(this),
            error: function() {
                alert("<?php echo esc_js(__('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal')); ?>");
                $(this).prop("disabled", false).html('<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Aprobar', 'worker-portal')); ?>');
            }.bind(this)
        });
    });
    
    // Rechazar un gasto individual
    $(".reject-expense").on("click", function() {
        const expenseId = $(this).data("expense-id");
        
        // Confirmar acción
        if (!confirm("<?php echo esc_js(__('¿Estás seguro de denegar este gasto?', 'worker-portal')); ?>")) {
            return;
        }
        
        // Enviar solicitud AJAX
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "reject_expense",
                nonce: "<?php echo wp_create_nonce('worker_portal_expenses_nonce'); ?>",
                expense_id: expenseId
            },
            beforeSend: function() {
                $(this).prop("disabled", true).text("<?php echo esc_js(__('Procesando...', 'worker-portal')); ?>");
            }.bind(this),
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    window.location.reload();
                } else {
                    alert(response.data);
                    $(this).prop("disabled", false).html('<span class="dashicons dashicons-no"></span> <?php echo esc_js(__('Denegar', 'worker-portal')); ?>');
                }
            }.bind(this),
            error: function() {
                alert("<?php echo esc_js(__('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal')); ?>");
                $(this).prop("disabled", false).html('<span class="dashicons dashicons-no"></span> <?php echo esc_js(__('Denegar', 'worker-portal')); ?>');
            }.bind(this)
        });
    });
    
    // Ver recibo en modal
    $(".view-receipt").on("click", function(e) {
        e.preventDefault();
        
        const receiptUrl = $(this).attr("href");
        let contentHtml = '';
        
        // Determinar tipo de contenido por extensión
        if (receiptUrl.toLowerCase().endsWith('.pdf')) {
            contentHtml = `<iframe src="${receiptUrl}" style="width:100%; height:500px; border:none;"></iframe>`;
        } else {
            contentHtml = `<img src="${receiptUrl}" style="max-width:100%; max-height:500px;">`;
        }
        
        // Mostrar en modal
        $("#receipt-modal-content").html(contentHtml);
        $("#receipt-modal").fadeIn();
    });
    
    // Ver detalles de gasto
    $(".view-expense-details").on("click", function() {
        const expenseId = $(this).data("expense-id");
        
        // Cargar detalles mediante AJAX
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "get_expense_details",
                nonce: "<?php echo wp_create_nonce('worker_portal_expenses_nonce'); ?>",
                expense_id: expenseId
            },
            beforeSend: function() {
                $("#expense-details-content").html('<div class="worker-portal-loader"><div class="worker-portal-loader-spinner"></div></div>');
                $("#expense-details-modal").fadeIn();
            },
            success: function(response) {
                if (response.success) {
                    $("#expense-details-content").html(response.data);
                } else {
                    $("#expense-details-content").html('<div class="worker-portal-error">' + response.data + '</div>');
                }
            },
            error: function() {
                $("#expense-details-content").html('<div class="worker-portal-error"><?php echo esc_js(__('Ha ocurrido un error al cargar los detalles.', 'worker-portal')); ?></div>');
            }
        });
    });
    
    // Cerrar modales
    $(".worker-portal-modal-close").on("click", function() {
        $(this).closest(".worker-portal-modal").fadeOut();
    });
    
    $(window).on("click", function(e) {
        if ($(e.target).hasClass("worker-portal-modal")) {
            $(".worker-portal-modal").fadeOut();
        }
    });
    
    // Añadir tipo de gasto
    $("#add-expense-type").on("click", function() {
        const newRow = `
            <tr class="expense-type-row">
                <td>
                    <input type="text" name="worker_portal_expense_types[keys][]" required>
                </td>
                <td>
                    <input type="text" name="worker_portal_expense_types[labels][]" required>
                </td>
                <td>
                    <button type="button" class="button button-small remove-expense-type">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>
        `;
        
        $("#expense-types-list").append(newRow);
    });
    
    // Eliminar tipo de gasto
    $(document).on("click", ".remove-expense-type", function() {
        // Si solo queda un tipo, mostrar mensaje
        if ($(".expense-type-row").length <= 1) {
            alert("<?php echo esc_js(__('Debe existir al menos un tipo de gasto.', 'worker-portal')); ?>");
            return;
        }
        
        $(this).closest("tr").remove();
    });
    
    // Exportar gastos
    $("#export-expenses-button").on("click", function() {
        // Obtener parámetros de filtrado de la URL actual
        const urlParams = new URLSearchParams(window.location.search);
        const user_id = urlParams.get('user_id') || '';
        const expense_type = urlParams.get('expense_type') || '';
        const status = urlParams.get('status') || '';
        const date_from = urlParams.get('date_from') || '';
        const date_to = urlParams.get('date_to') || '';
        
        // Mostrar indicador de carga
        $(this).prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> <?php echo esc_js(__('Exportando...', 'worker-portal')); ?>');
        
        // Realizar petición AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'admin_export_expenses',
                nonce: '<?php echo wp_create_nonce('worker_portal_expenses_nonce'); ?>',
                user_id: user_id,
                expense_type: expense_type, 
                status: status,
                date_from: date_from,
                date_to: date_to
            },
            success: function(response) {
                if (response.success) {
                    // Crear enlace para descargar
                    const link = document.createElement('a');
                    link.href = response.data.file_url;
                    link.download = response.data.filename;
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    alert('<?php echo esc_js(__('El archivo ha sido generado correctamente.', 'worker-portal')); ?>');
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Ha ocurrido un error durante la exportación.', 'worker-portal')); ?>');
            },
            complete: function() {
                // Restaurar botón
                $("#export-expenses-button").prop('disabled', false).html('<span class="dashicons dashicons-download"></span> <?php echo esc_js(__('Exportar a Excel', 'worker-portal')); ?>');
            }
        });
    });
});
</script>