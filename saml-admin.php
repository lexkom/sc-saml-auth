<?php
/**
 * SAML Authentication Admin Class
 * Handles plugin settings and admin interface
 *
 * @package       saml_auth
 * @author        Techtonyc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class SAML_Admin {
    /**
     * Plugin settings page slug
     * 
     * @var string
     */
    private string $page_slug = 'saml-auth';

    /**
     * Plugin settings option name
     * 
     * @var string
     */
    private string $option_name = 'saml_settings';

    /**
     * Constructor
     * Initialize admin hooks and settings
     */
    public function __construct()
    {
        add_action('admin_menu', array( $this, 'add_admin_menu' ) );
        add_action('admin_init', array( $this, 'register_settings' ) );
        add_action('admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action('admin_footer', array( $this, 'saml_js_inline' ) );
        add_action('wp_ajax_saml_check_idp_connection', array( $this, 'ajax_check_connection' ) );
        add_filter('login_message', array( $this, 'add_sso_button_before_login_form' ) );
        add_shortcode('saml_sso_button', array( $this, 'saml_login_button_shortcode' ) );

        //add_filter( 'pre_update_option_saml_settings', array( $this, 'saml_validate_settings' ) );
    }

    /**
     * Add plugin menu to WordPress admin
     * 
     * @return void
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __( 'SAML Authentication', 'saml_auth' ),
            __( 'SAML Auth', 'saml_auth' ),
            'manage_options',
            $this->page_slug,
            array( $this, 'render_settings_page' ),
            'dashicons-shield',
            80
        );
    }

    /**
     * Register plugin settings
     * 
     * @return void
     */
    public function register_settings()
    {
        register_setting( $this->option_name . '_group', $this->option_name );
    }

    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook Current admin page
     * @return void
     */
    public function enqueue_admin_scripts( $hook )
    {
        if ( 'toplevel_page_' . $this->page_slug !== $hook ) {
            return;
        }

        wp_enqueue_style( 'saml-auth-admin', plugins_url( 'assets/css/admin-styles.css', __FILE__ ) );
        //wp_enqueue_script( 'saml-auth-admin', plugins_url( 'assets/js/admin.js', dirname( __FILE__ ) ), array( 'jquery' ), '1.0.0', true );
    }

    /**
     * Add SSO button before login form
     * 
     * @param string $message Login message
     * @return string Modified login message
     */
    public function add_sso_button_before_login_form( $message )
    {
        $settings = $this->saml_get_option( 'buttons' );
        if ( ! empty( $settings['saml_add_sso_button'] ) ) {
            $button = '<div class="saml-sso-button">';
            $button .= '<a href="' . esc_url( site_url( SAML_Auth::$SAML_LOGIN_URL ) ) . '" class="button button-primary button-large" style="display: block; margin-bottom: 1em; float: none; text-align: center;">' . __( 'Login with SSO', 'saml_auth' ) . '</a>';
            $button .= '</div>';
            $message = $button . $message;
        }
        return $message;
    }

    /**
     * Render settings page
     * 
     * @return void
     */
    public function render_settings_page()
    {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $this->saml_settings_save();

        $options = $this->saml_get_option();
        $tab = isset( $_GET[ 'tab' ] ) ? sanitize_text_field( $_GET[ 'tab' ] ) : 'setup';
        ?>
        <div class="wrap">
            <h1><?php echo __( 'SAML Auth Settings', 'saml_auth' ) ?></h1>
            <?php if ( $errors = get_settings_errors( 'saml_settings' ) ) : ?>
                <?php foreach ( $errors as $error ) : ?>
                    <div class="notice notice-<?php echo $error[ 'type' ]?>">
                        <p><?php echo esc_html( $error[ 'message' ] ); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php
            include plugin_dir_path(__FILE__) . 'views/settings-tabs-nav.php';
            ?>
            <div class="tab-content">
                <form method="post" action="">
                    <input type="hidden" name="saml_settings[active_tab]" value="<?php echo $tab ?>">
                    <?php settings_fields('saml_settings_group'); ?>
                    <?php wp_nonce_field('saml_settings_action', 'saml_settings_nonce'); ?>
                    <?php
                    include plugin_dir_path(__FILE__) . 'views/settings-tab-' . $tab . '.php';
                    ?>
                    <?php if ( in_array( $tab, [ 'setup', 'attributes', 'buttons' ] ) ) : ?>
                        <?php submit_button(); ?>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Sanitize plugin settings
     * 
     * @param array $data Raw settings input
     * @return array|bool Sanitized settings
     */
    public function saml_validate_settings( array $data )
    {
        $data[ 'setup' ][ 'idp_entity_id' ] = sanitize_text_field( $data[ 'setup' ][ 'idp_entity_id' ] ?? '' );
        $data[ 'setup' ][ 'idp_sso_url' ] = esc_url_raw( $data[ 'setup' ][ 'idp_sso_url' ] ?? '' );
        $data[ 'setup' ][ 'idp_slo_url' ] = esc_url_raw( $data[ 'setup' ][ 'idp_slo_url' ] ?? '' );
        $data[ 'setup' ][ 'idp_x509' ] = trim( $data[ 'setup' ][ 'idp_x509' ] ?? '' );

        if ( ! empty( $data[ 'setup' ][ 'idp_x509' ] ) && strpos( $data[ 'setup' ][ 'idp_x509' ], 'BEGIN CERTIFICATE' ) === false ) {
            add_settings_error( 'saml_settings', 'cert_error', 'Check the format of the IdP certificate (must start with BEGIN CERTIFICATE)' );
            return false;
        }

        return $data;
    }

    /**
     * Get plugin settings
     * 
     * @return array Plugin settings
     */
    public function saml_get_settings()
    {
        $settings = get_option( $this->option_name, array() );
        
        // Set default values if not exists
        if ( empty( $settings ) ) {
            $settings = array(
                'setup' => array(
                    'idp_entity_id' => '',
                    'idp_sso_url' => '',
                    'idp_slo_url' => '',
                    'idp_x509' => '',
                    'allow_user_registration' => 0
                ),
                'attributes' => array(
                    'first_name' => '',
                    'last_name' => '',
                    'group' => '',
                    'default_role' => 'subscriber',
                    'custom_attributes' => array()
                ),
                'buttons' => array(
                    'show_sso_button' => 1
                )
            );
        }
        
        return $settings;
    }

    /**
     * Get specific plugin option
     * 
     * @param string|null $key Option key
     * @return mixed Option value
     */
    public function saml_get_option( $key = null )
    {
        $settings = $this->saml_get_settings();
        
        if ( $key === null ) {
            return $settings;
        }
        
        return isset( $settings[$key] ) ? $settings[$key] : null;
    }

    /**
     * Save plugin settings
     * 
     * @param array $settings Settings to save
     * @return void
     */
    public function saml_settings_save()
    {
        if (
            ! isset( $_POST[ 'saml_settings' ] ) ||
            ! wp_verify_nonce( $_POST[ 'saml_settings_nonce' ], 'saml_settings_action' )
        ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $tab = isset( $_POST[ 'saml_settings' ][ 'active_tab' ] ) ? sanitize_key( $_POST[ 'saml_settings' ][ 'active_tab' ] ) : 'setup';
        $all_settings = get_option( 'saml_settings' );
        if ( ! $all_settings ) {
            $all_settings = array(
                'setup' => array(),
                'metadata' => array(),
                'attributes' => array(),
                'buttons' => array(),
            );
        }
        $settings = $_POST[ 'saml_settings' ];

        switch ( $tab ) {
            case 'setup':
                $all_settings[ 'setup' ] = [
                    'idp_entity_id' => sanitize_text_field( $settings[ 'idp_entity_id' ] ?? '' ),
                    'idp_sso_url' => sanitize_text_field( $settings[ 'idp_sso_url' ] ?? '' ),
                    'idp_slo_url' => sanitize_text_field( $settings[ 'idp_slo_url' ] ?? '' ),
                    'idp_x509' => sanitize_textarea_field( $settings[ 'idp_x509' ] ?? '' ),
                    'allow_user_registration' => isset( $settings[ 'allow_user_registration' ] ) ? 1 : 0,
                ];
                break;

            case 'metadata':
                $all_settings[ 'metadata' ] = [
                    'entity_id' => sanitize_text_field( $settings[ 'entity_id' ] ?? '' ),
                    'acs_url' => sanitize_text_field( $settings[ 'acs_url' ] ?? '' ),
                    'nameid_format' => sanitize_text_field( $settings[ 'nameid_format' ] ?? '' ),
                ];
                break;

            case 'attributes':
                $all_settings[ 'attributes' ] = [
                    'first_name' => sanitize_text_field( $settings[ 'first_name' ] ?? '' ),
                    'last_name' => sanitize_text_field( $settings[ 'last_name' ] ?? '' ),
                    'group' => sanitize_text_field( $settings[ 'group' ] ?? '' ),
                    'custom_attributes' => [],
                    'default_role' => sanitize_text_field( $settings[ 'default_role' ] ?? '' ),
                ];

                if ( ! empty( $settings[ 'custom_attribute_name' ] ) && ! empty( $settings[ 'custom_attribute_meta' ] ) ) {
                    $names = (array) $settings[ 'custom_attribute_name' ];
                    $keys = (array) $settings[ 'custom_attribute_meta' ];

                    foreach ( $names as $i => $name ) {
                        $name = sanitize_text_field( $name );
                        $key = sanitize_text_field( $keys[ $i ] ?? '' );

                        if ( $name && $key ) {
                            $all_settings[ 'attributes' ][ 'custom_attributes' ][] = [
                                'idp_attribute_name' => $name,
                                'user_meta_field' => $key,
                            ];
                        }
                    }
                }
                break;

            case 'buttons':
                $all_settings[ 'buttons' ] = [
                    'saml_add_sso_button' => isset( $settings[ 'saml_add_sso_button' ] ) ? 1 : 0,
                ];
                break;
        }

        if ( $all_settings = $this->saml_validate_settings( $all_settings ) ) {
            // Сохраняем все настройки
            update_option('saml_settings', $all_settings);

            add_settings_error('saml_settings', 'settings_updated', __('Settings saved.', 'saml_auth'), 'success');
        }
    }

    /**
     * Add inline JavaScript for admin page
     * 
     * @return void
     */
    public function saml_js_inline()
    {
        $screen = get_current_screen();
        if ( $screen->id !== 'toplevel_page_' . $this->page_slug ) return;
        ?>
        <script>
            const chkIdp = document.getElementById( 'check-idp-connection' );
            if ( chkIdp ) {
                chkIdp.addEventListener('click', function () {
                    const resultEl = document.getElementById('idp-check-result');
                    resultEl.textContent = 'Checking...';

                    fetch(ajaxurl + '?action=saml_check_idp_connection', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type': 'application/json'}
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                resultEl.innerHTML = `<span style="color:green;">✅ ${data.data.message}</span>`;
                            } else {
                                resultEl.innerHTML = `<span style="color:red;">❌ ${data.data.message}</span>`;
                            }
                        })
                        .catch(err => {
                            resultEl.innerHTML = `<span style="color:red;">Request error ` + err + `</span>`;
                        });
                });
            }
        </script>
        <?php
    }

    /**
     * Check IdP connection via AJAX
     * 
     * @return void
     */
    public function ajax_check_connection()
    {
        if (!current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Restricted' ] );
        }

        $options = $this->saml_get_option( 'setup' );

        $url = $options[ 'idp_entity_id' ] ?? '';

        if ( empty( $url ) ) {
            wp_send_json_error( [ 'message' => 'Empty IdP Entity ID' ] );
        }

        $response = wp_remote_get( $url, [ 'timeout' => 10 ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => 'Request error: ' . $response->get_error_message() ] );
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            wp_send_json_error( [ 'message' => 'Empty response from IdP' ] );
        }

        // Check if response is valid XML
        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body );
        if ($xml === false) {
            wp_send_json_error( [ 'message' => 'Response from IdP is not correct XML' ] );
        }

        wp_send_json_success( [ 'message' => 'Successful connection. XML is correct.' ] );
    }

    /**
     * Shortcode handler for SAML login button
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function saml_login_button_shortcode($atts = array()) {
        $settings = $this->saml_get_option('buttons');
        if (empty($settings['saml_add_sso_button'])) {
            return '';
        }

        $atts = shortcode_atts(array(
            'class' => 'button button-primary button-large',
            'style' => 'display: block; margin-bottom: 1em; float: none; text-align: center;',
            'text' => __('Login with SSO', 'saml_auth')
        ), $atts);

        $button = '<div class="saml-sso-button">';
        $button .= '<a href="' . esc_url(site_url(SAML_Auth::$SAML_LOGIN_URL)) . '" class="' . esc_attr($atts['class']) . '" style="' . esc_attr($atts['style']) . '">' . esc_html($atts['text']) . '</a>';
        $button .= '</div>';

        return $button;
    }
}