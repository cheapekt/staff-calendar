<?php
/**
 * Plantilla principal del portal del trabajador (vista de usuario)
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Definir contenido específico para esta vista
ob_start();
?>

<div class="worker-portal-navigation">
    <ul>
        <li>
            <a href="#" class="worker-portal-button worker-portal-button-primary" data-section="documents">
                <i class="dashicons dashicons-media-document"></i> 
                <?php _e('Mis Documentos', 'worker-portal'); ?>
            </a>
        </li>
        <li>
            <a href="#" class="worker-portal-button worker-portal-button-primary" data-section="expenses">
                <i class="dashicons dashicons-money-alt"></i> 
                <?php _e('Mis Gastos', 'worker-portal'); ?>
            </a>
        </li>
        <li>
            <a href="#" class="worker-portal-button worker-portal-button-primary" data-section="worksheets">
                <i class="dashicons dashicons-clipboard"></i> 
                <?php _e('Mis Hojas de Trabajo', 'worker-portal'); ?>
            </a>
        </li>
        <li>
            <a href="#" class="worker-portal-button worker-portal-button-primary" data-section="incentives">
                <i class="dashicons dashicons-star-filled"></i> 
                <?php _e('Mis Incentivos', 'worker-portal'); ?>
            </a>
        </li>
    </ul>
</div>

<div class="worker-portal-sections">
    <div id="documents-section" class="worker-portal-section" style="display:none;">
        <?php echo do_shortcode('[worker_documents]'); ?>
    </div>

    <div id="expenses-section" class="worker-portal-section" style="display:none;">
        <?php echo do_shortcode('[worker_expenses]'); ?>
    </div>

    <div id="worksheets-section" class="worker-portal-section" style="display:none;">
        <?php echo do_shortcode('[worker_worksheets]'); ?>
    </div>

    <div id="incentives-section" class="worker-portal-section" style="display:none;">
        <?php echo do_shortcode('[worker_incentives]'); ?>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Navegación entre secciones del portal
    $('.worker-portal-navigation a').on('click', function(e) {
        e.preventDefault();
        
        // Ocultar todas las secciones
        $('.worker-portal-section').hide();
        
        // Remover clase activa de todos los botones
        $('.worker-portal-navigation a').removeClass('active');
        
        // Mostrar sección seleccionada
        const section = $(this).data('section');
        $(`#${section}-section`).show();
        
        // Marcar botón como activo
        $(this).addClass('active');
    });

    // Mostrar sección de gastos por defecto
    $('.worker-portal-navigation a[data-section="expenses"]').click();
});
</script>

<?php
$content = ob_get_clean();

// Definir la ubicación del contenido
$content_template = 'public/partials/portal-content-worker.php';

// Guardar el contenido en un archivo temporal
if (!file_exists(WORKER_PORTAL_PATH . $content_template)) {
    file_put_contents(WORKER_PORTAL_PATH . $content_template, $content);
}

// Incluir la plantilla base
include(WORKER_PORTAL_PATH . 'public/partials/portal-base.php');