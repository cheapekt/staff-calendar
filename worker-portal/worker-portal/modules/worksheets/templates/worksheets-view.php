// Añade este código al inicio de worksheets-view.php para diagnóstico
// Solo temporalmente para depuración

<?php
// Verificar las acciones AJAX registradas
global $wp_filter;
$ajax_actions = array(
    'wp_ajax_get_worksheet_details',
    'wp_ajax_delete_worksheet',
    'wp_ajax_submit_worksheet',
    'wp_ajax_filter_worksheets'
);

$missing_actions = array();
foreach ($ajax_actions as $action) {
    if (!isset($wp_filter[$action]) || empty($wp_filter[$action])) {
        $missing_actions[] = $action;
    }
}

if (!empty($missing_actions)) {
    echo '<div style="background-color:#ffdddd; padding:10px; margin:10px 0; border:1px solid #ff0000;">';
    echo '<strong>Acciones AJAX no registradas:</strong><br>';
    echo implode('<br>', $missing_actions);
    echo '</div>';
}

// Verificar que se están cargando los scripts correctamente
$scripts = wp_scripts();
$worksheets_script_loaded = false;
foreach ($scripts->registered as $handle => $script) {
    if (strpos($handle, 'worksheet') !== false) {
        $worksheets_script_loaded = true;
        break;
    }
}

if (!$worksheets_script_loaded) {
    echo '<div style="background-color:#ffdddd; padding:10px; margin:10px 0; border:1px solid #ff0000;">';
    echo '<strong>Scripts de hojas de trabajo no cargados correctamente.</strong>';
    echo '</div>';
}

// Verificar las capacidades del usuario actual
$current_user = wp_get_current_user();
$user_caps = array(
    'wp_worker_manage_worksheets' => current_user_can('wp_worker_manage_worksheets'),
    'manage_options' => current_user_can('manage_options')
);

echo '<div style="background-color:#e5f9e5; padding:10px; margin:10px 0; border:1px solid #00aa00;">';
echo '<strong>Capacidades del usuario actual:</strong><br>';
foreach ($user_caps as $cap => $has_cap) {
    echo $cap . ': ' . ($has_cap ? 'Sí' : 'No') . '<br>';
}
echo '</div>';
?>


<?php
/**
 * Plantilla para mostrar la sección de hojas de trabajo en el frontend
 *
 * @since      1.0.0
 */



 
// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos del usuario
if (!current_user_can('wp_worker_manage_worksheets')) {
    echo '<div class="worker-portal-error">' . 
        __('No tienes permiso para ver tus hojas de trabajo.', 'worker-portal') . 
        '</div>';
    return;
}
?>

