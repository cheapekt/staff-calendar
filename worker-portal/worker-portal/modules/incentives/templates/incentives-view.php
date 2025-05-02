<?php
/**
 * Plantilla para mostrar la sección de incentivos en el frontend
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos del usuario
if (!current_user_can('wp_worker_view_incentives')) {
    echo '<div class="worker-portal-error">' . 
        __('No tienes permiso para ver tus incentivos.', 'worker-portal') . 
        '</div>';
    return;
}

// Cargar utilidades
require_once WORKER_PORTAL_PATH . 'includes/class-utils.php';

// Verificar si el usuario es administrador
$is_admin = Worker_Portal_Utils::is_portal_admin();
?>

<div class="worker-portal-incentives">
    <h2><?php _e('Mis Incentivos', 'worker-portal'); ?></h2>
    
    <?php if ($is_admin): ?>
    <!-- Vista específica para administradores -->
    <div class="worker-portal-admin-incentives">
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
                            <?php foreach ($incentive_types as $key => $label): ?>
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
        <div class="worker-portal-filters">
            <form id="admin-incentives-filter-form" class="worker-portal-filter-form">
                <div class="worker-portal-filter-row">
                    <div class="worker-portal-filter-group">
                        <label for="filter-worker-inc"><?php _e('Trabajador:', 'worker-portal'); ?></label>
                        <select id="filter-worker-inc" name="user_id">
                            <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                            <?php foreach ($workers as $worker): ?>
                                <option value="<?php echo esc_attr($worker->ID); ?>"><?php echo esc_html($worker->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="worker-portal-filter-group">
                        <label for="filter-status-inc"><?php _e('Estado:', 'worker-portal'); ?></label>
                        <select id="filter-status-inc" name="status">
                            <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                            <option value="pending"><?php _e('Pendientes', 'worker-portal'); ?></option>
                            <option value="approved"><?php _e('Aprobados', 'worker-portal'); ?></option>
                            <option value="rejected"><?php _e('Rechazados', 'worker-portal'); ?></option>
                        </select>
                    </div>
                    
                    <div class="worker-portal-filter-group">
                        <label for="filter-date-from-inc"><?php _e('Desde:', 'worker-portal'); ?></label>
                        <input type="date" id="filter-date-from-inc" name="date_from">
                    </div>
                    
                    <div class="worker-portal-filter-group">
                        <label for="filter-date-to-inc"><?php _e('Hasta:', 'worker-portal'); ?></label>
                        <input type="date" id="filter-date-to-inc" name="date_to">
                    </div>
                </div>
                
                <div class="worker-portal-filter-actions">
                    <button type="submit" class="worker-portal-button worker-portal-button-secondary">
                        <i class="dashicons dashicons-search"></i> <?php _e('Filtrar', 'worker-portal'); ?>
                    </button>
                    <button type="button" id="clear-filters-inc" class="worker-portal-button worker-portal-button-outline">
                        <i class="dashicons dashicons-dismiss"></i> <?php _e('Limpiar filtros', 'worker-portal'); ?>
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
    <?php else: ?>
    <!-- Vista para trabajadores normales -->
    <div class="worker-portal-user-incentives">
        <!-- Filtros de incentivos -->
        <div class="worker-portal-filters">
            <form id="incentives-filter-form" class="worker-portal-filter-form">
                <div class="worker-portal-filter-row">
                    <div class="worker-portal-filter-group">
                        <label for="filter-type"><?php _e('Tipo:', 'worker-portal'); ?></label>
                        <select id="filter-type" name="type">
                            <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                            <?php foreach ($incentive_types as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="worker-portal-filter-group">
                        <label for="filter-status"><?php _e('Estado:', 'worker-portal'); ?></label>
                        <select id="filter-status" name="status">
                            <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                            <option value="pending"><?php _e('Pendientes', 'worker-portal'); ?></option>
                            <option value="approved"><?php _e('Aprobados', 'worker-portal'); ?></option>
                            <option value="rejected"><?php _e('Rechazados', 'worker-portal'); ?></option>
                        </select>
                    </div>
                    
                    <div class="worker-portal-filter-group">
                        <label for="filter-date-from"><?php _e('Desde:', 'worker-portal'); ?></label>
                        <input type="date" id="filter-date-from" name="date_from">
                    </div>
                    
                    <div class="worker-portal-filter-group">
                        <label for="filter-date-to"><?php _e('Hasta:', 'worker-portal'); ?></label>
                        <input type="date" id="filter-date-to" name="date_to">
                    </div>
                </div>
                
                <div class="worker-portal-filter-actions">
                    <button type="submit" class="worker-portal-button worker-portal-button-secondary">
                        <i class="dashicons dashicons-search"></i> <?php _e('Filtrar', 'worker-portal'); ?>
                    </button>
                    <button type="button" id="clear-filters" class="worker-portal-button worker-portal-button-outline">
                        <i class="dashicons dashicons-dismiss"></i> <?php _e('Limpiar filtros', 'worker-portal'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Lista de incentivos -->
        <div id="incentives-list-content">
            <?php if (empty($incentives)): ?>
                <p class="worker-portal-no-data"><?php _e('No hay incentivos disponibles.', 'worker-portal'); ?></p>
            <?php else: ?>
                <div class="worker-portal-table-responsive">
                    <table class="worker-portal-table worker-portal-incentives-table">
                        <thead>
                            <tr>
                                <th><?php _e('FECHA', 'worker-portal'); ?></th>
                                <th><?php _e('PLUS DE PRODUCTIVIDAD', 'worker-portal'); ?></th>
                                <th><?php _e('IMPORTE', 'worker-portal'); ?></th>
                                <th><?php _e('VALIDACIÓN', 'worker-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($incentives as $incentive): ?>
                                <tr data-incentive-id="<?php echo esc_attr($incentive['id']); ?>">
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($incentive['calculation_date'])); ?></td>
                                    <td><?php echo esc_html($incentive['description']); ?></td>
                                    <td><?php echo esc_html(number_format($incentive['amount'], 2, ',', '.')); ?> €</td>
                                    <td>
                                        <?php 
                                        switch ($incentive['status']) {
                                            case 'pending':
                                                echo '<span class="worker-portal-badge worker-portal-badge-warning">' . __('PENDIENTE', 'worker-portal') . '</span>';
                                                break;
                                            case 'approved':
                                                echo '<span class="worker-portal-badge worker-portal-badge-success">' . __('APROBADO', 'worker-portal') . '</span>';
                                                break;
                                            case 'rejected':
                                                echo '<span class="worker-portal-badge worker-portal-badge-danger">' . __('DENEGADO', 'worker-portal') . '</span>';
                                                break;
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php
                // Calcular total de incentivos aprobados
                $total_approved = 0;
                foreach ($incentives as $incentive) {
                    if ($incentive['status'] === 'approved') {
                        $total_approved += $incentive['amount'];
                    }
                }
                ?>
                
                <div class="worker-portal-incentives-total">
                    <p class="worker-portal-incentives-total-label"><?php _e('TOTAL PLUS DE PRODUCTIVIDAD', 'worker-portal'); ?></p>
                    <p class="worker-portal-incentives-total-value"><?php echo number_format($total_approved, 2, ',', '.'); ?> euros</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal para detalles de incentivo (solo para administradores) -->
<?php if ($is_admin): ?>
<div id="incentive-details-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Detalles del Incentivo', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="incentive-details-content">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>
</div>
<?php endif; ?>