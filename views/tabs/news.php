<?php
    if (!defined('ABSPATH')) exit;

    $per_page = (new Xxxwraithxxx\PayraCashCryptoPayment\Settings)->get_data()['result_per_page_on_news'];
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination param
    $paged = isset($_GET['paged']) ? max(1, intval(wp_unslash( $_GET['paged']))) : 1;

    $news_obj = new Xxxwraithxxx\PayraCashCryptoPayment\News;
    $news = $news_obj->get_news($per_page, $paged);
    $total_items = $news_obj->get_news_total_count();
    $total_pages = ceil($total_items / $per_page);
?>
<div class="wrap">
    <div class="payra-card">
        <h2><?php esc_html_e('Latest Payra News', 'payra-cash-crypto-payment'); ?></h2>
        <?php if (!empty($news)) : ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><b><?php esc_html_e('Date', 'payra-cash-crypto-payment'); ?></b></th>
                        <th scope="col"><b><?php esc_html_e('Title', 'payra-cash-crypto-payment'); ?></b></th>
                        <th scope="col"><b><?php esc_html_e('Excerpt', 'payra-cash-crypto-payment'); ?></b></th>
                        <th scope="col"><b><?php esc_html_e('Option', 'payra-cash-crypto-payment'); ?></b></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($news as $item) :
                        $nonce = wp_create_nonce('payracacr_action_delete_news');
                        $delete_url = admin_url('admin-post.php?action=payracacr_action_delete_news&id_key=' . $item['id_key'] . '&_wpnonce=' . $nonce);
                    ?>
                        <tr>
                            <td><?php echo esc_html($item['date_at']); ?></td>
                            <td>
                                <a href="<?php echo esc_url($item['url']); ?>" target="_blank"><?php echo esc_html($item['title']); ?></a>
                            </td>
                            <td><?php echo esc_html($item['excerpt']); ?></td>
                            <td>
                                <a href="<?php echo esc_url($delete_url); ?>"><?php esc_html_e('Delete', 'payra-cash-crypto-payment'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- PAGINATION -->
            <div class="tablenav bottom" style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <a href="<?php echo esc_url(self::NEWS_ALL_URL); ?>" target="_blank" class="button button-secondary"><?php esc_html_e('Show all news', 'payra-cash-crypto-payment'); ?></a>
                </div>
                <?php include(PAYRACACR_CASH_PLUGIN_PATH . 'views/partials/pagination.php'); ?>
            </div>
        <?php else : ?>
            <p><?php esc_html_e('No news available.', 'payra-cash-crypto-payment'); ?></p>
        <?php endif; ?>
    </div>
    <div class="tablenav bottom">
        <?php include(PAYRACACR_CASH_PLUGIN_PATH . 'views/footer.php'); ?>
        <br class="clear">
    </div>
</div>
