<?php
/********************************************************
 * Automated plugin update report
 * **********************************************
 * Este plugin permite registrar y enviar información detallada sobre las actualizaciones de plugins en tu sitio de WordPress. Cada vez que un plugin se actualiza, el sistema registra la versión anterior, la nueva versión, el usuario que realizó la actualización, y la fecha y hora exacta del proceso. Estos datos se envían automáticamente a un servicio externo mediante un webhook, permitiendo automatizar el registro y análisis de estas actualizaciones en herramientas como Google Sheets, sistemas de monitoreo, u otras plataformas de automatización.
 *******************************************************/

// Guardar la versión antigua antes de la actualización
if (!function_exists('save_old_plugin_version')) {
    function save_old_plugin_version($bool, $hook_extra) {
        if (isset($hook_extra['plugin'])) {
            $plugin_path = $hook_extra['plugin'];
            // Ruta completa al archivo del plugin
            $plugin_full_path = WP_PLUGIN_DIR . '/' . $plugin_path;

            // Obtenemos los datos del plugin usando el archivo principal del plugin
            $plugin_info = get_plugin_data($plugin_full_path);

            // Guardar la versión actual como versión antigua antes de la actualización
            if (isset($plugin_info['Version'])) {
                update_option($plugin_path . '_version_old', $plugin_info['Version']);
            }
        }
        return $bool;
    }
    add_filter('upgrader_pre_install', 'save_old_plugin_version', 10, 2);
}

// Disparar el webhook después de la actualización
if (!function_exists('notify_plugin_update')) {
    function notify_plugin_update($upgrader_object, $options) {
        if ($options['action'] == 'update' && $options['type'] == 'plugin') {
            // Inicializamos la variable para almacenar los datos del plugin
            $plugin_data = array();

            // Verificamos si se han proporcionado los plugins en la opción
            if (!empty($options['plugins'])) {
                foreach ($options['plugins'] as $plugin_path) {
                    // Ruta completa al archivo del plugin
                    $plugin_full_path = WP_PLUGIN_DIR . '/' . $plugin_path;

                    // Obtenemos los datos del plugin usando el archivo principal del plugin
                    $plugin_info = get_plugin_data($plugin_full_path);

                    // Recuperar la versión antigua del plugin que guardamos en `save_old_plugin_version`
                    $old_version = get_option($plugin_path . '_version_old', '');

                    // Añadimos los detalles del plugin al array de datos personalizados
                    $plugin_data[] = array(
                        'name' => $plugin_info['Name'],  // Nombre del plugin
                        'old_version' => $old_version,  // Versión antigua guardada antes de la actualización
                        'new_version' => $plugin_info['Version'],  // Nueva versión después de la actualización
                        'plugin_path' => $plugin_path  // Ruta del plugin
                    );

                    // Actualizamos la versión antigua con la nueva versión después de la actualización
                    update_option($plugin_path . '_version_old', $plugin_info['Version']);
                }
            }

            // Datos personalizados que deseas enviar al webhook
            $custom_data = array(
                'plugins' => $plugin_data,
                'updated_by' => wp_get_current_user()->user_login,
                'updated_at' => date('Y-m-d H:i:s'),  // Fecha y hora de la actualización en formato Y-m-d H:i:s
                'month_number' => date('m')  // Número del mes
            );

            // URL del webhook de Make
            $webhook_url = 'https://hook.eu2.make.com/te85whoxkrw3kh1eo3orw88ua1iw0amk';

            // Configuración de la solicitud HTTP POST
            $args = array(
                'body'        => json_encode($custom_data),
                'headers'     => array('Content-Type' => 'application/json'),
                'timeout'     => 15,
                'blocking'    => true,  // Esperar la respuesta
                'data_format' => 'body',
            );

            // Enviar los datos al webhook de Make
            $response = wp_remote_post($webhook_url, $args);

            // Puedes usar error_log para verificar la respuesta en el archivo de log de WordPress
            if (is_wp_error($response)) {
                error_log('Error enviando el webhook: ' . $response->get_error_message());
            } else {
                error_log('Webhook enviado correctamente: ' . print_r($response, true));
            }
        }
    }
    add_action('upgrader_process_complete', 'notify_plugin_update', 10, 2);
}

?>