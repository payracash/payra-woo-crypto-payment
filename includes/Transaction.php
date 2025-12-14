<?php
namespace Xxxwraithxxx\PayraCashCryptoPayment;

if (!defined('ABSPATH')) exit;

class Transaction extends Constants
{
    public function register()
    {
        add_action('admin_post_payra_search_transactions', [$this, 'payracacr_action_search_transactions']);
    }

    public function payracacr_action_search_transactions()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'payra-cash-crypto-payment'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'payra_search_transactions_nonce')) {
            wp_die(esc_html__('Invalid nonce', 'payra-cash-crypto-payment'));
        }

        $search = isset($_POST['transactions_search'])
            ? sanitize_text_field(wp_unslash($_POST['transactions_search']))
            : '';

        $results = $this->search_transactions($search);
        set_transient(self::SEARCH_RESULTS, $results ?? [], 10);
        wp_safe_redirect(admin_url('admin.php?page=' . esc_attr(self::SETTINGS_PAGE_SLUG) . '&tab=transactions'));

        exit;
    }

    /**
     * Get transactions with paginate
     */
    public function get_transactions($per_page = 50, $paged = 1)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare("
                SELECT
                    t.*,
                    s.name AS status_name,
                    s.description AS status_description,
                    n.label AS network_label,
                    n.block_explorer_url AS block_explorer_url
                FROM {$this->transactions_table} t
                LEFT JOIN {$this->transaction_statuses_table} s
                    ON t.status_id = s.id
                LEFT JOIN {$this->networks_table} n
                    ON t.network_id = n.id
                ORDER BY t.created_at DESC
                LIMIT %d OFFSET %d",
                $per_page,
                ($paged - 1) * $per_page
            ),
            ARRAY_A
        );
    }

    /**
    * Count all transactions
    */
    public function get_total_count()
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->transactions_table}");

        return $count;
    }

    public function search_transactions($search)
    {
        global $wpdb;

        if (!$search)
            return [];

        $search = trim($search);
        $like = '%' . $wpdb->esc_like($search) . '%';

        return $wpdb->get_results(
            $wpdb->prepare("
                SELECT
                    t.*,
                    s.name AS status_name,
                    s.description AS status_description,
                    n.label AS network_label,
                    n.block_explorer_url AS block_explorer_url
                FROM {$this->transactions_table} t
                LEFT JOIN {$this->transaction_statuses_table} s
                    ON t.status_id = s.id
                LEFT JOIN {$this->networks_table} n
                    ON t.network_id = n.id
                WHERE t.order_prefix_id LIKE %s
                    OR t.tx_hash LIKE %s
                    OR n.label LIKE %s
                    OR t.currency_symbol LIKE %s
                    OR t.sender LIKE %s
                    OR t.price LIKE %s
                    OR t.amount_in_wei LIKE %s
                    OR s.name LIKE %s
                ORDER BY t.id DESC",
                $like, $like, $like, $like, $like, $like, $like, $like
            ),
            ARRAY_A
        );
    }

}
