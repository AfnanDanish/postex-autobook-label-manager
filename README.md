# PostEx AutoBook & Label Manager

A powerful WooCommerce plugin to automate order booking, airway bill management, and real-time tracking with the PostEx API.

## Features
- **Automatic Booking**: Instantly book new COD orders with PostEx.
- **Order Admin Metabox**: View tracking, live status, timeline, and cancel or print airway bills from the order page.
- **Bulk Actions**: Book, generate load sheets, print airway bills (max 10 at once), and bulk cancel orders from the WooCommerce Orders screen.
- **Branded Tracking Widget**: Add a shortcode or widget for customers to track their orders by order number or tracking number.
- **Pickup Address Management**: Select your default pickup address from your PostEx account.
- **City Mapping**: Robust city matching between WooCommerce and PostEx.
- **Error Handling**: All API responses are logged in order notes.
- **Secure & Compatible**: Works with the latest WooCommerce and WordPress versions.

## Installation
1. Upload the `postex-autobook-label-manager` folder to your `wp-content/plugins/` directory, or use the WordPress plugin uploader with the provided ZIP.
2. Activate the plugin from the WordPress admin.
3. Go to **WooCommerce > Settings > Products > PostEx** to configure your API token, default pickup address, and default order note.

## Usage
### Admin
- **Automatic Booking**: New COD orders are booked automatically.
- **Order Page**: See real-time PostEx status, timeline, and print/cancel options in the order metabox.
- **Bulk Actions**: In WooCommerce > Orders, select orders and use the bulk actions menu for:
  - Book with PostEx (with review/edit step)
  - Generate Load Sheet (PDF)
  - Print Airway Bills (max 10)
  - Bulk Cancel with PostEx

### Customer
- **Tracking Widget/Shortcode**: Place `[postex_tracking_form]` on any page or use the "PostEx Tracking Widget" in Appearance > Widgets. Customers can enter their order number or tracking number to see real-time status and timeline.

## API Requirements
- You must have a valid PostEx API token (get from your PostEx merchant account).
- Your WooCommerce store must have valid pickup addresses set up in PostEx.

## Settings
- **API Token**: Your PostEx API token.
- **Default Pickup Address**: Select from your PostEx account addresses.
- **Default Order Note**: Pre-filled note for bulk bookings.

## License
GPLv2 or later. See LICENSE file.

---
For support, see the PostEx API documentation or contact your developer. 