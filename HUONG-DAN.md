# CheapDeals v3 — Multi-page PHP, 2 giao diện (Desktop + Mobile)

Bản v3 được **chia thành nhiều trang PHP riêng** + **tách JS/CSS**, và có **2 giao diện**:
- **Desktop**: bố cục rộng, thanh nav trên cùng, nhiều cột.
- **Mobile**: khung điện thoại ở giữa màn hình + thanh nav dưới (giữ như v2).
- Nút **🖥️/📱** ở góc phải để chuyển qua lại (hoặc thêm `?view=desktop` / `?view=mobile` vào URL).

Dùng **chung database `cheapdeals_v2`** (bạn đã import rồi) → **không phải import lại**.

> ✅ Bản này thêm **4 tính năng mới** (FR1, FR20, FR30, FR38). Hai bảng mới `audit_log` và `vouchers` **tự động được tạo** ngay lần mở trang đầu tiên (lệnh `CREATE TABLE IF NOT EXISTS` trong `includes/auth.php`) — **bạn KHÔNG phải import hay chỉnh database gì cả.**

---

## Chạy (trên laptop đã có XAMPP)
1. Chép cả thư mục **`cheapdeals-v3`** vào `C:\xampp\htdocs\`.
2. XAMPP: **Apache + MySQL** đang chạy (DB `cheapdeals_v2` đã có sẵn).
3. Mở: **`http://localhost/cheapdeals-v3/`**

Tài khoản: `admin@cheapdeals.com`/`admin123` · `staff@cheapdeals.com`/`staff123` · `mminhnhut4@gmail.com`/`123456`.

---

## Role admin (đã sửa đúng ý)
- **Chỉ admin gốc `admin@cheapdeals.com` bị khoá** (🔒 fixed, không đổi được).
- **Các admin khác** (nâng từ staff) **đổi role bình thường** — vào Admin → Users & roles, dùng ô chọn role.
- Chặn cả ở giao diện lẫn server (`admin.php`).

---

## Cấu trúc file
```
cheapdeals-v3/
├─ index.php        Packages (trang chủ)
├─ build.php        Build gói riêng
├─ offers.php       Ưu đãi
├─ login.php  register.php  logout.php
├─ account.php      Tài khoản (Overview/Personal/Usage/Billing)
├─ support.php      Chat hỗ trợ (khách)
├─ checkout.php     Thanh toán (thẻ VISA động) → ghi đơn vào DB
├─ thanks.php       Màn cảm ơn
├─ admin.php        Bảng Admin (thống kê + Users & roles)
├─ staff.php        Bảng Staff (gói / feedback / đơn)
├─ includes/
│  ├─ config.php    Cấu hình DB + email admin gốc cố định
│  ├─ db.php        Kết nối PDO
│  ├─ auth.php      Session, helper, seed tài khoản, current user
│  ├─ header.php    Đầu trang — CHỌN bố cục desktop/mobile + nav
│  └─ footer.php    Cuối trang — bottom nav (mobile) + nạp JS
├─ css/
│  ├─ base.css      Biến màu + reset + components (cyberpunk)
│  ├─ desktop.css   Bố cục desktop (nav trên, rộng)
│  └─ mobile.css    Bố cục mobile (khung điện thoại + nav dưới)
├─ js/
│  ├─ main.js       Theme, toast, ripple
│  ├─ packages.js   Lọc/sort/tìm gói
│  └─ checkout.js   Thẻ VISA động + tính tổng
└─ db/cheapdeals_v2.sql   (dùng lại; chỉ import nếu chưa có DB)
```

## Cách hoạt động của 2 giao diện
`includes/header.php` đọc lựa chọn view (cookie `cd_view`, mặc định **desktop**). Tùy view, nó nạp `desktop.css` hoặc `mobile.css` và render bố cục tương ứng. Nút 🖥️/📱 đổi view rồi tải lại trang. Mọi trang đều dùng chung `header.php` + `footer.php` nên giao diện đồng nhất.


---

## ⭐ Tính năng mới đã thêm (đã chạy thử thật trên laptop)

Tất cả dùng **đúng database `cheapdeals_v2`** đang có. Không import lại.

### FR1 — Đăng ký an toàn hơn (`register.php`)
- Email kiểm tra bằng `FILTER_VALIDATE_EMAIL` (regex), số thẻ kiểm tra bằng **thuật toán Luhn** trước khi tạo tài khoản.
- Mật khẩu băm bằng `password_hash()` (bcrypt) — đúng khuyến nghị OWASP.
- Demo: nhập email sai hoặc thẻ sai → bị chặn; thẻ test hợp lệ: **4111 1111 1111 1111**.

### FR20 — VISACheck (Luhn) khi thanh toán (`checkout.php`)
- Khi bấm **Pay now**, server chạy Luhn + kiểm CVV, **đo thời gian** và hiện ở trang cảm ơn ("verified in 0.05 ms" — đạt tiêu chí < 3 giây).
- Thẻ sai Luhn bị **từ chối ngay** ("VISACheck declined the card").
- Ô thẻ tự điền thẻ hợp lệ nếu thẻ đã lưu của khách không hợp lệ.

### FR30 — Voucher động (`staff.php` tab 🎟️ Vouchers + `voucher_check.php`)
- Staff/Admin vào **Staff → Vouchers**, chọn % rồi **Generate** → sinh mã duy nhất (vd `CD4E783F`), lưu DB.
- Khách nhập mã đó ở ô **Special offer code** lúc checkout → tự trừ tiền (kiểm tra qua `voucher_check.php`).

### FR38 — Nhật ký quản trị bất biến (`admin.php` tab 📜 Audit log)
- Mọi lần **đổi giá gói** (staff) và **đổi role** (admin) đều tự ghi vào bảng `audit_log` (chỉ ghi-thêm, app không có lệnh sửa/xoá → bất biến).
- Cũng ghi khi **phát voucher / thêm / xoá gói**.
- Admin xem ở **Admin → Audit log**: thời gian, ai làm, hành động, chi tiết (vd `Broadband Lite: £19.00 -> £18.00`).

---

## Cách chạy (không đổi)
1. Chép thư mục `cheapdeals-v3` vào `C:\xampp\htdocs\` (đã chép sẵn nếu bạn đang chạy bản này).
2. XAMPP bật **Apache + MySQL** (DB `cheapdeals_v2` đã có).
3. Mở **`http://localhost/cheapdeals-v3/`** (nếu `localhost` không vào được, dùng **`http://127.0.0.1/cheapdeals-v3/`**).

Tài khoản: `admin@cheapdeals.com`/`admin123` · `staff@cheapdeals.com`/`staff123` · `mminhnhut4@gmail.com`/`123456`.
