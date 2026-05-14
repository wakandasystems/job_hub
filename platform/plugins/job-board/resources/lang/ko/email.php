<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => '새 구직 신청 (관리자용)',
            'description' => '시스템에 새로운 구직 신청이 접수될 때 관리자에게 알림을 보내는 이메일 템플릿',
            'subject' => '새 구직 신청',
        ],
        'employer-new-job-application' => [
            'title' => '새 구직 신청 (고용주 및 동료용)',
            'description' => '시스템에 새로운 구직 신청이 접수될 때 고용주 및 동료에게 알림을 보내는 이메일 템플릿',
            'subject' => '새 구직 신청',
        ],
        'new-job-posted' => [
            'title' => '새 채용 공고 게시',
            'description' => '새 채용 공고가 게시될 때 관리자에게 이메일 발송',
            'subject' => '{{ job_author }}님이 {{ site_title }}에 새 채용 공고를 게시했습니다',
        ],
        'new-company-profile-created' => [
            'title' => '새 회사 프로필 생성',
            'description' => '고용주가 새 회사 프로필을 생성할 때 관리자에게 이메일 발송',
            'subject' => '{{ employer_name }}님이 {{ site_title }}에 새 회사 프로필을 생성했습니다',
        ],
        'job-expired-soon' => [
            'title' => '채용 공고 만료 임박',
            'description' => '채용 공고가 3일 내에 만료될 경우 작성자에게 이메일 발송',
            'subject' => '귀하의 채용 공고 "{{ job_name }}"이(가) {{ job_expired_after }}일 후에 만료됩니다',
        ],
        'job-renewed' => [
            'title' => '채용 공고 갱신',
            'description' => '채용 공고가 갱신될 때 작성자에게 이메일 발송',
            'subject' => '귀하의 채용 공고 "{{ job_name }}"이(가) 자동으로 갱신되었습니다',
        ],
        'payment-receipt' => [
            'title' => '결제 영수증',
            'description' => '사용자가 크레딧을 구매할 때 알림 발송',
            'subject' => '{{ site_title }}의 {{ package_name }} 패키지 결제 영수증',
        ],
        'account-registered' => [
            'title' => '계정 등록',
            'description' => '새로운 고용주/구직자가 등록할 때 관리자에게 알림 발송',
            'subject' => '{{ site_title }}에 새 {{ account_type }}이(가) 등록되었습니다',
        ],
        'confirm-email' => [
            'title' => '이메일 확인',
            'description' => '사용자가 계정을 등록할 때 이메일 확인을 위한 이메일 발송',
            'subject' => '이메일 확인 알림',
        ],
        'password-reminder' => [
            'title' => '비밀번호 재설정',
            'description' => '비밀번호 재설정 요청 시 사용자에게 이메일 발송',
            'subject' => '비밀번호 재설정',
        ],
        'free-credit-claimed' => [
            'title' => '무료 크레딧 청구',
            'description' => '무료 크레딧이 청구될 때 관리자에게 알림 발송',
            'subject' => '{{ account_name }}님이 {{ site_title }}에서 무료 크레딧을 청구했습니다',
        ],
        'payment-received' => [
            'title' => '결제 접수',
            'description' => '누군가 크레딧을 구매할 때 관리자에게 알림 발송',
            'subject' => '{{ site_title }}에서 {{ account_name }}님으로부터 결제가 접수되었습니다',
        ],
        'invoice-payment-created' => [
            'title' => '송장 결제 세부정보',
            'description' => '채용 공고 결제를 한 고객에게 알림 발송',
            'subject' => '{{ site_title }}에서 {{ account_name }}님으로부터 결제가 접수되었습니다',
        ],
        'job-seeker-job-alert' => [
            'title' => '새 채용 공고 게시',
            'description' => '새 채용 공고가 게시될 때 구직자에게 이메일 발송',
            'subject' => '{{ company_name }}에서 {{ job_name }} 모집 중',
        ],
        'job-approved' => [
            'title' => '채용 공고 승인',
            'description' => '채용 공고가 승인될 때 작성자에게 이메일 발송',
            'subject' => '귀하의 채용 공고 "{{ job_name }}"이(가) 승인되었습니다',
        ],
        'company-approved' => [
            'title' => '회사 승인',
            'description' => '회사가 승인될 때 작성자에게 이메일 발송',
            'subject' => '귀하의 회사 "{{ company_name }}"이(가) 승인되었습니다',
        ],
        'job-seeker-applied-job' => [
            'title' => '구직 신청 확인',
            'description' => '구직자가 채용 공고에 지원할 때 이메일 발송',
            'subject' => '{{ job_name }}에 대한 지원 확인',
        ],
    ],
    'variables' => [
        'name' => '이름',
        'position' => '직위',
        'email' => '이메일',
        'phone' => '전화번호',
        'summary' => '요약',
        'resume' => '이력서',
        'cover_letter' => '자기소개서',
        'job_application' => '구직 신청',
        'job_name' => '채용 공고명',
        'job_url' => '채용 공고 URL',
        'job_author' => '채용 공고 작성자',
        'company_name' => '회사명',
        'company_url' => '회사 URL',
        'employer_name' => '고용주명',
        'job_list' => '채용 공고 목록 URL',
        'job_expired_after' => 'x일 후 채용 공고 만료',
        'account_name' => '계정명',
        'account_email' => '계정 이메일',
        'package_name' => '패키지명',
        'package_price' => '가격',
        'package_percent_discount' => '할인율',
        'package_number_of_listings' => '게시 횟수',
        'package_price_per_credit' => '크레딧당 가격',
        'account_type' => '계정 유형 (고용주/구직자)',
        'verify_link' => '확인 링크',
        'reset_link' => '재설정 링크',
        'invoice_code' => '송장 코드',
        'invoice_link' => '송장 링크',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => '안녕하세요 관리자님!',
        'account_registered_new_account' => '새 :account_type이(가) 등록되었습니다:',
        'account_registered_name' => '이름: <strong>:account_name</strong>',
        'account_registered_email' => '이메일: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => '안녕하세요, :site_title에서 새로운 구직 신청을 받았습니다!',
        'admin_job_application_name' => '이름: :job_application_name',
        'admin_job_application_position' => '직위: :job_application_position',
        'admin_job_application_email' => '이메일: :job_application_email',
        'admin_job_application_phone' => '전화번호: :job_application_phone',
        'admin_job_application_summary' => '요약: :job_application_summary',
        'admin_job_application_resume' => '이력서: :job_application_resume',
        'admin_job_application_cover_letter' => '자기소개서: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => '안녕하세요, :site_title에서 새로운 구직 신청을 받았습니다!',
        'employer_job_application_name' => '이름: :job_application_name',
        'employer_job_application_position' => '직위: :job_application_position',
        'employer_job_application_email' => '이메일: :job_application_email',
        'employer_job_application_phone' => '전화번호: :job_application_phone',
        'employer_job_application_summary' => '요약: :job_application_summary',
        'employer_job_application_resume' => '이력서: :job_application_resume',
        'employer_job_application_cover_letter' => '자기소개서: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => '회사 승인',
        'company_approved_greeting' => '안녕하세요,',
        'company_approved_message' => '귀하의 회사가 승인되어 현재 플랫폼에 게시되었음을 알려드립니다.',
        'company_approved_info' => '회사 정보',
        'company_approved_name' => '이름: <strong>:company_name</strong>',
        'company_approved_view' => '보기',
        'company_approved_here' => '여기',

        // Confirm email template
        'confirm_email_greeting' => '안녕하세요!',
        'confirm_email_message' => '이 웹사이트에 액세스하려면 이메일 주소를 확인해주세요. 아래 버튼을 클릭하여 이메일을 확인하세요.',
        'confirm_email_button' => '지금 확인',
        'confirm_email_regards' => '감사합니다,',
        'confirm_email_trouble' => '"지금 확인" 버튼을 클릭하는 데 문제가 있으시면 다음 URL을 복사하여 웹 브라우저에 붙여넣으세요: :verify_link',

        // Job approved email template
        'job_approved_title' => '채용 공고 승인',
        'job_approved_greeting' => '안녕하세요 :job_author님,',
        'job_approved_message' => '귀하의 채용 공고가 승인되어 현재 플랫폼에 게시되었음을 알려드립니다.',
        'job_approved_info' => '채용 공고 정보',
        'job_approved_job_title' => '채용 공고 제목: <strong>:job_name</strong>',
        'job_approved_view' => '보기',
        'job_approved_here' => '여기',

        // Job expired soon email template
        'job_expired_soon_greeting' => '안녕하세요 :job_author님!',
        'job_expired_soon_message' => '귀하의 채용 공고 <a href=":job_url">:job_name</a>이(가) :job_expired_after일 후에 만료됩니다.',
        'job_expired_soon_renew' => '<a href=":job_list">여기</a>에서 채용 공고를 갱신하세요.',

        // Job renewed email template
        'job_renewed_greeting' => '안녕하세요 :job_author님!',
        'job_renewed_message' => '귀하의 채용 공고 <a href=":job_url">:job_name</a>이(가) 자동으로 갱신되었습니다.',

        // New job posted email template
        'new_job_posted_title' => '새 채용 공고 게시',
        'new_job_posted_admin_greeting' => '안녕하세요 관리자님,',
        'new_job_posted_message' => '고용주가 플랫폼에 새로운 채용 공고를 게시했음을 알려드립니다.',
        'new_job_posted_info' => '채용 공고',
        'new_job_posted_employer' => '고용주: <strong>:job_author</strong>',
        'new_job_posted_job_title' => '채용 공고 제목: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => '관리자 패널 링크',
        'new_job_posted_here' => '여기',

        // New company profile created email template
        'new_company_profile_title' => '새 회사 프로필 생성',
        'new_company_profile_admin_greeting' => '안녕하세요 관리자님!',
        'new_company_profile_message' => ':employer_name님이 ":company_name" 회사 프로필을 생성했습니다',
        'new_company_profile_info' => '회사 정보',
        'new_company_profile_employer' => '고용주: <strong>:employer_name</strong>',
        'new_company_profile_name' => '회사명: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => '관리자 패널 링크',
        'new_company_profile_here' => '여기',

        // Payment receipt email template
        'payment_receipt_greeting' => '안녕하세요 :account_name님!',
        'payment_receipt_message' => '구매에 대한 결제 영수증:',
        'payment_receipt_package' => '패키지: :package_name',
        'payment_receipt_price' => '가격: :package_price_per_credit/크레딧',
        'payment_receipt_total' => '합계: :package_number_of_listings 크레딧에 :package_price',
        'payment_receipt_save' => '(:package_percent_discount% 절약)',
        'payment_receipt_thanks' => '결제해 주셔서 감사합니다!',
        'payment_receipt_info' => '결제 정보',
        'payment_receipt_amount' => '금액: :package_price',
        'payment_receipt_invoice' => '송장 코드: :invoice_code',
        'payment_receipt_view_invoice' => '송장 보기',

        // Payment received email template
        'payment_received_admin_greeting' => '안녕하세요 관리자님!',
        'payment_received_message' => ':account_name님으로부터 결제가 접수되었습니다:',
        'payment_received_account' => '계정: :account_name (:account_email)',
        'payment_received_package' => '패키지: :package_name',
        'payment_received_price' => '가격: :package_price_per_credit/크레딧',
        'payment_received_total' => '합계: :package_number_of_listings 크레딧에 :package_price',
        'payment_received_save' => '(:package_percent_discount% 절약)',
        'payment_received_info' => '결제 정보',
        'payment_received_customer' => '고객: :account_name',
        'payment_received_amount' => '금액: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => '안녕하세요 :account_name님,',
        'invoice_payment_from' => ':site_title에서 이메일을 받으셨습니다',
        'invoice_payment_attached' => '송장 #:invoice_code이(가) 이 이메일에 첨부되어 있습니다.',
        'invoice_payment_view_online' => '온라인으로 보기',
        'invoice_payment_thanks' => '결제해 주셔서 감사합니다!',
        'invoice_payment_info' => '송장 정보',
        'invoice_payment_code' => '송장 코드: :invoice_code',
        'invoice_payment_view' => '송장 보기',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => '안녕하세요 관리자님,',
        'free_credit_claimed_message' => ':account_name님이 :site_title에서 무료 크레딧을 청구했습니다',
        'free_credit_claimed_info' => '계정 정보',
        'free_credit_claimed_name' => '이름: :account_name',
        'free_credit_claimed_email' => '이메일: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => '안녕하세요!',
        'password_reminder_message' => '귀하의 계정에 대한 비밀번호 재설정 요청을 받았기 때문에 이 이메일을 받으셨습니다.',
        'password_reminder_button' => '비밀번호 재설정',
        'password_reminder_no_action' => '비밀번호 재설정을 요청하지 않으셨다면 추가 조치가 필요하지 않습니다.',
        'password_reminder_regards' => '감사합니다,',
        'password_reminder_trouble' => '"비밀번호 재설정" 버튼을 클릭하는 데 문제가 있으시면 다음 URL을 복사하여 웹 브라우저에 붙여넣으세요: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => '안녕하세요 :account_name님!',
        'job_alert_hiring' => ':company_name에서 :job_name 모집 중',
        'job_alert_apply_forward' => '👇 지원하거나 친구에게 전달하세요: :job_url',
        'job_alert_message' => '귀하의 선호도와 일치하는 새로운 채용 기회가 게시되었습니다!',
        'job_alert_job_info' => '채용 공고: :job_name',
        'job_alert_company_info' => '회사: :company_name',
        'job_alert_view_job' => '채용 공고 보기',

        // Job seeker applied job email template
        'job_application_confirmation_title' => '구직 신청 확인',
        'job_application_confirmation_greeting' => ':job_application_name님께,',
        'job_application_confirmation_thanks' => ':company_name의 :job_name 직위에 관심을 가져주셔서 감사합니다. 귀하의 지원서가 시스템을 통해 성공적으로 제출되었음을 확인합니다.',
        'job_application_confirmation_reviewing' => '저희 채용팀이 귀하의 자격을 검토하고 있으며, 귀하의 기술과 경험이 이 직무의 요구 사항과 일치하면 연락드리겠습니다. 많은 지원으로 인해 이 과정에 시간이 걸릴 수 있음을 양해해 주시기 바랍니다.',
        'job_application_confirmation_thanks_again' => '지원해 주셔서 다시 한 번 감사드립니다!',
        'job_application_confirmation_regards' => '감사합니다,',
        'job_application_confirmation_team' => ':company_name 팀',

        // New job application (simplified) template
        'new_job_application_greeting' => '안녕하세요,',
        'new_job_application_received' => '새로운 구직 신청을 받았습니다.',
        'new_job_application_details' => '지원 세부정보:',
        'new_job_application_name' => '이름: :job_application_name',
        'new_job_application_position' => '직위: :job_application_position',
        'new_job_application_email' => '이메일: :job_application_email',
        'new_job_application_phone' => '전화번호: :job_application_phone',
    ],
];
