<?php if (!defined('ABSPATH')) exit; ?>
<div class="tablenav-pages">
    <span class="displaying-num"><?php echo esc_html($total_items); ?> <?php esc_html_e('items', 'payra-cash-crypto-payment'); ?></span>
    <span class="pagination-links">
        <?php if ($paged > 1): ?>
            <a class="first-page button" href="<?php echo esc_url(add_query_arg('paged', 1)); ?>">
                <span class="screen-reader-text"><?php esc_html_e('First page', 'payra-cash-crypto-payment'); ?></span>
                <span aria-hidden="true">«</span>
            </a>
            <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', $paged - 1)); ?>">
                <span class="screen-reader-text"><?php esc_html_e('Previous page', 'payra-cash-crypto-payment'); ?></span>
                <span aria-hidden="true">‹</span>
            </a>
        <?php else: ?>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
        <?php endif; ?>
        <span class="screen-reader-text"><?php esc_html_e('Current Page', 'payra-cash-crypto-payment'); ?></span>
        <span id="table-paging" class="paging-input">
            <span class="tablenav-paging-text">
                <?php echo esc_html( $paged ); ?>
                <?php esc_html_e('of', 'payra-cash-crypto-payment'); ?>
                <span class="total-pages"><?php echo esc_html($total_pages); ?></span>
            </span>
        </span>
        <?php if ($paged < $total_pages): ?>
            <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', $paged + 1)); ?>">
                <span class="screen-reader-text"><?php esc_html_e('Next page', 'payra-cash-crypto-payment'); ?></span>
                <span aria-hidden="true">›</span>
            </a>
            <a class="last-page button" href="<?php echo esc_url( add_query_arg( 'paged', $total_pages ) ); ?>">
                <span class="screen-reader-text"><?php esc_html_e('Last page', 'payra-cash-crypto-payment'); ?></span>
                <span aria-hidden="true">»</span>
            </a>
        <?php else: ?>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
        <?php endif; ?>
    </span>
</div>
