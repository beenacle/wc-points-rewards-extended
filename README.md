# WooCommerce Points and Rewards Extended

This plugin extends the functionality of WooCommerce Points and Rewards by adding additional features for points management and role-based rewards.

## Features

### 1. Role-Based Point Multipliers
- Set different point multipliers for different user roles
- Configure multipliers in WooCommerce > Settings > Points & Rewards
- Option to bypass role multipliers for specific coupons
- Works in both cart and order contexts

### 2. CSV Import for Points
- Import points for multiple users via CSV
- CSV format:
  ```
  user_email,points,note
  customer@example.com,100,Welcome bonus
  another@example.com,50,Points adjustment
  ```
- Validates user emails and points values
- Detailed error reporting
- Access via WooCommerce > Import Points

### 3. Auto-Apply Points
- Automatically applies available points at checkout
- Prevents earning points on points spent
- Seamless integration with WooCommerce checkout

## Installation

1. Download the plugin zip file
2. Go to WordPress admin > Plugins > Add New > Upload Plugin
3. Upload the zip file and click "Install Now"
4. Activate the plugin

## Requirements

- WordPress 5.0 or higher
- WooCommerce 7.5 or higher
- WooCommerce Points and Rewards plugin

## Configuration

### Role-Based Point Multipliers
1. Go to WooCommerce > Settings > Points & Rewards
2. Find the "Role-Based Point Multipliers" section
3. Set multipliers for each user role
4. Select coupons that should bypass role multipliers
5. Save changes

### CSV Import
1. Go to WooCommerce > Import Points
2. Prepare a CSV file with user emails and points
3. Upload the CSV file
4. Review the import results

## Usage

### Role Multipliers
- Users with different roles will earn different amounts of points
- Example: If a role has a 2x multiplier, they earn twice the normal points
- Selected coupons will bypass role multipliers

### CSV Import
- Use the import feature to bulk update user points
- Points can be positive (adding) or negative (deducting)
- Optional notes can be added for each points adjustment

### Auto-Apply Points
- Points are automatically applied at checkout if available
- Users can still manually remove points if desired
- Points are not earned on the portion of the order paid with points

## Support

For support, please contact the plugin author.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Role-based point multipliers
- CSV import functionality
- Auto-apply points feature

### 1.0.1
- Added coupon bypass functionality for role multipliers
- Improved points calculation logic
- Enhanced error handling in CSV import