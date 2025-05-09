# SAML Authentication for WordPress

Plugin for user authentication via SAML 2.0 Identity Provider (IdP).

## Description

The plugin allows you to configure Single Sign-On (SSO) in WordPress via SAML 2.0. Supports:
- Authentication via SAML IdP
- Automatic user registration
- Attribute mapping configuration
- User role configuration
- SSO button display on login page

## Installation

1. Upload the `sc-saml-auth` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to SAML Auth settings and configure the IdP connection

## Configuration

1. In the "Setup" section, specify:
   - Your IdP's Entity ID
   - IdP SSO URL
   - IdP SLO URL (optional)
   - IdP X.509 certificate

2. In the "Attributes" section, configure attribute mapping:
   - Username
   - Last name
   - Group
   - Default role
   - Additional attributes

3. In the "Buttons" section, configure SSO button display

## Usage

### Login Button Shortcode

```php
[saml_sso_button]
```

Additional parameters:
```php
[saml_sso_button class="custom-class" style="margin: 20px;" text="Login via SSO"]
```

### Available Hooks

#### Action Hooks

##### Authentication and Authorization

1. **saml_auth_before_process_response**
   - Fires before processing the SAML response
   - Use case: Logging, validation, or custom processing before authentication
   ```php
   add_action('saml_auth_before_process_response', function() {
       error_log('Processing SAML response started');
   });
   ```

2. **saml_auth_before_user_registration**
   - Fires before registering a new user
   - Parameters: `$email`, `$attributes`
   ```php
   add_action('saml_auth_before_user_registration', function($email, $attributes) {
       error_log("Attempting to register user: {$email}");
   });
   ```

3. **saml_auth_after_user_registration**
   - Fires after user registration is complete
   - Parameters: `$user`, `$attributes`
   ```php
   add_action('saml_auth_after_user_registration', function($user, $attributes) {
       wp_mail($user->user_email, 'Welcome', 'Your account has been created');
   });
   ```

4. **saml_auth_before_set_current_user**
   - Fires before setting the current user
   - Parameters: `$user`
   ```php
   add_action('saml_auth_before_set_current_user', function($user) {
       update_user_meta($user->ID, 'last_saml_login', current_time('mysql'));
   });
   ```

5. **saml_auth_after_successful_authentication**
   - Fires after successful authentication
   - Parameters: `$user`
   ```php
   add_action('saml_auth_after_successful_authentication', function($user) {
       wp_redirect(home_url('/dashboard'));
       exit;
   });
   ```

6. **saml_auth_authentication_failed**
   - Fires when authentication fails
   - Parameters: `$email`, `$attributes`
   ```php
   add_action('saml_auth_authentication_failed', function($email, $attributes) {
       error_log("Failed login attempt for {$email}");
   });
   ```

7. **saml_auth_saml_error**
   - Fires when SAML error occurs
   - Parameters: `$errors`, `$reason`
   ```php
   add_action('saml_auth_saml_error', function($errors, $reason) {
       error_log("SAML Error: " . implode(', ', $errors));
       error_log("Reason: " . $reason);
   });
   ```

##### Logout Process

8. **saml_auth_before_logout**
   - Fires before SAML logout process
   ```php
   add_action('saml_auth_before_logout', function() {
       error_log('Starting SAML logout process');
   });
   ```

9. **saml_auth_before_wp_logout**
   - Fires before WordPress logout
   ```php
   add_action('saml_auth_before_wp_logout', function() {
       wp_cache_flush();
   });
   ```

10. **saml_auth_after_wp_logout**
    - Fires after WordPress logout
    ```php
    add_action('saml_auth_after_wp_logout', function() {
        wp_redirect(home_url('/goodbye'));
        exit;
    });
    ```

##### User Registration

11. **saml_auth_registration_complete**
    - Fires after user registration is complete
    - Parameters: `$user`, `$attributes`
    ```php
    add_action('saml_auth_registration_complete', function($user, $attributes) {
        // Send welcome email
        wp_mail($user->user_email, 'Welcome', 'Your account has been created');
    });
    ```

#### Filter Hooks

##### Attributes and Data

12. **saml_auth_attributes**
    - Filters SAML attributes before processing
    - Parameters: `$attributes`
    ```php
    add_filter('saml_auth_attributes', function($attributes) {
        // Add custom attribute
        $attributes['custom_field'] = 'value';
        return $attributes;
    });
    ```

13. **saml_auth_registration_email**
    - Filters email during registration
    - Parameters: `$email`
    ```php
    add_filter('saml_auth_registration_email', function($email) {
        return strtolower($email);
    });
    ```

14. **saml_auth_registration_attributes**
    - Filters all attributes during registration
    - Parameters: `$attributes`
    ```php
    add_filter('saml_auth_registration_attributes', function($attributes) {
        // Modify attributes before registration
        return $attributes;
    });
    ```

15. **saml_auth_registration_username**
    - Filters username during registration
    - Parameters: `$username`
    ```php
    add_filter('saml_auth_registration_username', function($username) {
        return sanitize_user($username);
    });
    ```

16. **saml_auth_registration_password**
    - Filters password during registration
    - Parameters: `$password`
    ```php
    add_filter('saml_auth_registration_password', function($password) {
        return wp_generate_password(12, true);
    });
    ```

17. **saml_auth_registration_first_name**
    - Filters first name during registration
    - Parameters: `$first_name`
    ```php
    add_filter('saml_auth_registration_first_name', function($first_name) {
        return ucfirst(strtolower($first_name));
    });
    ```

18. **saml_auth_registration_last_name**
    - Filters last name during registration
    - Parameters: `$last_name`
    ```php
    add_filter('saml_auth_registration_last_name', function($last_name) {
        return ucfirst(strtolower($last_name));
    });
    ```

19. **saml_auth_registration_role**
    - Filters user role during registration
    - Parameters: `$role`
    ```php
    add_filter('saml_auth_registration_role', function($role) {
        // Set role based on email domain
        if (strpos($email, '@company.com') !== false) {
            return 'editor';
        }
        return $role;
    });
    ```

20. **saml_auth_registration_group**
    - Filters user group during registration
    - Parameters: `$group`
    ```php
    add_filter('saml_auth_registration_group', function($group) {
        return strtolower($group);
    });
    ```

21. **saml_auth_registration_attribute_value**
    - Filters specific attribute value during registration
    - Parameters: `$value`, `$field`
    ```php
    add_filter('saml_auth_registration_attribute_value', function($value, $field) {
        if ($field === 'department') {
            return strtoupper($value);
        }
        return $value;
    }, 10, 2);
    ```

## Requirements

- WordPress 5.0 or higher
- PHP 7.3 or higher
- PHP OpenSSL extension
- PHP DOM extension

## License

GPL-2.0+ 