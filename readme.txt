=== VN Address for WooCommerce ===
Contributors: jungdev
Tags: woocommerce, vietnam, address, checkout, provinces
Requires at least: 5.8
Tested up to: 7.0.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Tích hợp địa chỉ hành chính Việt Nam vào WooCommerce với dữ liệu Tỉnh/Thành phố - Quận/Huyện - Phường/Xã đóng gói sẵn. Hỗ trợ chuyển đổi địa chỉ từ cấu trúc cũ sang mới.

== Description ==

Plugin VN Address for WooCommerce giúp tích hợp hệ thống địa chỉ hành chính Việt Nam vào form checkout của WooCommerce. Toàn bộ dữ liệu Tỉnh/Thành phố, Quận/Huyện, Phường/Xã được đóng gói sẵn trong plugin — không cần API key, không phụ thuộc dịch vụ ngoài, không lo gián đoạn.

= Tính năng chính =

* **Tích hợp địa chỉ Việt Nam**: Thay thế hoặc bổ sung các trường địa chỉ mặc định của WooCommerce bằng hệ thống Tỉnh/Thành phố - Quận/Huyện - Phường/Xã
* **Hỗ trợ 2 cấu trúc**:
  - Cấu trúc cũ (trước 1/7/2025): 63 tỉnh thành với 3 cấp (Tỉnh - Quận/Huyện - Phường/Xã)
  - Cấu trúc mới (sau 1/7/2025): 34 tỉnh thành với 2 cấp (Tỉnh - Phường/Xã)
* **Hỗ trợ cả Classic Checkout và Block Checkout**: xem mục "Hỗ trợ Block Checkout" bên dưới
* **Dữ liệu đóng gói sẵn**: Toàn bộ danh sách Tỉnh/Thành phố, Quận/Huyện, Phường/Xã (cả 2 cấu trúc) nằm sẵn trong plugin — hoạt động ngay sau khi cài đặt, không gọi API ngoài, không cần internet, không cần API key
* **Server dữ liệu trung tâm (tuỳ chọn)**: Có thể trỏ plugin tới server dữ liệu riêng của bạn để cập nhật dữ liệu tập trung cho nhiều store cùng lúc mà không cần cập nhật plugin từng nơi. Hoàn toàn tuỳ chọn — bỏ trống thì plugin tự dùng dữ liệu đóng gói sẵn, và tự động quay lại dùng dữ liệu đóng gói sẵn nếu server không phản hồi được
* **Chuyển đổi tự động**: Chuyển đổi địa chỉ từ cấu trúc cũ sang cấu trúc mới cho các đơn hàng hiện có, dùng bảng chuyển đổi đóng gói sẵn
* **Giao diện thân thiện**: Trang cài đặt đầy đủ và dễ sử dụng trong WooCommerce Admin
* **Hiển thị trong Admin**: Xem thông tin địa chỉ chi tiết trong trang quản lý đơn hàng

= Yêu cầu =

* WordPress 5.8 trở lên
* WooCommerce 5.0 trở lên
* PHP 7.4 trở lên

= Hỗ trợ Block Checkout =

Plugin hỗ trợ cả **Classic Checkout** (shortcode) và **Block Checkout** (mặc định với store mới từ WooCommerce 8.3+):

* **Classic Checkout**: đầy đủ 2 cấu trúc địa chỉ (mới: Tỉnh/Thành phố → Xã/Phường, và cũ: Tỉnh/Thành phố → Quận/Huyện → Xã/Phường).
* **Block Checkout**: cấu trúc mới (Tỉnh/Thành phố → Xã/Phường) với ô tìm kiếm Xã/Phường gợi ý theo thời gian thực. Yêu cầu WooCommerce 8.9 trở lên. Cấu trúc cũ (có Quận/Huyện) hiện chỉ khả dụng trên Classic Checkout.

Nếu WooCommerce của bạn cũ hơn 8.9 và đang dùng Block Checkout, plugin sẽ hiện cảnh báo trong trang quản trị và bạn có thể bật "Cart and checkout shortcodes" tại WooCommerce > Settings > Advanced > Features để chuyển sang Classic Checkout.

= Ngôn ngữ =

Plugin hỗ trợ: Tiếng Việt (mặc định), English, Français, Deutsch, 日本語. Vì phần lớn khách hàng dùng site tiếng Việt, plugin sẽ hiển thị Tiếng Việt cho mọi site không có ngôn ngữ khớp riêng (kể cả site đang chạy WordPress bản tiếng Anh mặc định), thay vì hiển thị tiếng Anh. Site đã cấu hình rõ English/Français/Deutsch/日本語 vẫn hiển thị đúng ngôn ngữ đó.

= Hướng dẫn sử dụng =

