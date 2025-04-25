<?php
/**
 * Plantilla para mostrar el historial de fichajes del usuario
 *
 * @since      1.0.0
 */
?>

<div class="wp-time-clock-history-container">
    <h3 class="wp-time-clock-history-title"><?php _e('Mi Historial de Fichajes', 'wp-time-clock'); ?></h3>
    
    <?php if (empty($entries)): ?>
    
    <div class="wp-time-clock-no-entries">
        <p><?php printf(__('No hay registros de fichaje en los últimos %d días.', 'wp-time-clock'), $days); ?></p>
    </div>
    
    <?php else: ?>
    
    <div class="wp-time-clock-history-summary">
        <div class="wp-time-clock-summary-item">
            <span class="wp-time-clock-summary-label"><?php _e('Período:', 'wp-time-clock'); ?></span>
            <span class="wp-time-clock-summary-value">
                <?php echo date_i18n(get_option('date_format'), strtotime($start_date)); ?> - 
                <?php echo date_i18n(get_option('date_format'), strtotime($end_date)); ?>
            </span>
        </div>
        
        <?php
        // Calcular estadísticas
        $total_time = 0;
        $days_worked = array();
        
        foreach ($entries as $entry) {
            if ($entry->clock_out) {
                $total_time += $entry->time_worked['total_seconds'];
                $day = date('Y-m-d', strtotime($entry->clock_in));
                $days_worked[$day] = true;
            }
        }
        
        // Formatear tiempo total
        $hours = floor($total_time / 3600);
        $minutes = floor(($total_time % 3600) / 60);
        
        // Calcular media por día
        $avg_time = count($days_worked) > 0 ? $total_time / count($days_worked) : 0;
        $avg_hours = floor($avg_time / 3600);
        $avg_minutes = floor(($avg_time % 3600) / 60);
        ?>
        
        <div class="wp-time-clock-summary-item">
            <span class="wp-time-clock-summary-label"><?php _e('Tiempo total:', 'wp-time-clock'); ?></span>
            <span class="wp-time-clock-summary-value">
                <?php printf(__('%d horas y %d minutos', 'wp-time-clock'), $hours, $minutes); ?>
            </span>
        </div>
        
        <div class="wp-time-clock-summary-item">
            <span class="wp-time-clock-summary-label"><?php _e('Días trabajados:', 'wp-time-clock'); ?></span>
            <span class="wp-time-clock-summary-value">
                <?php echo count($days_worked); ?>
            </span>
        </div>
        
        <div class="wp-time-clock-summary-item">
            <span class="wp-time-clock-summary-label"><?php _e('Media diaria:', 'wp-time-clock'); ?></span>
            <span class="wp-time-clock-summary-value">
                <?php printf(__('%d horas y %d minutos', 'wp-time-clock'), $avg_hours, $avg_minutes); ?>
            </span>
        </div>
    </div>
    
    <table class="wp-time-clock-history">
        <thead>
            <tr>
                <th><?php _e('Fecha', 'wp-time-clock'); ?></th>
                <?php if ($show_times): ?>
                <th><?php _e('Entrada', 'wp-time-clock'); ?></th>
                <th><?php _e('Salida', 'wp-time-clock'); ?></th>
                <?php endif; ?>
                <th><?php _e('Tiempo', 'wp-time-clock'); ?></th>
                <?php if ($show_notes): ?>
                <th><?php _e('Notas', 'wp-time-clock'); ?></th>
                <?php endif; ?>
                <th><?php _e('Estado', 'wp-time-clock'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
            <tr>
                <td>
                    <?php echo date_i18n(get_option('date_format'), strtotime($entry->clock_in)); ?>
                </td>
                
                <?php if ($show_times): ?>
                <td>
                    <?php echo date_i18n(get_option('time_format'), strtotime($entry->clock_in)); ?>
                </td>
                <td>
                    <?php 
                    if ($entry->clock_out) {
                        echo date_i18n(get_option('time_format'), strtotime($entry->clock_out));
                    } else {
                        echo '<span class="wp-time-clock-pending">-</span>';
                    }
                    ?>
                </td>
                <?php endif; ?>
                
                <td>
                    <?php 
                    if ($entry->time_worked) {
                        echo $entry->time_worked['formatted'];
                    } else {
                        echo '<span class="wp-time-clock-pending">' . __('En curso', 'wp-time-clock') . '</span>';
                    }
                    ?>
                </td>
                
                <?php if ($show_notes): ?>
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
                <?php endif; ?>
                
                <td>
                    <?php 
                    switch ($entry->status) {
                        case 'active':
                            echo '<span class="wp-time-clock-status-active">' . __('Activo', 'wp-time-clock') . '</span>';
                            break;
                        case 'edited':
                            echo '<span class="wp-time-clock-status-edited">' . __('Editado', 'wp-time-clock') . '</span>';
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
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php endif; ?>
    
    <?php if (count($entries) > 0): ?>
    <div class="wp-time-clock-history-footer">
        <p class="wp-time-clock-history-note">
            <?php _e('Nota: Este historial muestra tus fichajes en el período seleccionado. Contacta con tu administrador si detectas alguna discrepancia.', 'wp-time-clock'); ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<style>
.wp-time-clock-history-container {
    margin: 2rem 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.wp-time-clock-history-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #1F2937;
}

.wp-time-clock-no-entries {
    padding: 1.5rem;
    background-color: #F9FAFB;
    border-radius: 0.5rem;
    border: 1px solid #E5E7EB;
    text-align: center;
    color: #6B7280;
}

.wp-time-clock-history-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background-color: #F3F4F6;
    border-radius: 0.5rem;
}

.wp-time-clock-summary-item {
    display: flex;
    flex-direction: column;
}

.wp-time-clock-summary-label {
    font-size: 0.875rem;
    color: #6B7280;
    margin-bottom: 0.25rem;
}

.wp-time-clock-summary-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1F2937;
}

.wp-time-clock-history {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
}

.wp-time-clock-history th {
    padding: 0.75rem;
    text-align: left;
    background-color: #F9FAFB;
    border-bottom: 2px solid #E5E7EB;
    font-weight: 600;
    color: #4B5563;
}

.wp-time-clock-history td {
    padding: 0.75rem;
    border-bottom: 1px solid #E5E7EB;
    color: #4B5563;
    vertical-align: top;
}

.wp-time-clock-history tr:hover {
    background-color: #F9FAFB;
}

.wp-time-clock-pending {
    color: #9CA3AF;
    font-style: italic;
}

.wp-time-clock-status-active {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: #DBEAFE;
    color: #1E40AF;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.wp-time-clock-status-edited {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: #FEF3C7;
    color: #92400E;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.wp-time-clock-status-approved {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: #D1FAE5;
    color: #065F46;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.wp-time-clock-status-rejected {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: #FEE2E2;
    color: #991B1B;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.wp-time-clock-status-unknown {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: #F3F4F6;
    color: #4B5563;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.wp-time-clock-history-footer {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #E5E7EB;
}

.wp-time-clock-history-note {
    font-size: 0.875rem;
    color: #6B7280;
    font-style: italic;
}

@media (max-width: 768px) {
    .wp-time-clock-history-summary {
        grid-template-columns: 1fr;
    }
    
    .wp-time-clock-history {
        display: block;
        overflow-x: auto;
    }
}
</style>
