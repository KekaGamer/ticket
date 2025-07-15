<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
$auth->redirectIfNotCliente();

$functions = new Functions();
$db = new Database();
$conn = $db->getConnection();

$servicio_id = $_GET['servicio_id'] ?? 0;
if ($servicio_id == 0) {
    header("Location: catalogo.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
    $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_SANITIZE_NUMBER_INT);
    $prioridad = filter_input(INPUT_POST, 'prioridad', FILTER_SANITIZE_STRING);
    $tipo_solicitud = $_POST['tipo_solicitud'] ?? 'individual';
    
    $descripcion_final = '';
    if ($tipo_solicitud === 'masivo') {
        $descripcion_final = "Solicitud Masiva. Por favor, revise el archivo adjunto.";
    } else {
        $descripcion_final = $_POST['descripcion_final'];
    }

    $stmt_user = $conn->prepare("SELECT id_empresa, id_area FROM usuarios WHERE id = :id");
    $stmt_user->execute([':id' => $_SESSION['user_id']]);
    $userInfo = $stmt_user->fetch(PDO::FETCH_ASSOC);
    
    if ($userInfo && $id_categoria) {
        $ticketId = $functions->crearTicket(
            $_SESSION['user_id'], $userInfo['id_empresa'], $userInfo['id_area'],
            $titulo, $descripcion_final, $prioridad, $id_categoria
        );
        if ($ticketId) {
            if (isset($_FILES['adjunto_masivo']) && $_FILES['adjunto_masivo']['error'] == UPLOAD_ERR_OK) {
                $archivo_masivo_adaptado = [
                    'name' => [$_FILES['adjunto_masivo']['name']], 'type' => [$_FILES['adjunto_masivo']['type']],
                    'tmp_name' => [$_FILES['adjunto_masivo']['tmp_name']], 'error' => [$_FILES['adjunto_masivo']['error']],
                    'size' => [$_FILES['adjunto_masivo']['size']],
                ];
                $functions->guardarAdjuntos(null, $ticketId, $archivo_masivo_adaptado);
            }
            $_SESSION['success_message'] = "Solicitud #$ticketId creada correctamente.";
            header("Location: tickets.php?action=view&id=$ticketId");
            exit();
        } else { $error = "Error al crear el ticket."; }
    } else { $error = "Información de usuario o categoría inválida."; }
}

$stmt_servicio = $conn->prepare("SELECT nombre FROM categorias_tickets WHERE id = ?");
$stmt_servicio->execute([$servicio_id]);
$servicio = $stmt_servicio->fetch(PDO::FETCH_ASSOC);

