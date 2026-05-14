<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Đơn ứng tuyển mới (gửi đến quản trị viên)',
            'description' => 'Mẫu email gửi thông báo đến quản trị viên khi hệ thống nhận được đơn ứng tuyển mới',
            'subject' => 'Đơn ứng tuyển mới',
        ],
        'employer-new-job-application' => [
            'title' => 'Đơn ứng tuyển mới (gửi đến nhà tuyển dụng và đồng nghiệp)',
            'description' => 'Mẫu email gửi thông báo đến nhà tuyển dụng và đồng nghiệp khi hệ thống nhận được đơn ứng tuyển mới',
            'subject' => 'Đơn ứng tuyển mới',
        ],
        'new-job-posted' => [
            'title' => 'Việc làm mới được đăng',
            'description' => 'Gửi email đến quản trị viên khi có việc làm mới được đăng',
            'subject' => 'Việc làm mới được đăng trên {{ site_title }} bởi {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Hồ sơ công ty mới được tạo',
            'description' => 'Gửi email đến quản trị viên khi nhà tuyển dụng tạo hồ sơ công ty mới',
            'subject' => 'Hồ sơ công ty mới được tạo trên {{ site_title }} bởi {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Việc làm sắp hết hạn',
            'description' => 'Gửi email đến tác giả nếu việc làm của họ sẽ hết hạn trong 3 ngày tới',
            'subject' => 'Việc làm "{{ job_name }}" của bạn sẽ hết hạn trong {{ job_expired_after }} ngày',
        ],
        'job-renewed' => [
            'title' => 'Việc làm đã được gia hạn',
            'description' => 'Gửi email đến tác giả khi việc làm của họ được gia hạn',
            'subject' => 'Việc làm "{{ job_name }}" của bạn đã được gia hạn tự động',
        ],
        'payment-receipt' => [
            'title' => 'Biên lai thanh toán',
            'description' => 'Gửi thông báo đến người dùng khi họ mua credit',
            'subject' => 'Biên lai thanh toán cho gói {{ package_name }} trên {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Tài khoản đã đăng ký',
            'description' => 'Gửi thông báo đến quản trị viên khi có nhà tuyển dụng/người tìm việc mới đăng ký',
            'subject' => '{{ account_type }} mới đăng ký trên {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Xác nhận email',
            'description' => 'Gửi email đến người dùng khi họ đăng ký tài khoản để xác minh email',
            'subject' => 'Thông báo xác nhận email',
        ],
        'password-reminder' => [
            'title' => 'Đặt lại mật khẩu',
            'description' => 'Gửi email đến người dùng khi yêu cầu đặt lại mật khẩu',
            'subject' => 'Đặt lại mật khẩu',
        ],
        'free-credit-claimed' => [
            'title' => 'Credit miễn phí đã được nhận',
            'description' => 'Gửi thông báo đến quản trị viên khi credit miễn phí được nhận',
            'subject' => '{{ account_name }} đã nhận credit miễn phí trên {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Đã nhận thanh toán',
            'description' => 'Gửi thông báo đến quản trị viên khi có người mua credit',
            'subject' => 'Đã nhận thanh toán từ {{ account_name }} trên {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Chi tiết thanh toán hóa đơn',
            'description' => 'Gửi thông báo đến khách hàng thực hiện thanh toán đăng tin tuyển dụng',
            'subject' => 'Đã nhận thanh toán từ {{ account_name }} trên {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Việc làm mới được đăng',
            'description' => 'Gửi email đến người tìm việc khi có việc làm mới được đăng',
            'subject' => 'Tuyển dụng {{ job_name }} tại {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Việc làm đã được phê duyệt',
            'description' => 'Gửi email đến tác giả khi việc làm của họ được phê duyệt',
            'subject' => 'Việc làm "{{ job_name }}" của bạn đã được phê duyệt',
        ],
        'company-approved' => [
            'title' => 'Công ty đã được phê duyệt',
            'description' => 'Gửi email đến tác giả khi công ty của họ được phê duyệt',
            'subject' => 'Công ty "{{ company_name }}" của bạn đã được phê duyệt',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Xác nhận đơn ứng tuyển',
            'description' => 'Gửi email đến người tìm việc khi họ ứng tuyển cho một công việc',
            'subject' => 'Xác nhận ứng tuyển cho {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Tên',
        'position' => 'Vị trí',
        'email' => 'Email',
        'phone' => 'Điện thoại',
        'summary' => 'Tóm tắt',
        'resume' => 'Hồ sơ',
        'cover_letter' => 'Thư xin việc',
        'job_application' => 'Đơn ứng tuyển',
        'job_name' => 'Tên việc làm',
        'job_url' => 'URL việc làm',
        'job_author' => 'Tác giả việc làm',
        'company_name' => 'Tên công ty',
        'company_url' => 'URL công ty',
        'employer_name' => 'Tên nhà tuyển dụng',
        'job_list' => 'URL danh sách việc làm',
        'job_expired_after' => 'Việc làm hết hạn sau x ngày',
        'account_name' => 'Tên tài khoản',
        'account_email' => 'Email tài khoản',
        'package_name' => 'Tên gói dịch vụ',
        'package_price' => 'Giá',
        'package_percent_discount' => 'Phần trăm giảm giá',
        'package_number_of_listings' => 'Số lượng tin đăng',
        'package_price_per_credit' => 'Giá mỗi credit',
        'account_type' => 'Loại tài khoản (nhà tuyển dụng/người tìm việc)',
        'verify_link' => 'Liên kết xác minh',
        'reset_link' => 'Liên kết đặt lại',
        'invoice_code' => 'Mã hóa đơn',
        'invoice_link' => 'Liên kết hóa đơn',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Xin chào Quản trị viên!',
        'account_registered_new_account' => ':account_type mới đã đăng ký:',
        'account_registered_name' => 'Tên: <strong>:account_name</strong>',
        'account_registered_email' => 'Email: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Xin chào, Chúng tôi đã nhận được đơn ứng tuyển mới từ :site_title!',
        'admin_job_application_name' => 'Tên: :job_application_name',
        'admin_job_application_position' => 'Vị trí: :job_application_position',
        'admin_job_application_email' => 'Email: :job_application_email',
        'admin_job_application_phone' => 'Điện thoại: :job_application_phone',
        'admin_job_application_summary' => 'Tóm tắt: :job_application_summary',
        'admin_job_application_resume' => 'Hồ sơ: :job_application_resume',
        'admin_job_application_cover_letter' => 'Thư xin việc: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Xin chào, Chúng tôi đã nhận được đơn ứng tuyển mới từ :site_title!',
        'employer_job_application_name' => 'Tên: :job_application_name',
        'employer_job_application_position' => 'Vị trí: :job_application_position',
        'employer_job_application_email' => 'Email: :job_application_email',
        'employer_job_application_phone' => 'Điện thoại: :job_application_phone',
        'employer_job_application_summary' => 'Tóm tắt: :job_application_summary',
        'employer_job_application_resume' => 'Hồ sơ: :job_application_resume',
        'employer_job_application_cover_letter' => 'Thư xin việc: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Công ty đã được phê duyệt',
        'company_approved_greeting' => 'Xin chào,',
        'company_approved_message' => 'Chúng tôi vui mừng thông báo rằng công ty của bạn đã được phê duyệt và hiện đã hoạt động trên nền tảng của chúng tôi.',
        'company_approved_info' => 'Thông tin công ty',
        'company_approved_name' => 'Tên: <strong>:company_name</strong>',
        'company_approved_view' => 'Xem',
        'company_approved_here' => 'tại đây',

        // Confirm email template
        'confirm_email_greeting' => 'Xin chào!',
        'confirm_email_message' => 'Vui lòng xác minh địa chỉ email của bạn để truy cập trang web này. Nhấp vào nút bên dưới để xác minh email của bạn.',
        'confirm_email_button' => 'Xác minh ngay',
        'confirm_email_regards' => 'Trân trọng,',
        'confirm_email_trouble' => 'Nếu bạn gặp sự cố khi nhấp vào nút "Xác minh ngay", hãy sao chép và dán URL bên dưới vào trình duyệt web của bạn: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Việc làm đã được phê duyệt',
        'job_approved_greeting' => 'Xin chào :job_author,',
        'job_approved_message' => 'Chúng tôi vui mừng thông báo rằng tin tuyển dụng của bạn đã được phê duyệt và hiện đã hoạt động trên nền tảng của chúng tôi.',
        'job_approved_info' => 'Thông tin việc làm',
        'job_approved_job_title' => 'Tiêu đề việc làm: <strong>:job_name</strong>',
        'job_approved_view' => 'Xem',
        'job_approved_here' => 'tại đây',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Xin chào :job_author!',
        'job_expired_soon_message' => 'Việc làm <a href=":job_url">:job_name</a> của bạn sẽ hết hạn trong :job_expired_after ngày.',
        'job_expired_soon_renew' => 'Vui lòng <a href=":job_list">vào đây</a> để gia hạn việc làm của bạn.',

        // Job renewed email template
        'job_renewed_greeting' => 'Xin chào :job_author!',
        'job_renewed_message' => 'Việc làm <a href=":job_url">:job_name</a> của bạn đã được gia hạn tự động.',

        // New job posted email template
        'new_job_posted_title' => 'Việc làm mới được đăng',
        'new_job_posted_admin_greeting' => 'Xin chào Quản trị viên,',
        'new_job_posted_message' => 'Chúng tôi vui mừng thông báo rằng một tin tuyển dụng mới đã được đăng bởi nhà tuyển dụng trên nền tảng của chúng tôi.',
        'new_job_posted_info' => 'Bài đăng việc làm',
        'new_job_posted_employer' => 'Nhà tuyển dụng: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Tiêu đề việc làm: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Liên kết bảng quản trị',
        'new_job_posted_here' => 'tại đây',

        // New company profile created email template
        'new_company_profile_title' => 'Hồ sơ công ty mới được tạo',
        'new_company_profile_admin_greeting' => 'Xin chào Quản trị viên!',
        'new_company_profile_message' => 'Hồ sơ công ty mới được tạo bởi :employer_name ":company_name"',
        'new_company_profile_info' => 'Thông tin công ty',
        'new_company_profile_employer' => 'Nhà tuyển dụng: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Tên công ty: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Liên kết bảng quản trị',
        'new_company_profile_here' => 'tại đây',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Xin chào :account_name!',
        'payment_receipt_message' => 'Biên lai thanh toán cho giao dịch mua hàng của bạn:',
        'payment_receipt_package' => 'Gói: :package_name',
        'payment_receipt_price' => 'Giá: :package_price_per_credit/credit',
        'payment_receipt_total' => 'Tổng: :package_price cho :package_number_of_listings credit',
        'payment_receipt_save' => '(Tiết kiệm :package_percent_discount%)',
        'payment_receipt_thanks' => 'Cảm ơn bạn đã thanh toán!',
        'payment_receipt_info' => 'Thông tin thanh toán',
        'payment_receipt_amount' => 'Số tiền: :package_price',
        'payment_receipt_invoice' => 'Mã hóa đơn: :invoice_code',
        'payment_receipt_view_invoice' => 'Xem hóa đơn',

        // Payment received email template
        'payment_received_admin_greeting' => 'Xin chào Quản trị viên!',
        'payment_received_message' => 'Đã nhận thanh toán từ :account_name:',
        'payment_received_account' => 'Tài khoản: :account_name (:account_email)',
        'payment_received_package' => 'Gói: :package_name',
        'payment_received_price' => 'Giá: :package_price_per_credit/credit',
        'payment_received_total' => 'Tổng: :package_price cho :package_number_of_listings credit',
        'payment_received_save' => '(Tiết kiệm :package_percent_discount%)',
        'payment_received_info' => 'Thông tin thanh toán',
        'payment_received_customer' => 'Khách hàng: :account_name',
        'payment_received_amount' => 'Số tiền: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Xin chào :account_name,',
        'invoice_payment_from' => 'Bạn đang nhận email từ :site_title',
        'invoice_payment_attached' => 'Hóa đơn #:invoice_code được đính kèm trong email này.',
        'invoice_payment_view_online' => 'Xem trực tuyến',
        'invoice_payment_thanks' => 'Cảm ơn bạn đã thanh toán!',
        'invoice_payment_info' => 'Thông tin hóa đơn',
        'invoice_payment_code' => 'Mã hóa đơn: :invoice_code',
        'invoice_payment_view' => 'Xem hóa đơn',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Xin chào Quản trị viên,',
        'free_credit_claimed_message' => ':account_name đã nhận credit miễn phí trên :site_title',
        'free_credit_claimed_info' => 'Thông tin tài khoản',
        'free_credit_claimed_name' => 'Tên: :account_name',
        'free_credit_claimed_email' => 'Email: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Xin chào!',
        'password_reminder_message' => 'Bạn nhận được email này vì chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.',
        'password_reminder_button' => 'Đặt lại mật khẩu',
        'password_reminder_no_action' => 'Nếu bạn không yêu cầu đặt lại mật khẩu, không cần thực hiện thêm hành động nào.',
        'password_reminder_regards' => 'Trân trọng,',
        'password_reminder_trouble' => 'Nếu bạn gặp sự cố khi nhấp vào nút "Đặt lại mật khẩu", hãy sao chép và dán URL bên dưới vào trình duyệt web của bạn: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Xin chào :account_name!',
        'job_alert_hiring' => 'Tuyển dụng :job_name tại :company_name',
        'job_alert_apply_forward' => '👇 Ứng tuyển hoặc Chuyển tiếp cho bạn bè: :job_url',
        'job_alert_message' => 'Cơ hội việc làm mới phù hợp với sở thích của bạn đã được đăng!',
        'job_alert_job_info' => 'Việc làm: :job_name',
        'job_alert_company_info' => 'Công ty: :company_name',
        'job_alert_view_job' => 'Xem việc làm',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Xác nhận đơn ứng tuyển',
        'job_application_confirmation_greeting' => 'Kính gửi :job_application_name,',
        'job_application_confirmation_thanks' => 'Cảm ơn bạn đã quan tâm đến vị trí :job_name tại :company_name. Chúng tôi vui mừng xác nhận rằng đơn ứng tuyển của bạn đã được gửi thành công qua hệ thống của chúng tôi.',
        'job_application_confirmation_reviewing' => 'Đội ngũ tuyển dụng của chúng tôi đang xem xét trình độ của bạn và sẽ liên hệ với bạn nếu kỹ năng và kinh nghiệm của bạn phù hợp với yêu cầu của vị trí này. Xin lưu ý rằng do số lượng đơn ứng tuyển lớn, quá trình này có thể mất một thời gian.',
        'job_application_confirmation_thanks_again' => 'Cảm ơn bạn một lần nữa vì đã ứng tuyển!',
        'job_application_confirmation_regards' => 'Trân trọng,',
        'job_application_confirmation_team' => 'Đội ngũ :company_name',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Xin chào,',
        'new_job_application_received' => 'Bạn đã nhận được đơn ứng tuyển mới.',
        'new_job_application_details' => 'Chi tiết đơn ứng tuyển:',
        'new_job_application_name' => 'Tên: :job_application_name',
        'new_job_application_position' => 'Vị trí: :job_application_position',
        'new_job_application_email' => 'Email: :job_application_email',
        'new_job_application_phone' => 'Điện thoại: :job_application_phone',
    ],
];
