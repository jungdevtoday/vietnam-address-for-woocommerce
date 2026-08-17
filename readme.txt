=== Vietnam Address for WooCommerce ===
Contributors: jungdev
Tags: woocommerce, vietnam, address, checkout, provinces
Requires at least: 5.8
Tested up to: 7.0.4
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Tích hợp địa chỉ hành chính Việt Nam mới nhất vào WooCommerce. Hỗ trợ chuyển đổi địa chỉ cũ sang địa chỉ mới.

== Description ==

Plugin Vietnam Address for WooCommerce giúp tích hợp hệ thống địa chỉ hành chính Việt Nam vào form checkout của WooCommerce. Toàn bộ dữ liệu Tỉnh/Thành phố, Quận/Huyện, Phường/Xã được đóng gói sẵn trong plugin — không cần API key, không phụ thuộc dịch vụ ngoài, không lo gián đoạn.

= Tính năng chính =

* **Tích hợp địa chỉ Việt Nam**: Thay thế hoặc bổ sung các trường địa chỉ mặc định của WooCommerce bằng hệ thống Tỉnh/Thành phố - Quận/Huyện - Phường/Xã
* **Hỗ trợ 2 cấu trúc**:
  - Cấu trúc cũ (trước 1/7/2025): 63 tỉnh thành với 3 cấp (Tỉnh - Quận/Huyện - Phường/Xã)
  - Cấu trúc mới (sau 1/7/2025): 34 tỉnh thành với 2 cấp (Tỉnh - Phường/Xã)
* **Hỗ trợ cả Classic Checkout và Block Checkout**: xem mục "Hỗ trợ Block Checkout" bên dưới
* **Dữ liệu đóng gói sẵn**: Toàn bộ danh sách Tỉnh/Thành phố, Quận/Huyện, Phường/Xã (cả 2 cấu trúc) nằm sẵn trong plugin — hoạt động ngay sau khi cài đặt, không gọi API ngoài, không cần internet, không cần API key
* **Server dữ liệu trung tâm**: Mặc định trỏ tới `https://api.jungdev.com` để nhận thông tin thay đổi hành chính sớm nhất mà không cần cập nhật plugin. Có thể đổi sang server tự host riêng, hoặc bỏ trống để chỉ dùng dữ liệu đóng gói sẵn trong plugin — dù cấu hình thế nào, plugin luôn tự động quay lại dùng dữ liệu đóng gói sẵn nếu server không phản hồi được, checkout không bao giờ bị gián đoạn
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
2. Vào WooCommerce > Vietnam Address
3. Chọn cấu trúc địa chỉ mặc định (mới hoặc cũ)
4. Nếu cần chuyển đổi đơn hàng cũ, cuộn tới mục "Công cụ chuyển đổi địa chỉ cũ sang mới" và nhấn "Chuyển đổi ngay"

== Installation ==

= Tự động =

1. Đăng nhập vào WordPress Admin
2. Vào Plugins > Add New
3. Tìm kiếm "Vietnam Address for WooCommerce"
4. Nhấn "Install Now" và sau đó "Activate"

= Thủ công =

1. Tải file plugin về máy
2. Giải nén và upload folder `vn-address-woocommerce` vào `/wp-content/plugins/`
3. Kích hoạt plugin trong WordPress Admin > Plugins
4. Cấu hình plugin tại WooCommerce > Vietnam Address

= Sau khi cài đặt =

1. Vào WooCommerce > Vietnam Address
2. Chọn cấu trúc địa chỉ mặc định phù hợp
3. Lưu cài đặt

Không cần đăng ký tài khoản hay nhập API key ở bất kỳ bước nào — dữ liệu địa chỉ đã có sẵn trong plugin.

== Frequently Asked Questions ==

= Plugin có cần API key hay tài khoản bên ngoài không? =

Không. Toàn bộ dữ liệu Tỉnh/Thành phố - Quận/Huyện - Phường/Xã được đóng gói sẵn trong plugin, hoạt động ngay sau khi cài đặt mà không cần đăng ký hay kết nối dịch vụ ngoài.

= Tôi có thể sử dụng cả 2 cấu trúc địa chỉ cùng lúc không? =

Không, bạn chỉ có thể chọn 1 trong 2 cấu trúc (cũ hoặc mới) tại một thời điểm.

= Chuyển đổi địa chỉ cũ sang mới hoạt động như thế nào? Có đáng tin cậy không? =

Khi bạn nhấn nút "Chuyển đổi ngay" trong phần Công cụ chuyển đổi địa chỉ cũ sang mới ở trang cài đặt, plugin tra từng đơn hàng vào bảng ánh xạ cũ → mới đóng gói sẵn trong plugin (xây dựng từ dữ liệu ánh xạ hành chính do VietMap công bố chính thức, không phải một API sống). Việc tra cứu diễn ra hoàn toàn cục bộ trên server của bạn — **không có request mạng nào trong bước chuyển đổi**, kể cả khi bạn đã cấu hình API Server, nên tốc độ và độ tin cậy không phụ thuộc vào bất kỳ dịch vụ bên ngoài nào tại thời điểm chuyển đổi.

Khoảng 97% số phường/xã cũ chuyển đổi tự động, chính xác 1-1. Một số ít (~3%) bị tách thành nhiều phường/xã mới sau sáp nhập — các trường hợp này được đánh dấu "cần xem xét thủ công" thay vì đoán, để bạn tự xác nhận địa chỉ chính xác cho từng đơn hàng đó. Địa chỉ gốc khách hàng đã nhập không bao giờ bị ghi đè hay xóa — kết quả chuyển đổi được lưu vào các trường dữ liệu riêng, tách biệt hoàn toàn với dữ liệu gốc, nên luôn có thể đối chiếu lại nếu cần.

