<?php
/**
 * Plugin Name: WooCommerce Points and Rewards Extended
 * Plugin URI: https://github.com/beenacle/wc-points-rewards-extended
 * Description: Extends WooCommerce Points and Rewards with role-based multipliers, CSV import, and auto-apply points
 * Version: 1.0.1
 * Author: Beenacle Technologies Pvt. Ltd.
 * Author URI: https://beenacle.com
 * Text Domain: wc-points-rewards-extended
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 7.5
 * WC tested up to: 9.8
 *
 * @package WC_Points_Rewards_Extended
 */


if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if required plugins are active
 *
 * @return bool True if all dependencies are met
 */
function wc_points_rewards_extended_check_dependencies() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' .
                esc_html__('WooCommerce Points and Rewards Extended requires WooCommerce to be installed and active.', 'wc-points-rewards-extended') .
                '</p></div>';
        });
        return false;
    }

if (!class_exists('WC_Points_Rewards')) {
    add_action('admin_notices', function() {
            $message = '<div class="error"><p>' .
                esc_html__('WooCommerce Points and Rewards Extended requires WooCommerce Points and Rewards to be installed and active.', 'wc-points-rewards-extended') .
                '</p><p>' . esc_html__('Please ensure that:', 'wc-points-rewards-extended') . '</p>';
            $message .= '<ul style="list-style-type: disc; margin-left: 20px;">';
            $message .= '<li>' . esc_html__('WooCommerce Points and Rewards is installed and activated', 'wc-points-rewards-extended') . '</li>';
            $message .= '<li>' . esc_html__('WooCommerce Points and Rewards is loaded before this plugin', 'wc-points-rewards-extended') . '</li>';
            $message .= '</ul></div>';

            echo wp_kses_post($message);
        });
        return false;
    }

    return true;
}

/**
 * Initialize the plugin
 */
function wc_points_rewards_extended_init() {
    if (!wc_points_rewards_extended_check_dependencies()) {
    return;
}
    WC_Points_Rewards_Extended();
}

add_action('plugins_loaded', 'wc_points_rewards_extended_init', 20);

/**
 * Main plugin class
 */
class WC_Points_Rewards_Extended {
    /**
     * Plugin instance
     *
     * @var WC_Points_Rewards_Extended
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return WC_Points_Rewards_Extended
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Settings integration
        add_filter('wc_points_rewards_settings', array($this, 'add_multiplier_settings'));
        add_action('woocommerce_admin_field_role_multipliers', array($this, 'render_role_multipliers_field'));
        add_filter('woocommerce_admin_settings_sanitize_option_wc_points_rewards_role_multipliers', array($this, 'sanitize_role_multipliers'), 10, 2);

        // Points calculation
        add_filter('wc_points_rewards_points_earned_for_purchase', array($this, 'calculate_points_earned_for_purchase'), 10, 2);
        add_filter('woocommerce_points_earned_for_order_item', array($this, 'apply_role_points_multiplier'), 10, 5);
        add_filter('wc_points_rewards_increase_points', array($this, 'apply_role_points_multiplier'), 10, 5);

        // Prevent earning points on points spent
        add_filter('wc_points_rewards_points_earned_for_purchase', array($this, 'adjust_points_for_points_spent'), 20, 2);

        // Auto-apply points
        add_action('woocommerce_before_checkout_form', array($this, 'auto_apply_points_discount'), 20);

        // Include CSV import functionality
        require_once plugin_dir_path(__FILE__) . 'includes/class-wc-points-rewards-extended-csv-import.php';
        new WC_Points_Rewards_Extended_CSV_Import();
    }

    /**
     * Add multiplier settings to the Points & Rewards settings
     *
     * @param array $settings Existing settings
     * @return array Modified settings
     */
    public function add_multiplier_settings($settings) {
        $settings[] = array(
            'name' => __('Role-Based Point Multipliers', 'wc-points-rewards-extended'),
            'type' => 'title',
            'desc' => __('Configure point multipliers for different user roles.', 'wc-points-rewards-extended'),
            'id' => 'wc_points_rewards_role_multipliers'
        );

        $settings[] = array(
            'type' => 'role_multipliers',
            'id' => 'wc_points_rewards_role_multipliers'
        );

        $settings[] = array(
            'name' => __('Coupons Bypassing Role Multipliers', 'wc-points-rewards-extended'),
            'type' => 'multiselect',
            'class' => 'wc-enhanced-select',
            'css' => 'width: 50%;',
            'desc' => __('Select coupons that will bypass role-based point multipliers. Points will be calculated normally for these coupons.', 'wc-points-rewards-extended'),
            'id' => 'wc_points_rewards_bypass_multiplier_coupons',
            'options' => $this->get_all_coupons(),
            'custom_attributes' => array(
                'data-placeholder' => __('Select coupons', 'wc-points-rewards-extended')
            )
        );

        $settings[] = array('type' => 'sectionend', 'id' => 'wc_points_rewards_role_multipliers');

        return $settings;
    }

