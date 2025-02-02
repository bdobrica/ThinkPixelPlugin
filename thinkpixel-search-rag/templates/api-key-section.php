<?php

namespace ThinkPixel\Core;
?>
<h2><?php esc_html_e('API Key', Strings::Domain); ?></h2>
<p><?php esc_html_e('Manage your ThinkPixel API Key.', Strings::Domain); ?></p>

<form method="post" action="">
    <?php
    // This is a simple example of a POST form. 
    // You could also do this with AJAX if you prefer.
    // WP security (nonce field)
    wp_nonce_field(Strings::ApiKeyAction, Strings::ApiKeyNonce);
    ?>

    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Current API Key', Strings::Domain); ?></th>
            <td>
                <?php if (! empty($api_key)) : ?>
                    <input type="text" readonly value="<?php echo esc_attr($api_key); ?>" size="40" />
                <?php else : ?>
                    <input type="text" value="" size="40" />
                    <button type="submit" name="thinkpixel_update_api_key" class="button button-primary">
                        <?php esc_html_e('Save API Key', Strings::Domain); ?>
                    </button>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Actions', Strings::Domain); ?></th>
            <td>
                <?php if (! empty($api_key)) : ?>
                    <button type="submit" name="thinkpixel_regenerate_api_key" class="button button-secondary">
                        <?php esc_html_e('Regenerate API Key', Strings::Domain); ?>
                    </button>
                <?php else : ?>
                    <button type="submit" name="thinkpixel_generate_api_key" class="button button-primary">
                        <?php esc_html_e('Generate API Key', Strings::Domain); ?>
                    </button>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</form>
