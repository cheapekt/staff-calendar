<?php
/**
 * Plantilla para mostrar la sección de gastos en el frontend
 *
 * @since      1.0.0
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="worker-portal-expenses">
    <h2><?php _e('Mis Gastos', 'worker-portal'); ?></h2>
    
    <?php if ($atts['show_form'] === 'yes'): ?>
    <div class="worker-portal-expenses-form-container">
        <h3><?php _e('Comunicar Nuevo Gasto', 'worker-portal'); ?></h3>
        
        <form id="worker-portal-expense-form" class="worker-portal-form">
            <div class="worker-portal-form-group">
                <label for="report-date"><?php _e('Fecha de comunicación:', 'worker-portal'); ?></label>
                <input type="text" id="report-date" value="<?php echo date_i18n(get_option('date_format')); ?>" readonly>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="expense-date"><?php _e('Fecha del gasto:', 'worker-portal'); ?></label>
                <input type="date" id="expense-date" name="expense_date" required>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="expense-type"><?php _e('Tipo de gasto:', 'worker-portal'); ?></label>
                <select id="expense-type" name="expense_type" required>
                    <option value=""><?php _e('Selecciona un tipo', 'worker-portal'); ?></option>
                    <?php foreach ($expense_types as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="expense-description"><?php _e('Motivo del gasto:', 'worker-portal'); ?></label>
                <textarea id="expense-description" name="description" rows="3" required></textarea>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="expense-amount"><?php _e('Importe del gasto:', 'worker-portal'); ?></label>
                <input type="number" id="expense-amount" name="amount" min="0.01" step="0.01" required>
                <span class="worker-portal-input-suffix">€</span>
            </div>
            
            <div class="worker-portal-form-group">
                <label for="expense-receipt"><?php _e('¿Aporta justificante?', 'worker-portal'); ?></label>
                <div class="worker-portal-checkbox-group">
                    <input type="checkbox" id="expense-has-receipt" name="has_receipt" value="yes">
                    <label for="expense-has-receipt"><?php _e('Sí, tengo un justificante', 'worker-portal'); ?></label>
                </div>
            </div>
            
            <div id="receipt-upload-container" class="worker-portal-form-group" style="display: none;">
                <label for="expense-receipt"><?php _e('Adjuntar justificante:', 'worker-portal'); ?></label>
                <div class="worker-portal-file-upload">
                    <input type="file" id="expense-receipt" name="receipt" accept="image/*,.pdf">
                    <button type="button" id="take-photo" class="worker-portal-button worker-portal-button-secondary">
                        <i class="dashicons dashicons-camera"></i> <?php _e('Tomar foto', 'worker-portal'); ?>
                    </button>
                </div>
                <div id="receipt-preview" class="worker-portal-receipt-preview"></div>
            </div>
            
            <div class="worker-portal-form-actions">
                <button type="submit" class="worker-portal-button worker-portal-button-primary">
                    <?php _e('Enviar Gasto', 'worker-portal'); ?>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="worker-portal-expenses-list-container">
        <h3><?php _e('Gastos Comunicados', 'worker-portal'); ?></h3>
        
        <?php if (empty($expenses)): ?>
            <p class="worker-portal-no-data"><?php _e('No hay gastos registrados.', 'worker-portal'); ?></p>
        <?php else: ?>
            <div class="worker-portal-table-responsive">
                <table class="worker-portal-table worker-portal-expenses-table">
                    <thead>
                        <tr>
                            <th><?php _e('FECHA', 'worker-portal'); ?></th>
                            <th><?php _e('TIPO', 'worker-portal'); ?></th>
                            <th><?php _e('GASTO (motivo del gasto)', 'worker-portal'); ?></th>
                            <th><?php _e('Fecha del gasto', 'worker-portal'); ?></th>
                            <th><?php _e('Km / Horas / Euros', 'worker-portal'); ?></th>
                            <th><?php _e('TICKET', 'worker-portal'); ?></th>
                            <th><?php _e('VALIDACIÓN', 'worker-portal'); ?></th>
                            <th><?php _e('Acciones', 'worker-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr data-expense-id="<?php echo esc_attr($expense['id']); ?>">
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($expense['report_date'])); ?></td>
                                <td>
                                    <?php 
                                    echo isset($expense_types[$expense['expense_type']]) 
                                        ? esc_html($expense_types[$expense['expense_type']]) 
                                        : esc_html($expense['expense_type']); 
                                    ?>
                                </td>
                                <td><?php echo esc_html($expense['description']); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($expense['expense_date'])); ?></td>
                                <td>
                                    <?php 
                                    // Mostrar unidad según tipo de gasto
                                    switch ($expense['expense_type']) {
                                        case 'km':
                                            echo esc_html($expense['amount']) . ' Km';
                                            break;
                                        case 'hours':
                                            echo esc_html($expense['amount']) . ' Horas';
                                            break;
                                        default:
                                            echo esc_html(number_format($expense['amount'], 2, ',', '.')) . ' Euros';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($expense['has_receipt']): ?>
                                        <span class="worker-portal-badge worker-portal-badge-success"><?php _e('SI', 'worker-portal'); ?></span>
                                        <?php if (!empty($expense['receipt_path'])): ?>
                                            <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/' . $expense['receipt_path']); ?>" target="_blank" class="worker-portal-view-receipt">
                                                <i class="dashicons dashicons-visibility"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="worker-portal-badge worker-portal-badge-secondary"><?php _e('NO', 'worker-portal'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    switch ($expense['status']) {
                                        case 'pending':
                                            echo '<span class="worker-portal-badge worker-portal-badge-warning">' . __('PENDIENTE', 'worker-portal') . '</span>';
                                            break;
                                        case 'approved':
                                            echo '<span class="worker-portal-badge worker-portal-badge-success">' . __('APROBADO', 'worker-portal') . '</span>';
                                            break;
                                        case 'rejected':
                                            echo '<span class="worker-portal-badge worker-portal-badge-danger">' . __('DENEGADO', 'worker-portal') . '</span>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($expense['status'] === 'pending'): ?>
                                        <button type="button" class="worker-portal-button worker-portal-button-small worker-portal-button-outline worker-portal-delete-expense" data-expense-id="<?php echo esc_attr($expense['id']); ?>">
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
        
        <div class="worker-portal-expenses-actions">
            <button type="button" id="new-expense-button" class="worker-portal-button worker-portal-button-primary">
                <i class="dashicons dashicons-plus-alt"></i> <?php _e('NUEVO GASTO', 'worker-portal'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Cámara para tomar fotos en dispositivos móviles -->
<div id="camera-modal" class="worker-portal-modal">
    <div class="worker-portal-modal-content">
        <div class="worker-portal-modal-header">
            <h3><?php _e('Tomar foto del ticket', 'worker-portal'); ?></h3>
            <button type="button" class="worker-portal-modal-close">&times;</button>
        </div>
        <div class="worker-portal-modal-body">
            <video id="camera-preview" autoplay playsinline></video>
            <canvas id="camera-capture" style="display: none;"></canvas>
            <div class="worker-portal-camera-controls">
                <button type="button" id="capture-photo" class="worker-portal-button worker-portal-button-primary">
                    <i class="dashicons dashicons-camera"></i> <?php _e('Capturar', 'worker-portal'); ?>
                </button>
                <button type="button" id="retry-photo" class="worker-portal-button worker-portal-button-secondary" style="display: none;">
                    <i class="dashicons dashicons-image-rotate"></i> <?php _e('Reintentar', 'worker-portal'); ?>
                </button>
                <button type="button" id="accept-photo" class="worker-portal-button worker-portal-button-success" style="display: none;">
                    <i class="dashicons dashicons-yes"></i> <?php _e('Aceptar', 'worker-portal'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Mostrar/ocultar el formulario de gastos
    $("#new-expense-button").on("click", function() {
        $(".worker-portal-expenses-form-container").slideToggle();
        $(this).toggleClass("active");
        
        if ($(this).hasClass("active")) {
            $(this).html('<i class="dashicons dashicons-minus"></i> <?php _e('CANCELAR', 'worker-portal'); ?>');
        } else {
            $(this).html('<i class="dashicons dashicons-plus-alt"></i> <?php _e('NUEVO GASTO', 'worker-portal'); ?>');
        }
    });
    
    // Mostrar/ocultar el campo de subida de recibo
    $("#expense-has-receipt").on("change", function() {
        if ($(this).is(":checked")) {
            $("#receipt-upload-container").slideDown();
        } else {
            $("#receipt-upload-container").slideUp();
            $("#expense-receipt").val("");
            $("#receipt-preview").empty();
        }
    });
    
    // Previsualizar el recibo seleccionado
    $("#expense-receipt").on("change", function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const preview = $("#receipt-preview");
                preview.empty();
                
                if (file.type.match('image.*')) {
                    $("<img>", {
                        src: e.target.result,
                        class: "worker-portal-receipt-image"
                    }).appendTo(preview);
                } else {
                    $("<div>", {
                        class: "worker-portal-receipt-file",
                        text: file.name
                    }).appendTo(preview);
                }
            };
            
            reader.readAsDataURL(file);
        }
    });
    
    // Tomar foto (abrir modal de cámara)
    $("#take-photo").on("click", function() {
        $("#camera-modal").fadeIn();
        startCamera();
    });
    
    // Cerrar modal de cámara
    $(".worker-portal-modal-close").on("click", function() {
        $("#camera-modal").fadeOut();
        stopCamera();
    });
    
    // Variables para la cámara
    let stream = null;
    
    // Iniciar la cámara
    function startCamera() {
        const video = document.getElementById("camera-preview");
        
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
                .then(function(s) {
                    stream = s;
                    video.srcObject = stream;
                    $("#capture-photo").show();
                    $("#retry-photo, #accept-photo").hide();
                })
                .catch(function(error) {
                    console.error("Error al acceder a la cámara:", error);
                    alert("<?php _e('No se pudo acceder a la cámara.', 'worker-portal'); ?>");
                    $("#camera-modal").fadeOut();
                });
        } else {
            alert("<?php _e('Tu dispositivo no soporta el acceso a la cámara.', 'worker-portal'); ?>");
            $("#camera-modal").fadeOut();
        }
    }
    
    // Detener la cámara
    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
    }
    
    // Capturar foto
    $("#capture-photo").on("click", function() {
        const video = document.getElementById("camera-preview");
        const canvas = document.getElementById("camera-capture");
        const context = canvas.getContext("2d");
        
        // Establecer las dimensiones del canvas iguales al video
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        // Dibujar el fotograma actual del video en el canvas
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Mostrar botones de aceptar/reintentar
        $("#capture-photo").hide();
        $("#retry-photo, #accept-photo").show();
    });
    
    // Reintentar captura
    $("#retry-photo").on("click", function() {
        $("#capture-photo").show();
        $("#retry-photo, #accept-photo").hide();
    });
    
    // Aceptar foto
    $("#accept-photo").on("click", function() {
        const canvas = document.getElementById("camera-capture");
        
        // Convertir el canvas a un blob
        canvas.toBlob(function(blob) {
            // Crear un archivo a partir del blob
            const file = new File([blob], "receipt-" + new Date().getTime() + ".jpg", { type: "image/jpeg" });
            
            // Crear un objeto de transferencia de archivos
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            
            // Asignar el archivo al input de archivo
            document.getElementById("expense-receipt").files = dataTransfer.files;
            
            // Disparar el evento change para actualizar la previsualización
            $("#expense-receipt").trigger("change");
            
            // Cerrar el modal
            $("#camera-modal").fadeOut();
            stopCamera();
        }, "image/jpeg", 0.9);
    });
    
    // Enviar formulario de gastos
    $("#worker-portal-expense-form").on("submit", function(e) {
        e.preventDefault();
        
        const form = this;
        const formData = new FormData(form);
        
        // Añadir nonce para seguridad
        formData.append("nonce", workerPortalExpenses.nonce);
        formData.append("action", "submit_expense");
        
        // Deshabilitar el botón de envío y mostrar indicador de carga
        const submitButton = $(form).find("button[type=submit]");
        submitButton.prop("disabled", true).html('<i class="dashicons dashicons-update-alt spinning"></i> <?php _e('Enviando...', 'worker-portal'); ?>');
        
        // Enviar los datos mediante AJAX
        $.ajax({
            url: workerPortalExpenses.ajax_url,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito
                    alert(response.data.message);
                    
                    // Limpiar formulario
                    form.reset();
                    $("#receipt-preview").empty();
                    $("#receipt-upload-container").slideUp();
                    
                    // Recargar la página para mostrar el nuevo gasto
                    window.location.reload();
                } else {
                    // Mostrar mensaje de error
                    alert(response.data);
                }
            },
            error: function() {
                alert(workerPortalExpenses.i18n.error);
            },
            complete: function() {
                // Restaurar el botón de envío
                submitButton.prop("disabled", false).html('<?php _e('Enviar Gasto', 'worker-portal'); ?>');
            }
        });
    });
    
    // Eliminar gasto
    $(".worker-portal-delete-expense").on("click", function() {
        if (confirm(workerPortalExpenses.i18n.confirm_delete)) {
            const expenseId = $(this).data("expense-id");
            
            $.ajax({
                url: workerPortalExpenses.ajax_url,
                type: "POST",
                data: {
                    action: "delete_expense",
                    nonce: workerPortalExpenses.nonce,
                    expense_id: expenseId
                },
                success: function(response) {
                    if (response.success) {
                        // Eliminar la fila de la tabla
                        $(`tr[data-expense-id="${expenseId}"]`).fadeOut(function() {
                            $(this).remove();
                            
                            // Si no quedan gastos, mostrar mensaje
                            if ($(".worker-portal-expenses-table tbody tr").length === 0) {
                                $(".worker-portal-table-responsive").html('<p class="worker-portal-no-data"><?php _e('No hay gastos registrados.', 'worker-portal'); ?></p>');
                            }
                        });
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert(workerPortalExpenses.i18n.error);
                }
            });
        }
    });
});
</script>