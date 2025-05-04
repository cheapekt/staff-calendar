<?php
/**
 * Plantilla para mostrar los detalles de un trabajador
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="worker-portal-worker-details">
    <h3><?php _e('Información del Trabajador', 'worker-portal'); ?></h3>
    
    <div class="worker-portal-profile-header">
        <div class="worker-portal-profile-avatar">
            <?php echo get_avatar($user_id, 120); ?>
        </div>
        <div class="worker-portal-profile-info">
            <h4><?php echo esc_html($user->display_name); ?></h4>
            <p class="worker-portal-profile-role"><?php echo esc_html($role); ?></p>
            <p class="worker-portal-profile-email">
                <i class="dashicons dashicons-email"></i> <?php echo esc_html($user->user_email); ?>
            </p>
        </div>
    </div>
    
    <div class="worker-portal-profile-details">
        <table class="worker-portal-details-table">
            <tr>
                <th><?php _e('Nombre:', 'worker-portal'); ?></th>
                <td><?php echo esc_html($user->first_name); ?></td>
            </tr>
            <tr>
                <th><?php _e('Apellidos:', 'worker-portal'); ?></th>
                <td><?php echo esc_html($user->last_name); ?></td>
            </tr>
            <tr>
                <th><?php _e('NIF/NIE:', 'worker-portal'); ?></th>
                <td><?php echo esc_html($nif); ?></td>
            </tr>
            <tr>
                <th><?php _e('Teléfono:', 'worker-portal'); ?></th>
                <td><?php echo esc_html($phone); ?></td>
            </tr>
            <tr>
                <th><?php _e('Dirección:', 'worker-portal'); ?></th>
                <td><?php echo esc_html($address); ?></td>
            </tr>
            <tr>
                <th><?php _e('Fecha de alta:', 'worker-portal'); ?></th>
                <td><?php echo date_i18n(get_option('date_format'), strtotime($registration_date)); ?></td>
            </tr>
            <tr>
                <th><?php _e('Estado:', 'worker-portal'); ?></th>
                <td>
                    <?php if ($status === 'active'): ?>
                        <span class="worker-portal-badge worker-portal-badge-success"><?php _e('Activo', 'worker-portal'); ?></span>
                    <?php else: ?>
                        <span class="worker-portal-badge worker-portal-badge-danger"><?php _e('Inactivo', 'worker-portal'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Último acceso:', 'worker-portal'); ?></th>
                <td><?php echo !empty($last_login) ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_login)) : __('Nunca', 'worker-portal'); ?></td>
            </tr>
        </table>
    </div>
    
    <h3><?php _e('Estadísticas', 'worker-portal'); ?></h3>
    
    <div class="worker-portal-profile-stats">
        <div class="worker-portal-profile-stat">
            <div class="worker-portal-profile-stat-value"><?php echo esc_html($documents_count); ?></div>
            <div class="worker-portal-profile-stat-label"><?php _e('Documentos', 'worker-portal'); ?></div>
        </div>
        
        <div class="worker-portal-profile-stat">
            <div class="worker-portal-profile-stat-value"><?php echo esc_html($expenses_count); ?></div>
            <div class="worker-portal-profile-stat-label"><?php _e('Gastos', 'worker-portal'); ?></div>
            <?php if ($pending_expenses > 0): ?>
                <div class="worker-portal-badge worker-portal-badge-warning"><?php echo sprintf(__('%d pendientes', 'worker-portal'), $pending_expenses); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="worker-portal-profile-stat">
            <div class="worker-portal-profile-stat-value"><?php echo esc_html($worksheets_count); ?></div>
            <div class="worker-portal-profile-stat-label"><?php _e('Hojas de Trabajo', 'worker-portal'); ?></div>
            <?php if ($pending_worksheets > 0): ?>
                <div class="worker-portal-badge worker-portal-badge-warning"><?php echo sprintf(__('%d pendientes', 'worker-portal'), $pending_worksheets); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="worker-portal-profile-stat">
            <div class="worker-portal-profile-stat-value"><?php echo number_format($incentives_amount, 2, ',', '.'); ?> €</div>
            <div class="worker-portal-profile-stat-label"><?php _e('Incentivos Totales', 'worker-portal'); ?></div>
        </div>
    </div>
    
    <?php if (!empty($recent_activity)): ?>
    <h3><?php _e('Actividad Reciente', 'worker-portal'); ?></h3>
    
    <div class="worker-portal-recent-activity">
        <?php foreach ($recent_activity as $activity): ?>
            <div class="worker-portal-activity-card">
                <div class="worker-portal-activity-header">
                    <h4 class="worker-portal-activity-title"><?php echo esc_html($activity['title']); ?></h4>
                    <span class="worker-portal-activity-date"><?php echo date_i18n(get_option('date_format'), strtotime($activity['date'])); ?></span>
                </div>
                <div class="worker-portal-activity-content">
                    <p><?php echo esc_html($activity['content']); ?></p>
                </div>
                <div class="worker-portal-activity-footer">
                    <a href="#" class="view-activity" data-type="<?php echo esc_attr($activity['type']); ?>" data-id="<?php echo esc_attr($activity['id']); ?>">
                        <?php _e('Ver detalles', 'worker-portal'); ?> →
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="worker-portal-profile-actions">
        <button type="button" class="worker-portal-button worker-portal-button-primary edit-worker" data-user-id="<?php echo esc_attr($user_id); ?>">
            <i class="dashicons dashicons-edit"></i> <?php _e('Editar', 'worker-portal'); ?>
        </button>
        
        <?php if ($status === 'active'): ?>
            <button type="button" class="worker-portal-button worker-portal-button-danger deactivate-worker" data-user-id="<?php echo esc_attr($user_id); ?>">
                <i class="dashicons dashicons-lock"></i> <?php _e('Desactivar', 'worker-portal'); ?>
            </button>
        <?php else: ?>
            <button type="button" class="worker-portal-button worker-portal-button-success activate-worker" data-user-id="<?php echo esc_attr($user_id); ?>">
                <i class="dashicons dashicons-unlock"></i> <?php _e('Activar', 'worker-portal'); ?>
            </button>
        <?php endif; ?>
        
        <button type="button" class="worker-portal-button worker-portal-button-warning reset-password" data-user-id="<?php echo esc_attr($user_id); ?>">
            <i class="dashicons dashicons-admin-network"></i> <?php _e('Restablecer Contraseña', 'worker-portal'); ?>
        </button>
    </div>
    
    <div class="worker-portal-worker-shortcuts">
        <h3><?php _e('Accesos Rápidos', 'worker-portal'); ?></h3>
        
        <div class="worker-portal-worker-shortcut-buttons">
            <button type="button" class="worker-portal-button worker-portal-button-secondary view-worker-expenses" data-user-id="<?php echo esc_attr($user_id); ?>">
                <i class="dashicons dashicons-money-alt"></i> <?php _e('Ver Gastos', 'worker-portal'); ?>
            </button>
            
            <button type="button" class="worker-portal-button worker-portal-button-secondary view-worker-worksheets" data-user-id="<?php echo esc_attr($user_id); ?>">
                <i class="dashicons dashicons-clipboard"></i> <?php _e('Ver Hojas de Trabajo', 'worker-portal'); ?>
            </button>
            
            <button type="button" class="worker-portal-button worker-portal-button-secondary view-worker-documents" data-user-id="<?php echo esc_attr($user_id); ?>">
                <i class="dashicons dashicons-media-document"></i> <?php _e('Ver Documentos', 'worker-portal'); ?>
            </button>
            
            <button type="button" class="worker-portal-button worker-portal-button-secondary view-worker-incentives" data-user-id="<?php echo esc_attr($user_id); ?>">
                <i class="dashicons dashicons-star-filled"></i> <?php _e('Ver Incentivos', 'worker-portal'); ?>
            </button>
        </div>
    </div>
</div>