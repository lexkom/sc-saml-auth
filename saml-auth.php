<?php
/**
 * SAML Authentication for WordPress
 *
 * @package       sc_saml_auth
 * @author        Techtonyc
 *
 * @wordpress-plugin
 * Plugin Name:  SAML Authentication for WP
 * Description:  Authenticate user using Identity Provider
 * Version:      0.1.0
 * Author:       Techtonyc
 * Author URI:   http://sitecraft.com/
 * License:      GPL-2.0+
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:  saml_auth
 * Domain Path:  /lang
 * Requires PHP: 7.3
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class SAML_Auth {
    public static $instance;

    public $saml_admin;

    static $SAML_SP_ENTITY_ID = 'saml-metadata';
    static $SAML_LOGIN_URL = 'saml-login';
    static $SAML_ACS_URL = 'saml-acs';
    static $SAML_SLS_URL = 'saml-sls';
    static $SAML_METADATA_URL = 'saml-metadata';
    static $SAML_DEFAULT_NAMEID_FORMAT = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';

    /**
     * Constructor
     * Initialize plugin hooks and settings
     */
    public function __construct() {
        require_once 'vendor/autoload.php';
        require_once 'saml-admin.php';

        $this->saml_admin = new SAML_Admin();

        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action('init', array( $this, 'saml_rewrite' ) );
        add_action('template_redirect', array( $this, 'saml_router' ), 99 );

        add_filter( 'query_vars', function ($vars) {
            $vars[] = 'saml_auth_action';
            return $vars;
        } );
    }

    /**
     * Initialize class and set instance
     * 
     * @return SAML_Auth
     */
    public static function init()
    {
        self::$instance = new SAML_Auth();
        return self::$instance;
    }

    /**
     * Plugin activation callback
     * 
     * @return void
     */
    public function activate()
    {
        if ( ! get_option( 'saml_auth_flush_rewrite_rules' ) ) {
            add_option( 'saml_auth_flush_rewrite_rules', true );
        }
    }

    /**
     * Plugin deactivation callback
     *
     * @return void
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules if the previously added flag exists,
     * and then remove the flag.
     */
    function saml_flush_rewrite_rules() {
        if ( get_option( 'saml_auth_flush_rewrite_rules' ) ) {
            flush_rewrite_rules();
            delete_option( 'saml_auth_flush_rewrite_rules' );
        }
    }

    /**
     * Get SAML configuration settings
     * 
     * @return array Configuration array for SAML
     */
    private function get_config()
    {
        $settings = $this->saml_admin->saml_get_option( 'setup' );

        if ( ! $settings )
            wp_die( '[SAMLAUTH_ERROR] #10 Wrong config' );

        return array(
            'strict' => true,
            'debug' => false,
            'sp' => array(
                'entityId' => site_url( '/' . static::$SAML_SP_ENTITY_ID . '/' ),
                'assertionConsumerService' => array(
                    'url' => site_url( '/' . static::$SAML_ACS_URL . '/' ),
                ),
                'singleLogoutService' => array(
                    'url' => site_url( '/' . static::$SAML_SLS_URL . '/' ),
                ),
            ),
            'idp' => array(
                'entityId' => $settings[ 'idp_entity_id' ],
                'singleSignOnService' => array(
                    'url' => $settings[ 'idp_sso_url' ],
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ),
                'singleLogoutService' => array(
                    'url' => $settings[ 'idp_slo_url' ],
                    'responseUrl' => '',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ),
                'x509cert' => $settings[ 'idp_x509' ],
            ),
            'security' => array(
                'nameIdFormat' => \OneLogin\Saml2\Constants::NAMEID_EMAIL_ADDRESS,
            ),
        );
    }

    /**
     * Get plugin configuration
     * 
     * @return array Configuration array for plugin
     */
    public function saml_get_option( $key = null )
    {
        return $this->saml_admin->saml_get_option( $key );
    }

    /**
     * Handle SAML login process
     *
     * @return void
     * @throws \OneLogin\Saml2\Error
     */
    public function saml_login()
    {
        $auth = new OneLogin\Saml2\Auth( $this->get_config() );

        try {
            $settings = $auth->getSettings();
            $metadata = $settings->getSPMetadata();
            $errors = $settings->validateMetadata( $metadata );
            if ( empty( $errors ) ) {
                $auth->login();
            } else {
                error_log( 'SAML Plugin Login Error: ' . implode(', ', $errors) );
            }
        } catch (Exception $e) {
            error_log( 'SAML Plugin Login Error: ' . $e->getMessage() );
        }
    }

    /**
     * Handle SAML Assertion Consumer Service (ACS)
     * Process SAML response and authenticate user
     * 
     * @return void
     */
    public function saml_acs()
    {
        do_action('saml_auth_before_process_response');
        
        $optionsSetup = $this->saml_get_option( 'setup' );
        try {
            $auth = new OneLogin\Saml2\Auth( $this->get_config() );
            $auth->processResponse();
            
            if ( $auth->isAuthenticated() ) {
                $attributes = $auth->getAttributes();
                $attributes = apply_filters('saml_auth_attributes', $attributes);

                $email = $auth->getNameId();

                if ( strpos( $email, '@' ) === false ) {
                    if ( ! empty( $attributes[ 'mail' ][ 0 ] ) ) {
                        $email = $attributes['mail'][0];
                    } else if ( ! empty( $attributes[ 'email' ][ 0 ] ) ) {
                        $email = $attributes['email'][0];
                    } else if ( ! empty( $attributes[ 'EmailAddress' ][ 0 ] ) ) {
                        $email = $attributes['EmailAddress'][0];
                    }
                }

                $user = get_user_by( 'email', $email );
                if ( ! $user && ! empty( $optionsSetup[ 'allow_user_registration' ] ) ) {
                    do_action('saml_auth_before_user_registration', $email, $attributes);
                    $user = $this->saml_user_registration( $email, $attributes );
                    do_action('saml_auth_after_user_registration', $user, $attributes);
                }

                if ( $user ) {
                    do_action('saml_auth_before_set_current_user', $user);
                    wp_set_current_user($user->ID);
                    wp_set_auth_cookie($user->ID);
                    do_action('saml_auth_after_successful_authentication', $user);
                    wp_redirect( home_url() );
                } else {
                    do_action('saml_auth_authentication_failed', $email, $attributes);
                    wp_die( '[SAMLAUTH_ERROR] #01 Invalid authentication data. Please check your login details and try again.' );
                }
                exit;
            } else {
                $errors = $auth->getErrors();
                $reason = $auth->getLastErrorReason();
                do_action('saml_auth_saml_error', $errors, $reason);
                error_log('SAML ACS ERROR: ' . implode(', ', $errors));
                error_log('SAML ACS REASON: ' . $reason);
                wp_die( '[SAMLAUTH_ERROR] #02 Invalid authentication data. Please check your login details and try again.' );
            }
        } catch (Exception $e) {
            error_log('SAML CRITICAL ERROR: ' . $e->getMessage());
            error_log('SAML Stack Trace: ' . $e->getTraceAsString());
            wp_die('[SAMLAUTH_ERROR] #03 We could not process this request. Please contact your administrator with the mentioned error code.');
        }
    }

    /**
     * Handle SAML Single Logout Service (SLS)
     * Process logout request and clear user session
     *
     * @return void
     * @throws \OneLogin\Saml2\Error
     */
    public function saml_sls()
    {
        do_action('saml_auth_before_logout');
        
        $auth = new OneLogin\Saml2\Auth( $this->get_config() );

        $auth->processSLO();
        
        do_action('saml_auth_before_wp_logout');
        wp_logout();
        do_action('saml_auth_after_wp_logout');
        
        wp_redirect( home_url() );
        exit;
    }

    /**
     * Generate and return SAML metadata XML
     *
     * @return void
     * @throws \OneLogin\Saml2\Error
     */
    public function saml_metadata()
    {
        $settings = new OneLogin\Saml2\Settings( $this->get_config() );
        header('Content-Type: text/xml');
        echo $settings->getSPMetadata();
        exit;
    }

    /**
     * Register rewrite rules for SAML endpoints
     * 
     * @return void
     */
    public function saml_rewrite()
    {
        add_rewrite_rule('^' . static::$SAML_LOGIN_URL . '/?$', 'index.php?saml_auth_action=login', 'top');
        add_rewrite_rule('^' . static::$SAML_ACS_URL . '/?$', 'index.php?saml_auth_action=acs', 'top');
        add_rewrite_rule('^' . static::$SAML_SLS_URL . '/?$', 'index.php?saml_auth_action=sls', 'top');
        add_rewrite_rule('^' . static::$SAML_METADATA_URL . '/?$', 'index.php?saml_auth_action=metadata', 'top');

        $this->saml_flush_rewrite_rules();
    }

    /**
     * Route SAML requests to appropriate handlers
     *
     * @return void
     * @throws \OneLogin\Saml2\Error
     */
    public function saml_router()
    {
        $saml_action = get_query_var('saml_auth_action');

        if ($saml_action) {
            switch ($saml_action) {
                case 'login':
                    $this->saml_login();
                    break;
                case 'acs':
                    $this->saml_acs();
                    break;
                case 'sls':
                    $this->saml_sls();
                    break;
                case 'metadata':
                    $this->saml_metadata();
                    break;
            }
            exit;
        }
    }

    /**
     * Register new user with SAML attributes
     * 
     * @param string $email User email address
     * @param array $attributes SAML attributes from IdP
     * @return WP_User|false User object on success, false on failure
     */
    public function saml_user_registration( $email, $attributes = array() )
    {
        $email = apply_filters('saml_auth_registration_email', $email);
        $attributes = apply_filters('saml_auth_registration_attributes', $attributes);
        
        // Get attribute settings
        $options = $this->saml_get_option( 'attributes' );
        
        // Create basic user information
        $username = strstr( $email, '@', true );
        $username = apply_filters('saml_auth_registration_username', $username);
        
        $password = wp_generate_password();
        $password = apply_filters('saml_auth_registration_password', $password);

        // Create user
        $user_id = wp_create_user( $username, $password, $email );
        
        if ( is_wp_error( $user_id ) ) {
            error_log( 'SAML User Registration Error: ' . $user_id->get_error_message() );
            return false;
        }
        
        $user = get_user_by( 'id', $user_id );
        
        // Fill basic fields
        if ( ! empty( $options[ 'first_name' ] ) && isset( $attributes[ $options[ 'first_name' ] ] ) ) {
            $first_name = $attributes[ $options[ 'first_name' ] ][0];
            $first_name = apply_filters('saml_auth_registration_first_name', $first_name);
            update_user_meta( $user_id, 'first_name', $first_name );
        }
        
        if ( ! empty( $options[ 'last_name' ] ) && isset( $attributes[ $options[ 'last_name' ] ] ) ) {
            $last_name = $attributes[ $options[ 'last_name' ] ][0];
            $last_name = apply_filters('saml_auth_registration_last_name', $last_name);
            update_user_meta( $user_id, 'last_name', $last_name );
        }
        
        // Set default role
        if ( ! empty( $options[ 'default_role' ] ) ) {
            $role = $options[ 'default_role' ];
            $role = apply_filters('saml_auth_registration_role', $role);
            $user->set_role( $role );
        }
        
        // Process group/role from attributes
        if ( ! empty( $options[ 'group' ] ) && isset( $attributes[ $options[ 'group' ] ] ) ) {
            $group = $attributes[ $options[ 'group' ] ][0];
            $group = apply_filters('saml_auth_registration_group', $group);
            update_user_meta( $user_id, 'saml_group', $group );
            
            // Use group as role if default role is not set
            if ( empty( $options[ 'default_role' ] ) ) {
                $user->set_role( strtolower( $group ) );
            }
        }
        
        // Process custom attributes
        if ( ! empty( $options[ 'custom_attributes' ] ) && is_array( $options[ 'custom_attributes' ] ) ) {
            foreach ( $options[ 'custom_attributes' ] as $attribute ) {
                if ( ! empty( $attribute[ 'idp_attribute_name' ] ) && 
                     ! empty( $attribute[ 'user_meta_field' ] ) &&
                     isset( $attributes[ $attribute[ 'idp_attribute_name' ] ] ) ) {
                    $value = $attributes[ $attribute[ 'idp_attribute_name' ] ][0];
                    $value = apply_filters('saml_auth_registration_attribute_value', $value, $attribute['user_meta_field']);
                    update_user_meta( 
                        $user_id, 
                        $attribute[ 'user_meta_field' ], 
                        $value 
                    );
                }
            }
        }
        
        do_action('saml_auth_registration_complete', $user, $attributes);
        
        // Log successful registration
        error_log( 'SAML User Registration Success: ' . $email );
        
        return $user;
    }
}

SAML_Auth::init();