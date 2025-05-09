<?php
global $wp_roles;
$roles = $wp_roles->get_names();
$options = $this->saml_get_option( 'attributes' );
?>

<table class="form-table" role="presentation">
    <tr valign="top">
        <th scope="row"><?php _e('Username (required)', 'saml_auth'); ?></th>
        <td><code>NameID</code></td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Email (required)', 'saml_auth'); ?></th>
        <td><code>NameID</code></td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('First Name', 'saml_auth'); ?></th>
        <td>
            <input type="text" name="saml_settings[first_name]" value="<?php echo esc_attr( $options[ 'first_name' ] ?? '' ); ?>" class="regular-text" />
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Last Name', 'saml_auth'); ?></th>
        <td>
            <input type="text" name="saml_settings[last_name]" value="<?php echo esc_attr( $options[ 'last_name' ] ?? '' ); ?>" class="regular-text" />
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Group/Role', 'saml_auth'); ?></th>
        <td>
            <input type="text" name="saml_settings[group]" value="<?php echo esc_attr( $options[ 'group' ] ?? '' ); ?>" class="regular-text" />
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Custom Attributes', 'saml_auth'); ?></th>
        <td>
            <div id="saml-custom-attributes-wrapper">
                <?php if (!empty( $options[ 'custom_attributes' ] ) && is_array( $options[ 'custom_attributes' ] )) : ?>
                    <?php foreach ( $options[ 'custom_attributes' ] as $pair) : ?>
                        <div class="saml-custom-attribute">
                            <input type="text" name="saml_settings[custom_attribute_name][]" value="<?php echo esc_attr($pair['idp_attribute_name']); ?>" placeholder="<?php _e('IdP Attribute Name', 'saml_auth'); ?>" />
                            <input type="text" name="saml_settings[custom_attribute_meta][]" value="<?php echo esc_attr($pair['user_meta_field']); ?>" placeholder="<?php _e('User Meta Field', 'saml_auth'); ?>" />
                            <button type="button" class="button saml-remove-attribute"><?php _e('Remove', 'saml_auth'); ?></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="button" id="saml-add-attribute"><?php _e('Add Attribute', 'saml_auth'); ?></button>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><?php _e('Default User Role', 'saml_auth'); ?></th>
        <td>
            <select name="saml_settings[default_role]">
                <?php foreach ($roles as $role_key => $role_name) : ?>
                    <option value="<?php echo esc_attr($role_key); ?>" <?php selected($role_key, ( $options[ 'default_role' ] ?? 'subscriber' ) ); ?>>
                        <?php echo esc_html($role_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
</table>

<script>
    document.getElementById('saml-add-attribute').addEventListener('click', function() {
        const wrapper = document.getElementById('saml-custom-attributes-wrapper');
        const div = document.createElement('div');
        div.className = 'saml-custom-attribute';
        div.innerHTML = `
        <input type="text" name="saml_settings[custom_attribute_name][]" placeholder="<?php _e('IdP Attribute Name', 'saml_auth'); ?>" />
        <input type="text" name="saml_settings[custom_attribute_meta][]" placeholder="<?php _e('User Meta Field', 'saml_auth'); ?>" />
        <button type="button" class="button saml-remove-attribute"><?php _e('Remove', 'saml_auth'); ?></button>
    `;
        wrapper.appendChild(div);
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('saml-remove-attribute')) {
            e.target.parentElement.remove();
        }
    });
</script>