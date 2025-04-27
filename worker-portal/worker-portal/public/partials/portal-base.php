<?php
/**
 * Plantilla base para el Portal del Trabajador
 * Esta plantilla define la estructura común compartida por todas las vistas del portal
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Obtener información del usuario actual
$current_user = wp_get_current_user();
$is_admin = Worker_Portal_Utils::is_portal_admin();
?>
<!--- Esta es la estructura base del portal que será compartida por la vista de trabajadores y administradores -->
<div class="worker-portal-container">
    <!-- Cabecera del portal -->
    <div class="worker-portal-header">
        <div class="worker-portal-header-content">
            <h1 class="worker-portal-title">
                <?php if ($is_admin): ?>
                    <?php _e('Portal del Trabajador - Panel de Gestión', 'worker-portal'); ?>
                <?php else: ?>
                    <?php _e('Portal del Trabajador', 'worker-portal'); ?>
                <?php endif; ?>
            </h1>
            <div class="worker-portal-user-info">
                <span class="worker-portal-welcome">
                    <?php printf(__('Bienvenido, %s', 'worker-portal'), esc_html($current_user->display_name)); ?>
                </span>
                <?php if ($is_admin): ?>
                    <span class="worker-portal-admin-badge">
                        <?php _e('Administrador', 'worker-portal'); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Contenido específico del portal (será reemplazado por cada vista) -->
    <div class="worker-portal-content">
        <?php 
        // Aquí es donde las plantillas específicas insertarán su contenido
        if (isset($content_template) && file_exists(WORKER_PORTAL_PATH . $content_template)) {
            include(WORKER_PORTAL_PATH . $content_template);
        }
        ?>
    </div>
    
    <!-- Pie de página del portal -->
    <div class="worker-portal-footer">
        <div class="worker-portal-footer-content">
            <p>
                &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?> - 
                <?php _e('Portal del Trabajador', 'worker-portal'); ?>
            </p>
            <?php if (current_user_can('manage_options')): ?>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=worker-portal'); ?>" class="worker-portal-admin-link">
                        <?php _e('Administración', 'worker-portal'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>