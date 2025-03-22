# WooCommerce Points and Rewards Extended

A WordPress plugin that extends WooCommerce Points and Rewards with additional features including auto-apply discounts and role-based points multipliers.

## Description

This plugin enhances the functionality of WooCommerce Points and Rewards by adding:

- **Auto-Apply Points**: Automatically applies available points at checkout
- **Role-Based Multipliers**: Set different point multipliers for different user roles
- **Customizable Settings**: Easy configuration through WooCommerce settings

## Requirements

- WordPress 5.0 or higher
- WooCommerce 7.5 or higher
- WooCommerce Points and Rewards plugin
- PHP 7.2 or higher

## Installation

1. Download the plugin zip file
2. Go to WordPress admin > Plugins > Add New
3. Click "Upload Plugin" and select the downloaded zip file
4. Click "Install Now" and then "Activate"

## Configuration

### Auto-Apply Points

1. Go to WooCommerce > Settings > Points & Rewards
2. Find the "Auto-Apply Points" section
3. Enable or disable the auto-apply feature
4. Save changes

### Role-Based Multipliers

1. Go to WooCommerce > Settings > Points & Rewards
2. Find the "Role-Based Point Multipliers" section
3. Set multipliers for each user role:
   - 1.0 = Standard rate (default)
   - 2.0 = Double points
   - 0.0 = No points earned
4. Save changes

## Features

### Auto-Apply Points

- Automatically applies available points at checkout
- Can be enabled/disabled in settings
- Works with existing points and rewards system
- Respects minimum points requirements

### Role-Based Multipliers

- Set different point multipliers for different user roles
- Supports decimal multipliers (e.g., 1.5x points)
- Zero multiplier option to prevent points earning
- Easy to configure through WooCommerce settings

## Development

### Hooks and Filters

```php
// Modify auto-apply behavior
add_filter('wc_points_rewards_auto_apply', 'my_custom_auto_apply', 10, 2);

// Modify role multiplier calculation
add_filter('wc_points_rewards_role_multiplier', 'my_custom_multiplier', 10, 3);
```

### File Structure

```
wc-points-rewards-extended/
├── wc-points-rewards-extended.php    # Main plugin file
├── README.md                         # This file
└── .gitignore                        # Git ignore rules
```

## Support

For support, please create an issue in the GitHub repository or contact Beenacle Technologies Pvt. Ltd.

## License

This plugin is licensed under the GNU General Public License v3.0.

## Credits

- Built by Beenacle Technologies Pvt. Ltd.
- Extends WooCommerce Points and Rewards by WooCommerce

## Changelog

### 1.0.0
- Initial release
- Added auto-apply points feature
- Added role-based multipliers
- Added WooCommerce settings integration