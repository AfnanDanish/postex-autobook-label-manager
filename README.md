# PostEx AutoBook & Label Manager

A comprehensive WordPress plugin for WooCommerce that automates PostEx courier booking, tracking, and management.

## 🚀 Features

### ✅ Automatic Booking
- **Auto-book COD orders**: Automatically books new WooCommerce orders with payment method "COD" via PostEx API
- **Smart city matching**: Advanced algorithm matches customer cities to PostEx operational cities with high accuracy
- **Error handling**: Comprehensive error logging and order notes for transparency

### ✅ Order Management
- **Order metabox**: View tracking numbers, live status, cancel bookings, and download airway bills directly from WooCommerce order page
- **Bulk actions**: Select multiple orders for bulk booking and load sheet generation
- **Editable review**: Review and edit order details before sending to PostEx

### ✅ Customer Tracking
- **Tracking widget**: Branded tracking widget for customers to track orders
- **Shortcode support**: Use `[postex_tracking_form]` anywhere on your site
- **Elementor integration**: Drag & drop widget with full customization options
- **Order/tracking lookup**: Customers can search by WooCommerce order number or PostEx tracking number

### ✅ Advanced Configuration
- **Pickup address management**: Select from your PostEx merchant addresses
- **Customizable settings**: Configure API token, default pickup address, and order notes
- **City matching debug**: See how cities are matched with similarity scores

## 📋 Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.4+
- Valid PostEx API credentials
- Elementor (optional, for drag & drop widget)

## 🛠️ Installation

### Method 1: Upload Plugin
1. Download the `postex-autobook-label-manager` folder
2. Upload to `wp-content/plugins/postex-autobook-label-manager/`
3. Activate the plugin in WordPress admin
4. Configure your PostEx API token

### Method 2: WordPress Admin
1. Go to **Plugins > Add New > Upload Plugin**
2. Choose the plugin zip file
3. Install and activate
4. Configure settings

## ⚙️ Configuration

### 1. API Setup
1. Go to **WooCommerce > Settings > Products > PostEx**
2. Enter your PostEx API token
3. Select your default pickup address
4. Set default order notes (optional)
5. Save settings

### 2. Test the Integration
1. Create a test COD order
2. Check if it auto-books with PostEx
3. Verify tracking number appears in order notes

## 📖 Usage Guide

### Automatic Booking
- New COD orders are automatically booked when customers complete checkout
- Tracking numbers are saved to order meta and displayed in admin
- API responses are logged in order notes

### Bulk Booking
1. Go to **WooCommerce > Orders**
2. Select multiple orders (checkbox)
3. Choose **"Book with PostEx"** from bulk actions dropdown
4. Review and edit order details
5. Click **"Book All with PostEx"**

### Customer Tracking

#### Shortcode
Place `[postex_tracking_form]` on any page

#### WordPress Widget
Add "PostEx Tracking Widget" to sidebar via **Appearance > Widgets**

#### Elementor Widget
1. Open Elementor editor
2. Search for "PostEx Order Tracking"
3. Drag to your page
4. Customize colors, text, and styling

Customers can enter:
- WooCommerce order number (e.g., `#1234`)
- PostEx tracking number (e.g., `PX123456789`)

### Order Management
In each WooCommerce order admin page:
- **View tracking number**
- **Check live status** from PostEx
- **Cancel booking** if needed
- **Download airway bill**

## 🧠 Smart City Matching

The plugin uses an advanced algorithm to match customer cities to PostEx operational cities:

- **Exact matching**: Case-insensitive exact matches
- **Substring matching**: Partial city name matching
- **Similarity scoring**: Levenshtein distance algorithm
- **Phonetic matching**: Sound-based matching for variations
- **Common abbreviations**: LHR → Lahore, KHI → Karachi

**Debug Mode**: Enable in bulk booking to see matching scores and understand city selections.

## 🔧 API Integration

### Supported PostEx APIs
- `POST /create-order` - Book new orders
- `GET /track-order/{trackingNumber}` - Live tracking
- `PUT /cancel-order` - Cancel bookings
- `GET /get-invoice` - Download airway bills
- `POST /generate-load-sheet` - Bulk load sheets
- `GET /get-merchant-address` - Pickup addresses
- `GET /get-operational-city` - Available cities

### Error Handling
- All API errors are logged in WooCommerce order notes
- Network timeouts and connection issues are handled gracefully
- Invalid responses are captured and reported

## 🎨 Customization

### Shortcode Options
```php
[postex_tracking_form]
```

### Elementor Widget Options
- **Content**: Title, placeholder text, button text
- **Style**: Colors, sizing, padding, borders
- **Responsive**: Mobile-friendly controls

### Hooks and Filters
The plugin provides several hooks for customization:

```php
// Modify PostEx API data before sending
add_filter('postex_api_order_data', function($data, $order) {
    // Customize order data
    return $data;
}, 10, 2);

// Custom city matching logic
add_filter('postex_city_match', function($matched_city, $user_city, $operational_cities) {
    // Your custom matching logic
    return $matched_city;
}, 10, 3);
```

## 🛡️ Security

- **Nonce verification**: All forms use WordPress nonces
- **Capability checks**: Proper user permission validation
- **Data sanitization**: All inputs are sanitized
- **API token protection**: Secure storage of sensitive credentials

## 🐛 Troubleshooting

### Common Issues

**Orders not auto-booking:**
1. Check API token in settings
2. Ensure payment method is "COD"
3. Verify pickup address is selected
4. Check order notes for error messages

**City matching issues:**
1. Use "Show Available Cities" in bulk booking
2. Check debug info for similarity scores
3. Manually select correct city if needed

**Tracking widget not working:**
1. Verify API token is configured
2. Check if tracking numbers exist in order meta
3. Test with known tracking numbers

**Elementor widget not appearing:**
1. Ensure Elementor is installed and active
2. Clear cache if using caching plugins
3. Check under "Basic" widgets in Elementor

### Debug Mode
- Add `?debug_cities=1` to bulk booking URL
- Shows all PostEx operational cities
- Displays city matching scores and logic

## 📂 Project Structure

```
postex-autobook-label-manager/
├── postex-autobook-label-manager.php    # Main plugin file
├── README.txt                           # WordPress plugin readme
└── includes/
    ├── settings.php                     # Plugin settings and configuration
    ├── hooks.php                        # Automatic booking hooks
    ├── metabox.php                      # Order admin metabox
    ├── bulk-actions.php                 # Bulk booking functionality
    ├── pickup-address.php               # Pickup address management
    ├── tracking-widget.php              # Customer tracking widget
    └── elementor-widget.php             # Elementor integration
```

## 📝 Changelog

### Version 1.0.0
- Initial release
- Automatic COD order booking
- Bulk booking with review interface
- Customer tracking widget
- Smart city matching algorithm
- Complete PostEx API integration
- Elementor widget support
- Black & white branded design

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

GPL v2 or later - See [LICENSE](LICENSE) file

## 🆘 Support

For support and bug reports:
1. Check the troubleshooting section
2. Review order notes for API error messages
3. Enable debug mode for detailed information
4. Contact your developer with specific error details

## 🔗 PostEx API Documentation

This plugin implements PostEx API v4.1.9. Refer to the official PostEx API documentation for detailed endpoint specifications.

---

**Made with ❤️ for WooCommerce and PostEx integration**
