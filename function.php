<?php 
add_action('template_redirect', 'check_and_redirect_or_verify_contract');

function check_and_redirect_or_verify_contract() {
    global $post;

    //*************/ Comprobar si la página contiene el shortcode [wp_e_signature_sad]*************/
    if (has_shortcode($post->post_content, 'wp_e_signature_sad')) {

        //****************/ Si tiene el parámetro verification=true en la URL****************/
        if (isset($_GET['verification']) && $_GET['verification'] == 'true') {
            //error_log('Iniciando verificación y acceso a la página de contrato...');

            //********************* Verificar si el usuario tiene una sesión externa activa (basado en la cookie de sesión externa)*********************/
            if (isset($_COOKIE['external_session_id']) && !empty($_COOKIE['external_session_id'])) {
                //error_log("Acceso permitido: Sesión externa detectada para el usuario.");

                //*************** Inserción de la contraseña directamente***************/
                $password = 'eRY3j8Vp0#wP@0FoTs)k!rFaqPsaG@^Rx@eow$JwcLqwS5@j'; // Contraseña que se va a insertar automáticamente
                
                ?>
				<script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function() {
                    //console.log('DOM completamente cargado y analizado.');

                    setTimeout(function() {
                        //************ Encuentra el formulario de contraseña***********/
                        var form = document.querySelector('form.post-password-form');
                        //console.log('Formulario encontrado:', form);

                        if (form) {
                            //******************/ Encuentra el campo de contraseña******************/
                            var passwordField = form.querySelector('input[name="post_password"]');
                            //console.log('Campo de contraseña encontrado:', passwordField);

                            if (passwordField) {
                                //**********/ Inserta la contraseña y envía el formulario automáticamente**********/
                                passwordField.value = '<?php echo esc_js($password); ?>';
                                //console.log('Contraseña insertada:', passwordField.value);
                                form.submit();
                                //console.log('Formulario enviado automáticamente.');
                            } else {
                                //console.log('No se encontró el campo de contraseña.');
                            }
                        } else {
                            //console.log('No se encontró el formulario de contraseña.');
                        }
                    }, 0); // Espera 0 milisegundos para asegurarse de que la página esté completamente cargada
                });
                </script>
				<?php

                return; // No redirigir, se permite el acceso
            } else {
                //***************/ Si no hay sesión externa válida, redirigir al login***************/
                //error_log("Acceso denegado: Sesión externa no válida o expirada.");
                wp_redirect(site_url('/login-user-ccg?token=invalid_or_expired'));
                exit();
            }
        } elseif (isset($_GET['action']) && $_GET['action'] == 'postpass') {
            if (isset($_COOKIE['external_session_id']) && !empty($_COOKIE['external_session_id'])) {
                return;
            } else {
                //***************/ Si no hay sesión externa válida, redirigir al login***************/
                //error_log("Acceso denegado: Sesión externa no válida o expirada.");
                wp_redirect(site_url('/login-user-ccg?token=invalid_or_expired'));
                exit();
            }
        } else {
			// Si no tiene el parámetro verification=true, redirigir a la home
			wp_redirect(home_url());
			exit();
		}
    }
}
?>