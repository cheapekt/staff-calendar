<?php
/**
 * Plantilla para mostrar la sección de hojas de trabajo en el frontend
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos del usuario
if (!current_user_can('wp_worker_manage_worksheets')) {
    echo '<div class="worker-portal-error">' . 
        __('No tienes permiso para ver tus hojas de trabajo.', 'worker-portal') . 
        '</div>';
    return;
}
?>

<div class="worker-portal-worksheets">
    <h2><?php _e('Mis Hojas de Trabajo', 'worker-portal'); ?></h2>
    
    <?php if ($atts['show_form'] === 'yes'): ?>
    <div class="worker-portal-worksheets-form-container" style="display: none;">
        <h3><?php _e('Registrar Nueva Hoja de Trabajo', 'worker-portal'); ?></h3>
        
        <form id="worker-portal-worksheet-form" class="worker-portal-form">
            <div class="worker-portal-form-row">
                <div class="worker-portal-form-group">
                    <label for="work-date"><?php _e('Fecha:', 'worker-portal'); ?></label>
                    <input type="date" id="work-date" name="work_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="project-id"><?php _e('Obra:', 'worker-portal'); ?></label>
                    <select id="project-id" name="project_id" required>
                        <option value=""><?php _e('Seleccionar obra', 'worker-portal'); ?></option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo esc_attr($project['id']); ?>">
                                <?php echo esc_html($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="worker-portal-form-row">
                <div class="worker-portal-form-group">
                    <label for="difficulty"><?php _e('Dificultad:', 'worker-portal'); ?></label>
                    <select id="difficulty" name="difficulty">
                        <?php foreach ($difficulty_levels as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($key, 'media'); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="system-type"><?php _e('SISTEMA:', 'worker-portal'); ?></label>
                    <select id="system-type" name="system_type" required>
                        <option value=""><?php _e('Seleccionar sistema', 'worker-portal'); ?></option>
                        <?php foreach ($system_types as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="worker-portal-form-row">
                <div class="worker-portal-form-group">
                    <label for="unit-type"><?php _e('Ud.:', 'worker-portal'); ?></label>
                    <select id="unit-type" name="unit_type" required>
                        <?php foreach ($unit_types as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="quantity"><?php _e('Cantidad:', 'worker-portal'); ?></label>
                    <input type="number" id="quantity" name="quantity" step="0.01" min="0" required>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="hours"><?php _e('HORAS:', 'worker-portal'); ?></label>
                    <input type="number" id="hours" name="hours" step="0.5" min="0" required>
                </div>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="notes"><?php _e('Notas:', 'worker-portal'); ?></label>
                <textarea id="notes" name="notes" rows="3"></textarea>
            </div>
            
            <div class="worker-portal-form-actions">
                <button type="submit" class="worker-portal-button worker-portal-button-primary">
                    <?php _e('Enviar Hoja de Trabajo', 'worker-portal'); ?>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="worker-portal-worksheets-list-container">
        <h3><?php _e('Hojas de Trabajo Registradas', 'worker-portal'); ?></h3>
        
        <!-- Filtros de hojas de trabajo -->
        <div class="worker-portal-filters">
            <form id="worksheets-filter-form" class="worker-portal-filter-form">
                <div class="worker-portal-filter-row">
                    <div class="worker-portal-filter-group">
                        <label for="filter-project"><?php _e('Obra:', 'worker-portal'); ?></label>
                        <select id="filter-project" name="project_id">
                            <option value=""><?php _e('Todas', 'worker-portal'); ?></option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo esc_attr($project['id']); ?>"><?php echo esc_html($project['name']); ?></option>
                            <?php endforeach; ?>
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
                    
                    <div class="worker-portal-filter-group">
                        <label for="filter-status"><?php _e('Estado:', 'worker-portal'); ?></label>
                        <select id="filter-status" name="status">
                            <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                            <option value="pending"><?php _e('Pendiente', 'worker-portal'); ?></option>
                            <option value="validated"><?php _e('Validada', 'worker-portal'); ?></option>
                        </select>
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
        
        <div id="worksheets-list-content">
            <?php if (empty($worksheets)): ?>
                <p class="worker-portal-no-data"><?php _e('No hay hojas de trabajo registradas.', 'worker-portal'); ?></p>
            <?php else: ?>
                <div class="worker-portal-table-responsive">
                    <table class="worker-portal-table worker-portal-worksheets-table">
                        <thead>
                            <tr>
                                <th><?php _e('FECHA', 'worker-portal'); ?></th>
                                <th><?php _e('OBRA', 'worker-portal'); ?></th>
                                <th><?php _e('DIF.', 'worker-portal'); ?></th>
                                <th><?php _e('Ud.', 'worker-portal'); ?></th>
                                <th><?php _e('SISTEMA', 'worker-portal'); ?></th>
                                <th><?php _e('HORAS', 'worker-portal'); ?></th>
                                <th><?php _e('VALIDACIÓN', 'worker-portal'); ?></th>
                                <th><?php _e('ACCIONES', 'worker-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($worksheets as $worksheet): ?>
                                <tr data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($worksheet['work_date'])); ?></td>
                                    <td><?php echo esc_html($worksheet['project_name']); ?></td>
                                    <td>
                                        <?php 
                                        echo isset($difficulty_levels[$worksheet['difficulty']]) 
                                            ? esc_html($difficulty_levels[$worksheet['difficulty']]) 
                                            : esc_html(ucfirst($worksheet['difficulty'])); 
                                        ?>
                                    </td>
                                    <td><?php echo esc_html($worksheet['quantity']); ?> <?php echo $worksheet['unit_type'] == 'm2' ? 'm²' : 'H'; ?></td>
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
                                                echo '<span class="worker-portal-badge worker-portal-badge-warning">' . __('PENDIENTE', 'worker-portal') . '</span>';
                                                break;
                                            case 'validated':
                                                echo '<span class="worker-portal-badge worker-portal-badge-success">' . __('VALIDADA', 'worker-portal') . '</span>';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($worksheet['status'] === 'pending'): ?>
                                            <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-delete-worksheet" data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                                                <i class="dashicons dashicons-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-view-worksheet" data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                                            <i class="dashicons dashicons-visibility"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="worker-portal-worksheets-actions">
            <button type="button" id="new-worksheet-button" class="worker-portal-button worker-portal-button-primary">
                <i class="dashicons dashicons-plus-alt"></i> <?php _e('NUEVA HOJA DE TRABAJO', 'worker-portal'); ?>
            </button>
            
            <button type="button" id="export-worksheets-button" class="worker-portal-button worker-portal-button-secondary">
                <i class="dashicons dashicons-download"></i> <?php _e('Exportar a Excel', 'worker-portal'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de la hoja de trabajo -->
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

<?php
// Pasamos algunas variables necesarias a JavaScript
$js_vars = array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('worker_portal_worksheets_nonce'),
    'i18n' => array(
        'confirm_delete' => __('¿Estás seguro de que deseas eliminar esta hoja de trabajo?', 'worker-portal'),
        'loading' => __('Cargando detalles...', 'worker-portal'),
        'error_load' => __('Error al cargar los detalles. Por favor, inténtalo de nuevo.', 'worker-portal'),
        'error_delete' => __('Error al eliminar la hoja de trabajo.', 'worker-portal'),
    )
);
?>
<script type="text/javascript">
// Proporcionamos estas variables necesarias para el módulo de hojas de trabajo
window.workerPortalWorksheets = <?php echo json_encode($js_vars); ?>;
</script>