<div class="worker-portal-worksheets">
    <h2><?php _e('Mis Hojas de Trabajo', 'worker-portal'); ?></h2>
    
    <?php if ($atts['show_form'] === 'yes'): ?>
    <div class="worker-portal-worksheets-form-container" style="display: none;">
        <h3><?php _e('Registrar Nueva Hoja de Trabajo', 'worker-portal'); ?></h3>
        
        <form id="worker-portal-worksheet-form" class="worker-portal-form">
            <div class="worker-portal-form-row">
                <div class="worker-portal-form-group">
                    <label for="work-date"><?php _e('Fecha:', 'worker-portal'); ?></label>
                    <input type="date" id="work-date" name="work_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="project-id"><?php _e('Obra:', 'worker-portal'); ?></label>
                    <select id="project-id" name="project_id" required>
                        <option value=""><?php _e('Seleccionar obra', 'worker-portal'); ?></option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo esc_attr($project['id']); ?>">
                                <?php echo esc_html($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="worker-portal-form-row">
                <div class="worker-portal-form-group">
                    <label for="difficulty"><?php _e('Dificultad:', 'worker-portal'); ?></label>
                    <select id="difficulty" name="difficulty">
                        <?php foreach ($difficulty_levels as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($key, 'media'); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="system-type"><?php _e('SISTEMA:', 'worker-portal'); ?></label>
                    <select id="system-type" name="system_type" required>
                        <option value=""><?php _e('Seleccionar sistema', 'worker-portal'); ?></option>
                        <?php foreach ($system_types as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="worker-portal-form-row">
                <div class="worker-portal-form-group">
                    <label for="unit-type"><?php _e('Ud.:', 'worker-portal'); ?></label>
                    <select id="unit-type" name="unit_type" required>
                        <?php foreach ($unit_types as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="quantity"><?php _e('Cantidad:', 'worker-portal'); ?></label>
                    <input type="number" id="quantity" name="quantity" step="0.01" min="0" required>
                </div>
                
                <div class="worker-portal-form-group">
                    <label for="hours"><?php _e('HORAS:', 'worker-portal'); ?></label>
                    <input type="number" id="hours" name="hours" step="0.5" min="0" required>
                </div>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="notes"><?php _e('Notas:', 'worker-portal'); ?></label>
                <textarea id="notes" name="notes" rows="3"></textarea>
            </div>
            
            <div class="worker-portal-form-actions">
                <button type="submit" class="worker-portal-button worker-portal-button-primary">
                    <?php _e('Enviar Hoja de Trabajo', 'worker-portal'); ?>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="worker-portal-worksheets-list-container">
        <h3><?php _e('Hojas de Trabajo Registradas', 'worker-portal'); ?></h3>
        
        <!-- Filtros de hojas de trabajo -->
        <div class="worker-portal-filters">
            <form id="worksheets-filter-form" class="worker-portal-filter-form">
                <div class="worker-portal-filter-row">
                    <div class="worker-portal-filter-group">
                        <label for="filter-project"><?php _e('Obra:', 'worker-portal'); ?></label>
                        <select id="filter-project" name="project_id">
                            <option value=""><?php _e('Todas', 'worker-portal'); ?></option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo esc_attr($project['id']); ?>"><?php echo esc_html($project['name']); ?></option>
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
                        <label for="filter-status"><?php _e('Estado:', 'worker-portal'); ?></label>
                        <select id="filter-status" name="status">
                            <option value=""><?php _e('Todos', 'worker-portal'); ?></option>
                            <option value="pending"><?php _e('Pendiente', 'worker-portal'); ?></option>
                            <option value="validated"><?php _e('Validada', 'worker-portal'); ?></option>
                        </select>
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
        
        <div id="worksheets-list-content">
            <?php if (empty($worksheets)): ?>
                <p class="worker-portal-no-data"><?php _e('No hay hojas de trabajo registradas.', 'worker-portal'); ?></p>
            <?php else: ?>
                <div class="worker-portal-table-responsive">
                    <table class="worker-portal-table worker-portal-worksheets-table">
                        <thead>
                            <tr>
                                <th><?php _e('FECHA', 'worker-portal'); ?></th>
                                <th><?php _e('OBRA', 'worker-portal'); ?></th>
                                <th><?php _e('DIF.', 'worker-portal'); ?></th>
                                <th><?php _e('Ud.', 'worker-portal'); ?></th>
                                <th><?php _e('SISTEMA', 'worker-portal'); ?></th>
                                <th><?php _e('HORAS', 'worker-portal'); ?></th>
                                <th><?php _e('VALIDACIÓN', 'worker-portal'); ?></th>
                                <th><?php _e('ACCIONES', 'worker-portal'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($worksheets as $worksheet): ?>
                                <tr data-worksheet-id="<?php echo esc_attr($worksheet['id']); ?>">
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($worksheet['work_date'])); ?></td>
                                    <td><?php echo esc_html($worksheet['project_name']); ?></td>
                                    <td>
                                        <?php 
                                        echo isset($difficulty_levels[$worksheet['difficulty']]) 
                                            ? esc_html($difficulty_levels[$worksheet['difficulty']]) 
                                            : esc_html(ucfirst($worksheet['difficulty'])); 
                                        ?>
                                    </td>
                                    <td><?php echo esc_html($worksheet['quantity']); ?> <?php echo $worksheet['unit_type'] == 'm2' ? 'm²' : 'H'; ?></td>
                                    <td>
                                        <?php 
                                        echo isset($system_types[$worksheet['system_type']]) 
                                            ? esc_html($system_types[$worksheet['system_type']]) 
                                            : esc_html($worksheet['system_type']); 
                                        ?>
                                    </td>
                                    <td><?php echo esc_html($worksheet['hours']); ?> h</td>
                                    <td>
                                        <?php
                                        switch ($worksheet['status']) {
                                            case 'pending':
                                                echo '<span class="worker-portal-badge worker-portal-badge-warning">' . __('PENDIENTE', 'worker-portal') . '</span>';
                                                break;
                                            case 'validated':
                                                echo '<span class="worker-portal-badge worker-portal-badge-success">' . __('VALIDADA', 'worker-portal') . '</span>';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($worksheet['status'] === 'pending'): ?>
                                            <a href="javascript:void(0);" 
                                            onclick="deleteWorksheet(<?php echo esc_attr($worksheet['id']); ?>)" 
                                            class="worker-portal-button worker-portal-button-small worker-portal-button-outline">
                                                <i class="dashicons dashicons-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="javascript:void(0);" 
                                        onclick="viewWorksheetDetails(<?php echo esc_attr($worksheet['id']); ?>)"
                                        class="worker-portal-button worker-portal-button-small worker-portal-button-outline">
                                            <i class="dashicons dashicons-visibility"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <script type="text/javascript">
function viewWorksheetDetails(worksheetId) {
    console.log("Viendo detalles de la hoja ID:", worksheetId);
    
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'get_worksheet_details',
            worksheet_id: worksheetId,
            nonce: '<?php echo wp_create_nonce('worker_portal_worksheets_nonce'); ?>'
        },
        beforeSend: function() {
            jQuery("#worksheet-details-content").html(
                '<div class="worker-portal-loading"><div class="worker-portal-spinner"></div><p>Cargando detalles...</p></div>'
            );
            jQuery("#worksheet-details-modal").fadeIn();
        },
        success: function(response) {
            if (response.success) {
                jQuery("#worksheet-details-content").html(response.data);
            } else {
                jQuery("#worksheet-details-content").html(
                    '<div class="worker-portal-error">' + response.data + '</div>'
                );
            }
        },
        error: function(xhr, status, error) {
            console.error("Error AJAX:", status, error);
            jQuery("#worksheet-details-content").html(
                '<div class="worker-portal-error">Error al cargar los detalles. Por favor, inténtalo de nuevo.</div>'
            );
        }
    });
}

function deleteWorksheet(worksheetId) {
    console.log("Eliminando hoja ID:", worksheetId);
    
    if (confirm("¿Estás seguro que deseas eliminar esta hoja de trabajo?")) {
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'delete_worksheet',
                worksheet_id: worksheetId,
                nonce: '<?php echo wp_create_nonce('worker_portal_worksheets_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert("Hoja de trabajo eliminada correctamente");
                    jQuery('tr[data-worksheet-id="' + worksheetId + '"]').fadeOut(function() {
                        jQuery(this).remove();
                    });
                } else {
                    alert(response.data || "Error al eliminar la hoja de trabajo");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error AJAX:", status, error);
                alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
            }
        });
    }
}

