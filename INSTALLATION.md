# VN Address for WooCommerce - Hướng dẫn cài đặt

## Yêu cầu hệ thống

- WordPress 5.8 trở lên
- WooCommerce 5.0 trở lên
- PHP 7.4 trở lên

Không cần API key hay tài khoản bên ngoài — toàn bộ dữ liệu địa chỉ hành chính Việt Nam được đóng gói sẵn trong plugin.

## Cài đặt

### Phương pháp 1: Upload qua WordPress Admin

1. Nén folder `vn-address-woocommerce` thành file ZIP
2. Đăng nhập WordPress Admin
3. Vào **Plugins > Add New > Upload Plugin**
4. Chọn file ZIP và nhấn **Install Now**
5. Nhấn **Activate Plugin**

### Phương pháp 2: Upload qua FTP

1. Upload folder `vn-address-woocommerce` vào `/wp-content/plugins/`
2. Đăng nhập WordPress Admin
3. Vào **Plugins**
4. Tìm **VN Address for WooCommerce** và nhấn **Activate**

## Cấu hình

### Bước 1: Chọn cấu trúc địa chỉ

1. Trong WordPress Admin, vào **WooCommerce > VN Address**
2. Chọn **Default Address Structure**:
   - **New Structure**: Dành cho cấu trúc mới (34 tỉnh thành, sau 1/7/2025)
   - **Old Structure**: Dành cho cấu trúc cũ (63 tỉnh thành, trước 1/7/2025)
3. Nhấn **Save Changes**

### Bước 2 (Tùy chọn): Trỏ tới server dữ liệu trung tâm

Nếu bạn vận hành server dữ liệu riêng (xem dự án `vn-address-api-server` đi kèm), dán URL server vào ô **Central Data Server URL**, nhấn **Test Connection** để kiểm tra, rồi **Save Changes**. Bỏ trống ô này thì plugin dùng dữ liệu đóng gói sẵn — mọi thứ vẫn hoạt động bình thường, đây chỉ là tuỳ chọn nâng cao để cập nhật dữ liệu tập trung cho nhiều store.

### Bước 3: Chuyển đổi đơn hàng cũ (Tùy chọn)

Nếu bạn đã có đơn hàng với cấu trúc địa chỉ cũ và muốn chuyển sang cấu trúc mới:

1. Bật **Enable Converter**
2. Nhấn **Save Changes**
3. Nhấn **Convert All Orders**
4. Đợi quá trình chuyển đổi hoàn tất — hầu hết đơn hàng chuyển đổi tự động; một số ít trường hợp phường/xã bị tách sau sáp nhập sẽ được đánh dấu "cần xem xét thủ công" thay vì đoán sai

## Sử dụng

### Form Checkout

Sau khi cài đặt, form checkout sẽ tự động hiển thị các trường:

**Cấu trúc mới:**
- Tỉnh/Thành phố
- Phường/Xã

**Cấu trúc cũ:**
- Tỉnh/Thành phố
- Quận/Huyện
- Phường/Xã

### Quản lý đơn hàng

Trong trang quản lý đơn hàng (WooCommerce > Orders), bạn sẽ thấy thông tin địa chỉ chi tiết của khách hàng.

## Tính năng

### 1. Dữ liệu đóng gói sẵn

Toàn bộ danh sách Tỉnh/Thành phố, Quận/Huyện, Phường/Xã (cả 2 cấu trúc) nằm sẵn trong plugin dưới dạng file JSON tĩnh:
- Không gọi API bên ngoài, không cần internet để hiển thị danh sách địa chỉ
- Không cần đăng ký tài khoản hay API key
- Tốc độ tra cứu tức thời

### 2. Chuyển đổi địa chỉ

Công cụ chuyển đổi giúp:
- Tự động tra bảng chuyển đổi cũ → mới đóng gói sẵn trong plugin
- Xử lý hàng loạt nhiều đơn hàng cùng lúc
- Đánh dấu riêng các trường hợp phường/xã bị tách thành nhiều phường/xã mới (~3% tổng số), để bạn xác nhận thủ công thay vì đoán sai
- Báo cáo chi tiết kết quả: đã chuyển đổi / cần xem xét / lỗi

## Xử lý sự cố

### Plugin không xuất hiện các trường địa chỉ

1. Kiểm tra WooCommerce đã được cài đặt và kích hoạt chưa
2. Xóa cache của WordPress và browser
3. Kiểm tra theme có tương thích với WooCommerce không
4. **Nếu trang Checkout dùng Block Checkout** (mặc định với store mới tạo từ WooCommerce 8.3 trở lên): plugin đã hỗ trợ Block Checkout với cấu trúc địa chỉ mới (Tỉnh/Thành phố → Xã/Phường), yêu cầu WooCommerce 8.9 trở lên. Nếu WooCommerce của bạn cũ hơn, hãy cập nhật hoặc vào **WooCommerce > Settings > Advanced > Features** và bật "Cart and checkout shortcodes" để chuyển sang Classic Checkout. Cấu trúc địa chỉ cũ (có Quận/Huyện) hiện chỉ khả dụng trên Classic Checkout.

### Một số đơn hàng bị đánh dấu "cần xem xét thủ công" khi chuyển đổi

Đây là hành vi có chủ đích, không phải lỗi: phường/xã đó bị tách thành nhiều phường/xã mới sau sáp nhập nên plugin không thể tự xác định chính xác. Hãy mở đơn hàng đó, xem địa chỉ chi tiết (số nhà, tên đường) khách hàng đã nhập, và chọn phường/xã mới phù hợp thủ công.

### Cache trình duyệt/WordPress

1. Xóa cache của WordPress và trình duyệt nếu giao diện không cập nhật
2. Một số cache plugin có thể ảnh hưởng đến việc tải JS/CSS mới, thử tắt tạm thời khi gỡ lỗi

## Tối ưu hóa

### Performance

Dữ liệu địa chỉ được đọc trực tiếp từ file JSON đóng gói sẵn trong plugin, không có độ trễ mạng, không phụ thuộc dịch vụ ngoài.

### SEO

Plugin không ảnh hưởng đến SEO của website vì chỉ hoạt động ở trang checkout.

### Security

1. Plugin sử dụng WordPress nonces để bảo mật AJAX requests
2. Tất cả input được sanitize và validate

## Cấu trúc thư mục

```
vn-address-woocommerce/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── checkout.css
│   ├── data/
│   │   ├── provinces-new.json / wards-new.json (cấu trúc mới, đóng gói sẵn)
│   │   ├── provinces-old.json / districts-old.json / wards-old.json (cấu trúc cũ)
│   │   ├── ward-mapping-old-to-new.json (bảng chuyển đổi cũ → mới)
│   │   └── LICENSE-vietmap-data.txt
│   └── js/
│       ├── admin.js
│       ├── checkout.js (Classic Checkout)
│       └── checkout-blocks.js (Block Checkout)
├── includes/
│   ├── class-vn-address-admin.php
│   ├── class-vn-address-blocks.php (Block Checkout support)
│   ├── class-vn-address-checkout.php (Classic Checkout support)
│   ├── class-vn-address-converter.php
│   └── class-vn-address-data.php (dữ liệu địa chỉ đóng gói sẵn)
├── languages/
├── readme.txt
└── vn-address-woocommerce.php
```

## Support

Nếu bạn cần hỗ trợ, vui lòng truy cập https://jungdev.com

## License

GPLv2 or later
https://www.gnu.org/licenses/gpl-2.0.html