1. Cài đặt và kích hoạt plugin
2. Vào WooCommerce > VN Address
3. Chọn cấu trúc địa chỉ mặc định (mới hoặc cũ)
4. Nếu cần chuyển đổi đơn hàng cũ, bật "Enable Converter" và nhấn "Convert All Orders"

== Installation ==

= Tự động =

1. Đăng nhập vào WordPress Admin
2. Vào Plugins > Add New
3. Tìm kiếm "VN Address for WooCommerce"
4. Nhấn "Install Now" và sau đó "Activate"

= Thủ công =

1. Tải file plugin về máy
2. Giải nén và upload folder `vn-address-woocommerce` vào `/wp-content/plugins/`
3. Kích hoạt plugin trong WordPress Admin > Plugins
4. Cấu hình plugin tại WooCommerce > VN Address

= Sau khi cài đặt =

1. Vào WooCommerce > VN Address
2. Chọn cấu trúc địa chỉ mặc định phù hợp
3. Lưu cài đặt

Không cần đăng ký tài khoản hay nhập API key ở bất kỳ bước nào — dữ liệu địa chỉ đã có sẵn trong plugin.

== Frequently Asked Questions ==

= Plugin có cần API key hay tài khoản bên ngoài không? =

Không. Toàn bộ dữ liệu Tỉnh/Thành phố - Quận/Huyện - Phường/Xã được đóng gói sẵn trong plugin, hoạt động ngay sau khi cài đặt mà không cần đăng ký hay kết nối dịch vụ ngoài.

= Tôi có thể sử dụng cả 2 cấu trúc địa chỉ cùng lúc không? =

Không, bạn chỉ có thể chọn 1 trong 2 cấu trúc (cũ hoặc mới) tại một thời điểm.

= Chuyển đổi địa chỉ hoạt động như thế nào? =

Khi bạn bật tính năng Converter và nhấn "Convert All Orders", plugin sẽ tra bảng chuyển đổi đóng gói sẵn để chuyển địa chỉ từ cấu trúc cũ (63 tỉnh thành, 3 cấp) sang cấu trúc mới (34 tỉnh thành, 2 cấp) cho tất cả đơn hàng. Khoảng 97% số phường/xã cũ chuyển đổi tự động, chính xác 1-1. Một số ít (~3%) bị tách thành nhiều phường/xã mới sau sáp nhập — các trường hợp này được đánh dấu "cần xem xét thủ công" thay vì đoán, để bạn tự xác nhận địa chỉ chính xác cho từng đơn hàng đó.

= Plugin có tương thích với theme của tôi không? =

Plugin được thiết kế để tương thích với hầu hết các theme WooCommerce. Nếu gặp vấn đề về giao diện, vui lòng liên hệ support.

= "Central Data Server URL" trong trang cài đặt là gì? =

Đây là tuỳ chọn nâng cao, không bắt buộc. Nếu bạn vận hành nhiều store và muốn cập nhật dữ liệu địa chỉ tập trung một chỗ, có thể trỏ trường này tới server dữ liệu riêng của bạn (phát hành cùng bộ mã nguồn mở với plugin). Bỏ trống thì mọi thứ vẫn hoạt động bình thường với dữ liệu đóng gói sẵn — server chỉ là một lớp tăng cường tuỳ chọn, không phải yêu cầu bắt buộc, và nếu server không phản hồi được thì plugin tự động quay lại dùng dữ liệu đóng gói sẵn ngay lập tức, checkout không bao giờ bị gián đoạn vì lý do này.

== Screenshots ==

1. Trang cài đặt plugin
2. Form checkout với địa chỉ Việt Nam
3. Thông tin địa chỉ trong admin đơn hàng
4. Công cụ chuyển đổi địa chỉ hàng loạt

== Changelog ==

= 1.0.0 =
* Phiên bản đầu tiên
* Tích hợp địa chỉ hành chính Việt Nam với dữ liệu đóng gói sẵn, không cần API key
* Hỗ trợ 2 cấu trúc địa chỉ (cũ và mới)
* Hỗ trợ Classic Checkout và Block Checkout
* Chuyển đổi địa chỉ tự động bằng bảng chuyển đổi đóng gói sẵn
* Trang admin settings đầy đủ
* Đa ngôn ngữ: English (mặc định), Tiếng Việt, Français, Deutsch, 日本語
* Tương thích HPOS (High-Performance Order Storage)

== Upgrade Notice ==

= 1.0.0 =
Phiên bản đầu tiên của plugin.

== Support ==

Nếu bạn cần hỗ trợ, vui lòng truy cập https://jungdev.com

== Credits ==

* Phát triển bởi jungdev (https://jungdev.com)
* Dữ liệu địa chỉ hành chính Việt Nam được cung cấp bởi VietMap (https://github.com/vietmap-company/vietnam_administrative_address), sử dụng theo VietMap Administrative Data License
* Plugin được phát triển cho WooCommerce
