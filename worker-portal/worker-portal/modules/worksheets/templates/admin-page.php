<?php
/**
 * Plantilla para la página de administración de hojas de trabajo
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

$worksheet_validators = get_option('worker_portal_worksheet_validators', array());

// Pestañas de navegación
$tabs = array(
    'dashboard' => __('Dashboard', 'worker-portal'),
    'pending' => __('Hojas Pendientes', 'worker-portal'),
    'all' => __('Todas las Hojas', 'worker-portal'),
    'projects' => __('Proyectos', 'worker-portal'),
    'settings' => __('Configuración', 'worker-portal')
);

$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
if (!array_key_exists($current_tab, $tabs)) {
    $current_tab = 'dashboard';
}

// Obtener estadísticas para el dashboard
$total_worksheets = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_worksheets");
$pending_worksheets = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_worksheets WHERE status = 'pending'");
$validated_worksheets = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_worksheets WHERE status = 'validated'");
$total_projects = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_projects WHERE status = 'active'");

// Obtener las hojas de trabajo más recientes para el dashboard
$recent_worksheets = $wpdb->get_results(
    "SELECT w.*, u.display_name AS user_name, p.name AS project_name
     FROM {$wpdb->prefix}worker_worksheets w
     LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID
     LEFT JOIN {$wpdb->prefix}worker_projects p ON w.project_id = p.id
     ORDER BY w.work_date DESC LIMIT 5",
    ARRAY_A
);

// Obtener usuarios para el selector de validadores
$users = get_users(array(
    'role__in' => array('administrator', 'editor', 'supervisor'),
    'orderby' => 'display_name'
));

// Obtener proyectos
$projects = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}worker_projects WHERE status = 'active' ORDER BY name ASC",
    ARRAY_A
);

// Obtener hojas según la pestaña actual
$status_filter = '';
$page_title = '';

switch ($current_tab) {
    case 'dashboard':
        $page_title = __('Dashboard de Hojas de Trabajo', 'worker-portal');
        break;
    case 'pending':
        $status_filter = 'pending';
        $page_title = __('Hojas de Trabajo Pendientes de Validación', 'worker-portal');
        break;
    case 'all':
        $page_title = __('Todas las Hojas de Trabajo', 'worker-portal');
        break;
    case 'projects':
        $page_title = __('Gestión de Proyectos', 'worker-portal');
        break;
    case 'settings':
        $page_title = __('Configuración de Hojas de Trabajo', 'worker-portal');
        break;
}

// Paginación para listados de hojas
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($current_page - 1) * $per_page;

// Obtener hojas para las pestañas de listado
if ($current_tab === 'pending' || $current_tab === 'all') {
    // Construir consulta base
    $query = "SELECT w.*, u.display_name AS user_name, p.name AS project_name
              FROM {$wpdb->prefix}worker_worksheets w
              LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID
              LEFT JOIN {$wpdb->prefix}worker_projects p ON w.project_id = p.id";
    
    $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}worker_worksheets";
    
    // Añadir filtro de estado si es necesario
    if ($status_filter) {
        $query .= " WHERE w.status = '$status_filter'";
        $count_query .= " WHERE status = '$status_filter'";
    }
    
    // Ordenar y limitar
    $query .= " ORDER BY w.work_date DESC LIMIT $per_page OFFSET $offset";
    
    // Ejecutar consultas
    $worksheets = $wpdb->get_results($query, ARRAY_A);
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
            <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-worksheets&tab=' . $tab_key)); ?>" class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_label); ?>
                <?php if ($tab_key === 'pending' && $pending_worksheets > 0): ?>
                    <span class="pending-count"><?php echo esc_html($pending_worksheets); ?></span>
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
                      <h2><?php _e('Resumen de Hojas de Trabajo', 'worker-portal'); ?></h2>
                       <div class="worker-portal-stats-grid">
                           <div class="worker-portal-stat-box worker-portal-stat-total">
                               <div class="worker-portal-stat-value"><?php echo esc_html($total_worksheets); ?></div>
                               <div class="worker-portal-stat-label"><?php _e('Total de Hojas', 'worker-portal'); ?></div>
                           </div>
                           
                           <div class="worker-portal-stat-box worker-portal-stat-pending">
                               <div class="worker-portal-stat-value"><?php echo esc_html($pending_worksheets); ?></div>
                               <div class="worker-portal-stat-label"><?php _e('Pendientes', 'worker-portal'); ?></div>
                               <?php if ($pending_worksheets > 0): ?>
                                   <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-worksheets&tab=pending')); ?>" class="worker-portal-stat-action">
                                       <?php _e('Ver pendientes', 'worker-portal'); ?> →
                                   </a>
                               <?php endif; ?>
                           </div>
                           
                           <div class="worker-portal-stat-box worker-portal-stat-validated">
                               <div class="worker-portal-stat-value"><?php echo esc_html($validated_worksheets); ?></div>
                               <div class="worker-portal-stat-label"><?php _e('Validadas', 'worker-portal'); ?></div>
                           </div>
                           
                           <div class="worker-portal-stat-box worker-portal-stat-projects">
                               <div class="worker-portal-stat-value"><?php echo esc_html($total_projects); ?></div>
                               <div class="worker-portal-stat-label"><?php _e('Proyectos', 'worker-portal'); ?></div>
                               <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-worksheets&tab=projects')); ?>" class="worker-portal-stat-action">
                                   <?php _e('Ver proyectos', 'worker-portal'); ?> →
                               </a>
                           </div>
                       </div>
                   </div>
                   
                   <!-- Hojas de trabajo recientes -->
                   <div class="worker-portal-widget worker-portal-recent-widget">
                       <h2><?php _e('Hojas de Trabajo Recientes', 'worker-portal'); ?></h2>
                       
                       <?php if (empty($recent_worksheets)): ?>
                           <p class="worker-portal-no-items"><?php _e('No hay hojas de trabajo registradas.', 'worker-portal'); ?></p>
                       <?php else: ?>
                           <table class="wp-list-table widefat fixed striped">
                               <thead>
                                   <tr>
                                       <th><?php _e('Fecha', 'worker-portal'); ?></th>
                                       <th><?php _e('Usuario', 'worker-portal'); ?></th>
                                       <th><?php _e('Proyecto', 'worker-portal'); ?></th>
                                       <th><?php _e('Sistema', 'worker-portal'); ?></th>
                                       <th><?php _e('Horas', 'worker-portal'); ?></th>
                                       <th><?php _e('Estado', 'worker-portal'); ?></th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <?php foreach ($recent_worksheets as $worksheet): ?>
                                       <tr>
                                           <td>
                                               <?php echo date_i18n(get_option('date_format'), strtotime($worksheet['work_date'])); ?>
                                           </td>
                                           <td><?php echo esc_html($worksheet['user_name']); ?></td>
                                           <td><?php echo esc_html($worksheet['project_name']); ?></td>
                                           <td>
                                               <?php 
                                               echo isset($system_types[$worksheet['system_type']]) 
                                                   ? esc_html($system_types[$worksheet['system_type']]) 
                                                   : esc_html($worksheet['system_type']); 
                                               ?>
                                           </td>
                                           <td><?php echo esc_html($worksheet['hours']); ?> h</td>
                                           <td>
                                               <?php
                                               switch ($worksheet['status']) {
                                                   case 'pending':
                                                       echo '<span class="worker-portal-status-pending">' . __('Pendiente', 'worker-portal') . '</span>';
                                                       break;
                                                   case 'validated':
                                                       echo '<span class="worker-portal-status-validated">' . __('Validada', 'worker-portal') . '</span>';
                                                       break;
                                               }
                                               ?>
                                           </td>
                                       </tr>
                                   <?php endforeach; ?>
                               </tbody>
                           </table>
                           
                           <p class="worker-portal-view-all">
                               <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-worksheets&tab=all')); ?>" class="button-secondary">
                                   <?php _e('Ver Todas las Hojas de Trabajo', 'worker-portal'); ?>
                               </a>
                           </p>
                       <?php endif; ?>
                   </div>
               </div>
           </div>
           
       <?php elseif ($current_tab === 'pending' || $current_tab === 'all'): ?>
           <!-- Listado de hojas de trabajo -->
           <div class="worker-portal-worksheets-list">
               <!-- Filtros -->
               <div class="worker-portal-admin-filters">
                   <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                       <input type="hidden" name="page" value="worker-portal-worksheets">
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
                               <label for="filter-project"><?php _e('Proyecto:', 'worker-portal'); ?></label>
                               <select id="filter-project" name="project_id">
                                   <option value=""><?php _e('Todos los proyectos', 'worker-portal'); ?></option>
                                   <?php foreach ($projects as $project): ?>
                                       <option value="<?php echo esc_attr($project['id']); ?>" <?php selected(isset($_GET['project_id']) ? $_GET['project_id'] : '', $project['id']); ?>>
                                           <?php echo esc_html($project['name']); ?>
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
                                       <option value="validated" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'validated'); ?>><?php _e('Validada', 'worker-portal'); ?></option>
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
                               
                               <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-worksheets&tab=' . $current_tab)); ?>" class="button button-link">
                                   <?php _e('Limpiar filtros', 'worker-portal'); ?>
                               </a>
                           </div>
                       </div>
                   </form>
               </div>
               
               <?php if (empty($worksheets)): ?>
                   <div class="worker-portal-no-items-message">
                       <p><?php _e('No se encontraron hojas de trabajo con los criterios seleccionados.', 'worker-portal'); ?></p>
                   </div>
               <?php else: ?>
                   <!-- Acciones masivas si es la pestaña de pendientes -->
                   <?php if ($current_tab === 'pending'): ?>
                       <div class="worker-portal-bulk-actions">
                           <form id="bulk-validate-form" method="post">
                               <div class="worker-portal-bulk-actions-row">
                                   <div class="worker-portal-bulk-action-select">
                                       <label for="bulk-action"><?php _e('Acción masiva:', 'worker-portal'); ?></label>
                                       <select id="bulk-action" name="bulk_action">
                                           <option value=""><?php _e('Seleccionar acción', 'worker-portal'); ?></option>
                                           <option value="validate"><?php _e('Validar seleccionadas', 'worker-portal'); ?></option>
                                       </select>
                                   </div>
                                   
                                   <div class="worker-portal-bulk-action-apply">
                                       <button type="submit" class="button button-primary" id="apply-bulk-action">
                                           <?php _e('Aplicar', 'worker-portal'); ?>
                                       </button>
                                   </div>
                               </div>
                               
                               <input type="hidden" name="worksheets_nonce" value="<?php echo wp_create_nonce('worker_portal_worksheets_bulk_action'); ?>">
                           </form>
                       </div>
                   <?php endif; ?>
                   
                   <!-- Tabla de hojas de trabajo -->
                   <form id="worksheets-list-form">
                       <table class="wp-list-table widefat fixed striped worksheets-table">
                           <thead>
                               <tr>
                                   <?php if ($current_tab === 'pending'): ?>
                                       <th class="check-column">
                                           <input type="checkbox" id="select-all-worksheets">
                                       </th>
                                   <?php endif; ?>
                                   <th><?php _e('ID', 'worker-portal'); ?></th>
                                   <th><?php _e('Fecha', 'worker-portal'); ?></th>
                                   <th><?php _e('Usuario', 'worker-portal'); ?></th>
                                   <th><?php _e('Proyecto', 'worker-portal'); ?></th>
                                   <th><?php _e('Dificultad', 'worker-portal'); ?></th>
                                   <th><?php _e('Sistema', 'worker-portal'); ?></th>
                                   <th><?php _e('Cantidad', 'worker-portal'); ?></th>
                                   <th><?php _e('Horas', 'worker-portal'); ?></th>
                                   <th><?php _e('Estado', 'worker-portal'); ?></th>
                                   <th><?php _e('Acciones', 'worker-portal'); ?></th>
                               </tr>
                           </thead>
                           <tbody>
                               <?php foreach ($worksheets as $worksheet): ?>
                                   <tr>
                                       <?php if ($current_tab === 'pending'): ?>
                                           <td class="check-column">
                                               <input type="checkbox" name="worksheet_ids[]" value="<?php echo esc_attr($worksheet['id']); ?>" class="worksheet-checkbox">
                                           </td>
                                       <?php endif; ?>
                                       <td><?php echo esc_html($worksheet['id']); ?></td>
                                       <td><?php echo date_i18n(get_option('date_format'), strtotime($worksheet['work_date'])); ?></td>
                                       <td><?php echo esc_html($worksheet['user_name']); ?></td>
                                       <td><?php echo esc_html($worksheet['project_name']); ?></td>
                                       <td>
                                           <?php 
                                           echo isset($difficulty_levels[$worksheet['difficulty']]) 
                                               ? esc_html($difficulty_levels[$worksheet['difficulty']]) 
                                               : esc_html(ucfirst($worksheet['difficulty'])); 
                                           ?>
                                       </td>
                                       <td>
                                           <?php 
                                           echo isset($system_types[$worksheet['system_type']]) 
                                               ? esc_html($system_types[$worksheet['system_type']]) 
                                               : esc_html($worksheet['system_type']); 
                                           ?>
                                       </td>
                                       <td>
                                           <?php echo esc_html($worksheet['quantity']); ?> 
                                           <?php echo $worksheet['unit_type'] == 'm2' ? 'm²' : 'H'; ?>
                                       </td>
                                       <td><?php echo esc_html($worksheet['hours']); ?> h</td>
                                       <td>
                                           <?php
                                           switch ($worksheet['status']) {
                                               case 'pending':
                                                   echo '<span class="worker-portal-status-pending">' . __('Pendiente', 'worker-portal') . '</span>';
                                                   break;
                                               case 'validated':
                                                   echo '<span class="worker-portal-status-validated">' . __('Validada', 'worker-portal') . '</span>';
                                                   if (!empty($worksheet['validated_by'])) {
                                                       $validator = get_userdata($worksheet['validated_by']);
                                                       if ($validator) {
                                                           echo '<br><small>' . sprintf(__('por %s', 'worker-portal'), $validator->display_name) . '</small>';
                                                       }
                                                   }
                                                   break;
                                           }
                                           ?>
                                       </td>
                                       <td class="worksheet-actions">
                                           <?php if ($worksheet['status'] === 'pending'): ?>
                                               <button type="button" class="button button-small validate-worksheet" data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                                                   <span class="dashicons dashicons-yes"></span> <?php _e('Validar', 'worker-portal'); ?>
                                               </button>
                                           <?php endif; ?>
                                           <button type="button" class="button button-small view-worksheet-details" data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                                               <span class="dashicons dashicons-info"></span> <?php _e('Detalles', 'worker-portal'); ?>
                                           </button>
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
                   
                   <!-- Exportar hojas de trabajo -->
                   <div class="worker-portal-admin-actions">
                       <button type="button" id="export-worksheets-button" class="button button-secondary">
                           <span class="dashicons dashicons-download"></span> <?php _e('Exportar a Excel', 'worker-portal'); ?>
                       </button>
                   </div>
               <?php endif; ?>
           </div>
           
       <?php elseif ($current_tab === 'projects'): ?>
           <!-- Gestión de proyectos -->
           <div class="worker-portal-projects">
               <div class="worker-portal-projects-form">
                   <h2><?php _e('Añadir Nuevo Proyecto', 'worker-portal'); ?></h2>
                   
                   <form id="add-project-form" method="post">
                       <table class="form-table" role="presentation">
                           <tr>
                               <th scope="row"><label for="project-name"><?php _e('Nombre:', 'worker-portal'); ?></label></th>
                               <td><input type="text" id="project-name" name="project_name" class="regular-text" required></td>
                           </tr>
                           <tr>
                               <th scope="row"><label for="project-location"><?php _e('Ubicación:', 'worker-portal'); ?></label></th>
                               <td><input type="text" id="project-location" name="project_location" class="regular-text"></td>
                           </tr>
                           <tr>
                               <th scope="row"><label for="project-description"><?php _e('Descripción:', 'worker-portal'); ?></label></th>
                               <td><textarea id="project-description" name="project_description" rows="3" class="regular-text"></textarea></td>
                           </tr>
                           <tr>
                               <th scope="row"><label for="project-start-date"><?php _e('Fecha de inicio:', 'worker-portal'); ?></label></th>
                               <td><input type="date" id="project-start-date" name="project_start_date"></td>
                           </tr>
                           <tr>
                               <th scope="row"><label for="project-end-date"><?php _e('Fecha de fin prevista:', 'worker-portal'); ?></label></th>
                               <td><input type="date" id="project-end-date" name="project_end_date"></td>
                           </tr>
                       </table>
                       
                       <p class="submit">
                           <input type="submit" name="add_project" id="add-project" class="button button-primary" value="<?php _e('Añadir Proyecto', 'worker-portal'); ?>">
                       </p>
                       
                       <?php wp_nonce_field('worker_portal_add_project', 'project_nonce'); ?>
                   </form>
               </div>
               
               <div class="worker-portal-projects-list">
                   <h2><?php _e('Proyectos Existentes', 'worker-portal'); ?></h2>
                   
                   <?php if (empty($projects)): ?>
                       <p class="worker-portal-no-items"><?php _e('No hay proyectos activos.', 'worker-portal'); ?></p>
                   <?php else: ?>
                       <table class="wp-list-table widefat fixed striped">
                           <thead>
                               <tr>
                                   <th><?php _e('ID', 'worker-portal'); ?></th>
                                   <th><?php _e('Nombre', 'worker-portal'); ?></th>
                                   <th><?php _e('Ubicación', 'worker-portal'); ?></th>
                                   <th><?php _e('Inicio', 'worker-portal'); ?></th>
                                   <th><?php _e('Fin previsto', 'worker-portal'); ?></th>
                                   <th><?php _e('Acciones', 'worker-portal'); ?></th>
                               </tr>
                           </thead>
                           <tbody>
                               <?php foreach ($projects as $project): ?>
                                   <tr>
                                       <td><?php echo esc_html($project['id']); ?></td>
                                       <td><?php echo esc_html($project['name']); ?></td>
                                       <td><?php echo esc_html($project['location']); ?></td>
                                       <td>
                                           <?php 
                                           echo !empty($project['start_date']) 
                                               ? date_i18n(get_option('date_format'), strtotime($project['start_date']))
                                               : '—';
                                           ?>
                                       </td>
                                       <td>
                                           <?php 
                                           echo !empty($project['end_date']) 
                                               ? date_i18n(get_option('date_format'), strtotime($project['end_date']))
                                               : '—';
                                           ?>
                                       </td>
                                       <td>
                                           <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-worksheets&tab=projects&edit=' . $project['id'])); ?>" class="button button-small">
                                               <span class="dashicons dashicons-edit"></span> <?php _e('Editar', 'worker-portal'); ?>
                                           </a>
                                           
                                           <button type="button" class="button button-small button-link-delete deactivate-project" data-project-id="<?php echo esc_attr($project['id']); ?>">
                                               <span class="dashicons dashicons-hidden"></span> <?php _e('Desactivar', 'worker-portal'); ?>
                                           </button>
                                       </td>
                                   </tr>
                               <?php endforeach; ?>
                           </tbody>
                       </table>
                   <?php endif; ?>
               </div>
           </div>
           
       <?php elseif ($current_tab === 'settings'): ?>
           <!-- Configuración de hojas de trabajo -->
           <div class="worker-portal-admin-settings">
               <form method="post" action="options.php" class="worker-portal-settings-form">
                   <?php
                   settings_fields('worker_portal_worksheets');
                   do_settings_sections('worker_portal_worksheets');
                   ?>
                   
                   <h2><?php _e('Tipos de Sistemas', 'worker-portal'); ?></h2>
                   <p class="description"><?php _e('Configura los tipos de sistemas disponibles para las hojas de trabajo.', 'worker-portal'); ?></p>
                   
                   <table class="form-table system-types-table" role="presentation">
                       <thead>
                           <tr>
                               <th><?php _e('Clave', 'worker-portal'); ?></th>
                               <th><?php _e('Nombre', 'worker-portal'); ?></th>
                               <th><?php _e('Acciones', 'worker-portal'); ?></th>
                           </tr>
                       </thead>
                       <tbody id="system-types-list">
                           <?php foreach ($system_types as $key => $label): ?>
                               <tr class="system-type-row">
                                   <td>
                                       <input type="text" name="worker_portal_system_types[keys][]" value="<?php echo esc_attr($key); ?>" required>
                                   </td>
                                   <td>
                                       <input type="text" name="worker_portal_system_types[labels][]" value="<?php echo esc_attr($label); ?>" required>
                                   </td>
                                   <td>
                                       <button type="button" class="button button-small remove-system-type">
                                           <span class="dashicons dashicons-trash"></span>
                                       </button>
                                   </td>
                               </tr>
                           <?php endforeach; ?>
                       </tbody>
                       <tfoot>
                           <tr>
                               <td colspan="3">
                                   <button type="button" id="add-system-type" class="button button-secondary">
                                       <span class="dashicons dashicons-plus"></span> <?php _e('Añadir tipo de sistema', 'worker-portal'); ?>
                                   </button>
                               </td>
                           </tr>
                       </tfoot>
                   </table>
                   
                   <h2><?php _e('Tipos de Unidades', 'worker-portal'); ?></h2>
                   <p class="description"><?php _e('Configura los tipos de unidades disponibles para las hojas de trabajo.', 'worker-portal'); ?></p>
                   
                   <table class="form-table unit-types-table" role="presentation">
                       <thead>
                           <tr>
                               <th><?php _e('Clave', 'worker-portal'); ?></th>
                               <th><?php _e('Nombre', 'worker-portal'); ?></th>
                               <th><?php _e('Acciones', 'worker-portal'); ?></th>
                           </tr>
                       </thead>
                       <tbody id="unit-types-list">
                           <?php foreach ($unit_types as $key => $label): ?>
                               <tr class="unit-type-row">
                                   <td>
                                       <input type="text" name="worker_portal_unit_types[keys][]" value="<?php echo esc_attr($key); ?>" required>
                                   </td>
                                   <td>
                                       <input type="text" name="worker_portal_unit_types[labels][]" value="<?php echo esc_attr($label); ?>" required>
                                   </td>
                                   <td>
                                       <button type="button" class="button button-small remove-unit-type">
                                           <span class="dashicons dashicons-trash"></span>
                                       </button>
                                   </td>
                               </tr>
                           <?php endforeach; ?>
                       </tbody>
                       <tfoot>
                           <tr>
                               <td colspan="3">
                                   <button type="button" id="add-unit-type" class="button button-secondary">
<span class="dashicons dashicons-plus"></span> <?php _e('Añadir tipo de unidad', 'worker-portal'); ?>
                                   </button>
                               </td>
                           </tr>
                       </tfoot>
                   </table>
                   
                   <h2><?php _e('Niveles de Dificultad', 'worker-portal'); ?></h2>
                   <p class="description"><?php _e('Configura los niveles de dificultad disponibles para las hojas de trabajo.', 'worker-portal'); ?></p>
                   
                   <table class="form-table difficulty-levels-table" role="presentation">
                       <thead>
                           <tr>
                               <th><?php _e('Clave', 'worker-portal'); ?></th>
                               <th><?php _e('Nombre', 'worker-portal'); ?></th>
                               <th><?php _e('Acciones', 'worker-portal'); ?></th>
                           </tr>
                       </thead>
                       <tbody id="difficulty-levels-list">
                           <?php foreach ($difficulty_levels as $key => $label): ?>
                               <tr class="difficulty-level-row">
                                   <td>
                                       <input type="text" name="worker_portal_difficulty_levels[keys][]" value="<?php echo esc_attr($key); ?>" required>
                                   </td>
                                   <td>
                                       <input type="text" name="worker_portal_difficulty_levels[labels][]" value="<?php echo esc_attr($label); ?>" required>
                                   </td>
                                   <td>
                                       <button type="button" class="button button-small remove-difficulty-level">
                                           <span class="dashicons dashicons-trash"></span>
                                       </button>
                                   </td>
                               </tr>
                           <?php endforeach; ?>
                       </tbody>
                       <tfoot>
                           <tr>
                               <td colspan="3">
                                   <button type="button" id="add-difficulty-level" class="button button-secondary">
                                       <span class="dashicons dashicons-plus"></span> <?php _e('Añadir nivel de dificultad', 'worker-portal'); ?>
                                   </button>
                               </td>
                           </tr>
                       </tfoot>
                   </table>
                   
                   <h2><?php _e('Validadores de Hojas de Trabajo', 'worker-portal'); ?></h2>
                   <p class="description"><?php _e('Selecciona los usuarios que pueden validar hojas de trabajo además de los administradores.', 'worker-portal'); ?></p>
                   
                   <table class="form-table" role="presentation">
                       <tr>
                           <th scope="row"><?php _e('Validadores', 'worker-portal'); ?></th>
                           <td>
                               <select name="worker_portal_worksheet_validators[]" multiple size="6">
                                   <?php foreach ($users as $user): ?>
                                       <option value="<?php echo esc_attr($user->ID); ?>" <?php selected(in_array($user->ID, $worksheet_validators)); ?>>
                                           <?php echo esc_html($user->display_name); ?>
                                       </option>
                                   <?php endforeach; ?>
                               </select>
                               <p class="description"><?php _e('Mantén presionada la tecla Ctrl (o Cmd en Mac) para seleccionar múltiples usuarios.', 'worker-portal'); ?></p>
                           </td>
                       </tr>
                   </table>
                   
                   <?php submit_button(); ?>
               </form>
           </div>
       <?php endif; ?>
   </div>
</div>

<!-- Modal para detalles de hoja de trabajo -->
<div id="worksheet-details-modal" class="worker-portal-modal">
   <div class="worker-portal-modal-content">
       <div class="worker-portal-modal-header">
           <h3><?php _e('Detalles de la Hoja de Trabajo', 'worker-portal'); ?></h3>
           <button type="button" class="worker-portal-modal-close">&times;</button>
       </div>
       <div class="worker-portal-modal-body">
           <div id="worksheet-details-content">
               <!-- El contenido se cargará dinámicamente -->
           </div>
       </div>
   </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
   // Seleccionar/deseleccionar todas las hojas
   $("#select-all-worksheets").on("click", function() {
       $(".worksheet-checkbox").prop("checked", $(this).prop("checked"));
       checkBulkSelection();
   });
   
   // Comprobar selección para habilitar/deshabilitar botón de acción masiva
   function checkBulkSelection() {
       if ($(".worksheet-checkbox:checked").length > 0) {
           $("#apply-bulk-action").prop("disabled", false);
       } else {
           $("#apply-bulk-action").prop("disabled", true);
       }
   }
   
   $(".worksheet-checkbox").on("change", checkBulkSelection);
   $("#select-all-worksheets").on("change", checkBulkSelection);
   
   // Inicialmente desactivar botón de acción masiva
   $("#apply-bulk-action").prop("disabled", true);
   
   // Enviar formulario de acciones masivas
   $("#bulk-validate-form").on("submit", function(e) {
       e.preventDefault();
       
       const action = $("#bulk-action").val();
       if (!action) {
           alert("<?php echo esc_js(__('Por favor, selecciona una acción.', 'worker-portal')); ?>");
           return;
       }
       
       const checkedWorksheets = $(".worksheet-checkbox:checked");
       if (checkedWorksheets.length === 0) {
           alert("<?php echo esc_js(__('Por favor, selecciona al menos una hoja de trabajo.', 'worker-portal')); ?>");
           return;
       }
       
       // Confirmar acción
       if (!confirm("<?php echo esc_js(__('¿Estás seguro? Esta acción no se puede deshacer.', 'worker-portal')); ?>")) {
           return;
       }
       
       // Recoger IDs de hojas seleccionadas
       const worksheetIds = [];
       checkedWorksheets.each(function() {
           worksheetIds.push($(this).val());
       });
       
       // Enviar solicitud AJAX
       $.ajax({
           url: ajaxurl,
           type: "POST",
           data: {
               action: "admin_bulk_worksheet_action",
               nonce: "<?php echo wp_create_nonce('worker_portal_ajax_nonce'); ?>",
               bulk_action: action,
               worksheet_ids: worksheetIds
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
   
   // Validar una hoja individual
   $(".validate-worksheet").on("click", function() {
       const worksheetId = $(this).data("worksheet-id");
       
       // Confirmar acción
       if (!confirm('<?php echo esc_js(__('¿Estás seguro de validar esta hoja de trabajo?', 'worker-portal')); ?>')) {
           return;
       }
       
       // Enviar solicitud AJAX
       $.ajax({
           url: ajaxurl,
           type: 'POST',
           data: {
               action: 'admin_validate_worksheet',
               nonce: "<?php echo wp_create_nonce('worker_portal_ajax_nonce'); ?>",
               worksheet_id: worksheetId
           },
           beforeSend: function() {
               $(this).prop("disabled", true).text("<?php echo esc_js(__('Procesando...', 'worker-portal')); ?>");
           }.bind(this),
           success: function(response) {
               if (response.success) {
                   alert(response.data.message);
                   window.location.reload();
               } else {
                   alert(response.data);
                   $(this).prop("disabled", false).html('<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Validar', 'worker-portal')); ?>');
               }
           }.bind(this),
           error: function() {
               alert('<?php echo esc_js(__('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal')); ?>');
               $(this).prop("disabled", false).html('<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Validar', 'worker-portal')); ?>');
           }.bind(this)
       });
   });
   
   // Ver detalles de una hoja
   $(".view-worksheet-details").on("click", function() {
       const worksheetId = $(this).data("worksheet-id");
       
       // Cargar detalles mediante AJAX
       $.ajax({
           url: ajaxurl,
           type: "POST",
           data: {
               action: "admin_get_worksheet_details",
               nonce: "<?php echo wp_create_nonce('worker_portal_ajax_nonce'); ?>",
               worksheet_id: worksheetId
           },
           beforeSend: function() {
               $("#worksheet-details-content").html('<div class="worker-portal-loading"><div class="worker-portal-spinner"></div><p><?php echo esc_js(__('Cargando detalles...', 'worker-portal')); ?></p></div>');
               $("#worksheet-details-modal").fadeIn();
           },
           success: function(response) {
               if (response.success) {
                   $("#worksheet-details-content").html(response.data);
               } else {
                   $("#worksheet-details-content").html('<div class="worker-portal-error">' + response.data + '</div>');
               }
           },
           error: function() {
               $("#worksheet-details-content").html('<div class="worker-portal-error"><?php echo esc_js(__('Ha ocurrido un error al cargar los detalles.', 'worker-portal')); ?></div>');
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
   
   // Gestión de tipos de sistemas
   $("#add-system-type").on("click", function() {
       const newRow = `
           <tr class="system-type-row">
               <td>
                   <input type="text" name="worker_portal_system_types[keys][]" required>
               </td>
               <td>
                   <input type="text" name="worker_portal_system_types[labels][]" required>
               </td>
               <td>
                   <button type="button" class="button button-small remove-system-type">
                       <span class="dashicons dashicons-trash"></span>
                   </button>
               </td>
           </tr>
       `;
       
       $("#system-types-list").append(newRow);
   });
   
   $(document).on("click", ".remove-system-type", function() {
       // Si solo queda un tipo, mostrar mensaje
       if ($(".system-type-row").length <= 1) {
           alert("<?php echo esc_js(__('Debe existir al menos un tipo de sistema.', 'worker-portal')); ?>");
           return;
       }
       
       $(this).closest("tr").remove();
   });
   
   // Gestión de tipos de unidades
   $("#add-unit-type").on("click", function() {
       const newRow = `
           <tr class="unit-type-row">
               <td>
                   <input type="text" name="worker_portal_unit_types[keys][]" required>
               </td>
               <td>
                   <input type="text" name="worker_portal_unit_types[labels][]" required>
               </td>
               <td>
                   <button type="button" class="button button-small remove-unit-type">
                       <span class="dashicons dashicons-trash"></span>
                   </button>
               </td>
           </tr>
       `;
       
       $("#unit-types-list").append(newRow);
   });
   
   $(document).on("click", ".remove-unit-type", function() {
       // Si solo queda un tipo, mostrar mensaje
       if ($(".unit-type-row").length <= 1) {
           alert("<?php echo esc_js(__('Debe existir al menos un tipo de unidad.', 'worker-portal')); ?>");
           return;
       }
       
       $(this).closest("tr").remove();
   });
   
   // Gestión de niveles de dificultad
   $("#add-difficulty-level").on("click", function() {
       const newRow = `
           <tr class="difficulty-level-row">
               <td>
                   <input type="text" name="worker_portal_difficulty_levels[keys][]" required>
               </td>
               <td>
                   <input type="text" name="worker_portal_difficulty_levels[labels][]" required>
               </td>
               <td>
                   <button type="button" class="button button-small remove-difficulty-level">
                       <span class="dashicons dashicons-trash"></span>
                   </button>
               </td>
           </tr>
       `;
       
       $("#difficulty-levels-list").append(newRow);
   });
   
   $(document).on("click", ".remove-difficulty-level", function() {
       // Si solo queda un nivel, mostrar mensaje
       if ($(".difficulty-level-row").length <= 1) {
           alert("<?php echo esc_js(__('Debe existir al menos un nivel de dificultad.', 'worker-portal')); ?>");
           return;
       }
       
       $(this).closest("tr").remove();
   });
   
   // Desactivar proyecto
   $(".deactivate-project").on("click", function() {
       const projectId = $(this).data("project-id");
       
       if (confirm("<?php echo esc_js(__('¿Estás seguro de desactivar este proyecto? Los trabajadores no podrán seleccionarlo para nuevas hojas de trabajo.', 'worker-portal')); ?>")) {
           $.ajax({
               url: ajaxurl,
               type: "POST",
               data: {
                   action: "deactivate_project",
                   nonce: "<?php echo wp_create_nonce('worker_portal_ajax_nonce'); ?>",
                   project_id: projectId
               },
               success: function(response) {
                   if (response.success) {
                       window.location.reload();
                   } else {
                       alert(response.data);
                   }
               },
               error: function() {
                   alert("<?php echo esc_js(__('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal')); ?>");
               }
           });
       }
   });
   
   // Exportar hojas de trabajo
   $("#export-worksheets-button").on("click", function() {
       // Obtener parámetros de filtrado de la URL actual
       const urlParams = new URLSearchParams(window.location.search);
       const user_id = urlParams.get('user_id') || '';
       const project_id = urlParams.get('project_id') || '';
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
               action: 'admin_export_worksheets',
               nonce: '<?php echo wp_create_nonce('worker_portal_ajax_nonce'); ?>',
               user_id: user_id,
               project_id: project_id, 
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
               $("#export-worksheets-button").prop('disabled', false).html('<span class="dashicons dashicons-download"></span> <?php echo esc_js(__('Exportar a Excel', 'worker-portal')); ?>');
           }
       });
   });
});
</script>