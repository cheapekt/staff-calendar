<?php
/**
 * Enhanced template for displaying documents in the frontend with admin capabilities
 *
 * @since      1.0.0
 */

// If directly accessed, exit
if (!defined('ABSPATH')) {
    exit;
}

// Verify user permissions
if (!current_user_can('wp_worker_view_own_documents')) {
    echo '<div class="worker-portal-error">' . 
        __('No tienes permiso para ver tus documentos.', 'worker-portal') . 
        '</div>';
    return;
}

// Check if user is admin or supervisor
$is_admin = Worker_Portal_Utils::is_portal_admin();

// Load database class if not already loaded
if (!class_exists('Worker_Portal_Database')) {
    require_once WORKER_PORTAL_PATH . 'includes/class-database.php';
}
$database = Worker_Portal_Database::get_instance();

// Get document categories
$categories = get_option('worker_portal_document_categories', array(
    'payroll' => __('Nóminas', 'worker-portal'),
    'contract' => __('Contratos', 'worker-portal'),
    'communication' => __('Comunicaciones', 'worker-portal'),
    'other' => __('Otros', 'worker-portal')
));

// Get users for admin view
$users = array();
if ($is_admin) {
    $users = get_users(array(
        'role__not_in' => array('administrator'),
        'orderby' => 'display_name'
    ));
}

// Define tabs for admin view
$tabs = array();
if ($is_admin) {
    $tabs = array(
        'all' => __('Todos los Documentos', 'worker-portal'),
        'upload' => __('Subir Documento', 'worker-portal'),
        'settings' => __('Configuración', 'worker-portal')
    );
}

// Set current tab (default to 'all')
$current_tab = isset($_GET['doc_tab']) ? sanitize_key($_GET['doc_tab']) : 'all';
if (!array_key_exists($current_tab, $tabs) && $is_admin) {
    $current_tab = 'all';
}

