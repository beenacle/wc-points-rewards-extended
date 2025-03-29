<?php
/**
 * WC Points Rewards Extended CSV Import
 *
 * @package WC_Points_Rewards_Extended
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Points_Rewards_Extended_CSV_Import Class
 */
class WC_Points_Rewards_Extended_CSV_Import {

    /**
     * Initialize the CSV import functionality
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_import_menu'));
        add_action('admin_init', array($this, 'handle_csv_import'));
    }

    /**
     * Add import menu item
     */
    public function add_import_menu() {
        add_submenu_page(
            'woocommerce',
            __('Import Points', 'wc-points-rewards-extended'),
            __('Import Points', 'wc-points-rewards-extended'),
            'manage_woocommerce',
            'wc-points-rewards-import',
            array($this, 'render_import_page')
        );
    }

    /**
     * Render the import page
     */
    public function render_import_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Import Points', 'wc-points-rewards-extended'); ?></h1>

            <div class="card">
                <h2><?php echo esc_html__('Import Points via CSV', 'wc-points-rewards-extended'); ?></h2>
                <p><?php echo esc_html__('Upload a CSV file to import points for users. The CSV should have the following columns:', 'wc-points-rewards-extended'); ?></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><?php echo esc_html__('user_email - The email address of the user', 'wc-points-rewards-extended'); ?></li>
                    <li><?php echo esc_html__('points - The number of points to add (can be negative to deduct points)', 'wc-points-rewards-extended'); ?></li>
                    <li><?php echo esc_html__('note - Optional note about the points adjustment', 'wc-points-rewards-extended'); ?></li>
                </ul>

                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('wc_points_rewards_import', 'wc_points_rewards_import_nonce'); ?>
                    <input type="file" name="points_csv" accept=".csv" required>
                    <p class="submit">
                        <input type="submit" name="import_points" class="button button-primary" value="<?php echo esc_attr__('Import Points', 'wc-points-rewards-extended'); ?>">
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Handle the CSV import
     */
    public function handle_csv_import() {
        if (!isset($_POST['import_points']) || !isset($_FILES['points_csv'])) {
            return;
        }

        if (!check_admin_referer('wc_points_rewards_import', 'wc_points_rewards_import_nonce')) {
            wp_die(__('Invalid nonce', 'wc-points-rewards-extended'));
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to import points', 'wc-points-rewards-extended'));
        }

        $file = $_FILES['points_csv'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_die(__('Error uploading file', 'wc-points-rewards-extended'));
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            wp_die(__('Error opening file', 'wc-points-rewards-extended'));
        }

        // Skip header row
        fgetcsv($handle);

        $success_count = 0;
        $error_count = 0;
        $errors = array();

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 2) {
                continue;
            }

            $user_email = sanitize_email($data[0]);
            $points = intval($data[1]);
            $note = isset($data[2]) ? sanitize_text_field($data[2]) : '';

            if (empty($user_email) || !is_email($user_email)) {
                $error_count++;
                $errors[] = sprintf(__('Invalid email address: %s', 'wc-points-rewards-extended'), $user_email);
                continue;
            }

            $user = get_user_by('email', $user_email);
            if (!$user) {
                $error_count++;
                $errors[] = sprintf(__('User not found: %s', 'wc-points-rewards-extended'), $user_email);
                continue;
            }

            if ($points === 0) {
                $error_count++;
                $errors[] = sprintf(__('Invalid points value for user: %s', 'wc-points-rewards-extended'), $user_email);
                continue;
            }

            // Set points balance using the parent plugin's function
            WC_Points_Rewards_Manager::set_points_balance(
                $user->ID,
                $points,
                'admin_adjustment'
            );

            $success_count++;
        }

        fclose($handle);

        // Show results
        $message = sprintf(
            __('Import completed. Successfully imported: %d, Errors: %d', 'wc-points-rewards-extended'),
            $success_count,
            $error_count
        );

        if (!empty($errors)) {
            $message .= '<br><br>' . __('Errors:', 'wc-points-rewards-extended') . '<br>' . implode('<br>', $errors);
        }

        add_settings_error(
            'wc_points_rewards_import',
            'import_complete',
            $message,
            $error_count > 0 ? 'error' : 'success'
        );
    }
}