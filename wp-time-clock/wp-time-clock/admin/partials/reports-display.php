<?php
/**
 * Plantilla para mostrar informes de fichajes
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Obtener los parámetros del informe
$report_type = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : 'monthly';
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$department = isset($_GET['department']) ? sanitize_text_field($_GET['department']) : '';

// Calcular fechas según el tipo de informe
$start_date = '';
$end_date = '';

switch ($report_type) {
    case 'daily':
        $day = isset($_GET['day']) ? intval($_GET['day']) : date('j');
        $start_date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
        $end_date = $start_date;
        break;
        
    case 'weekly':
        $week = isset($_GET['week']) ? intval($_GET['week']) : date('W');
        $dto = new DateTime();
        $dto->setISODate($year, $week);
        $start_date = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $end_date = $dto->format('Y-m-d');
        break;
        
    case 'monthly':
    default:
        $start_date = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
        $end_date = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
        break;
        
    case 'yearly':
        $start_date = date('Y-01-01', mktime(0, 0, 0, 1, 1, $year));
        $end_date = date('Y-12-31', mktime(0, 0, 0, 12, 31, $year));
        break;
        
    case 'custom':
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-t');
        break;
}

// Obtener usuarios
$users_args = array(
    'orderby' => 'display_name',
    'order' => 'ASC'
);

if (!empty($department)) {
    // Si se utiliza el campo 'department' como metadato de usuario
    $users_args['meta_query'] = array(
        array(
            'key' => 'department',
            'value' => $department,
            'compare' => '='
        )
    );
}

$users = get_users($users_args);

// Inicializar gestor de fichajes
$clock_manager = new WP_Time_Clock_Manager();

// Obtener datos para el informe
$report_data = array();

// Si se ha seleccionado un usuario específico
if ($user_id > 0) {
    $entries = $clock_manager->get_user_entries($user_id, $start_date, $end_date);
    $report_data[$user_id] = array(
        'user' => get_userdata($user_id),
        'entries' => $entries,
        'stats' => calculate_user_stats($entries)
    );
} else {
    // Para todos los usuarios o filtrados por departamento
    foreach ($users as $user) {
        $entries = $clock_manager->get_user_entries($user->ID, $start_date, $end_date);
        if (!empty($entries)) {
            $report_data[$user->ID] = array(
                'user' => $user,
                'entries' => $entries,
                'stats' => calculate_user_stats($entries)
            );
        }
    }
}

// Función para calcular estadísticas de usuario
function calculate_user_stats($entries) {
    $stats = array(
        'total_time' => 0,
        'days_worked' => array(),
        'entries_count' => count($entries),
        'completed_entries' => 0
    );
    
    foreach ($entries as $entry) {
        if ($entry->clock_out) {
            $stats['total_time'] += $entry->time_worked['total_seconds'];
            $day = date('Y-m-d', strtotime($entry->clock_in));
            $stats['days_worked'][$day] = true;
            $stats['completed_entries']++;
        }
    }
    
    // Formatear tiempo total
    $hours = floor($stats['total_time'] / 3600);
    $minutes = floor(($stats['total_time'] % 3600) / 60);
    $stats['total_time_formatted'] = sprintf('%02d:%02d', $hours, $minutes);
    
    // Calcular días trabajados
    $stats['days_worked_count'] = count($stats['days_worked']);
    
    // Calcular promedio diario
    $avg_daily = $stats['days_worked_count'] > 0 ? $stats['total_time'] / $stats['days_worked_count'] : 0;
    $avg_hours = floor($avg_daily / 3600);
    $avg_minutes = floor(($avg_daily % 3600) / 60);
    $stats['avg_time_formatted'] = sprintf('%02d:%02d', $avg_hours, $avg_minutes);
    
    return $stats;
}

// Obtener departamentos (si se utilizan)
$departments = array();
$users_with_dept = get_users(array(
    'meta_query' => array(
        array(
            'key' => 'department',
            'compare' => 'EXISTS'
        )
    )
));

foreach ($users_with_dept as $user) {
    $dept = get_user_meta($user->ID, 'department', true);
    if (!empty($dept) && !in_array($dept, $departments)) {
        $departments[] = $dept;
    }
}
sort($departments);

?>

<div class="wrap wp-time-clock-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Filtros de informe -->
    <div class="wp-time-clock-report-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="wp-time-clock-reports">
            
            <div class="wp-time-clock-filter-group">
                <label for="report_type"><?php _e('Tipo de informe:', 'wp-time-clock'); ?></label>
                <select name="report_type" id="report_type">
                    <option value="daily" <?php selected($report_type, 'daily'); ?>><?php _e('Diario', 'wp-time-clock'); ?></option>
                    <option value="weekly" <?php selected($report_type, 'weekly'); ?>><?php _e('Semanal', 'wp-time-clock'); ?></option>
                    <option value="monthly" <?php selected($report_type, 'monthly'); ?>><?php _e('Mensual', 'wp-time-clock'); ?></option>
                    <option value="yearly" <?php selected($report_type, 'yearly'); ?>><?php _e('Anual', 'wp-time-clock'); ?></option>
                    <option value="custom" <?php selected($report_type, 'custom'); ?>><?php _e('Personalizado', 'wp-time-clock'); ?></option>
                </select>
            </div>
            
            <!-- Campos específicos según el tipo de informe -->
            <div id="daily-fields" class="report-type-fields" <?php echo $report_type !== 'daily' ? 'style="display:none;"' : ''; ?>>
                <div class="wp-time-clock-filter-group">
                    <label for="day"><?php _e('Día:', 'wp-time-clock'); ?></label>
                    <input type="number" name="day" id="day" min="1" max="31" value="<?php echo isset($_GET['day']) ? intval($_GET['day']) : date('j'); ?>">
                </div>
            </div>
            
            <div id="weekly-fields" class="report-type-fields" <?php echo $report_type !== 'weekly' ? 'style="display:none;"' : ''; ?>>
                <div class="wp-time-clock-filter-group">
                    <label for="week"><?php _e('Semana:', 'wp-time-clock'); ?></label>
                    <input type="number" name="week" id="week" min="1" max="53" value="<?php echo isset($_GET['week']) ? intval($_GET['week']) : date('W'); ?>">
                </div>
            </div>
            
            <div id="monthly-fields" class="report-type-fields" <?php echo $report_type !== 'monthly' && $report_type !== 'daily' && $report_type !== 'weekly' ? 'style="display:none;"' : ''; ?>>
                <div class="wp-time-clock-filter-group">
                    <label for="month"><?php _e('Mes:', 'wp-time-clock'); ?></label>
                    <select name="month" id="month">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($month, $i); ?>>
                                <?php echo date_i18n('F', mktime(0, 0, 0, $i, 1, 2000)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div id="yearly-fields" class="report-type-fields" <?php echo $report_type !== 'yearly' && $report_type !== 'monthly' && $report_type !== 'daily' && $report_type !== 'weekly' ? 'style="display:none;"' : ''; ?>>
                <div class="wp-time-clock-filter-group">
                    <label for="year"><?php _e('Año:', 'wp-time-clock'); ?></label>
                    <select name="year" id="year">
                        <?php 
                        $current_year = date('Y');
                        for ($i = $current_year - 5; $i <= $current_year + 1; $i++): 
                        ?>
                            <option value="<?php echo $i; ?>" <?php selected($year, $i); ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div id="custom-fields" class="report-type-fields" <?php echo $report_type !== 'custom' ? 'style="display:none;"' : ''; ?>>
                <div class="wp-time-clock-filter-group">
                    <label for="start_date"><?php _e('Fecha inicio:', 'wp-time-clock'); ?></label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date); ?>">
                </div>
                
                <div class="wp-time-clock-filter-group">
                    <label for="end_date"><?php _e('Fecha fin:', 'wp-time-clock'); ?></label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date); ?>">
                </div>
            </div>
            
            <div class="wp-time-clock-filter-group">
                <label for="user_id"><?php _e('Usuario:', 'wp-time-clock'); ?></label>
                <select name="user_id" id="user_id">
                    <option value="0"><?php _e('Todos los usuarios', 'wp-time-clock'); ?></option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($user_id, $user->ID); ?>>
                            <?php echo esc_html($user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if (!empty($departments)): ?>
            <div class="wp-time-clock-filter-group">
                <label for="department"><?php _e('Departamento:', 'wp-time-clock'); ?></label>
                <select name="department" id="department">
                    <option value=""><?php _e('Todos los departamentos', 'wp-time-clock'); ?></option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo esc_attr($dept); ?>" <?php selected($department, $dept); ?>>
                            <?php echo esc_html($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="wp-time-clock-filter-actions">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Generar Informe', 'wp-time-clock'); ?>
                </button>
            </div>
            
            <?php if (!empty($report_data)): ?>
            <div class="wp-time-clock-export-button">
                <button type="submit" name="export" value="csv" class="button">
                    <span class="dashicons dashicons-media-spreadsheet"></span>
                    <?php _e('Exportar CSV', 'wp-time-clock'); ?>
                </button>
                
                <button type="submit" name="export" value="pdf" class="button">
                    <span class="dashicons dashicons-pdf"></span>
                    <?php _e('Exportar PDF', 'wp-time-clock'); ?>
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Resultados del informe -->
    <?php if (empty($report_data)): ?>
        
        <div class="wp-time-clock-no-data">
            <p><?php _e('No hay datos disponibles para el informe seleccionado.', 'wp-time-clock'); ?></p>
        </div>
        
    <?php else: ?>
        
        <!-- Título del informe -->
        <div class="wp-time-clock-report-title">
            <h2>
                <?php 
                switch ($report_type) {
                    case 'daily':
                        printf(
                            __('Informe Diario: %s', 'wp-time-clock'),
                            date_i18n(get_option('date_format'), strtotime($start_date))
                        );
                        break;
                        
                    case 'weekly':
                        printf(
                            __('Informe Semanal: Semana %s del %s', 'wp-time-clock'),
                            isset($_GET['week']) ? intval($_GET['week']) : date('W'),
                            $year
                        );
                        break;
                        
                    case 'monthly':
                        printf(
                            __('Informe Mensual: %s %s', 'wp-time-clock'),
                            date_i18n('F', mktime(0, 0, 0, $month, 1, 2000)),
                            $year
                        );
                        break;
                        
                    case 'yearly':
                        printf(
                            __('Informe Anual: %s', 'wp-time-clock'),
                            $year
                        );
                        break;
                        
                    case 'custom':
                        printf(
                            __('Informe Personalizado: %s - %s', 'wp-time-clock'),
                            date_i18n(get_option('date_format'), strtotime($start_date)),
                            date_i18n(get_option('date_format'), strtotime($end_date))
                        );
                        break;
                }
                ?>
            </h2>
            <?php if ($user_id > 0): ?>
                <p>
                    <?php 
                    $user_info = get_userdata($user_id);
                    printf(
                        __('Usuario: %s', 'wp-time-clock'),
                        $user_info->display_name
                    );
                    ?>
                </p>
            <?php elseif (!empty($department)): ?>
                <p>
                    <?php 
                    printf(
                        __('Departamento: %s', 'wp-time-clock'),
                        $department
                    );
                    ?>
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Resumen global -->
        <div class="wp-time-clock-stats-card">
            <div class="wp-time-clock-card-header">
                <h2><?php _e('Resumen Global', 'wp-time-clock'); ?></h2>
            </div>
            <div class="wp-time-clock-card-content">
                <?php
                // Calcular estadísticas globales
                $global_stats = array(
                    'users_count' => count($report_data),
                    'total_entries' => 0,
                    'total_days' => array(),
                    'total_time' => 0
                );
                
                foreach ($report_data as $user_data) {
                    $global_stats['total_entries'] += $user_data['stats']['entries_count'];
                    $global_stats['total_time'] += $user_data['stats']['total_time'];
                    
                    foreach ($user_data['stats']['days_worked'] as $day => $value) {
                        $global_stats['total_days'][$day] = true;
                    }
                }
                
                // Formatear tiempo total
                $hours = floor($global_stats['total_time'] / 3600);
                $minutes = floor(($global_stats['total_time'] % 3600) / 60);
                $global_time_formatted = sprintf('%02d:%02d', $hours, $minutes);
                
                // Calcular días totales
                $total_days_count = count($global_stats['total_days']);
                ?>
                
                <div class="wp-time-clock-stat-grid">
                    <div class="wp-time-clock-stat">
                        <span class="wp-time-clock-stat-value"><?php echo esc_html($global_stats['users_count']); ?></span>
                        <span class="wp-time-clock-stat-label"><?php _e('Usuarios', 'wp-time-clock'); ?></span>
                    </div>
                    
                    <div class="wp-time-clock-stat">
                        <span class="wp-time-clock-stat-value"><?php echo esc_html($global_stats['total_entries']); ?></span>
                        <span class="wp-time-clock-stat-label"><?php _e('Fichajes', 'wp-time-clock'); ?></span>
                    </div>
                    
                    <div class="wp-time-clock-stat">
                        <span class="wp-time-clock-stat-value"><?php echo esc_html($total_days_count); ?></span>
                        <span class="wp-time-clock-stat-label"><?php _e('Días', 'wp-time-clock'); ?></span>
                    </div>
                    
                    <div class="wp-time-clock-stat">
                        <span class="wp-time-clock-stat-value"><?php echo esc_html($global_time_formatted); ?></span>
                        <span class="wp-time-clock-stat-label"><?php _e('Tiempo Total', 'wp-time-clock'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de resultados por usuario -->
        <div class="wp-time-clock-report-users">
            <h3><?php _e('Desglose por Usuario', 'wp-time-clock'); ?></h3>
            
            <table class="wp-list-table widefat fixed striped wp-time-clock-report-table">
                <thead>
                    <tr>
                        <th><?php _e('Usuario', 'wp-time-clock'); ?></th>
                        <th><?php _e('Días Trabajados', 'wp-time-clock'); ?></th>
                        <th><?php _e('Horas Totales', 'wp-time-clock'); ?></th>
                        <th><?php _e('Media Diaria', 'wp-time-clock'); ?></th>
                        <th><?php _e('Fichajes', 'wp-time-clock'); ?></th>
                        <th><?php _e('Acciones', 'wp-time-clock'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $user_id => $user_data): ?>
                    <tr>
                        <td>
                            <?php echo esc_html($user_data['user']->display_name); ?>
                            <?php 
                            $user_dept = get_user_meta($user_id, 'department', true);
                            if (!empty($user_dept)) {
                                echo '<br><small>' . esc_html($user_dept) . '</small>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($user_data['stats']['days_worked_count']); ?></td>
                        <td><?php echo esc_html($user_data['stats']['total_time_formatted']); ?></td>
                        <td><?php echo esc_html($user_data['stats']['avg_time_formatted']); ?></td>
                        <td><?php echo esc_html($user_data['stats']['entries_count']); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=wp-time-clock-entries&user_id=' . $user_id . '&start_date=' . $start_date . '&end_date=' . $end_date); ?>" class="button">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php _e('Ver Detalles', 'wp-time-clock'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Gráficos (si hay disponibles) -->
        <?php if (count($report_data) > 0): ?>
        <div class="wp-time-clock-report-charts">
            <div class="wp-time-clock-card">
                <div class="wp-time-clock-card-header">
                    <h2><?php _e('Gráficos', 'wp-time-clock'); ?></h2>
                </div>
                <div class="wp-time-clock-card-content">
                    <div class="wp-time-clock-chart-container">
                        <canvas id="wp-time-clock-hours-chart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<!-- Scripts para manejar la UI del informe -->
<script>
jQuery(document).ready(function($) {
    // Cambiar campos visibles según el tipo de informe
    $('#report_type').on('change', function() {
        var reportType = $(this).val();
        $('.report-type-fields').hide();
        
        switch (reportType) {
            case 'daily':
                $('#daily-fields, #monthly-fields, #yearly-fields').show();
                break;
            case 'weekly':
                $('#weekly-fields, #yearly-fields').show();
                break;
            case 'monthly':
                $('#monthly-fields, #yearly-fields').show();
                break;
            case 'yearly':
                $('#yearly-fields').show();
                break;
            case 'custom':
                $('#custom-fields').show();
                break;
        }
    });
    
    // Inicializar gráfico de horas
    <?php if (!empty($report_data) && count($report_data) > 0): ?>
    var ctx = document.getElementById('wp-time-clock-hours-chart').getContext('2d');
    var hoursChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                $labels = array();
                foreach ($report_data as $user_id => $user_data) {
                    $labels[] = "'" . esc_js($user_data['user']->display_name) . "'";
                }
                echo implode(', ', $labels);
                ?>
            ],
            datasets: [{
                label: '<?php _e('Horas Trabajadas', 'wp-time-clock'); ?>',
                data: [
                    <?php 
                    $values = array();
                    foreach ($report_data as $user_id => $user_data) {
                        $values[] = round($user_data['stats']['total_time'] / 3600, 2);
                    }
                    echo implode(', ', $values);
                    ?>
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '<?php _e('Horas', 'wp-time-clock'); ?>'
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>