// Get documents
if ($is_admin) {
    // Admin sees all documents
    $user_id = isset($_GET['user_filter']) ? intval($_GET['user_filter']) : 0;
    $category = isset($_GET['category_filter']) ? sanitize_text_field($_GET['category_filter']) : '';
    
    // Limit to most recent 20 documents for initial view
    $documents = $database->get_user_documents($user_id, 20, 0, $category);
} else {
    // Regular users only see their own documents
    $user_id = get_current_user_id();
    $documents = $database->get_user_documents($user_id, $atts['limit'], 0, '');
}
?>
<div class="worker-portal-documents">
    <h2><?php _e('Mis Documentos', 'worker-portal'); ?></h2>
    
    <?php if ($is_admin): ?>
    <!-- Admin Tabs - only for admins -->
    <div class="worker-portal-admin-tabs">
        <ul class="worker-portal-admin-tabs-nav">
            <?php foreach ($tabs as $tab_key => $tab_label): ?>
                <a href="?doc_tab=<?php echo esc_attr($tab_key); ?>" class="worker-portal-tab-link <?php echo $current_tab === $tab_key ? 'active' : ''; ?>">
                    <?php if ($tab_key === 'all'): ?>
                        <i class="dashicons dashicons-media-document"></i> 
                    <?php elseif ($tab_key === 'upload'): ?>
                        <i class="dashicons dashicons-upload"></i> 
                    <?php elseif ($tab_key === 'settings'): ?>
                        <i class="dashicons dashicons-admin-settings"></i> 
                    <?php endif; ?>
                    <?php echo esc_html($tab_label); ?>
                </a>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="worker-portal-admin-tabs-content">
        <?php if (!$is_admin || $current_tab === 'all'): ?>
            <!-- All Documents Tab (default for workers, one of the tabs for admins) -->
            <div class="worker-portal-documents-list">
                <!-- Filters -->
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
                            
                            <?php if ($is_admin): ?>
                            <div class="worker-portal-filter-group">
                                <label for="filter-user"><?php _e('Usuario:', 'worker-portal'); ?></label>
                                <select id="filter-user" name="user_id">
                                    <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
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
                
                <!-- Document List -->
                <div id="documents-list-content">
                    <?php if (empty($documents)): ?>
                        <p class="worker-portal-no-data"><?php _e('No hay documentos disponibles.', 'worker-portal'); ?></p>
                    <?php else: ?>
                        <div class="worker-portal-table-responsive">
                            <table class="worker-portal-table worker-portal-documents-table">
                                <thead>
                                    <tr>
                                        <?php if ($is_admin): ?>
                                            <th><?php _e('ID', 'worker-portal'); ?></th>
                                        <?php endif; ?>
                                        <th><?php _e('Fecha', 'worker-portal'); ?></th>
                                        <th><?php _e('Título', 'worker-portal'); ?></th>
                                        <th><?php _e('Categoría', 'worker-portal'); ?></th>
                                        <?php if ($is_admin): ?>
                                            <th><?php _e('Usuario', 'worker-portal'); ?></th>
                                        <?php endif; ?>
                                        <th><?php _e('Acciones', 'worker-portal'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $document): 
                                        // Get username for admin view
                                        $username = '';
                                        if ($is_admin) {
                                            $user = get_userdata($document['user_id']);
                                            $username = $user ? $user->display_name : __('Usuario desconocido', 'worker-portal');
                                        }
                                    ?>
                                        <tr data-document-id="<?php echo esc_attr($document['id']); ?>">
                                            <?php if ($is_admin): ?>
                                                <td><?php echo esc_html($document['id']); ?></td>
                                            <?php endif; ?>
                                            <td><?php echo date_i18n(get_option('date_format'), strtotime($document['upload_date'])); ?></td>
                                            <td><?php echo esc_html($document['title']); ?></td>
                                            <td>
                                                <?php 
                                                echo isset($categories[$document['category']]) 
                                                    ? esc_html($categories[$document['category']]) 
                                                    : esc_html(ucfirst($document['category'])); 
                                                ?>
                                            </td>
                                            <?php if ($is_admin): ?>
                                                <td><?php echo esc_html($username); ?></td>
                                            <?php endif; ?>
                                            <td>
                                                <a href="#" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-download-document" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                                    <i class="dashicons dashicons-download"></i> <?php _e('Descargar', 'worker-portal'); ?>
                                                </a>
                                                <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-view-document" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                                    <i class="dashicons dashicons-visibility"></i> <?php _e('Ver', 'worker-portal'); ?>
                                                </button>
                                                <?php if ($is_admin): ?>
                                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-document-details" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                                        <i class="dashicons dashicons-info"></i> <?php _e('Detalles', 'worker-portal'); ?>
                                                    </button>
                                                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-delete-document" data-document-id="<?php echo esc_attr($document['id']); ?>">
                                                        <i class="dashicons dashicons-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
 <?php if ($is_admin && $current_tab === 'upload'): ?>
            <!-- Upload Document Tab (only for admins) -->
            <div class="worker-portal-upload-document">
                <h3><?php _e('Subir Nuevo Documento', 'worker-portal'); ?></h3>
                
                <form id="upload-document-form" method="post" enctype="multipart/form-data" class="worker-portal-form">
                    <div class="worker-portal-form-row">
                        <div class="worker-portal-form-group">
                            <label for="document-title"><?php _e('Título:', 'worker-portal'); ?></label>
                            <input type="text" id="document-title" name="title" required>
                        </div>
                        
                        <div class="worker-portal-form-group">
                            <label for="document-category"><?php _e('Categoría:', 'worker-portal'); ?></label>
                            <select id="document-category" name="category" required>
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="worker-portal-form-group">
                        <label for="document-description"><?php _e('Descripción:', 'worker-portal'); ?></label>
                        <textarea id="document-description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="worker-portal-form-group">
                        <label for="document-file"><?php _e('Archivo (PDF):', 'worker-portal'); ?></label>
                        <input type="file" id="document-file" name="document" accept="application/pdf" required>
                        <p class="description"><?php _e('Solo se aceptan archivos PDF.', 'worker-portal'); ?></p>
                    </div>
                    
                    <div class="worker-portal-form-group">
                        <label for="document-users"><?php _e('Destinatarios:', 'worker-portal'); ?></label>
                        <select id="document-users" name="users[]" multiple size="5" required>
                            <option value="all"><?php _e('Todos los trabajadores', 'worker-portal'); ?></option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Mantén presionada la tecla Ctrl (o Cmd en Mac) para seleccionar múltiples usuarios.', 'worker-portal'); ?></p>
                    </div>
                    
                    <div class="worker-portal-form-group">
                        <label for="document-notify">
                            <input type="checkbox" id="document-notify" name="notify" value="1" checked>
                            <?php _e('Enviar notificación por email a los usuarios', 'worker-portal'); ?>
                        </label>
                    </div>
                    
                    <div class="worker-portal-form-actions">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('worker_portal_admin_nonce'); ?>">
                        <input type="hidden" name="action" value="admin_upload_document">
                        <button type="submit" class="worker-portal-button worker-portal-button-primary">
                            <i class="dashicons dashicons-upload"></i> <?php _e('Subir Documento', 'worker-portal'); ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($is_admin && $current_tab === 'settings'): ?>
            <!-- Settings Tab (only for admins) -->
            <div class="worker-portal-documents-settings">
                <h3><?php _e('Configuración de Documentos', 'worker-portal'); ?></h3>
                
                <form id="document-settings-form" method="post" class="worker-portal-form">
                    <h4><?php _e('Categorías de Documentos', 'worker-portal'); ?></h4>
                    <p class="description"><?php _e('Configura las categorías disponibles para clasificar documentos.', 'worker-portal'); ?></p>
                    
                    <table class="worker-portal-table categories-table">
                        <thead>
                            <tr>
                                <th><?php _e('Clave', 'worker-portal'); ?></th>
                                <th><?php _e('Nombre', 'worker-portal'); ?></th>
                                <th><?php _e('Acciones', 'worker-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="categories-list">
                            <?php foreach ($categories as $key => $label): ?>
                                <tr class="category-row">
                                    <td>
                                        <input type="text" name="categories[keys][]" value="<?php echo esc_attr($key); ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" name="categories[labels][]" value="<?php echo esc_attr($label); ?>" required>
                                    </td>
                                    <td>
                                        <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline remove-category">
                                            <i class="dashicons dashicons-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">
                                    <button type="button" id="add-category" class="worker-portal-button worker-portal-button-secondary">
                                        <i class="dashicons dashicons-plus"></i> <?php _e('Añadir categoría', 'worker-portal'); ?>
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <h4><?php _e('Notificaciones', 'worker-portal'); ?></h4>
                    
                    <div class="worker-portal-form-group">
                        <label for="notification-email"><?php _e('Email de notificación:', 'worker-portal'); ?></label>
                        <input type="email" id="notification-email" name="notification_email" value="<?php echo esc_attr(get_option('worker_portal_document_notification_email', get_option('admin_email'))); ?>" class="regular-text">
                        <p class="description"><?php _e('Email al que se enviarán las notificaciones cuando se suba un nuevo documento.', 'worker-portal'); ?></p>
                    </div>
                    
                    <div class="worker-portal-form-actions">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('worker_portal_admin_nonce'); ?>">
                        <input type="hidden" name="action" value="admin_save_document_settings">
                        <button type="submit" class="worker-portal-button worker-portal-button-primary">
                            <?php _e('Guardar Cambios', 'worker-portal'); ?>
                        </button>
                    </div>
                </form>
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

<!-- Modal para detalles de documento (solo para admin) -->
<?php if ($is_admin): ?>
<div id="document-details-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Detalles del Documento', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="document-details-content">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- JavaScript para la funcionalidad -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Inicializar filtros
    $("#documents-filter-form").on("submit", function(e) {
        e.preventDefault();
        loadFilteredDocuments(1);
    });
    
    // Limpiar filtros
    $("#clear-filters").on("click", function() {
        $("#documents-filter-form")[0].reset();
        loadFilteredDocuments(1);
    });
    
    // Acciones de documentos
    $(document).on("click", ".worker-portal-download-document", function(e) {
        e.preventDefault();
        const documentId = $(this).data("document-id");
        downloadDocument(documentId);
    });
    
    $(document).on("click", ".worker-portal-view-document", function(e) {
        e.preventDefault();
        const documentId = $(this).data("document-id");
        viewDocument(documentId);
    });
    
    <?php if ($is_admin): ?>
    // Acciones administrativas
    $(document).on("click", ".worker-portal-document-details", function(e) {
        e.preventDefault();
        const documentId = $(this).data("document-id");
        viewDocumentDetails(documentId);
    });
    
    $(document).on("click", ".worker-portal-delete-document", function(e) {
        e.preventDefault();
        const documentId = $(this).data("document-id");
        deleteDocument(documentId);
    });
    
    // Gestión de categorías en la configuración
    $("#add-category").on("click", function() {
        addCategoryRow();
    });
    
    $(document).on("click", ".remove-category", function() {
        removeCategoryRow($(this));
    });
    
    // Formulario de subida de documento
    $("#upload-document-form").on("submit", function(e) {
        e.preventDefault();
        uploadDocument($(this));
    });
    
    // Formulario de configuración
    $("#document-settings-form").on("submit", function(e) {
        e.preventDefault();
        saveDocumentSettings($(this));
    });
    <?php endif; ?>
    
    // Cerrar modales
    $(".worker-portal-modal-close").on("click", function() {
        $(this).closest(".worker-portal-modal").fadeOut();
    });
    
    $(window).on("click", function(e) {
        if ($(e.target).hasClass("worker-portal-modal")) {
            $(".worker-portal-modal").fadeOut();
        }
    });
    
    // Función para cargar documentos filtrados
    function loadFilteredDocuments(page) {
        // Mostrar indicador de carga
        $("#documents-list-content").html(
            '<div class="worker-portal-loading">' +
            '<div class="worker-portal-spinner"></div>' +
            '<p>Cargando documentos...</p>' +
            '</div>'
        );
        
        // Obtener datos del formulario
        const formData = new FormData($("#documents-filter-form")[0]);
        formData.append("action", "filter_documents");
        formData.append("nonce", workerPortalDocuments.nonce);
        formData.append("page", page);
        formData.append("per_page", 10);
        
        // Realizar petición AJAX
        $.ajax({
            url: workerPortalDocuments.ajax_url,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $("#documents-list-content").html(response.data.html);
                    
                    // Inicializar paginación
                    initPagination(response.data.current_page, response.data.total_pages);
                } else {
                    $("#documents-list-content").html(
                        '<p class="worker-portal-no-data">' + response.data + '</p>'
                    );
                }
            },
            error: function() {
                $("#documents-list-content").html(
                    '<p class="worker-portal-no-data">' +
                    workerPortalDocuments.i18n.error +
                    '</p>'
                );
            }
        });
    }
    
    // Descargar documento
    function downloadDocument(documentId) {
        $.ajax({
            url: workerPortalDocuments.ajax_url,
            type: "POST",
            data: {
                action: "download_document",
                nonce: workerPortalDocuments.nonce,
                document_id: documentId
            },
            success: function(response) {
                if (response.success) {
                    // Crear enlace de descarga
                    const link = document.createElement("a");
                    link.href = response.data.download_url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(workerPortalDocuments.i18n.error);
            }
        });
    }
    
    // Ver documento
    function viewDocument(documentId) {
        $.ajax({
            url: workerPortalDocuments.ajax_url,
            type: "POST",
            data: {
                action: "download_document",
                nonce: workerPortalDocuments.nonce,
                document_id: documentId
            },
            beforeSend: function() {
                $("#document-modal-content").html(
                    '<div class="worker-portal-loading">' +
                    '<div class="worker-portal-spinner"></div>' +
                    '<p>' + workerPortalDocuments.i18n.loading + '</p>' +
                    '</div>'
                );
                $("#document-view-modal").fadeIn();
            },
            success: function(response) {
                if (response.success) {
                    // Mostrar PDF en iframe
                    const html = `<iframe src="${response.data.download_url}" style="width:100%; height:500px; border:none;"></iframe>`;
                    $("#document-modal-content").html(html);
                    
                    // Obtener título del documento
                    $.ajax({
                        url: workerPortalDocuments.ajax_url,
                        type: "POST",
                        data: {
                            action: "get_document_details",
                            nonce: workerPortalDocuments.nonce,
                            document_id: documentId
                        },
                        success: function(detailsResponse) {
                            if (detailsResponse.success) {
                                $("#document-modal-title").text(detailsResponse.data.title);
                            }
                        }
                    });
                } else {
                    $("#document-modal-content").html(
                        '<div class="worker-portal-error">' + response.data + '</div>'
                    );
                }
            },
            error: function() {
                $("#document-modal-content").html(
                    '<div class="worker-portal-error">' +
                    workerPortalDocuments.i18n.error +
                    '</div>'
                );
            }
        });
    }
    
    <?php if ($is_admin): ?>
    // Ver detalles del documento (admin)
    function viewDocumentDetails(documentId) {
        $.ajax({
            url: workerPortalDocuments.ajax_url,
            type: "POST",
            data: {
                action: "admin_get_document_details",
                nonce: workerPortalDocuments.nonce,
                document_id: documentId
            },
            beforeSend: function() {
                $("#document-details-content").html(
                    '<div class="worker-portal-loading">' +
                    '<div class="worker-portal-spinner"></div>' +
                    '<p>Cargando detalles...</p>' +
                    '</div>'
                );
                $("#document-details-modal").fadeIn();
            },
            success: function(response) {
                if (response.success) {
                    const document = response.data;
                    
                    let html = `
                        <table class="worker-portal-details-table">
                            <tr>
                                <th>ID:</th>
                                <td>${document.id}</td>
                            </tr>
                            <tr>
                                <th>Título:</th>
                                <td>${document.title}</td>
                            </tr>
                            <tr>
                                <th>Categoría:</th>
                                <td>${document.category_name}</td>
                            </tr>
                            <tr>
                                <th>Descripción:</th>
                                <td>${document.description || 'Sin descripción'}</td>
                            </tr>
                            <tr>
                                <th>Usuario:</th>
                                <td>${document.user_name}</td>
                            </tr>
                            <tr>
                                <th>Fecha de subida:</th>
                                <td>${document.upload_date}</td>
                            </tr>
                            <tr>
                                <th>Archivo:</th>
                                <td>
                                    <a href="${document.download_url}" target="_blank" class="worker-portal-button worker-portal-button-small worker-portal-button-outline">
                                        <i class="dashicons dashicons-visibility"></i> Ver documento
                                    </a>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="worker-portal-document-actions" style="margin-top: 20px;">
                            <button type="button" class="worker-portal-button worker-portal-button-danger worker-portal-delete-document" data-document-id="${document.id}">
                                <i class="dashicons dashicons-trash"></i> Eliminar documento
                            </button>
                        </div>
                    `;
                    
                    $("#document-details-content").html(html);
                } else {
                    $("#document-details-content").html('<div class="worker-portal-error">' + response.data + '</div>');
                }
            },
            error: function() {
                $("#document-details-content").html('<div class="worker-portal-error">Ha ocurrido un error al cargar los detalles.</div>');
            }
        });
    }
    
    // Eliminar documento (admin)
    function deleteDocument(documentId) {
        if (!confirm("¿Estás seguro de eliminar este documento? Esta acción no se puede deshacer.")) {
            return;
        }
        
        $.ajax({
            url: workerPortalDocuments.ajax_url,
            type: "POST",
            data: {
                action: "admin_delete_document",
                nonce: workerPortalDocuments.nonce,
                document_id: documentId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    
                    // Cerrar modal si está abierto
                    $("#document-details-modal").fadeOut();
                    
                    // Recargar lista de documentos
                    loadFilteredDocuments(1);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert("Ha ocurrido un error al eliminar el documento.");
            }
        });
    }
    
    // Añadir fila de categoría
    function addCategoryRow() {
        const newRow = `
            <tr class="category-row">
                <td>
                    <input type="text" name="categories[keys][]" required>
                </td>
                <td>
                    <input type="text" name="categories[labels][]" required>
                </td>
                <td>
                    <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline remove-category">
                        <i class="dashicons dashicons-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $("#categories-list").append(newRow);
    }
    
    // Eliminar fila de categoría
    function removeCategoryRow(button) {
        // Si solo queda una categoría, mostrar mensaje
        if ($(".category-row").length <= 1) {
            alert("Debe existir al menos una categoría.");
            return;
        }
        
        button.closest("tr").remove();
    }
    
    // Subir documento
    function uploadDocument(form) {
        var formData = new FormData(form[0]);
        
        // Validar selección de usuarios
        var usersSelect = form.find("#document-users");
        if (usersSelect.val() === null || usersSelect.val().length === 0) {
            alert("Por favor, selecciona al menos un destinatario.");
            return;
        }
        
        $.ajax({
            url: workerPortalDocuments.ajax_url,
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function() {
                form.find("button[type=submit]").prop("disabled", true).html('<i class="dashicons dashicons-update-alt spinning"></i> Subiendo...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    form[0].reset();
                    
                    // Redirigir a la pestaña de todos los documentos
                    window.location.href = "?doc_tab=all";
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert("Ha ocurrido un error al subir el documento. Por favor, inténtalo de nuevo.");
            },
            complete: function() {
                form.find("button[type=submit]").prop("disabled", false).html('<i class="dashicons dashicons-upload"></i> Subir Documento');
            }
        });
    }
    
    // Guardar configuración de documentos
    function saveDocumentSettings(form) {
        var formData = new FormData(form[0]);
        
        $.ajax({
            url: workerPortalDocuments.ajax_url,
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function() {
                form.find("button[type=submit]").prop("disabled", true).html('Guardando...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert("Ha ocurrido un error al guardar la configuración. Por favor, inténtalo de nuevo.");
            },
            complete: function() {
                form.find("button[type=submit]").prop("disabled", false).html('Guardar Cambios');
            }
        });
    }
    <?php endif; ?>
    
    // Inicializar paginación
    function initPagination(currentPage, totalPages) {
        $(document).on("click", ".worker-portal-pagination-number", function(e) {
            e.preventDefault();
            var page = $(this).data("page");
            loadFilteredDocuments(page);
        });
        
        $(document).on("click", ".worker-portal-pagination-prev", function(e) {
            e.preventDefault();
            var page = $(this).data("page");
            loadFilteredDocuments(page);
        });
        
        $(document).on("click", ".worker-portal-pagination-next", function(e) {
            e.preventDefault();
            var page = $(this).data("page");
            loadFilteredDocuments(page);
        });
    }
});
</script>       