<?php
// Получение сохранённых значений
$options = $this->saml_get_option( 'setup' );
$allow_user_registration = isset( $options[ 'allow_user_registration' ] ) ? (bool)$options[ 'allow_user_registration' ] : true;

?>
<table class="form-table" role="presentation">
    <tr valign="top">
        <th scope="row"><label for="idp_entity_id"><?php _e('IdP Entity ID or Issuer<sup>*</sup>:', 'saml_auth'); ?></label></th>
        <td><input type="text" name="saml_settings[idp_entity_id]" value="<?php echo esc_attr( $options[ 'idp_entity_id' ] ?? '' ); ?>" class="regular-text" required /></td>
    </tr>

    <tr valign="top">
        <th scope="row"><label for="idp_sso_url"><?php _e('IdP SSO URL<sup>*</sup>:', 'saml_auth'); ?></label></th>
        <td><input type="url" name="saml_settings[idp_sso_url]" value="<?php echo esc_attr( $options[ 'idp_sso_url' ] ?? '' ); ?>" class="regular-text" required /></td>
    </tr>

    <tr valign="top">
        <th scope="row"><label for="idp_slo_url"><?php _e('SLO URL:', 'saml_auth'); ?></label></th>
        <td><input type="url" name="saml_settings[idp_slo_url]" value="<?php echo esc_attr( $options[ 'idp_slo_url' ] ?? '' ); ?>" class="regular-text" /></td>
    </tr>

    <tr valign="top">
        <th scope="row">
            <label for="idp_x509"><?php _e('IdP X.509 Cert<sup>*</sup>:', 'saml_auth'); ?></label>
            <p class="description"><?php _e('Format of the certificate:<br>-----BEGIN CERTIFICATE-----<br>xxxxxxxxxxxxxxxxxxxxxxxxxxx<br>-----END CERTIFICATE-----', 'saml_auth'); ?></p>
        </th>
        <td>
            <textarea name="saml_settings[idp_x509]" rows="8" class="large-text code" required><?php echo esc_textarea( $options[ 'idp_x509' ] ?? '' ); ?></textarea>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><label for="allow_user_registration"><?php _e('Allow New User Registration', 'saml_auth'); ?></label></th>
        <td>
            <input type="checkbox" name="saml_settings[allow_user_registration]" value="1" <?php checked( $allow_user_registration, true ); ?> />
            <p class="description"><?php _e('Allow automatic creation of new users if they do not exist.', 'saml_auth'); ?></p>
        </td>
    </tr>
</table>

<p>
    <button type="button" class="button button-secondary" id="check-idp-connection"><?php _e('Check IdP Connection', 'saml_auth'); ?></button>
    <span id="idp-check-result" style="margin-left: 10px;"></span>
</p>