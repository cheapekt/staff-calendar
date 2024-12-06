<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    return;
}
?>

<div class="staff-vehicles-manager">
    <h2>Gestión de Vehículos</h2>
    
    <div id="vehicles-messages" class="vehicles-messages" style="display: none;"></div>
    
    <div class="add-vehicle-form">
        <h3>Añadir Nuevo Vehículo</h3>
        <form id="new-vehicle-form">
            <div class="form-group">
                <label for="vehicle-name">Nombre del Vehículo:</label>
                <input type="text" id="vehicle-name" required>
            </div>
            
            <div class="form-group">
                <label for="vehicle-plate">Matrícula:</label>
                <input type="text" id="vehicle-plate">
            </div>
            
            <div class="form-group">
                <label for="vehicle-status">Estado:</label>
                <select id="vehicle-status">
                    <option value="active">Activo</option>
                    <option value="maintenance">En Mantenimiento</option>
                    <option value="inactive">Inactivo</option>
                </select>
            </div>
            
            <button type="submit" class="button button-primary">Añadir Vehículo</button>
        </form>
    </div>
    
    <div class="vehicles-list">
        <h3>Vehículos Registrados</h3>
        <div class="table-container">
            <table class="wp-list-table widefat fixed striped vehicles-table">
                <thead>
                    <tr>
                        <th class="column-name">Nombre</th>
                        <th class="column-plate">Matrícula</th>
                        <th class="column-status">Estado</th>
                        <th class="column-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody id="vehicles-list-body">
                    <!-- Los vehículos se cargarán aquí mediante JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para editar vehículo -->
<div id="edit-vehicle-modal" class="vehicle-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Editar Vehículo</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="edit-vehicle-form">
                <input type="hidden" id="edit-vehicle-id">
                
                <div class="form-group">
                    <label for="edit-vehicle-name">Nombre del Vehículo:</label>
                    <input type="text" id="edit-vehicle-name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-vehicle-plate">Matrícula:</label>
                    <input type="text" id="edit-vehicle-plate">
                </div>
                
                <div class="form-group">
                    <label for="edit-vehicle-status">Estado:</label>
                    <select id="edit-vehicle-status">
                        <option value="active">Activo</option>
                        <option value="maintenance">En Mantenimiento</option>
                        <option value="inactive">Inactivo</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="button button-primary">Guardar Cambios</button>
                    <button type="button" class="button modal-cancel">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
</script>