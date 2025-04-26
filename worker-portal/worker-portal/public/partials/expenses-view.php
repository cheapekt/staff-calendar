<?php
/**
 * Plantilla para mostrar la sección de gastos en el frontend público
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos del usuario
if (!current_user_can('wp_worker_manage_expenses')) {
    echo '<div class="worker-portal-error">' . 
        __('No tienes permiso para ver tus gastos.', 'worker-portal') . 
        '</div>';
    return;
}
?>

<div class="worker-portal-expenses">
    <h2><?php _e('Mis Gastos', 'worker-portal'); ?></h2>
    
    <?php if ($atts['show_form'] === 'yes'): ?>
    <div class="worker-portal-expenses-form-container" style="display: none;">
        <h3><?php _e('Comunicar Nuevo Gasto', 'worker-portal'); ?></h3>
        
        <form id="worker-portal-expense-form" class="worker-portal-form">
            <div class="worker-portal-form-group">
                <label for="report-date"><?php _e('Fecha de comunicación:', 'worker-portal'); ?></label>
                <input type="text" id="report-date" value="<?php echo date_i18n(get_option('date_format')); ?>" readonly>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="expense-date"><?php _e('Fecha del gasto:', 'worker-portal'); ?></label>
                <input type="date" id="expense-date" name="expense_date" required>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="expense-type"><?php _e('Tipo de gasto:', 'worker-portal'); ?></label>
                <select id="expense-type" name="expense_type" required>
                    <option value=""><?php _e('Selecciona un tipo', 'worker-portal'); ?></option>
                    <?php foreach ($expense_types as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="expense-description"><?php _e('Motivo del gasto:', 'worker-portal'); ?></label>
                <textarea id="expense-description" name="description" rows="3" required></textarea>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="expense-amount"><?php _e('Importe del gasto:', 'worker-portal'); ?></label>
                <input type="number" id="expense-amount" name="amount" min="0.01" step="0.01" required>
                <span class="worker-portal-input-suffix">€</span>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="expense-receipt"><?php _e('¿Aporta justificante?', 'worker-portal'); ?></label>
                <div class="worker-portal-checkbox-group">
                    <input type="checkbox" id="expense-has-receipt" name="has_receipt" value="yes">
                    <label for="expense-has-receipt"><?php _e('Sí, tengo un justificante', 'worker-portal'); ?></label>
                </div>
            </div>
            
            <div id="receipt-upload-container" class="worker-portal-form-group" style="display: none;">
                <label for="expense-receipt"><?php _e('Adjuntar justificante:', 'worker-portal'); ?></label>
                <div class="worker-portal-file-upload">
                    <input type="file" id="expense-receipt" name="receipt" accept="image/*,.pdf">
                    <button type="button" id="take-photo" class="worker-portal-button worker-portal-button-secondary">
                        <i class="dashicons dashicons-camera"></i> <?php _e('Tomar foto', 'worker-portal'); ?>
                    </button>
                </div>
                <div id="receipt-preview" class="worker-portal-receipt-preview"></div>
            </div>
            
            <div class="worker-portal-form-actions">
                <button type="submit" class="worker-portal-button worker-portal-button-primary">
                    <?php _e('Enviar Gasto', 'worker-portal'); ?>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="worker-portal-expenses-list-container">
        <h3><?php _e('Gastos Comunicados', 'worker-portal'); ?></h3>
        
        <!-- Filtros de gastos -->
        <div class="worker-portal-filters">
            <form id="expenses-filter-form" class="worker-portal-filter-form">
                <div class="worker-portal-filter-row">
                    <div class="worker-portal-filter-group">
                        <label for="filter-date-from"><?php _e('Desde:', 'worker-portal'); ?></label>
                        <input type="date" id="filter-date-from" name="date_from">
                    </div>
                    
                    <div class="worker-portal-filter-group">
                        <label for="filter-date-to"><?php _e('Hasta:', 'worker-portal'); ?></label>
                        <input type="date" id="filter-date-to" name="date_to">
                    </div>
                    
                    <div class="worker-portal-filter-group">
                        <label for="filter-type"><?php _e('Tipo:', 'worker-portal'); ?></label>
                        <select id="filter-type" name="expense_type">
                            <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                            <?php foreach ($expense_types as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="worker-portal-filter-group">
                        <label for="filter-status"><?php _e('Estado:', 'worker-portal'); ?></label>
                        <select id="filter-status" name="status">
                            <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                            <option value="pending"><?php _e('Pendiente', 'worker-portal'); ?></option>
                            <option value="approved"><?php _e('Aprobado', 'worker-portal'); ?></option>
                            <option value="rejected"><?php _e('Denegado', 'worker-portal'); ?></option>
                        </select>
                    </div>
                    
                    <div class="worker-portal-filter-group">
                        <label for="filter-search"><?php _e('Buscar:', 'worker-portal'); ?></label>
                        <input type="text" id="filter-search" name="search" placeholder="<?php _e('Buscar en motivo...', 'worker-portal'); ?>">
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
        
        <div id="expenses-list-content">
            <?php if (empty($expenses)): ?>
                <p class="worker-portal-no-data"><?php _e('No hay gastos registrados.', 'worker-portal'); ?></p>
            <?php else: ?>
                <div class="worker-portal-table-responsive">
                    <table class="worker-portal-table worker-portal-expenses-table">
                        <thead>
                            <tr>
                                <th><?php _e('FECHA', 'worker-portal'); ?></th>
                                <th><?php _e('TIPO', 'worker-portal'); ?></th>
                                <th><?php _e('GASTO (motivo del gasto)', 'worker-portal'); ?></th>
                                <th><?php _e('Fecha del gasto', 'worker-portal'); ?></th>
                                <th><?php _e('Km / Horas / Euros', 'worker-portal'); ?></th>
                                <th><?php _e('TICKET', 'worker-portal'); ?></th>
                                <th><?php _e('VALIDACIÓN', 'worker-portal'); ?></th>
                                <th><?php _e('ACCIONES', 'worker-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $expense): ?>
                                <tr data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($expense['report_date'])); ?></td>
                                    <td>
                                        <?php 
                                        echo isset($expense_types[$expense['expense_type']]) 
                                            ? esc_html($expense_types[$expense['expense_type']]) 
                                            : esc_html($expense['expense_type']); 
                                        ?>
                                    </td>
                                    <td><?php echo esc_html($expense['description']); ?></td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($expense['expense_date'])); ?></td>
                                    <td>
                                        <?php 
                                        // Mostrar unidad según tipo de gasto
                                        switch ($expense['expense_type']) {
                                            case 'km':
                                                echo esc_html($expense['amount']) . ' Km';
                                                break;
                                            case 'hours':
                                                echo esc_html($expense['amount']) . ' Horas';
                                                break;
                                            default:
                                                echo esc_html(number_format($expense['amount'], 2, ',', '.')) . ' Euros';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($expense['has_receipt']): ?>
                                            <span class="worker-portal-badge worker-portal-badge-success"><?php _e('SI', 'worker-portal'); ?></span>
                                            <?php if (!empty($expense['receipt_path'])): ?>
                                                <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . $expense['receipt_path']); ?>" target="_blank" class="worker-portal-view-receipt">
                                                    <i class="dashicons dashicons-visibility"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="worker-portal-badge worker-portal-badge-secondary"><?php _e('NO', 'worker-portal'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        switch ($expense['status']) {
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
                                    <td>
                                        <?php if ($expense['status'] === 'pending'): ?>
                                            <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-delete-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                                <i class="dashicons dashicons-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($expense['receipt_path'])): ?>
                                            <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . $expense['receipt_path']); ?>" target="_blank" class="worker-portal-button worker-portal-button-small worker-portal-button-outline">
                                                <i class="dashicons dashicons-visibility"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="worker-portal-pagination">
                    <?php
                    // Mostrar paginación si hay más de 10 gastos
                    if (isset($total_pages) && $total_pages > 1) {
                        echo '<div class="worker-portal-pagination-info">';
                        printf(
                            __('Mostrando %1$s - %2$s de %3$s gastos', 'worker-portal'),
                            (($current_page - 1) * $per_page) + 1,
                            min($current_page * $per_page, $total_items),
                            $total_items
                        );
                        echo '</div>';
                        
                        echo '<div class="worker-portal-pagination-links">';
                        // Botón anterior
                        if ($current_page > 1) {
                            echo '<a href="#" class="worker-portal-pagination-prev" data-page="' . ($current_page - 1) . '">&laquo; ' . __('Anterior', 'worker-portal') . '</a>';
                        }
                        
                        // Números de página
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $class = ($i === $current_page) ? 'worker-portal-pagination-current' : '';
                            echo '<a href="#" class="worker-portal-pagination-number ' . $class . '" data-page="' . $i . '">' . $i . '</a>';
                        }
                        
                        // Botón siguiente
                        if ($current_page < $total_pages) {
                            echo '<a href="#" class="worker-portal-pagination-next" data-page="' . ($current_page + 1) . '">' . __('Siguiente', 'worker-portal') . ' &raquo;</a>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="worker-portal-expenses-actions">
            <button type="button" id="new-expense-button" class="worker-portal-button worker-portal-button-primary">
                <i class="dashicons dashicons-plus-alt"></i> <?php _e('NUEVO GASTO', 'worker-portal'); ?>
            </button>
            
            <button type="button" id="export-expenses-button" class="worker-portal-button worker-portal-button-secondary">
                <i class="dashicons dashicons-download"></i> <?php _e('Exportar a Excel', 'worker-portal'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Cámara para tomar fotos en dispositivos móviles -->
<div id="camera-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Tomar foto del ticket', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <video id="camera-preview" autoplay playsinline></video>
            <canvas id="camera-capture" style="display: none;"></canvas>
            <div class="worker-portal-camera-controls">
                <button type="button" id="capture-photo" class="worker-portal-button worker-portal-button-primary">
                    <i class="dashicons dashicons-camera"></i> <?php _e('Capturar', 'worker-portal'); ?>
                </button>
                <button type="button" id="retry-photo" class="worker-portal-button worker-portal-button-secondary" style="display: none;">
                    <i class="dashicons dashicons-image-rotate"></i> <?php _e('Reintentar', 'worker-portal'); ?>
                </button>
                <button type="button" id="accept-photo" class="worker-portal-button worker-portal-button-success" style="display: none;">
                    <i class="dashicons dashicons-yes"></i> <?php _e('Aceptar', 'worker-portal'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para visualizar recibos -->
<div id="receipt-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content worker-portal-modal-large">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Justificante', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="receipt-modal-content"></div>
        </div>
    </div>
</div>