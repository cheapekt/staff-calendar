<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$is_admin = current_user_can('manage_options');

if (!is_user_logged_in()) {
    echo '<div class="staff-calendar-error">Debes iniciar sesión para ver el calendario.</div>';
    return;
}
?>

<div class="staff-calendar-frontend">
    <div id="calendar-messages" class="calendar-messages" style="display: none;"></div>
    
    <div class="calendar-controls">
        <div class="calendar-navigation">
            <button class="button prev-month" aria-label="Mes anterior">&larr; Mes anterior</button>
            <span class="current-month"></span>
            <button class="button next-month" aria-label="Mes siguiente">Mes siguiente &rarr;</button>
        </div>
    </div>

    <div class="calendar-container">
        <div class="calendar-loading">
            <div class="loading-spinner"></div>
            <span>Cargando calendario...</span>
        </div>
        
        <table class="calendar-table">
            <thead>
                <tr>
                    <th class="user-column">Usuario</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users = get_users([
                    'role' => 'subscriber',
                    'orderby' => 'display_name',
                    'order' => 'ASC'
                ]);

                foreach ($users as $user) {
                    $user_data = get_userdata($user->ID);
                    $department = get_user_meta($user->ID, 'department', true);
                    
                    echo '<tr data-user-id="' . esc_attr($user->ID) . '">';
                    echo '<td class="user-info">';
                    echo '<div class="user-name">' . esc_html($user_data->display_name) . '</div>';
                    if ($department) {
                        echo '<div class="user-department">' . esc_html($department) . '</div>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Modal para ver/editar destino y vehículo -->
    <div id="destination-modal" class="destination-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Información Laboral</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-info">
                    <p><strong>Usuario:</strong> <span class="modal-user"></span></p>
                    <p><strong>Fecha:</strong> <span class="modal-date"></span></p>
                </div>
                <?php if ($is_admin): ?>
                    <div class="modal-input-group">
                        <div class="input-row">
                            <label for="modal-destination">Destino:</label>
                            <input type="text" id="modal-destination" class="modal-destination-input">
                        </div>
                        
                        <div class="input-row">
                            <label for="modal-vehicle">Vehículo:</label>
                            <input type="text" id="modal-vehicle" class="modal-vehicle-input">
                        </div>
                        
                        <div class="date-range-inputs">
                            <div class="date-input">
                                <label for="modal-start-date">Fecha inicio:</label>
                                <input type="date" id="modal-start-date" class="modal-date-input">
                            </div>
                            <div class="date-input">
                                <label for="modal-end-date">Fecha fin:</label>
                                <input type="date" id="modal-end-date" class="modal-date-input">
                            </div>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button class="button button-primary modal-save">Guardar</button>
                        <button class="button modal-cancel">Cancelar</button>
                    </div>
                <?php else: ?>
                    <div class="modal-info-display">
                        <p><strong>Destino:</strong> <span class="modal-destination-text">Sin destino asignado</span></p>
                        <p><strong>Vehículo:</strong> <span class="modal-vehicle-text">Sin vehículo asignado</span></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    window.staffCalendarConfig = {
        isAdmin: <?php echo $is_admin ? 'true' : 'false'; ?>,
        currentUserId: <?php echo get_current_user_id(); ?>,
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('staff_calendar_nonce'); ?>',
        translations: {
            loading: 'Cargando...',
            error: 'Ha ocurrido un error',
            success: 'Cambios guardados correctamente',
            noData: 'No hay datos disponibles'
        }
    };
</script>