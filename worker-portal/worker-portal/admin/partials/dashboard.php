<?php
/**
 * Plantilla del dashboard de administración
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap worker-portal-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="worker-portal-dashboard-widgets">
        <div class="worker-portal-widget">
            <h2><?php _e('Resumen del Portal', 'worker-portal'); ?></h2>
            <div class="worker-portal-widget-content">
                <?php
                // Obtener estadísticas generales
                $total_users = count_users();
                $total_documents = $this->get_total_documents();
                $total_expenses = $this->get_total_expenses();
                $total_worksheets = $this->get_total_worksheets();
                ?>
                <ul>
                    <li>
                        <strong><?php _e('Usuarios', 'worker-portal'); ?>:</strong> 
                        <?php echo esc_html($total_users['total_users']); ?>
                    </li>
                    <li>
                        <strong><?php _e('Documentos', 'worker-portal'); ?>:</strong> 
                        <?php echo esc_html($total_documents); ?>
                    </li>
                    <li>
                        <strong><?php _e('Gastos', 'worker-portal'); ?>:</strong> 
                        <?php echo esc_html($total_expenses); ?>
                    </li>
                    <li>
                        <strong><?php _e('Hojas de Trabajo', 'worker-portal'); ?>:</strong> 
                        <?php echo esc_html($total_worksheets); ?>
                    </li>
                </ul>
            </div>
        </div>

        <div class="worker-portal-widget">
            <h2><?php _e('Configuraciones Rápidas', 'worker-portal'); ?></h2>
            <div class="worker-portal-widget-content">
                <ul>
                    <li>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-documents')); ?>">
                            <?php _e('Configurar Documentos', 'worker-portal'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-expenses')); ?>">
                            <?php _e('Configurar Gastos', 'worker-portal'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-worksheets')); ?>">
                            <?php _e('Configurar Hojas de Trabajo', 'worker-portal'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
// Métodos auxiliares (deberían estar en la clase Admin o en una clase de utilidades)
function get_total_documents() {
    global $wpdb;
    return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_documents");
}

function get_total_expenses() {
    global $wpdb;
    return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_expenses");
}

function get_total_worksheets() {
    global $wpdb;
    return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}worker_worksheets");
}