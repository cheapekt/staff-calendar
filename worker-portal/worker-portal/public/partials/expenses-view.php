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
    <div class="worker-portal-expenses-form-container">
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="worker-portal-expenses-actions">
            <button type="button" id="new-expense-button" class="worker-portal-button worker-portal-button-primary">
                <i class="dashicons dashicons-plus-alt"></i> <?php _e('NUEVO GASTO', 'worker-portal'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Mostrar/ocultar el formulario de gastos
    $("#new-expense-button").on("click", function() {
        $(".worker-portal-expenses-form-container").slideToggle();
        $(this).toggleClass("active");
        
        if ($(this).hasClass("active")) {
            $(this).html('<i class="dashicons dashicons-minus"></i> <?php _e('CANCELAR', 'worker-portal'); ?>');
        } else {
            $(this).html('<i class="dashicons dashicons-plus-alt"></i> <?php _e('NUEVO GASTO', 'worker-portal'); ?>');
        }
    });
    
    // Resto del script similar al de la versión de admin
    // ...
});
</script>