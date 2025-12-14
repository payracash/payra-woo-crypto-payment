<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

class News extends Constants
{
    public function register()
    {
        add_action('admin_post_payracacr_action_delete_news', [$this, 'payracacr_action_delete_news']);
    }

    public function payracacr_action_delete_news(): void
    {
        global $wpdb;

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'payra-cash-crypto-payment'));
        }

        if (!isset($_GET['id_key'])) {
            wp_die(esc_html__('Missing id_key', 'payra-cash-crypto-payment'));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'payracacr_action_delete_news')) {
            wp_die(esc_html__('Invalid nonce', 'payra-cash-crypto-payment'));
        }

        $id_key = sanitize_text_field(wp_unslash($_GET['id_key']));

        $wpdb->update(
            self::$db_table_news,
            ['is_hidden' => 1],
            ['id_key' => $id_key],
            ['%d'],
            ['%s']
        );

        wp_safe_redirect(admin_url('admin.php?page=' . esc_attr(self::SETTINGS_PAGE_SLUG) . '&tab=news'));

        exit;
    }

    public function get_news($per_page = 50, $paged = 1)
    {
        global $wpdb;

        $offset = ($paged - 1) * $per_page;
        $query = $wpdb->prepare("
            SELECT *
            FROM {$this->news_table}
            WHERE is_hidden = 0
            ORDER BY date_at DESC
            LIMIT %d OFFSET %d",
            $per_page,
            $offset
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results($query, ARRAY_A);
    }

    public function get_news_total_count()
    {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->news_table} WHERE is_hidden = 0");
    }

    public function fetch_news()
    {
        global $wpdb;

        $cached_news = get_transient(self::NEWS_LAST_FETCH);
        if ($cached_news !== false) {
            return;
        }

        $response = wp_remote_get(self::NEWS_ENDPOINT);
        if (is_wp_error($response)) {
            return;
        }

        $json_resp = json_decode(wp_remote_retrieve_body($response), true);
        if (!$json_resp || empty($json_resp['items'])) {
            return;
        }

        // Check updatedAt
        $lastUpdatedAt    = get_option(self::LAST_NEWS_UPDATED_AT);
        $currentUpdatedAt = $json_resp['updatedAt'] ?? null;

        if ($currentUpdatedAt && $currentUpdatedAt !== $lastUpdatedAt) {

            foreach ($json_resp['items'] as $item) {
                // Generate hash title + url
                $hash = md5($item['title'] . $item['url']);

                // Check if hash exists
                $exists = $wpdb->get_var(
                    $wpdb->prepare("SELECT COUNT(*) FROM {$this->news_table} WHERE id_key = %s", $hash)
                );

                if (!$exists) {
                    $wpdb->insert(
                        $this->news_table, [
                            'id_key'    => $hash,
                            'title'     => $item['title'],
                            'url'       => $item['url'],
                            'excerpt'   => $item['excerpt'],
                            'date_at'   => $item['date'],
                            'is_hidden' => 0,
                        ], [
                            '%s','%s','%s','%s','%s','%d'
                        ]
                    );
                }
            }
            // Flag news
            update_option(self::IS_UNREAD_NEWS, 1);
            update_option(self::LAST_NEWS_UPDATED_AT, $currentUpdatedAt);
        }
        // Update last check
        set_transient(self::NEWS_LAST_FETCH, true, DAY_IN_SECONDS);
    }

}