// Para el botón de nueva hoja de trabajo
jQuery(document).ready(function($) {
    $("#new-worksheet-button").on("click", function() {
        $(".worker-portal-worksheets-form-container").slideToggle();
        $(this).toggleClass("active");

        if ($(this).hasClass("active")) {
            $(this).html('<i class="dashicons dashicons-minus"></i> Cancelar');
        } else {
            $(this).html(
                '<i class="dashicons dashicons-plus-alt"></i> NUEVA HOJA DE TRABAJO'
            );
        }
    });
    
    // Cerrar modal
    $(".worker-portal-modal-close").on("click", function() {
        $("#worksheet-details-modal").fadeOut();
    });
});
</script>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="worker-portal-worksheets-actions">
            <button type="button" id="new-worksheet-button" class="worker-portal-button worker-portal-button-primary">
                <i class="dashicons dashicons-plus-alt"></i> <?php _e('NUEVA HOJA DE TRABAJO', 'worker-portal'); ?>
            </button>
            
            <button type="button" id="export-worksheets-button" class="worker-portal-button worker-portal-button-secondary">
                <i class="dashicons dashicons-download"></i> <?php _e('Exportar a Excel', 'worker-portal'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de la hoja de trabajo -->
<div id="worksheet-details-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Detalles de la Hoja de Trabajo', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <div id="worksheet-details-content">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Script de inicialización para asegurar que los listeners estén configurados -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    console.log("Inicializando eventos de hojas de trabajo en carga directa");
    
    // Asegurarnos que los botones de la tabla tengan sus manejadores de eventos
    $(".worker-portal-view-worksheet").on("click", function() {
        const worksheetId = $(this).data("worksheet-id");
        console.log("Click en botón de ver worksheet ID:", worksheetId);
        
        $.ajax({
            url: "<?php echo admin_url('admin-ajax.php'); ?>",
            type: "POST",
            data: {
                action: "get_worksheet_details",
                nonce: "<?php echo wp_create_nonce('worker_portal_worksheets_nonce'); ?>",
                worksheet_id: worksheetId,
            },
            beforeSend: function() {
                $("#worksheet-details-content").html(
                    '<div class="worker-portal-loading"><div class="worker-portal-spinner"></div><p>Cargando detalles...</p></div>'
                );
                $("#worksheet-details-modal").fadeIn();
            },
            success: function(response) {
                if (response.success) {
                    $("#worksheet-details-content").html(response.data);
                } else {
                    $("#worksheet-details-content").html(
                        '<div class="worker-portal-error">' + response.data + "</div>"
                    );
                }
            },
            error: function() {
                $("#worksheet-details-content").html(
                    '<div class="worker-portal-error">Error al cargar los detalles. Por favor, inténtalo de nuevo.</div>'
                );
            }
        });
    });
    
    $(".worker-portal-delete-worksheet").on("click", function() {
        const worksheetId = $(this).data("worksheet-id");
        console.log("Click en botón de eliminar worksheet ID:", worksheetId);
        
        if (confirm("¿Estás seguro de que deseas eliminar esta hoja de trabajo?")) {
            $.ajax({
                url: "<?php echo admin_url('admin-ajax.php'); ?>",
                type: "POST",
                data: {
                    action: "delete_worksheet",
                    nonce: "<?php echo wp_create_nonce('worker_portal_worksheets_nonce'); ?>",
                    worksheet_id: worksheetId,
                },
                success: function(response) {
                    if (response.success) {
                        $('tr[data-worksheet-id="' + worksheetId + '"]').fadeOut(function() {
                            $(this).remove();
                            if ($(".worker-portal-worksheets-table tbody tr").length === 0) {
                                $(".worker-portal-table-responsive").html(
                                    '<p class="worker-portal-no-data">No hay hojas de trabajo registradas.</p>'
                                );
                            }
                        });
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
                }
            });
        }
    });
    
    // Botón de nueva hoja de trabajo
    $("#new-worksheet-button").on("click", function() {
        console.log("Click en botón de nueva hoja de trabajo");
        $(".worker-portal-worksheets-form-container").slideToggle();
        $(this).toggleClass("active");

        if ($(this).hasClass("active")) {
            $(this).html('<i class="dashicons dashicons-minus"></i> Cancelar');
        } else {
            $(this).html(
                '<i class="dashicons dashicons-plus-alt"></i> NUEVA HOJA DE TRABAJO'
            );
        }
    });
    
    // Cerrar modal
    $(".worker-portal-modal-close").on("click", function() {
        $(this).closest(".worker-portal-modal").fadeOut();
    });
    
    // Cerrar modal haciendo clic fuera
    $(window).on("click", function(e) {
        if ($(e.target).hasClass("worker-portal-modal")) {
            $(".worker-portal-modal").fadeOut();
        }
    });
    
    // Manejar envío del formulario de hoja de trabajo
    $("#worker-portal-worksheet-form").on("submit", function(e) {
        e.preventDefault();
        console.log("Enviando formulario de hoja de trabajo");
        
        const formData = new FormData(this);
        formData.append("action", "submit_worksheet");
        formData.append("nonce", "<?php echo wp_create_nonce('worker_portal_worksheets_nonce'); ?>");
        
        // Deshabilitar el botón de envío
        const submitButton = $(this).find("button[type=submit]");
        submitButton.prop("disabled", true).html('<i class="dashicons dashicons-update-alt spinning"></i> Enviando...');
        
        $.ajax({
            url: "<?php echo admin_url('admin-ajax.php'); ?>",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.reload(); // Recargar para mostrar la nueva hoja
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert("Ha ocurrido un error al enviar el formulario. Por favor, inténtalo de nuevo.");
            },
            complete: function() {
                submitButton.prop("disabled", false).html('Enviar Hoja de Trabajo');
            }
        });
    });
    
    // Exportar a Excel
    $("#export-worksheets-button").on("click", function() {
        console.log("Click en botón de exportar");
        
        const formData = new FormData($("#worksheets-filter-form")[0]);
        formData.append("action", "export_worksheets");
        formData.append("nonce", "<?php echo wp_create_nonce('worker_portal_worksheets_nonce'); ?>");
        
        // Deshabilitar botón
        const button = $(this);
        button.prop("disabled", true).html('<i class="dashicons dashicons-update-alt spinning"></i> Exportando...');
        
        $.ajax({
            url: "<?php echo admin_url('admin-ajax.php'); ?>",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Crear enlace para descarga
                    const link = document.createElement("a");
                    link.href = response.data.file_url;
                    link.download = response.data.filename || "hojas-trabajo.csv";
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert(response.data || "Error al exportar las hojas de trabajo");
                }
            },
            error: function() {
                alert("Ha ocurrido un error durante la exportación. Por favor, inténtalo de nuevo.");
            },
            complete: function() {
                button.prop("disabled", false).html('<i class="dashicons dashicons-download"></i> Exportar a Excel');
            }
        });
    });
    
    // Filtrar hojas de trabajo
    $("#worksheets-filter-form").on("submit", function(e) {
        e.preventDefault();
        console.log("Aplicando filtros de hojas de trabajo");
        
        // Mostrar indicador de carga
        $("#worksheets-list-content").html(
            '<div class="worker-portal-loading">' +
            '<div class="worker-portal-spinner"></div>' +
            "<p>Cargando hojas de trabajo...</p>" +
            "</div>"
        );
        
        const formData = new FormData(this);
        formData.append("action", "filter_worksheets");
        formData.append("nonce", "<?php echo wp_create_nonce('worker_portal_worksheets_nonce'); ?>");
        formData.append("page", 1);
        
        $.ajax({
            url: "<?php echo admin_url('admin-ajax.php'); ?>",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $("#worksheets-list-content").html(response.data);
                    
                    // Reinicializar eventos para los botones
                    $(".worker-portal-view-worksheet").on("click", function() {
                        const worksheetId = $(this).data("worksheet-id");
                        // Código para ver detalles (igual que arriba)
                        $.ajax({
                            url: "<?php echo admin_url('admin-ajax.php'); ?>",
                            type: "POST",
                            data: {
                                action: "get_worksheet_details",
                                nonce: "<?php echo wp_create_nonce('worker_portal_worksheets_nonce'); ?>",
                                worksheet_id: worksheetId,
                            },
                            beforeSend: function() {
                                $("#worksheet-details-content").html(
                                    '<div class="worker-portal-loading"><div class="worker-portal-spinner"></div><p>Cargando detalles...</p></div>'
                                );
                                $("#worksheet-details-modal").fadeIn();
                            },
                            success: function(response) {
                                if (response.success) {
                                    $("#worksheet-details-content").html(response.data);
                                } else {
                                    $("#worksheet-details-content").html(
                                        '<div class="worker-portal-error">' + response.data + "</div>"
                                    );
                                }
                            },
                            error: function() {
                                $("#worksheet-details-content").html(
                                    '<div class="worker-portal-error">Error al cargar los detalles. Por favor, inténtalo de nuevo.</div>'
                                );
                            }
                        });
                    });
                    
                    $(".worker-portal-delete-worksheet").on("click", function() {
                        const worksheetId = $(this).data("worksheet-id");
                        // Código para eliminar (igual que arriba)
                        if (confirm("¿Estás seguro de que deseas eliminar esta hoja de trabajo?")) {
                            $.ajax({
                                url: "<?php echo admin_url('admin-ajax.php'); ?>",
                                type: "POST",
                                data: {
                                    action: "delete_worksheet",
                                    nonce: "<?php echo wp_create_nonce('worker_portal_worksheets_nonce'); ?>",
                                    worksheet_id: worksheetId,
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $('tr[data-worksheet-id="' + worksheetId + '"]').fadeOut(function() {
                                            $(this).remove();
                                            if ($(".worker-portal-worksheets-table tbody tr").length === 0) {
                                                $(".worker-portal-table-responsive").html(
                                                    '<p class="worker-portal-no-data">No hay hojas de trabajo registradas.</p>'
                                                );
                                            }
                                        });
                                    } else {
                                        alert(response.data);
                                    }
                                },
                                error: function() {
                                    alert("Ha ocurrido un error. Por favor, inténtalo de nuevo.");
                                }
                            });
                        }
                    });
                } else {
                    $("#worksheets-list-content").html(
                        '<p class="worker-portal-no-data">' + (response.data || "Error al filtrar hojas de trabajo") + "</p>"
                    );
                }
            },
            error: function() {
                $("#worksheets-list-content").html(
                    '<p class="worker-portal-no-data">Error al cargar las hojas de trabajo. Por favor, inténtalo de nuevo.</p>'
                );
            }
        });
    });
    
    // Limpiar filtros
    $("#clear-filters").on("click", function() {
        console.log("Limpiando filtros");
        $("#worksheets-filter-form")[0].reset();
        $("#worksheets-filter-form").submit(); // Enviar formulario para recargar datos
    });
});
</script>