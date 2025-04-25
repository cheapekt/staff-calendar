<?php
/**
 * Plantilla para mostrar todas las entradas de fichaje
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Inicializar gestor de fichajes
$clock_manager = new WP_Time_Clock_Manager();

// Obtener parámetros para filtrado
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01'); // Primer día del mes
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-t'); // Último día del mes
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';

// Obtener usuarios
$users = get_users(array(
    'orderby' => 'display_name',
    'order' => 'ASC'
));

// Si se ha seleccionado un usuario, obtener sus entradas
$entries = array();
if ($user_id > 0) {
    $entries = $clock_manager->get_user_entries($user_id, $start_date, $end_date, $status);
}

// Calcular estadísticas si hay entradas
$stats = array(
    'total_time' => 0,
    'days_worked' => array(),
    'entries_count' => count($entries)
);

if (!empty($entries)) {
    foreach ($entries as $entry) {
        if ($entry->clock_out) {
            $stats['total_time'] += $entry->time_worked['total_seconds'];
            $day = date('Y-m-d', strtotime($entry->clock_in));
            $stats['days_worked'][$day] = true;
        }
    }
}

// Formatear tiempo total
$total_hours = floor($stats['total_time'] / 3600);
$total_minutes = floor(($stats['total_time'] % 3600) / 60);
$total_time_formatted = sprintf('%02d:%02d', $total_hours, $total_minutes);

// Calcular días trabajados
$days_worked_count = count($stats['days_worked']);

// Calcular promedio diario
$avg_daily = $days_worked_count > 0 ? $stats['total_time'] / $days_worked_count : 0;
$avg_hours = floor($avg_daily / 3600);
$avg_minutes = floor(($avg_daily % 3600) / 60);
$avg_time_formatted = sprintf('%02d:%02d', $avg_hours, $avg_minutes);

?>

<div class="wrap wp-time-clock-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Filtros -->
    <div class="wp-time-clock-report-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="wp-time-clock-entries">
            
            <div class="wp-time-clock-filter-group">
                <label for="user_id"><?php _e('Usuario:', 'wp-time-clock'); ?></label>
                <select name="user_id" id="user_id">
                    <option value="0"><?php _e('Seleccionar usuario', 'wp-time-clock'); ?></option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($user_id, $user->ID); ?>>
                            <?php echo esc_html($user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="wp-time-clock-filter-group">
                <label for="start_date"><?php _e('Fecha inicio:', 'wp-time-clock'); ?></label>
                <input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($start_date); ?>">
            </div>
            
            <div class="wp-time-clock-filter-group">
                <label for="end_date"><?php _e('Fecha fin:', 'wp-time-clock'); ?></label>
                <input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($end_date); ?>">
            </div>
            
            <div class="wp-time-clock-filter-group">
                <label for="status"><?php _e('Estado:', 'wp-time-clock'); ?></label>
                <select name="status" id="status">
                    <option value="all" <?php selected($status, 'all'); ?>><?php _e('Todos', 'wp-time-clock'); ?></option>
                    <option value="active" <?php selected($status, 'active'); ?>><?php _e('Activo', 'wp-time-clock'); ?></option>
                    <option value="edited" <?php selected($status, 'edited'); ?>><?php _e('Editado', 'wp-time-clock'); ?></option>
                    <option value="approved" <?php selected($status, 'approved'); ?>><?php _e('Aprobado', 'wp-time-clock'); ?></option>
                    <option value="rejected" <?php selected($status, 'rejected'); ?>><?php _e('Rechazado', 'wp-time-clock'); ?></option>
                </select>
            </div>
            
            <div class="wp-time-clock-filter-actions">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Filtrar', 'wp-time-clock'); ?>
                </button>
            </div>
            
            <?php if ($user_id > 0 && !empty($entries)): ?>
            <div class="wp-time-clock-export-button">
                <button type="submit" name="export" value="csv" class="button">
                    <span class="dashicons dashicons-media-spreadsheet"></span>
                    <?php _e('Exportar CSV', 'wp-time-clock'); ?>
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <?php if ($user_id > 0): ?>
    
        <?php if (empty($entries)): ?>
            
            <div class="wp-time-clock-no-data">
                <p><?php _e('No se encontraron entradas para el usuario y período seleccionados.', 'wp-time-clock'); ?></p>
            </div>
            
        <?php else: ?>
            
            <!-- Estadísticas del período -->
            <div class="wp-time-clock-stats-card">
                <div class="wp-time-clock-card-header">
                    <h2><?php _e('Resumen del período', 'wp-time-clock'); ?></h2>
                </div>
                <div class="wp-time-clock-card-content">
                    <div class="wp-time-clock-stat-grid">
                        <div class="wp-time-clock-stat">
                            <span class="wp-time-clock-stat-value"><?php echo esc_html($stats['entries_count']); ?></span>
                            <span class="wp-time-clock-stat-label"><?php _e('Total Fichajes', 'wp-time-clock'); ?></span>
                        </div>
                        
                        <div class="wp-time-clock-stat">
                            <span class="wp-time-clock-stat-value"><?php echo esc_html($days_worked_count); ?></span>
                            <span class="wp-time-clock-stat-label"><?php _e('Días Trabajados', 'wp-time-clock'); ?></span>
                        </div>
                        
                        <div class="wp-time-clock-stat">
                            <span class="wp-time-clock-stat-value"><?php echo esc_html($total_time_formatted); ?></span>
                            <span class="wp-time-clock-stat-label"><?php _e('Tiempo Total', 'wp-time-clock'); ?></span>
                        </div>
                        
                        <div class="wp-time-clock-stat">
                            <span class="wp-time-clock-stat-value"><?php echo esc_html($avg_time_formatted); ?></span>
                            <span class="wp-time-clock-stat-label"><?php _e('Promedio Diario', 'wp-time-clock'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de entradas -->
            <div class="wp-time-clock-table-wrapper">
                <table class="wp-list-table widefat fixed striped wp-time-clock-entries-table">
                    <thead>
                        <tr>
                            <th><?php _e('Fecha', 'wp-time-clock'); ?></th>
                            <th><?php _e('Entrada', 'wp-time-clock'); ?></th>
                            <th><?php _e('Salida', 'wp-time-clock'); ?></th>
                            <th><?php _e('Tiempo', 'wp-time-clock'); ?></th>
                            <th><?php _e('Notas', 'wp-time-clock'); ?></th>
                            <th><?php _e('Estado', 'wp-time-clock'); ?></th>
                            <th><?php _e('Acciones', 'wp-time-clock'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                        <tr data-entry-id="<?php echo esc_attr($entry->id); ?>">
                            <td>
                                <?php echo date_i18n(get_option('date_format'), strtotime($entry->clock_in)); ?>
                            </td>
                            <td>
                                <?php echo date_i18n(get_option('time_format'), strtotime($entry->clock_in)); ?>
                            </td>
                            <td>
                                <?php 
                                if ($entry->clock_out) {
                                    echo date_i18n(get_option('time_format'), strtotime($entry->clock_out));
                                } else {
                                    echo '<span class="wp-time-clock-pending">' . __('En curso', 'wp-time-clock') . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($entry->time_worked) {
                                    echo esc_html($entry->time_worked['formatted']);
                                } else {
                                    echo '<span class="wp-time-clock-pending">' . __('En curso', 'wp-time-clock') . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $notes = array();
                                
                                if (!empty($entry->clock_in_note)) {
                                    $notes[] = '<strong>' . __('Entrada:', 'wp-time-clock') . '</strong> ' . esc_html($entry->clock_in_note);
                                }
                                
                                if (!empty($entry->clock_out_note)) {
                                    $notes[] = '<strong>' . __('Salida:', 'wp-time-clock') . '</strong> ' . esc_html($entry->clock_out_note);
                                }
                                
                                if (!empty($notes)) {
                                    echo implode('<br>', $notes);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                switch ($entry->status) {
                                    case 'active':
                                        echo '<span class="wp-time-clock-status-active">' . __('Activo', 'wp-time-clock') . '</span>';
                                        break;
                                    case 'edited':
                                        echo '<span class="wp-time-clock-status-edited">' . __('Editado', 'wp-time-clock') . '</span>';
                                        if (!empty($entry->edited_by)) {
                                            $editor = get_userdata($entry->edited_by);
                                            echo '<br><small>' . __('Por:', 'wp-time-clock') . ' ' . esc_html($editor->display_name) . '</small>';
                                        }
                                        break;
                                    case 'approved':
                                        echo '<span class="wp-time-clock-status-approved">' . __('Aprobado', 'wp-time-clock') . '</span>';
                                        break;
                                    case 'rejected':
                                        echo '<span class="wp-time-clock-status-rejected">' . __('Rechazado', 'wp-time-clock') . '</span>';
                                        break;
                                    default:
                                        echo '<span class="wp-time-clock-status-unknown">' . esc_html($entry->status) . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <button class="button wp-time-clock-edit-entry" 
                                        data-entry-id="<?php echo esc_attr($entry->id); ?>"
                                        data-user-id="<?php echo esc_attr($entry->user_id); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php _e('Editar', 'wp-time-clock'); ?>
                                </button>
                                
                                <?php if (empty($entry->clock_out)): ?>
                                <button class="button wp-time-clock-register-exit" 
                                        data-entry-id="<?php echo esc_attr($entry->id); ?>"
                                        data-user-id="<?php echo esc_attr($entry->user_id); ?>">
                                    <span class="dashicons dashicons-exit"></span>
                                    <?php _e('Registrar Salida', 'wp-time-clock'); ?>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php endif; ?>
        
    <?php else: ?>
        
        <div class="wp-time-clock-message wp-time-clock-message-info">
            <?php _e('Selecciona un usuario y un período para ver sus registros de fichaje.', 'wp-time-clock'); ?>
        </div>
        
    <?php endif; ?>
</div>

<!-- Modal para editar entrada (igual que en dashboard) -->
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

<script>
jQuery(document).ready(function($) {
    // Inicializar modal
    $('.wp-time-clock-edit-entry').on('click', function() {
        var entryId = $(this).data('entry-id');
        var userId = $(this).data('user-id');
        
        // Cargar datos en el modal (simulado por ahora)
        $('#wp-time-clock-entry-id').val(entryId);
        $('#wp-time-clock-user-id').val(userId);
        
        // En un caso real, cargaríamos los datos mediante AJAX
        var userName = '<?php echo isset($users[0]) ? esc_js($users[0]->display_name) : "Usuario"; ?>';
        $('#wp-time-clock-user-name').val(userName);
        
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
            
            // Implementar la lógica para registrar la salida mediante AJAX
            alert('Funcionalidad en desarrollo: Registrar salida para el usuario ID ' + userId);
        }
    });
    
    // Manejar submit del formulario
    $('#wp-time-clock-edit-form').on('submit', function(e) {
        e.preventDefault();
        
        // Implementar la lógica para guardar los cambios mediante AJAX
        alert('Funcionalidad en desarrollo: Guardar cambios para la entrada');
        
        $('#wp-time-clock-edit-modal').fadeOut(200);
    });
});
</script>
