<?php
$tab = isset( $_GET[ 'tab' ] ) ? sanitize_text_field( $_GET[ 'tab' ] ) : 'setup';
$tabs = [
    'setup'     => __( 'Service Provider Setup', 'saml_auth' ),
    'metadata'  => __( 'Service Provider Metadata', 'saml_auth' ),
    'attributes'=> __( 'Attribute Mapping', 'saml_auth' ),
    'buttons'   => __( 'Buttons', 'saml_auth' )
];
echo '<h2 class="nav-tab-wrapper saml-auth-settings-tabs">';
foreach ( $tabs as $key => $label ) {
    $active = ( $tab === $key ) ? ' nav-tab-active' : '';
    echo '<a href="?page=' . $this->page_slug . '&tab=' . esc_attr( $key ) . '" class="nav-tab' . esc_attr( $active ) . '">' . esc_html( $label ) . '</a>';
}
echo '</h2>';