= Plugin có tương thích với theme của tôi không? =

Plugin được thiết kế để tương thích với hầu hết các theme WooCommerce. Nếu gặp vấn đề về giao diện, vui lòng liên hệ support.

= "API Server" trong trang cài đặt là gì? =

Đây là nơi lưu trữ dữ liệu địa chỉ hành chính Việt Nam được cập nhật liên tục, mặc định là `https://api.jungdev.com`. Nên giữ nguyên giá trị mặc định này để nhận thông tin thay đổi hành chính (đổi tên, sáp nhập tỉnh/xã...) sớm nhất ngay khi có, mà không cần cập nhật lại plugin. Dữ liệu hành chính lấy từ VietMap (https://github.com/vietmap-company/vietnam_administrative_address).

Đây không phải yêu cầu bắt buộc: bỏ trống ô này, plugin vẫn hoạt động đầy đủ với dữ liệu đóng gói sẵn ngay trong plugin, và nếu server không phản hồi được vì bất kỳ lý do gì thì plugin tự động quay lại dùng dữ liệu đóng gói sẵn ngay lập tức — checkout không bao giờ bị gián đoạn vì lý do này. Nếu muốn tự chủ hoàn toàn, bạn có thể tự host server riêng dựa trên mã nguồn mở tại https://github.com/jungdevtoday/vn-address-api-server và trỏ về server của chính mình.

== External services ==

This plugin can optionally connect to a central data server, `https://api.jungdev.com`, operated by the plugin author (jungdev, https://jungdev.com), to fetch up-to-date Vietnamese administrative address data (provinces, wards, and old-to-new mapping tables) without requiring a plugin update whenever administrative boundaries change (renames, mergers, new codes).

What is sent: only administrative lookup codes (e.g. a province or ward code) as GET request query parameters. No personal data, customer information, or order data is ever sent to this service.

When it is used: when a customer loads the checkout page (to look up province/ward lists for the address autocomplete), when a site administrator clicks "Test Connection" on the plugin's settings page, and via a background cache-warming job that runs shortly after the API Server setting is saved or the plugin is activated.

This connection is entirely optional. Leaving the "API Server" field blank, or if the server is temporarily unreachable, the plugin automatically and transparently falls back to the Vietnamese administrative address data bundled inside the plugin itself - checkout is never interrupted by this.

Site owners who prefer not to connect to this service at all may run their own copy instead: the server is open source at https://github.com/jungdevtoday/vn-address-api-server.

== Screenshots ==

1. Trang cài đặt plugin
2. Form checkout với địa chỉ Việt Nam
3. Thông tin địa chỉ trong admin đơn hàng
4. Công cụ chuyển đổi địa chỉ hàng loạt

== Changelog ==

= 1.1.1 =
* Sửa lỗi ô "API Server" hiển thị trống với một số khách hàng (do dùng placeholder thay vì giá trị thật) khiến nút "Kiểm tra kết nối" báo lỗi gây bối rối; giờ luôn hiển thị giá trị đang dùng, mặc định là server của plugin
* Bỏ checkbox "Bật tính năng Converter" và bước Lưu cài đặt riêng — công cụ chuyển đổi địa chỉ giờ luôn sẵn sàng với nút "Chuyển đổi ngay"
* Không còn tự động kết nối API Server ngay khi kích hoạt plugin; chỉ kết nối khi quản trị viên chủ động lưu cài đặt, bấm "Kiểm tra kết nối", hoặc khi khách hàng thực sự vào trang checkout
* Thêm mục "External services" trong readme mô tả rõ dữ liệu nào được gửi đi và khi nào
* Thêm uninstall.php để dọn dẹp tùy chọn, cache và cron khi gỡ cài đặt plugin
* Xoá các dòng console.log/console.error còn sót lại trong script checkout
* Cập nhật văn bản tiếng Việt trên trang cài đặt theo yêu cầu

= 1.1.0 =
* Đổi tên plugin thành "Vietnam Address for WooCommerce"
* Thêm link "Cài đặt" ngay trong bảng danh sách Plugins
* API Server: mặc định trỏ tới https://api.jungdev.com, tự động làm nóng cache khi kích hoạt hoặc khi đổi server (chạy nền, không chặn trang)
* Công cụ chuyển đổi địa chỉ: chuyển hẳn sang dùng dữ liệu cục bộ, không còn gọi mạng trong lúc chuyển đổi hàng loạt, kể cả khi đã cấu hình API Server
* Bổ sung ghi chú nguồn dữ liệu VietMap và hướng dẫn tự host server riêng ngay trên trang cài đặt
* Cập nhật văn bản tiếng Việt trên trang cài đặt

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

= 1.1.1 =
Sửa lỗi ô API Server hiển thị trống gây bối rối, đơn giản hoá công cụ chuyển đổi địa chỉ (bỏ checkbox, thêm nút Chuyển đổi ngay), và không còn tự động kết nối server khi kích hoạt plugin.

= 1.1.0 =
Đổi tên plugin, thêm API Server mặc định để cập nhật dữ liệu tự động, sửa converter để không phụ thuộc mạng khi chuyển đổi hàng loạt.

= 1.0.0 =
Phiên bản đầu tiên của plugin.

== Support ==

Nếu bạn cần hỗ trợ, vui lòng truy cập https://jungdev.com

== Credits ==

* Phát triển bởi jungdev (https://jungdev.com)
* Dữ liệu địa chỉ hành chính Việt Nam được cung cấp bởi VietMap (https://github.com/vietmap-company/vietnam_administrative_address), sử dụng theo VietMap Administrative Data License
* Plugin được phát triển cho WooCommerce
