<?php
/**
 * Plantilla para mostrar la sección de perfil del trabajador en el frontend
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos del usuario
if (!is_user_logged_in()) {
    echo '<div class="worker-portal-error">' . 
        __('Debes iniciar sesión para ver tu perfil.', 'worker-portal') . 
        '</div>';
    return;
}

// Obtener datos del usuario actual
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$user_meta = get_user_meta($user_id);

// Obtener estadísticas del trabajador
global $wpdb;

// Contar documentos del usuario
$documents_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}worker_documents WHERE user_id = %d",
    $user_id
));

// Contar gastos del usuario
$expenses_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses WHERE user_id = %d",
    $user_id
));

// Contar hojas de trabajo del usuario
$worksheets_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}worker_worksheets WHERE user_id = %d",
    $user_id
));

// Calcular incentivos totales del usuario
$incentives_amount = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(amount) FROM {$wpdb->prefix}worker_incentives WHERE user_id = %d AND status = 'approved'",
    $user_id
));
$incentives_amount = $incentives_amount ? $incentives_amount : 0;

// Obtener fichajes recientes si existe la tabla
$time_entries = array();
if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}time_clock_entries'") == "{$wpdb->prefix}time_clock_entries") {
    $time_entries = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}time_clock_entries WHERE user_id = %d ORDER BY clock_in DESC LIMIT 5",
        $user_id
    ), ARRAY_A);
}
?>

<div class="worker-portal-profile">
    <h2><?php _e('Mi Perfil', 'worker-portal'); ?></h2>
    
    <!-- Información personal -->
    <div class="worker-portal-profile-section">
        <h3><i class="dashicons dashicons-admin-users"></i> <?php _e('Información Personal', 'worker-portal'); ?></h3>
        
        <div class="worker-portal-profile-card">
            <div class="worker-portal-profile-header">
                <div class="worker-portal-profile-avatar">
                    <?php echo get_avatar($user_id, 120); ?>
                </div>
                <div class="worker-portal-profile-info">
                    <h4><?php echo esc_html($current_user->display_name); ?></h4>
                    <p class="worker-portal-profile-role">
                        <?php 
                        if (current_user_can('administrator')) {
                            _e('Administrador', 'worker-portal');
                        } elseif (current_user_can('wp_worker_approve_expenses')) {
                            _e('Supervisor', 'worker-portal');
                        } else {
                            _e('Trabajador', 'worker-portal');
                        }
                        ?>
                    </p>
                    <p class="worker-portal-profile-email">
                        <i class="dashicons dashicons-email"></i> <?php echo esc_html($current_user->user_email); ?>
                    </p>
                </div>
            </div>
            
            <div class="worker-portal-profile-details">
                <div class="worker-portal-profile-detail-item">
                    <span class="worker-portal-profile-detail-label"><?php _e('Nombre:', 'worker-portal'); ?></span>
                    <span class="worker-portal-profile-detail-value"><?php echo esc_html($current_user->first_name); ?></span>
                </div>
                
                <div class="worker-portal-profile-detail-item">
                    <span class="worker-portal-profile-detail-label"><?php _e('Apellidos:', 'worker-portal'); ?></span>
                    <span class="worker-portal-profile-detail-value"><?php echo esc_html($current_user->last_name); ?></span>
                </div>
                
                <div class="worker-portal-profile-detail-item">
                    <span class="worker-portal-profile-detail-label"><?php _e('NIF/NIE:', 'worker-portal'); ?></span>
                    <span class="worker-portal-profile-detail-value"><?php echo esc_html(get_user_meta($user_id, 'nif', true)); ?></span>
                </div>
                
                <div class="worker-portal-profile-detail-item">
                    <span class="worker-portal-profile-detail-label"><?php _e('Teléfono:', 'worker-portal'); ?></span>
                    <span class="worker-portal-profile-detail-value"><?php echo esc_html(get_user_meta($user_id, 'phone', true)); ?></span>
                </div>
                
                <div class="worker-portal-profile-detail-item">
                    <span class="worker-portal-profile-detail-label"><?php _e('Dirección:', 'worker-portal'); ?></span>
                    <span class="worker-portal-profile-detail-value"><?php echo esc_html(get_user_meta($user_id, 'address', true)); ?></span>
                </div>
                
                <div class="worker-portal-profile-detail-item">
                    <span class="worker-portal-profile-detail-label"><?php _e('Fecha de alta:', 'worker-portal'); ?></span>
                    <span class="worker-portal-profile-detail-value">
                        <?php
                        $registration_date = get_user_meta($user_id, 'registration_date', true);
                        if (!$registration_date) {
                            $registration_date = $current_user->user_registered;
                        }
                        echo date_i18n(get_option('date_format'), strtotime($registration_date));
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="worker-portal-profile-actions">
                <button id="edit-profile-button" class="worker-portal-button worker-portal-button-primary">
                    <i class="dashicons dashicons-edit"></i> <?php _e('Editar Perfil', 'worker-portal'); ?>
                </button>
                <button id="change-password-button" class="worker-portal-button worker-portal-button-secondary">
                    <i class="dashicons dashicons-lock"></i> <?php _e('Cambiar Contraseña', 'worker-portal'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Actividad y Estadísticas -->
    <div class="worker-portal-profile-section">
        <h3><i class="dashicons dashicons-chart-bar"></i> <?php _e('Mi Actividad', 'worker-portal'); ?></h3>
        
        <div class="worker-portal-stats-grid">
            <div class="worker-portal-stat-card">
                <div class="worker-portal-stat-icon documents-icon">
                    <i class="dashicons dashicons-media-document"></i>
                </div>
                <div class="worker-portal-stat-content">
                    <div class="worker-portal-stat-value"><?php echo esc_html($documents_count); ?></div>
                    <div class="worker-portal-stat-label"><?php _e('Documentos', 'worker-portal'); ?></div>
                    <a href="#" class="worker-portal-stat-link section-link" data-section="documents">
                        <?php _e('Ver documentos', 'worker-portal'); ?> →
                    </a>
                </div>
            </div>
            
            <div class="worker-portal-stat-card">
                <div class="worker-portal-stat-icon expenses-icon">
                    <i class="dashicons dashicons-money-alt"></i>
                </div>
                <div class="worker-portal-stat-content">
                    <div class="worker-portal-stat-value"><?php echo esc_html($expenses_count); ?></div>
                    <div class="worker-portal-stat-label"><?php _e('Gastos', 'worker-portal'); ?></div>
                    <a href="#" class="worker-portal-stat-link section-link" data-section="expenses">
                        <?php _e('Ver gastos', 'worker-portal'); ?> →
                    </a>
                </div>
            </div>
            
            <div class="worker-portal-stat-card">
                <div class="worker-portal-stat-icon worksheets-icon">
                    <i class="dashicons dashicons-clipboard"></i>
                </div>
                <div class="worker-portal-stat-content">
                    <div class="worker-portal-stat-value"><?php echo esc_html($worksheets_count); ?></div>
                    <div class="worker-portal-stat-label"><?php _e('Hojas de Trabajo', 'worker-portal'); ?></div>
                    <a href="#" class="worker-portal-stat-link section-link" data-section="worksheets">
                        <?php _e('Ver hojas', 'worker-portal'); ?> →
                    </a>
                </div>
            </div>
            
            <div class="worker-portal-stat-card">
                <div class="worker-portal-stat-icon incentives-icon">
                    <i class="dashicons dashicons-star-filled"></i>
                </div>
                <div class="worker-portal-stat-content">
                    <div class="worker-portal-stat-value"><?php echo number_format($incentives_amount, 2, ',', '.'); ?> €</div>
                    <div class="worker-portal-stat-label"><?php _e('Incentivos Totales', 'worker-portal'); ?></div>
                    <a href="#" class="worker-portal-stat-link section-link" data-section="incentives">
                        <?php _e('Ver incentivos', 'worker-portal'); ?> →
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Fichajes Recientes -->
    <?php if (!empty($time_entries)): ?>
    <div class="worker-portal-profile-section">
        <h3><i class="dashicons dashicons-clock"></i> <?php _e('Fichajes Recientes', 'worker-portal'); ?></h3>
        
        <div class="worker-portal-table-responsive">
            <table class="worker-portal-table worker-portal-time-entries-table">
                <thead>
                    <tr>
                        <th><?php _e('Fecha', 'worker-portal'); ?></th>
                        <th><?php _e('Entrada', 'worker-portal'); ?></th>
                        <th><?php _e('Salida', 'worker-portal'); ?></th>
                        <th><?php _e('Duración', 'worker-portal'); ?></th>
                        <th><?php _e('Estado', 'worker-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_entries as $entry): 
                        // Calcular duración
                        $duration = '';
                        if (!empty($entry['clock_out'])) {
                            $start_time = strtotime($entry['clock_in']);
                            $end_time = strtotime($entry['clock_out']);
                            $diff = $end_time - $start_time;
                            
                            $hours = floor($diff / 3600);
                            $minutes = floor(($diff % 3600) / 60);
                            
                            $duration = sprintf('%02d:%02d', $hours, $minutes);
                        }
                    ?>
                        <tr>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($entry['clock_in'])); ?></td>
                            <td><?php echo date_i18n(get_option('time_format'), strtotime($entry['clock_in'])); ?></td>
                            <td>
                                <?php 
                                if (!empty($entry['clock_out'])) {
                                    echo date_i18n(get_option('time_format'), strtotime($entry['clock_out']));
                                } else {
                                    echo '<span class="worker-portal-badge worker-portal-badge-warning">' . __('Activo', 'worker-portal') . '</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo $duration ?: '-'; ?></td>
                            <td>
                                <?php 
                                $status_class = 'worker-portal-badge ';
                                $status_text = '';
                                
                                if (empty($entry['clock_out'])) {
                                    $status_class .= 'worker-portal-badge-warning';
                                    $status_text = __('Activo', 'worker-portal');
                                } elseif ($entry['status'] === 'edited') {
                                    $status_class .= 'worker-portal-badge-secondary';
                                    $status_text = __('Editado', 'worker-portal');
                                } else {
                                    $status_class .= 'worker-portal-badge-success';
                                    $status_text = __('Completado', 'worker-portal');
                                }
                                
                                echo '<span class="' . esc_attr($status_class) . '">' . esc_html($status_text) . '</span>';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="worker-portal-profile-actions">
            <a href="#" class="worker-portal-button worker-portal-button-secondary section-link" data-section="timeclock">
                <i class="dashicons dashicons-list-view"></i> <?php _e('Ver todos los fichajes', 'worker-portal'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal para editar perfil -->
<div id="edit-profile-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Editar Perfil', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <form id="edit-profile-form" class="worker-portal-form">
                <div class="worker-portal-form-row">
                    <div class="worker-portal-form-group">
                        <label for="first-name"><?php _e('Nombre:', 'worker-portal'); ?></label>
                        <input type="text" id="first-name" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>">
                    </div>
                    
                    <div class="worker-portal-form-group">
                        <label for="last-name"><?php _e('Apellidos:', 'worker-portal'); ?></label>
                        <input type="text" id="last-name" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>">
                    </div>
                </div>
                
                <div class="worker-portal-form-row">
                    <div class="worker-portal-form-group">
                        <label for="email"><?php _e('Email:', 'worker-portal'); ?></label>
                        <input type="email" id="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>">
                    </div>
                    
                    <div class="worker-portal-form-group">
                        <label for="phone"><?php _e('Teléfono:', 'worker-portal'); ?></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo esc_attr(get_user_meta($user_id, 'phone', true)); ?>">
                    </div>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="address"><?php _e('Dirección:', 'worker-portal'); ?></label>
                    <textarea id="address" name="address" rows="3"><?php echo esc_textarea(get_user_meta($user_id, 'address', true)); ?></textarea>
                </div>
                
                <div class="worker-portal-form-actions">
                    <input type="hidden" name="action" value="update_worker_profile">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('worker_profile_nonce'); ?>">
                    <button type="submit" class="worker-portal-button worker-portal-button-primary">
                        <i class="dashicons dashicons-yes"></i> <?php _e('Guardar Cambios', 'worker-portal'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para cambiar contraseña -->
<div id="change-password-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Cambiar Contraseña', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <form id="change-password-form" class="worker-portal-form">
                <div class="worker-portal-form-group">
                    <label for="current-password"><?php _e('Contraseña actual:', 'worker-portal'); ?></label>
                    <input type="password" id="current-password" name="current_password" required>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="new-password"><?php _e('Nueva contraseña:', 'worker-portal'); ?></label>
                    <input type="password" id="new-password" name="new_password" required>
                    <div class="password-strength-meter"></div>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="confirm-password"><?php _e('Confirmar nueva contraseña:', 'worker-portal'); ?></label>
                    <input type="password" id="confirm-password" name="confirm_password" required>
                </div>
                
                <div class="worker-portal-form-actions">
                    <input type="hidden" name="action" value="update_worker_password">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('worker_password_nonce'); ?>">
                    <button type="submit" class="worker-portal-button worker-portal-button-primary">
                        <i class="dashicons dashicons-yes"></i> <?php _e('Cambiar Contraseña', 'worker-portal'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Abrir modal para editar perfil
    $('#edit-profile-button').on('click', function() {
        $('#edit-profile-modal').fadeIn();
    });
    
    // Abrir modal para cambiar contraseña
    $('#change-password-button').on('click', function() {
        $('#change-password-modal').fadeIn();
    });
    
    // Cerrar modales
    $('.worker-portal-modal-close').on('click', function() {
        $(this).closest('.worker-portal-modal').fadeOut();
    });
    
    // Cerrar modales al hacer clic fuera
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('worker-portal-modal')) {
            $('.worker-portal-modal').fadeOut();
        }
    });
    
    // Cerrar modales con Escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.worker-portal-modal').fadeOut();
        }
    });
    
    // Enviar formulario de edición de perfil
    $('#edit-profile-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: worker_portal_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#edit-profile-form button[type="submit"]').prop('disabled', true).html(
                    '<i class="dashicons dashicons-update-alt spinning"></i> <?php _e('Guardando...', 'worker-portal'); ?>'
                );
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#edit-profile-modal').fadeOut();
                    // Recargar página para mostrar cambios
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'); ?>');
            },
            complete: function() {
                $('#edit-profile-form button[type="submit"]').prop('disabled', false).html(
                    '<i class="dashicons dashicons-yes"></i> <?php _e('Guardar Cambios', 'worker-portal'); ?>'
                );
            }
        });
    });
    
    // Enviar formulario de cambio de contraseña
    $('#change-password-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validar que las contraseñas coinciden
        var newPassword = $('#new-password').val();
        var confirmPassword = $('#confirm-password').val();
        
        if (newPassword !== confirmPassword) {
            alert('<?php _e('Las contraseñas no coinciden.', 'worker-portal'); ?>');
            return;
        }
        
        var formData = new FormData(this);
        
        $.ajax({
            url: worker_portal_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#change-password-form button[type="submit"]').prop('disabled', true).html(
                    '<i class="dashicons dashicons-update-alt spinning"></i> <?php _e('Cambiando...', 'worker-portal'); ?>'
                );
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#change-password-modal').fadeOut();
                    $('#change-password-form')[0].reset();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php _e('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'worker-portal'); ?>');
            },
            complete: function() {
                $('#change-password-form button[type="submit"]').prop('disabled', false).html(
                    '<i class="dashicons dashicons-yes"></i> <?php _e('Cambiar Contraseña', 'worker-portal'); ?>'
                );
            }
        });
    });
    
    // Navegación a otras secciones
    $('.section-link').on('click', function(e) {
        e.preventDefault();
        var section = $(this).data('section');
        $('.worker-portal-navigation a[data-section="' + section + '"]').click();
    });
    
    // Medidor de fortaleza de contraseña
    $('#new-password').on('keyup', function() {
        var password = $(this).val();
        var strength = 0;
        
        // Si la contraseña es mayor a 6 caracteres, sumar puntos
        if (password.length >= 6) strength += 1;
        
        // Si la contraseña tiene letras minúsculas y mayúsculas, sumar puntos
        if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength += 1;
        
        // Si la contraseña tiene números, sumar puntos
        if (password.match(/([0-9])/)) strength += 1;
        
        // Si la contraseña tiene caracteres especiales, sumar puntos
        if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength += 1;
        
        // Mostrar el indicador de fuerza
        var strengthMeter = $('.password-strength-meter');
        
        if (strength < 2) {
            strengthMeter.html('<?php _e('Débil', 'worker-portal'); ?>').css('color', 'red');
        } else if (strength === 2) {
            strengthMeter.html('<?php _e('Regular', 'worker-portal'); ?>').css('color', 'orange');
        } else if (strength === 3) {
            strengthMeter.html('<?php _e('Buena', 'worker-portal'); ?>').css('color', 'yellowgreen');
        } else {
            strengthMeter.html('<?php _e('Fuerte', 'worker-portal'); ?>').css('color', 'green');
        }
    });
});
</script>