$stmt_subcat = $conn->prepare("SELECT id, nombre, plantilla FROM categorias_tickets WHERE parent_id = ? AND estado = 1 ORDER BY nombre");
$stmt_subcat->execute([$servicio_id]);
$tipos_solicitud = $stmt_subcat->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Nueva Solicitud: " . htmlspecialchars($servicio['nombre']);
include_once '../includes/header.php';
?>
<div class="container">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1><?php echo $page_title; ?></h1>
            <a href="catalogo.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al Catálogo</a>
        </div>
        
        <?php if (isset($error)): ?> <div class="alert alert-danger"><?php echo $error; ?></div> <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form id="dynamicTicketForm" method="POST" action="solicitud.php?servicio_id=<?php echo $servicio_id; ?>" class="needs-validation" novalidate enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="id_categoria">Tipo de Solicitud Específica</label>
                        <select class="form-control" id="id_categoria" name="id_categoria" required>
                            <option value="">Seleccione una opción...</option>
                            <?php foreach ($tipos_solicitud as $tipo): ?>
                                <option value="<?php echo $tipo['id']; ?>" data-plantilla="<?php echo htmlspecialchars($tipo['plantilla']); ?>">
                                    <?php echo htmlspecialchars($tipo['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="form-fields-wrapper" style="display:none;">
                        <div class="form-group request-type-selector">
                            <label>Modalidad de la Solicitud</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_solicitud" id="tipo_individual" value="individual" checked>
                                    <label class="form-check-label" for="tipo_individual">Individual</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_solicitud" id="tipo_masivo" value="masivo">
                                    <label class="form-check-label" for="tipo_masivo">Masiva (por archivo)</label>
                                </div>
                            </div>
                        </div>

                        <div id="individual-form">
                               <div class="row">
                                   <div class="col-md-8">
                                       <div class="form-group">
                                           <label for="titulo">Título Corto de la Solicitud</label>
                                           <input type="text" class="form-control" id="titulo" name="titulo" required>
                                       </div>
                                   </div>
                                   <div class="col-md-4">
                                       <div class="form-group">
                                           <label for="prioridad">Prioridad</label>
                                           <select class="form-control" id="prioridad" name="prioridad" required>
                                               <option value="baja">Baja</option>
                                               <option value="media" selected>Media</option>
                                               <option value="alta">Alta</option>
                                               <option value="urgente">Urgente</option>
                                           </select>
                                       </div>
                                   </div>
                               </div>
                            <div class="dynamic-form-container"></div>
                        </div>

                        <div id="masivo-form" style="display:none;">
                            <div class="form-group">
                                <label for="titulo_masivo">Título de la Solicitud Masiva</label>
                                <input type="text" class="form-control" id="titulo_masivo" value="Carga Masiva: <?php echo htmlspecialchars($servicio['nombre']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="adjunto_masivo">Adjuntar Archivo (Excel, CSV)</label>
                                <input type="file" class="form-control-file" id="adjunto_masivo" name="adjunto_masivo">
                                <small class="form-text text-muted">Asegúrese de que el archivo contenga todas las columnas necesarias.</small>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="descripcion_final" id="descripcion_final">

                    <div class="form-actions">
                        <a href="catalogo.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar Solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    
    function renderDynamicFields(plantilla, container) {
        container.html(''); // Limpiar solo el contenedor dinámico
        if (!plantilla) return;

        container.append('<hr><h4>Detalles Específicos de la Solicitud</h4>');
        const grid = $('<div class="dynamic-form-grid"></div>');
        container.append(grid);

        const regex = /\[(texto|area|opciones|fecha|checkbox|separador):([^\]]*)\]/g;
        let match;
        while ((match = regex.exec(plantilla)) !== null) {
            const type = match[1];
            const content = match[2];
            let formGroup = $('<div class="form-group"></div>');
            let field;
            const fieldId = `dyn_field_${Math.random().toString(36).substr(2, 9)}`;

            if (type === 'separador') {
                field = $(`<h5 class="form-separator">${content}</h5>`);
                formGroup.addClass('full-width');
            } else if (type === 'area') {
                field = $(`<label for="${fieldId}">${content}</label><textarea id="${fieldId}" class="form-control dynamic-field" rows="4" data-label="${content}"></textarea>`);
                formGroup.addClass('full-width');
            } else if (type === 'checkbox') {
                  field = $(`<div class="form-check"><input type="checkbox" class="form-check-input dynamic-field-check" id="${fieldId}" data-label="${content}"><label class="form-check-label" for="${fieldId}">${content}</label></div>`);
                  formGroup.addClass('full-width');
            } else if (type === 'opciones') {
                const parts = content.split('|').map(item => item.trim());
                const label = parts.shift();
                field = $(`<label for="${fieldId}">${label}</label><select id="${fieldId}" class="form-control dynamic-field" data-label="${label}"><option value="">Seleccionar...</option></select>`);
                parts.forEach(option => field.filter('select').append(`<option value="${option}">${option}</option>`));
            } else { // texto, fecha
                field = $(`<label for="${fieldId}">${content}</label><input type="${type}" id="${fieldId}" class="form-control dynamic-field" data-label="${content}">`);
            }
            if(field) { formGroup.append(field); grid.append(formGroup); }
        }
    }

    // Función para actualizar la visibilidad de los formularios
    function updateFormVisibility() {
        const selectedMode = $('input[name="tipo_solicitud"]:checked').val();
        
        if (selectedMode === 'individual') {
            $('#individual-form').show();
            $('#masivo-form').hide();
            // Asegurarse de que los campos correctos tengan el atributo 'name' para ser enviados
            $('#titulo_individual').attr('name', 'titulo');
            $('#prioridad_individual').attr('name', 'prioridad');
            $('#titulo_masivo').removeAttr('name');
            $('#adjunto_masivo').removeAttr('required');
        } else { // masivo
            $('#individual-form').hide();
            $('#masivo-form').show();
            $('#titulo_masivo').attr('name', 'titulo');
            $('#prioridad_masivo').attr('name', 'prioridad');
            $('#titulo_individual').removeAttr('name');
            $('#adjunto_masivo').attr('required', 'required');
        }
    }

    // Evento cuando cambia el TIPO DE SOLICITUD
    $('#id_categoria').change(function() {
        const plantilla = $(this).find('option:selected').data('plantilla') || '';
        if ($(this).val()) {
            $('#form-fields-wrapper').show();
            renderDynamicFields(plantilla, $('#individual-form .dynamic-form-container'));
            updateFormVisibility(); // Actualiza la vista por si el radio button ya estaba seleccionado
        } else {
            $('#form-fields-wrapper').hide();
        }
    });

    // Evento cuando cambia la MODALIDAD (Individual/Masivo)
    $('input[name="tipo_solicitud"]').change(updateFormVisibility);

    // Al enviar, construir la descripción final
    $('#dynamicTicketForm').submit(function(e) {
        if ($('input[name="tipo_solicitud"]:checked').val() === 'individual') {
            let finalDescription = ''; // <-- ¡AQUÍ ESTÁ EL CAMBIO! Se inicia como una cadena vacía.
            $('#individual-form .dynamic-field, #individual-form .dynamic-field-check').each(function() {
                const label = $(this).data('label');
                if ($(this).is(':checkbox')) {
                    const status = $(this).is(':checked') ? '[x]' : '[ ]';
                    finalDescription += `${status} ${label}\n`;
                } else {
                    const value = $(this).val();
                    if (value) {
                        finalDescription += `**${label}:**\n${value}\n\n`;
                    }
                }
            });
            $('#descripcion_final').val(finalDescription.trim());
        }
    });
});
</script>
</body>
</html>