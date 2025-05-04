<?php
/**
 * Plantilla para el formulario de edición de un trabajador
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}
?>

<form id="edit-worker-form" class="worker-portal-form">
    <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
    <input type="hidden" name="action" value="update_worker">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('worker_admin_nonce'); ?>">
    
    <div class="worker-portal-form-row">
        <div class="worker-portal-form-group">
            <label for="edit-first-name"><?php _e('Nombre:', 'worker-portal'); ?></label>
            <input type="text" id="edit-first-name" name="first_name" value="<?php echo esc_attr($user->first_name); ?>" required>
        </div>
        
        <div class="worker-portal-form-group">
            <label for="edit-last-name"><?php _e('Apellidos:', 'worker-portal'); ?></label>
            <input type="text" id="edit-last-name" name="last_name" value="<?php echo esc_attr($user->last_name); ?>" required>
        </div>
    </div>
    
    <div class="worker-portal-form-row">
        <div class="worker-portal-form-group">
            <label for="edit-email"><?php _e('Email:', 'worker-portal'); ?></label>
            <input type="email" id="edit-email" name="email" value="<?php echo esc_attr($user->user_email); ?>" required>
        </div>
        
        <div class="worker-portal-form-group">
            <label for="edit-phone"><?php _e('Teléfono:', 'worker-portal'); ?></label>
            <input type="tel" id="edit-phone" name="phone" value="<?php echo esc_attr($phone); ?>">
        </div>
    </div>
    
    <div class="worker-portal-form-group">
        <label for="edit-address"><?php _e('Dirección:', 'worker-portal'); ?></label>
        <textarea id="edit-address" name="address" rows="3"><?php echo esc_textarea($address); ?></textarea>
    </div>
    
    <div class="worker-portal-form-group">
        <label for="edit-role"><?php _e('Rol:', 'worker-portal'); ?></label>
        <select id="edit-role" name="role">
            <option value="subscriber" <?php selected(in_array('subscriber', $user->roles)); ?>><?php _e('Trabajador', 'worker-portal'); ?></option>
            <option value="supervisor" <?php selected(in_array('supervisor', $user->roles)); ?>><?php _e('Supervisor', 'worker-portal'); ?></option>
        </select>
    </div>
    
    <div class="worker-portal-form-group">
        <label>
            <input type="checkbox" id="edit-reset-password" name="reset_password" value="1">
            <?php _e('Restablecer contraseña', 'worker-portal'); ?>
        </label>
    </div>
    
    <div id="reset-password-container" style="display: none;">
        <div class="worker-portal-form-row">
            <div class="worker-portal-form-group">
                <label for="edit-password"><?php _e('Nueva contraseña:', 'worker-portal'); ?></label>
                <input type="password" id="edit-password" name="password">
                <div class="password-strength-meter"></div>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="edit-confirm-password"><?php _e('Confirmar contraseña:', 'worker-portal'); ?></label>
                <input type="password" id="edit-confirm-password" name="confirm_password">
            </div>
        </div>
        
        <div class="worker-portal-form-group">
            <label>
                <input type="checkbox" name="notify_user" value="1" checked>
                <?php _e('Notificar al usuario', 'worker-portal'); ?>
            </label>
            <p class="description"><?php _e('Envía un email al usuario con su nueva contraseña.', 'worker-portal'); ?></p>
        </div>
    </div>
    
    <div class="worker-portal-form-actions">
        <button type="button" class="worker-portal-button worker-portal-button-link worker-portal-modal-cancel">
            <?php _e('Cancelar', 'worker-portal'); ?>
        </button>
        <button type="submit" class="worker-portal-button worker-portal-button-primary">
            <i class="dashicons dashicons-yes"></i> <?php _e('Guardar Cambios', 'worker-portal'); ?>
        </button>
    </div>
</form>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Mostrar/ocultar campos de contraseña
        $("#edit-reset-password").on("change", function() {
            if ($(this).is(":checked")) {
                $("#reset-password-container").slideDown();
            } else {
                $("#reset-password-container").slideUp();
            }
        });
        
        // Medidor de fortaleza de contraseña
        $("#edit-password").on("keyup", function() {
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
            var strengthMeter = $(".password-strength-meter");
            
            if (strength < 2) {
                strengthMeter.html("<?php _e('Débil', 'worker-portal'); ?>").css("color", "red");
            } else if (strength === 2) {
                strengthMeter.html("<?php _e('Regular', 'worker-portal'); ?>").css("color", "orange");
            } else if (strength === 3) {
                strengthMeter.html("<?php _e('Buena', 'worker-portal'); ?>").css("color", "yellowgreen");
            } else {
                strengthMeter.html("<?php _e('Fuerte', 'worker-portal'); ?>").css("color", "green");
            }
        });
        
        // Cerrar modal
        $(".worker-portal-modal-cancel").on("click", function() {
            $("#edit-worker-modal").fadeOut();
        });
    });
</script>