    /**
     * Get all active coupons
     */
    private function get_all_coupons() {
        $coupons = array();
        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'shop_coupon',
            'post_status' => 'publish'
        );

        $posts = get_posts($args);
        foreach ($posts as $post) {
            $coupon = new WC_Coupon($post->ID);
            $coupons[$coupon->get_code()] = $coupon->get_code();
        }

        return $coupons;
    }

    /**
     * Render the role multipliers field
     *
     * @param array $field Field data
     */
    public function render_role_multipliers_field($field) {
        if (!isset($field['title']) || !isset($field['id'])) {
            return;
        }

        $multipliers = get_option($field['id'], $field['default']);
        $roles = $this->get_sorted_roles();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field['id']); ?>">
                    <?php echo wp_kses_post($field['title']); ?>
                    <?php echo isset($field['desc_tip']) ? wc_help_tip($field['desc_tip']) : ''; ?>
                </label>
            </th>
            <td class="forminp forminp-role-multipliers">
                <?php $this->render_multipliers_table($field['id'], $multipliers, $roles); ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Get sorted list of roles
     *
     * @return array Sorted roles
     */
    private function get_sorted_roles() {
        $roles = get_editable_roles();
        $role_names = array();

        foreach ($roles as $role_id => $role_details) {
            $role_names[$role_id] = translate_user_role($role_details['name']);
        }

        asort($role_names);
        return $role_names;
    }

    /**
     * Render the multipliers table
     *
     * @param string $field_id Field ID
     * @param array $multipliers Current multipliers
     * @param array $roles Sorted roles
     */
    private function render_multipliers_table($field_id, $multipliers, $roles) {
        ?>
        <table class="form-table">
            <tbody>
                <?php foreach ($roles as $role_id => $role_name) :
                    $multiplier = isset($multipliers[$role_id]) ? $multipliers[$role_id] : 1;
                ?>
                <tr>
                    <th scope="row" class="titledesc">
                        <label for="<?php echo esc_attr($field_id . '_' . $role_id); ?>">
                            <?php echo esc_html($role_name); ?>
                        </label>
                    </th>
                    <td class="forminp">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            id="<?php echo esc_attr($field_id . '_' . $role_id); ?>"
                            name="<?php echo esc_attr($field_id); ?>[<?php echo esc_attr($role_id); ?>]"
                            value="<?php echo esc_attr($multiplier); ?>"
                            class="wc_input_decimal"
                            style="width: 80px;"
                        />
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="description">
            <?php echo esc_html__('Note: A multiplier of 1 means no change in points earned. Set to 0 to prevent earning points.', 'wc-points-rewards-extended'); ?>
        </p>
        <?php
    }

    /**
     * Sanitize role multipliers
     *
     * @param mixed $value Input value
     * @param array $option Option data
     * @return array Sanitized multipliers
     */
    public function sanitize_role_multipliers($value, $option) {
        if (!is_array($value)) {
            return $option['default'];
        }

        $sanitized_input = array();
        foreach ($value as $role => $multiplier) {
            $sanitized_role = sanitize_text_field($role);
            $sanitized_multiplier = floatval($multiplier);

            if ($sanitized_multiplier >= 0) {
                $sanitized_input[$sanitized_role] = $sanitized_multiplier;
            }
        }

        return $sanitized_input;
    }

    /**
     * Auto-apply points discount at checkout
     */
    public function auto_apply_points_discount() {
        if (!is_checkout() || !is_user_logged_in()) {
            return;
        }

        // Check if auto-apply is enabled
        if (get_option('wc_points_rewards_auto_apply', 'yes') !== 'yes') {
            return;
        }

        $existing_discount = WC_Points_Rewards_Discount::get_discount_code();
        if (!empty($existing_discount) && WC()->cart->has_discount($existing_discount)) {
            return;
        }

        $available_points = WC_Points_Rewards_Manager::get_users_points(get_current_user_id());
        if ($available_points <= 0) {
            return;
        }

        $max_discount = WC_Points_Rewards_Cart_Checkout::get_discount_for_redeeming_points(true);
        if ($max_discount <= 0) {
            return;
        }

        WC()->session->set('wc_points_rewards_discount_amount', $available_points);
        WC()->cart->add_discount(WC_Points_Rewards_Discount::generate_discount_code());
    }

    /**
     * Apply role-based point multiplier
     */
    public function apply_role_points_multiplier($points, $order_or_product, $item_key = null, $item = null, $order = null) {
        // Check for bypass coupons in cart context
        if (function_exists('WC') && WC()->cart) {
            $bypass_coupons = get_option('wc_points_rewards_bypass_multiplier_coupons', array());
            $applied_coupons = WC()->cart->get_applied_coupons();

            foreach ($applied_coupons as $coupon_code) {
                if (in_array($coupon_code, $bypass_coupons)) {
                    return $points;
                }
            }
        }

        // Check for bypass coupons in order context
        if ($order instanceof WC_Order || $order_or_product instanceof WC_Order) {
            $order = $order ?: $order_or_product;
            $bypass_coupons = get_option('wc_points_rewards_bypass_multiplier_coupons', array());
            $applied_coupons = $order->get_coupon_codes();

            foreach ($applied_coupons as $coupon_code) {
                if (in_array($coupon_code, $bypass_coupons)) {
                    return $points;
                }
            }
        }

        // If no bypass coupons are found, proceed with role-based multipliers
        $user_id = $this->get_user_id($order_or_product, $order);
        if (!$user_id) {
            return $points;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return $points;
        }

        $role_multipliers = get_option('wc_points_rewards_role_multipliers', array());

        foreach ($user->roles as $role) {
            if (isset($role_multipliers[$role]) && $role_multipliers[$role] > 0) {
                return round($points * $role_multipliers[$role]);
            }
        }

        return $points;
    }

    /**
     * Calculate points earned for purchase based on actual amount paid
     */
    public function calculate_points_earned_for_purchase($points, $cart) {
        if (!is_a($cart, 'WC_Cart')) {
            return $points;
        }

        // Get the cart subtotal before any discounts
        $subtotal = $cart->get_subtotal();

        // Subtract all non-points discounts
        foreach ($cart->get_coupons() as $coupon) {
            if (strpos($coupon->get_code(), 'wc_points_redemption_') !== 0) {
                $subtotal -= $coupon->get_amount();
            }
        }

        // Subtract all fees (like the Easter discount)
        foreach ($cart->get_fees() as $fee) {
            if ($fee->amount < 0) { // Only subtract negative fees (discounts)
                $subtotal += $fee->amount;
            }
        }

        // Calculate points based on the actual amount paid
        $points_earned = WC_Points_Rewards_Manager::calculate_points($subtotal);

        // Apply role multiplier with coupon bypass check
        $points_earned = $this->apply_role_points_multiplier($points_earned, $cart);

        // Round the points
        $points_earned = WC_Points_Rewards_Manager::round_the_points($points_earned);

        return $points_earned;
    }

    /**
     * Get user ID from order or product
     */
    private function get_user_id($order_or_product, $order) {
        if ($order_or_product instanceof WC_Order) {
            return $order_or_product->get_user_id();
        } elseif ($order instanceof WC_Order) {
            return $order->get_user_id();
        } else {
            return get_current_user_id();
        }
    }

    /**
     * Adjust points earned to exclude amount spent using points
     *
     * @param int $points Points to adjust
     * @param WC_Order $order Order object
     * @return int Adjusted points
     */
    public function adjust_points_for_points_spent($points, $order) {
        if (!$order instanceof WC_Order) {
            return $points;
        }

        // Get the discount amount from points
        $points_discount = 0;
        foreach ($order->get_coupon_codes() as $coupon_code) {
            if (strpos($coupon_code, 'wc_points_redemption_') === 0) {
                $coupon = new WC_Coupon($coupon_code);
                $points_discount += $coupon->get_amount();
            }
        }

        if ($points_discount <= 0) {
            return $points;
        }

        // Calculate points that would be earned on the discount amount using the parent plugin's calculation
        $points_for_discount = WC_Points_Rewards_Manager::calculate_points($points_discount);

        // Apply any role multipliers to the points being subtracted
        $points_for_discount = $this->apply_role_points_multiplier($points_for_discount, $order);

        // Subtract points that would be earned on the discount amount
        return max(0, $points - $points_for_discount);
    }
}

// Declare compatibility with WooCommerce custom order tables
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

function WC_Points_Rewards_Extended() {
    return WC_Points_Rewards_Extended::instance();
}
