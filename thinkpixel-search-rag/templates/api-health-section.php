<?php

namespace ThinkPixel\Core;
?>
<h2><?php esc_html_e('API Health Status', Strings::Domain); ?></h2>

<table class="form-table">
    <tr>
        <th scope="row"><?php esc_html_e('Status', Strings::Domain); ?>:</th>
        <td id="thinkpixel-api-status"><?php esc_html_e('Checking ThinkPixel API availability...', Strings::Domain); ?></td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('Version', Strings::Domain); ?>:</th>
        <td id="thinkpixel-api-version"><?php esc_html_e('Unknown', Strings::Domain); ?></td>
    </tr>
</table>
