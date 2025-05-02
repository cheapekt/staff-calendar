<?php
/**
 * Plantilla para la página de administración de documentos
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Cargar dependencias de base de datos
require_once WORKER_PORTAL_PATH . 'includes/class-database.php';
$database = Worker_Portal_Database::get_instance();

// Obtener categorías de documentos
$categories = get_option('worker_portal_document_categories', array(
    'payroll' => __('Nóminas', 'worker-portal'),
    'contract' => __('Contratos', 'worker-portal'),
    'communication' => __('Comunicaciones', 'worker-portal'),
    'other' => __('Otros', 'worker-portal')
));

// Pestañas de navegación
$tabs = array(
    'all' => __('Todos los Documentos', 'worker-portal'),
    'upload' => __('Subir Documento', 'worker-portal'),
    'settings' => __('Configuración', 'worker-portal')
);

$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'all';
if (!array_key_exists($current_tab, $tabs)) {
    $current_tab = 'all';
}

// Obtener usuarios para el selector
$users = get_users(array(
    'role__not_in' => array('administrator'),
    'orderby' => 'display_name'
));
?>

<div class="wrap worker-portal-admin">
    <h1><?php _e('Gestión de Documentos', 'worker-portal'); ?></h1>
    
    <!-- Pestañas de navegación -->
    <nav class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($tabs as $tab_key => $tab_label): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=worker-portal-documents&tab=' . $tab_key)); ?>" class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_label); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="worker-portal-admin-content">
        <?php if ($current_tab === 'all'): ?>
            <!-- Todos los documentos -->
            <div class="worker-portal-documents-list">
                <h2><?php _e('Documentos Disponibles', 'worker-portal'); ?></h2>
                
                <!-- Filtros de documentos -->
                <div class="worker-portal-admin-filters">
                    <form id="admin-documents-filter-form" class="worker-portal-admin-filter-form">
                        <div class="worker-portal-admin-filter-row">
                            <div class="worker-portal-admin-filter-group">
                                <label for="filter-category"><?php _e('Categoría:', 'worker-portal'); ?></label>
                                <select id="filter-category" name="category">
                                    <option value=""><?php _e('Todas', 'worker-portal'); ?></option>
                                    <?php foreach ($categories as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="worker-portal-admin-filter-group">
                                <label for="filter-user"><?php _e('Usuario:', 'worker-portal'); ?></label>
                                <select id="filter-user" name="user_id">
                                    <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="worker-portal-admin-filter-actions">
                                <button type="submit" class="button button-secondary">
                                    <span class="dashicons dashicons-search"></span> <?php _e('Filtrar', 'worker-portal'); ?>
                                </button>
                                <button type="button" id="clear-documents-filters" class="button button-link">
                                    <?php _e('Limpiar filtros', 'worker-portal'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Lista de documentos -->
                <div id="documents-list-container" data-nonce="<?php echo wp_create_nonce('worker_portal_admin_nonce'); ?>">
                    <div class="worker-portal-loading">
                        <div class="worker-portal-spinner"></div>
                        <p><?php _e('Cargando documentos...', 'worker-portal'); ?></p>
                    </div>
                </div>
            </div>
            
        <?php elseif ($current_tab === 'upload'): ?>
            <!-- Subir documento -->
            <div class="worker-portal-upload-document">
                <h2><?php _e('Subir Nuevo Documento', 'worker-portal'); ?></h2>
                
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
                        <select id="document-users" name="users[]" multiple size="8" required>
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
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-upload"></span> <?php _e('Subir Documento', 'worker-portal'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
        <?php elseif ($current_tab === 'settings'): ?>
            <!-- Configuración de documentos -->
            <div class="worker-portal-documents-settings">
                <h2><?php _e('Configuración de Documentos', 'worker-portal'); ?></h2>
                
                <form method="post" action="options.php" class="worker-portal-form">
                    <?php
                    settings_fields('worker_portal_documents');
                    do_settings_sections('worker_portal_documents');
                    ?>
                    
                    <h3><?php _e('Categorías de Documentos', 'worker-portal'); ?></h3>
                    <p class="description"><?php _e('Configura las categorías disponibles para clasificar documentos.', 'worker-portal'); ?></p>
                    
                    <table class="form-table categories-table" role="presentation">
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
                                        <input type="text" name="worker_portal_document_categories[keys][]" value="<?php echo esc_attr($key); ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" name="worker_portal_document_categories[labels][]" value="<?php echo esc_attr($label); ?>" required>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small remove-category">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">
                                    <button type="button" id="add-category" class="button button-secondary">
                                        <span class="dashicons dashicons-plus"></span> <?php _e('Añadir categoría', 'worker-portal'); ?>
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <h3><?php _e('Notificaciones', 'worker-portal'); ?></h3>
                    
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php _e('Email de notificación:', 'worker-portal'); ?></th>
                            <td>
                                <input type="email" name="worker_portal_document_notification_email" value="<?php echo esc_attr(get_option('worker_portal_document_notification_email', get_option('admin_email'))); ?>" class="regular-text">
                                <p class="description"><?php _e('Email al que se enviarán las notificaciones cuando se suba un nuevo documento.', 'worker-portal'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para detalles de documento -->
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

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Cargar documentos al inicio
    loadDocuments();
    
    // Filtros de documentos
    $("#admin-documents-filter-form").on("submit", function(e) {
        e.preventDefault();
        loadDocuments();
    });
    
    // Limpiar filtros
    $("#clear-documents-filters").on("click", function() {
        $("#admin-documents-filter-form")[0].reset();
        loadDocuments();
    });
    
    // Subir documento
    $("#upload-document-form").on("submit", function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'admin_upload_document');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(this).find('button[type=submit]').prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> <?php echo esc_js(__('Subiendo...', 'worker-portal')); ?>');
            }.bind(this),
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    this.reset();
                } else {
                    alert(response.data);
                }
            }.bind(this),
            error: function() {
                alert('<?php echo esc_js(__('Ha ocurrido un error al subir el documento. Por favor, inténtalo de nuevo.', 'worker-portal')); ?>');
            },
            complete: function() {
                $(this).find('button[type=submit]').prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> <?php echo esc_js(__('Subir Documento', 'worker-portal')); ?>');
            }.bind(this)
        });
    });
    
    // Gestión de categorías
    $("#add-category").on("click", function() {
        const newRow = `
            <tr class="category-row">
                <td>
                    <input type="text" name="worker_portal_document_categories[keys][]" required>
                </td>
                <td>
                    <input type="text" name="worker_portal_document_categories[labels][]" required>
                </td>
                <td>
                    <button type="button" class="button button-small remove-category">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>
        `;
        
        $("#categories-list").append(newRow);
    });
    
    $(document).on("click", ".remove-category", function() {
        // Si solo queda una categoría, mostrar mensaje
        if ($(".category-row").length <= 1) {
            alert("<?php echo esc_js(__('Debe existir al menos una categoría.', 'worker-portal')); ?>");
            return;
        }
        
        $(this).closest("tr").remove();
    });
    
    // Ver detalles de documento
    $(document).on("click", ".view-document-details", function() {
        const documentId = $(this).data("document-id");
        
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "admin_get_document_details",
                nonce: $("#documents-list-container").data("nonce"),
                document_id: documentId
            },
            beforeSend: function() {
                $("#document-details-content").html('<div class="worker-portal-loading"><div class="worker-portal-spinner"></div><p><?php echo esc_js(__('Cargando detalles...', 'worker-portal')); ?></p></div>');
                $("#document-details-modal").fadeIn();
            },
            success: function(response) {
                if (response.success) {
                    const document = response.data;
                    
                    let html = `
                        <table class="worker-portal-details-table">
                            <tr>
                                <th><?php echo esc_js(__('ID:', 'worker-portal')); ?></th>
                                <td>${document.id}</td>
                            </tr>
                            <tr>
                                <th><?php echo esc_js(__('Título:', 'worker-portal')); ?></th>
                                <td>${document.title}</td>
                            </tr>
                            <tr>
                                <th><?php echo esc_js(__('Categoría:', 'worker-portal')); ?></th>
                                <td>${document.category_name}</td>
                            </tr>
                            <tr>
                                <th><?php echo esc_js(__('Descripción:', 'worker-portal')); ?></th>
                                <td>${document.description || '<?php echo esc_js(__('Sin descripción', 'worker-portal')); ?>'}</td>
                            </tr>
                            <tr>
                                <th><?php echo esc_js(__('Usuario:', 'worker-portal')); ?></th>
                                <td>${document.user_name}</td>
                            </tr>
                            <tr>
                                <th><?php echo esc_js(__('Fecha de subida:', 'worker-portal')); ?></th>
                                <td>${document.upload_date}</td>
                            </tr>
                            <tr>
                                <th><?php echo esc_js(__('Archivo:', 'worker-portal')); ?></th>
                                <td>
                                    <a href="${document.download_url}" target="_blank" class="button button-small">
                                        <span class="dashicons dashicons-visibility"></span> <?php echo esc_js(__('Ver documento', 'worker-portal')); ?>
                                    </a>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="worker-portal-document-actions">
                            <button type="button" class="button button-link-delete delete-document" data-document-id="${document.id}">
                                <span class="dashicons dashicons-trash"></span> <?php echo esc_js(__('Eliminar documento', 'worker-portal')); ?>
                            </button>
                        </div>
                    `;
                    
                    $("#document-details-content").html(html);
                } else {
                    $("#document-details-content").html('<div class="worker-portal-error">' + response.data + '</div>');
                }
            },
            error: function() {
                $("#document-details-content").html('<div class="worker-portal-error"><?php echo esc_js(__('Ha ocurrido un error al cargar los detalles.', 'worker-portal')); ?></div>');
            }
        });
    });
    
    // Eliminar documento
    $(document).on("click", ".delete-document", function() {
        const documentId = $(this).data("document-id");
        
        if (confirm("<?php echo esc_js(__('¿Estás seguro de eliminar este documento? Esta acción no se puede deshacer.', 'worker-portal')); ?>")) {
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "admin_delete_document",
                    nonce: $("#documents-list-container").data("nonce"),
                    document_id: documentId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        
                        // Cerrar modal si está abierto
                        $("#document-details-modal").fadeOut();
                        
                        // Recargar lista de documentos
                        loadDocuments();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert("<?php echo esc_js(__('Ha ocurrido un error al eliminar el documento.', 'worker-portal')); ?>");
                }
            });
        }
    });
    
    // Cerrar modales
    $(".worker-portal-modal-close").on("click", function() {
        $(this).closest(".worker-portal-modal").fadeOut();
    });
    
    $(window).on("click", function(e) {
        if ($(e.target).hasClass("worker-portal-modal")) {
            $(".worker-portal-modal").fadeOut();
        }
    });
    
    // Función para cargar documentos
    function loadDocuments() {
        const formData = new FormData($("#admin-documents-filter-form")[0]);
        formData.append('action', 'admin_load_documents');
        formData.append('nonce', $("#documents-list-container").data("nonce"));
        
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $("#documents-list-container").html('<div class="worker-portal-loading"><div class="worker-portal-spinner"></div><p><?php echo esc_js(__('Cargando documentos...', 'worker-portal')); ?></p></div>');
            },
            success: function(response) {
                if (response.success) {
                    $("#documents-list-container").html(response.data.html);
                } else {
                    $("#documents-list-container").html('<div class="worker-portal-error">' + response.data + '</div>');
                }
            },
            error: function() {
                $("#documents-list-container").html('<div class="worker-portal-error"><?php echo esc_js(__('Ha ocurrido un error al cargar los documentos.', 'worker-portal')); ?></div>');
            }
        });
    }
});
</script>