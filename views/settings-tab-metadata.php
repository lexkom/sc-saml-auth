<?php
$options = $this->saml_get_option( 'setup' );

$sp_entity_id = site_url('/' . SAML_Auth::$SAML_SP_ENTITY_ID . '/');
$acs_url = site_url('/' . SAML_Auth::$SAML_ACS_URL . '/');
$sls_url = site_url('/' . SAML_Auth::$SAML_SLS_URL . '/');
$metadata_url = site_url('/' . SAML_Auth::$SAML_METADATA_URL . '/');
$nameid_format = SAML_Auth::$SAML_DEFAULT_NAMEID_FORMAT;
?>

<table class="form-table" role="presentation">
    <tr valign="top">
        <th scope="row"><?php _e('Metadata URL', 'saml_auth'); ?></th>
        <td>
            <?php
            if ( empty( $options[ 'idp_entity_id' ] ) ||
                 empty( $options[ 'idp_sso_url' ] ) ||
                 empty( $options[ 'idp_x509' ] )
            ) : ?>
                <p class="warning"><?php _e('Next fields have to be filled: IdP Entity ID, IdP SSO URL, IdP X.509 Cert.', 'saml_auth'); ?></p>
            <?php else : ?>
                <code><?php echo esc_url( $metadata_url ); ?></code>
            <?php endif; ?>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Metadata XML File', 'saml_auth'); ?></th>
        <td>
            <?php
            if ( empty( $options[ 'idp_entity_id' ] ) ||
                empty( $options[ 'idp_sso_url' ] ) ||
                empty( $options[ 'idp_x509' ] )
            ) : ?>
                <p class="warning"><?php _e('Next fields have to be filled: IdP Entity ID, IdP SSO URL, IdP X.509 Cert.', 'saml_auth'); ?></p>
            <?php else : ?>
                <a href="<?php echo esc_url( $metadata_url ); ?>" class="button" download="saml-metadata.xml"><?php _e('Download XML', 'saml_auth'); ?></a>
            <?php endif; ?>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('SP-EntityID / Issuer', 'saml_auth'); ?></th>
        <td><code><?php echo esc_html( $sp_entity_id ); ?></code></td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('ACS URL', 'saml_auth'); ?></th>
        <td><code><?php echo esc_html( $acs_url ); ?></code></td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('SLS URL', 'saml_auth'); ?></th>
        <td><code><?php echo esc_html( $sls_url ); ?></code></td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('NameID Format', 'saml_auth'); ?></th>
        <td><code><?php echo esc_html( $nameid_format ); ?></code></td>
    </tr>
</table>