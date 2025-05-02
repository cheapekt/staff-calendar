<?php
/**
 * Plantilla para mostrar la sección de documentos en el frontend
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos del usuario
if (!current_user_can('wp_worker_view_own_documents')) {
    echo '<div class="worker-portal-error">' . 
        __('No tienes permiso para ver tus documentos.', 'worker-portal') . 
        '</div>';
    return;
}
?>

<div class="worker-portal-documents">
    <h2><?php _e('Mis Documentos', 'worker-portal'); ?></h2>
    
    <!-- Filtros de documentos -->
    <div class="worker-portal-filters">
        <form id="documents-filter-form" class="worker-portal-filter-form">
            <div class="worker-portal-filter-row">
                <div class="worker-portal-filter-group">
                    <label for="filter-category"><?php _e('Categoría:', 'worker-portal'); ?></label>
                    <select id="filter-category" name="category">
                        <option value=""><?php _e('Todas', 'worker-portal'); ?></option>
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
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
                    <label for="filter-search"><?php _e('Buscar:', 'worker-portal'); ?></label>
                    <input type="text" id="filter-search" name="search" placeholder="<?php _e('Buscar en título...', 'worker-portal'); ?>">
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
    
    <!-- Contenedor para la lista de documentos -->
    <div id="documents-list-content">
        <?php if (empty($documents)): ?>
            <p class="worker-portal-no-data"><?php _e('No hay documentos disponibles.', 'worker-portal'); ?></p>
        <?php else: ?>
            <div class="worker-portal-table-responsive">
                <table class="worker-portal-table worker-portal-documents-table">
                    <thead>
                        <tr>
                            <th><?php _e('Fecha', 'worker-portal'); ?></th>
                            <th><?php _e('Título', 'worker-portal'); ?></th>
                            <th><?php _e('Categoría', 'worker-portal'); ?></th>
                            <th><?php _e('Acciones', 'worker-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr data-document-id="<?php echo esc_attr($document['id']); ?>">
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($document['upload_date'])); ?></td>
                                <td><?php echo esc_html($document['title']); ?></td>
                                <td>
                                    <?php 
                                    echo isset($categories[$document['category']]) 
                                        ? esc_html($categories[$document['category']]) 
                                        : esc_html(ucfirst($document['category'])); 
                                    ?>
                                </td>
                                <td>
                                    <a href="#" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-download-document" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                        <i class="dashicons dashicons-download"></i> <?php _e('Descargar', 'worker-portal'); ?>
                                    </a>
                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-view-document" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                        <i class="dashicons dashicons-visibility"></i> <?php _e('Ver', 'worker-portal'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para ver documentos -->
<div id="document-view-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content worker-portal-modal-large">
        <div class="worker-portal-modal-header">
            <h3 id="document-modal-title"><?php _e('Documento', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="document-modal-content">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>
</div>