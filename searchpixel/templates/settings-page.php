<?php

namespace SearchPixel\Core;
?>
<div class="wrap">
    <h1><?php esc_html_e('SearchPixel Settings', Strings::Domain); ?></h1>

    <!-- SECTION 1: API KEY -->
    <?php $this->render_api_key_section(); ?>

    <!-- SECTION 2: API HEALTH STATUS -->
    <?php $this->render_api_health_section(); ?>

    <!-- SECTION 3: PAGE INDEXING STATS + BULK INDEXING -->
    <?php $this->render_page_indexing_section(); ?>

    <!-- SECTION 4: SKIP ITEMS -->
    <?php $this->render_skip_items_section(); ?>
</div>
