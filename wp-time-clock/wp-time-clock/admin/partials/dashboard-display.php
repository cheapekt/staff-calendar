<?php
/**
 * Plantilla para mostrar el panel de control de administración
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Obtener datos para el dashboard
global $wpdb;
$table_entries = $wpdb->prefix . 'time_clock_entries';
$clock_manager = new WP_Time_Clock_Manager();

// Contar entradas totales
$total_entries = $wpdb->get_var("SELECT COUNT(*) FROM $table_entries");

// Contar usuarios únicos con fichajes
$unique_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_entries");

// Contar fichajes activos (sin hora de salida)
$active_entries = $wpdb->get_var("SELECT COUNT(*) FROM $table_entries WHERE clock_out IS NULL");

// Obtener datos para el gráfico de los últimos 30 días
$thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
$today = date('Y-m-d');

$daily_entries = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE(clock_in) as date, COUNT(*) as count 
     FROM $table_entries 
     WHERE DATE(clock_in) BETWEEN %s AND %s 
     GROUP BY DATE(clock_in) 
     ORDER BY date ASC",
    $thirty_days_ago,
    $today
), ARRAY_A);

// Formatear datos para JavaScript
$chart_labels = array();
$chart_data = array();

// Crear array con todas las fechas de los últimos 30 días
$date_range = new DatePeriod(
    new DateTime($thirty_days_ago),
    new DateInterval('P1D'),
    new DateTime($today . ' +1 day')
);

foreach ($date_range as $date) {
    $date_str = $date->format('Y-m-d');
    $chart_labels[] = $date->format(get_option('date_format'));
    
    // Buscar si hay entradas para esta fecha
    $found = false;
    foreach ($daily_entries as $entry) {
        if ($entry['date'] === $date_str) {
            $chart_data[] = intval($entry['count']);
            $found = true;
            break;
        }
    }
    
    // Si no hay entradas para esta fecha, añadir 0
    if (!$found) {
        $chart_data[] = 0;
    }
}

// Obtener usuarios con fichajes activos
$active_users = $wpdb->get_results(
    "SELECT e.id, e.user_id, e.clock_in, u.display_name 
     FROM $table_entries e
     JOIN {$wpdb->users} u ON e.user_id = u.ID
     WHERE e.clock_out IS NULL
     ORDER BY e.clock_in DESC",
    ARRAY_A
);

?>

<div class="wrap wp-time-clock-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wp-time-clock-admin-header">
        <div class="wp-time-clock-current-time">
            <span class="dashicons dashicons-clock"></span>
            <span id="wp-time-clock-admin-time"><?php echo date_i18n('H:i:s'); ?></span>
        </div>
        
        <div class="wp-time-clock-admin-actions">
            <a href="<?php echo admin_url('admin.php?page=wp-time-clock-reports'); ?>" class="button">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php _e('Informes Completos', 'wp-time-clock'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wp-time-clock-entries'); ?>" class="button">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e('Todas las Entradas', 'wp-time-clock'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wp-time-clock-settings'); ?>" class="button">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Configuración', 'wp-time-clock'); ?>
            </a>
        </div>
    </div>
    
    <div class="wp-time-clock-dashboard-grid">
        <!-- Tarjetas de resumen -->
        <div class="wp-time-clock-card wp-time-clock-stats-card">
            <div class="wp-time-clock-card-header">
                <h2><?php _e('Resumen', 'wp-time-clock'); ?></h2>
            </div>
            <div class="wp-time-clock-card-content">
                <div class="wp-time-clock-stat-grid">
                    <div class="wp-time-clock-stat">
                        <span class="wp-time-clock-stat-value"><?php echo esc_html($total_entries); ?></span>
                        <span class="wp-time-clock-stat-label"><?php _e('Fichajes Totales', 'wp-time-clock'); ?></span>
                    </div>
                    
                    <div class="wp-time-clock-stat">
                        <span class="wp-time-clock-stat-value"><?php echo esc_html($unique_users); ?></span>
                        <span class="wp-time-clock-stat-label"><?php _e('Usuarios Activos', 'wp-time-clock'); ?></span>
                    </div>
                    
                    <div class="wp-time-clock-stat">
                        <span class="wp-time-clock-stat-value"><?php echo esc_html($active_entries); ?></span>
                        <span class="wp-time-clock-stat-label"><?php _e('Fichajes En Curso', 'wp-time-clock'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de actividad -->
        <div class="wp-time-clock-card wp-time-clock-chart-card">
            <div class="wp-time-clock-card-header">
                <h2><?php _e('Actividad (Últimos 30 días)', 'wp-time-clock'); ?></h2>
            </div>
            <div class="wp-time-clock-card-content">
                <canvas id="wp-time-clock-activity-chart" height="300"></canvas>
            </div>
        </div>
        
        <!-- Usuarios actualmente fichados -->
        <div class="wp-time-clock-card wp-time-clock-active-users-card">
            <div class="wp-time-clock-card-header">
                <h2><?php _e('Usuarios Actualmente Fichados', 'wp-time-clock'); ?></h2>
            </div>
            <div class="wp-time-clock-card-content">
                <?php if (empty($active_users)): ?>
                <div class="wp-time-clock-no-data">
                    <p><?php _e('No hay usuarios con fichajes activos en este momento.', 'wp-time-clock'); ?></p>
                </div>
                <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Usuario', 'wp-time-clock'); ?></th>
                            <th><?php _e('Hora de Entrada', 'wp-time-clock'); ?></th>
                            <th><?php _e('Tiempo Transcurrido', 'wp-time-clock'); ?></th>
                            <th><?php _e('Acciones', 'wp-time-clock'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_users as $user): 
                            $start_time = strtotime($user['clock_in']);
                            $current_time = time();
                            $elapsed = $current_time - $start_time;
                            $elapsed_formatted = $clock_manager->format_time_worked($elapsed);
                        ?>
                        <tr>
                            <td><?php echo esc_html($user['display_name']); ?></td>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($user['clock_in'])); ?></td>
                            <td class="wp-time-clock-elapsed-cell" data-seconds="<?php echo esc_attr($elapsed); ?>">
                                <?php echo esc_html($elapsed_formatted); ?>
                            </td>
                            <td>
                                <button class="button wp-time-clock-edit-entry" 
                                        data-entry-id="<?php echo esc_attr($user['id']); ?>"
                                        data-user-id="<?php echo esc_attr($user['user_id']); ?>"
                                        data-user-name="<?php echo esc_attr($user['display_name']); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php _e('Editar', 'wp-time-clock'); ?>
                                </button>
                                
                                <button class="button wp-time-clock-register-exit" 
                                        data-entry-id="<?php echo esc_attr($user['id']); ?>"
                                        data-user-id="<?php echo esc_attr($user['user_id']); ?>"
                                        data-user-name="<?php echo esc_attr($user['display_name']); ?>">
                                    <span class="dashicons dashicons-exit"></span>
                                    <?php _e('Registrar Salida', 'wp-time-clock'); ?>
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

<!-- Modal para editar entrada -->
<div id="wp-time-clock-edit-modal" class="wp-time-clock-modal" style="display: none;">
    <div class="wp-time-clock-modal-content">
        <div class="wp-time-clock-modal-header">
            <h3><?php _e('Editar Fichaje', 'wp-time-clock'); ?></h3>
            <button class="wp-time-clock-modal-close">&times;</button>
        </div>
        <div class="wp-time-clock-modal-body">
            <form id="wp-time-clock-edit-form">
                <input type="hidden" id="wp-time-clock-entry-id">
                <input type="hidden" id="wp-time-clock-user-id">
                
                <div class="wp-time-clock-form-row">
                    <label for="wp-time-clock-user-name"><?php _e('Usuario:', 'wp-time-clock'); ?></label>
                    <input type="text" id="wp-time-clock-user-name" readonly>
                </div>
                
                <div class="wp-time-clock-form-row">
                    <label for="wp-time-clock-clock-in"><?php _e('Hora de Entrada:', 'wp-time-clock'); ?></label>
                    <input type="datetime-local" id="wp-time-clock-clock-in">
                </div>
                
                <div class="wp-time-clock-form-row">
                    <label for="wp-time-clock-clock-out"><?php _e('Hora de Salida:', 'wp-time-clock'); ?></label>
                    <input type="datetime-local" id="wp-time-clock-clock-out">
                </div>
                
                <div class="wp-time-clock-form-row">
                    <label for="wp-time-clock-status"><?php _e('Estado:', 'wp-time-clock'); ?></label>
                    <select id="wp-time-clock-status">
                        <option value="active"><?php _e('Activo', 'wp-time-clock'); ?></option>
                        <option value="edited"><?php _e('Editado', 'wp-time-clock'); ?></option>
                        <option value="approved"><?php _e('Aprobado', 'wp-time-clock'); ?></option>
                        <option value="rejected"><?php _e('Rechazado', 'wp-time-clock'); ?></option>
                    </select>
                </div>
                
                <div class="wp-time-clock-form-row">
                    <label for="wp-time-clock-note"><?php _e('Nota Administrativa:', 'wp-time-clock'); ?></label>
                    <textarea id="wp-time-clock-note"></textarea>
                </div>
                
                <div class="wp-time-clock-form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Guardar Cambios', 'wp-time-clock'); ?></button>
                    <button type="button" class="button wp-time-clock-modal-cancel"><?php _e('Cancelar', 'wp-time-clock'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts para el gráfico -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    // Inicializar gráfico
    var ctx = document.getElementById('wp-time-clock-activity-chart').getContext('2d');
    var activityChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: '<?php _e('Fichajes por día', 'wp-time-clock'); ?>',
                data: <?php echo json_encode($chart_data); ?>,
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
                    precision: 0
                }
            }
        }
    });
    
    // Actualizar reloj
    function updateAdminClock() {
        var now = new Date();
        var hours = String(now.getHours()).padStart(2, '0');
        var minutes = String(now.getMinutes()).padStart(2, '0');
        var seconds = String(now.getSeconds()).padStart(2, '0');
        
        $('#wp-time-clock-admin-time').text(hours + ':' + minutes + ':' + seconds);
    }
    
    // Actualizar tiempo transcurrido en celdas
    function updateElapsedTimes() {
        $('.wp-time-clock-elapsed-cell').each(function() {
            var $cell = $(this);
            var seconds = parseInt($cell.data('seconds')) + 1;
            
            $cell.data('seconds', seconds);
            $cell.text(formatElapsedTime(seconds));
        });
    }
    
    // Formatear tiempo transcurrido
    function formatElapsedTime(seconds) {
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = seconds % 60;
        
        return String(hours).padStart(2, '0') + ':' + 
               String(minutes).padStart(2, '0') + ':' + 
               String(secs).padStart(2, '0');
    }
    
    // Iniciar temporizadores
    setInterval(updateAdminClock, 1000);
    setInterval(updateElapsedTimes, 1000);
    
    // Manejar modal
    $('.wp-time-clock-edit-entry').on('click', function() {
        var entryId = $(this).data('entry-id');
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        
        // Cargar datos en el modal
        $('#wp-time-clock-entry-id').val(entryId);
        $('#wp-time-clock-user-id').val(userId);
        $('#wp-time-clock-user-name').val(userName);
        
        // TODO: Cargar los datos reales de la entrada mediante AJAX
        
        // Mostrar modal
        $('#wp-time-clock-edit-modal').fadeIn(200);
    });
    
    $('.wp-time-clock-modal-close, .wp-time-clock-modal-cancel').on('click', function() {
        $('#wp-time-clock-edit-modal').fadeOut(200);
    });
    
    // Manejar registro de salida
    $('.wp-time-clock-register-exit').on('click', function() {
        if (confirm('<?php _e('¿Estás seguro de que deseas registrar la salida para este usuario?', 'wp-time-clock'); ?>')) {
            var entryId = $(this).data('entry-id');
            var userId = $(this).data('user-id');
            
            // TODO: Implementar la lógica para registrar la salida mediante AJAX
            alert('Funcionalidad en desarrollo: Registrar salida para el usuario ID ' + userId);
        }
    });
    
    // Manejar submit del formulario
    $('#wp-time-clock-edit-form').on('submit', function(e) {
        e.preventDefault();
        
        // TODO: Implementar la lógica para guardar los cambios mediante AJAX
        alert('Funcionalidad en desarrollo: Guardar cambios para la entrada');
        
        $('#wp-time-clock-edit-modal').fadeOut(200);
    });
});
</script>
