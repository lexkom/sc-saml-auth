<?php
$options = $this->saml_get_option( 'buttons' );
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php _e('Add SSO button to login form', 'saml_auth'); ?></th>
        <td>
            <input type="checkbox" name="saml_settings[saml_add_sso_button]" value="1" <?php checked( $options[ 'saml_add_sso_button' ] ?? 1, true ); ?> />
            <p class="description"><?php _e('Enable to automatically display an SSO login button on the default WordPress login form.', 'saml_auth'); ?></p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php _e('Shortcode for manual placement', 'saml_auth'); ?></th>
        <td>
            <code>[saml_sso_button]</code>
            <p class="description"><?php _e('Use this shortcode to place the SSO login button anywhere on your site.', 'saml_auth'); ?></p>
        </td>
    </tr>
</table>
