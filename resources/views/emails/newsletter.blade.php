<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $subject }}</title>
<style>
  body { margin: 0; padding: 0; background: #f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; }
  .wrapper { max-width: 620px; margin: 30px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
  .header { background: #1a3c6e; padding: 28px 40px; text-align: center; }
  .header img { max-height: 44px; }
  .header-title { color: #ffffff; font-size: 13px; letter-spacing: 2px; text-transform: uppercase; margin-top: 8px; opacity: 0.75; }
  .banner-img { width: 100%; height: auto; display: block; }
  .content { padding: 40px 44px; }
  .nl-title { font-size: 26px; font-weight: 800; color: #1a3c6e; margin: 0 0 6px; line-height: 1.25; }
  .nl-divider { border: none; border-bottom: 3px solid #f0a500; width: 48px; margin: 0 0 24px; }
  .body-text { font-size: 15px; line-height: 1.8; color: #444; white-space: pre-line; margin: 0 0 32px; }
  .cta { text-align: center; margin: 32px 0; }
  .cta a { background: #1a3c6e; color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 6px; font-size: 15px; font-weight: 600; display: inline-block; }
  .attachment-notice { background: #f8f9fb; border-left: 4px solid #1a3c6e; padding: 12px 16px; margin: 0 0 24px; font-size: 13px; color: #555; border-radius: 0 4px 4px 0; }
  .attachment-notice span { font-weight: 600; color: #1a3c6e; }
  .divider { border: none; border-top: 1px solid #e8ecf0; margin: 32px 0 0; }
  .footer { background: #f8f9fb; padding: 24px 44px; text-align: center; }
  .footer p { font-size: 12px; color: #888; margin: 4px 0; }
  .footer a { color: #1a3c6e; text-decoration: none; }
</style>
</head>
<body>
<div class="wrapper">

  <!-- Logo header -->
  <div class="header">
    <img src="https://www.wakandajobs.com/storage/chatgpt-image-may-14-2026-03-00-04-pm.png" alt="Wakanda Jobs">
    <div class="header-title">Newsletter</div>
  </div>

  <!-- Full-width banner image -->
  @if($imageUrl)
  <img src="{{ $imageUrl }}" alt="" class="banner-img">
  @endif

  <!-- Content -->
  <div class="content">

    <div class="nl-title">{{ $subject }}</div>
    <hr class="nl-divider">

    @if($pdfName)
    <div class="attachment-notice">📎 <span>Brochure attached:</span> {{ $pdfName }}</div>
    @endif

    <div class="body-text">{{ $body }}</div>

    <div class="cta">
      <a href="https://www.wakandajobs.com/jobs">Browse Latest Jobs &rarr;</a>
    </div>
  </div>

  <hr class="divider">

  <!-- Footer -->
  <div class="footer">
    <p>©2026 Wakanda Systems. All rights reserved.</p>
    <p>
      <a href="https://www.wakandajobs.com">WakandaJobs.com</a> &nbsp;|&nbsp;
      <a href="{{ url('/newsletter/unsubscribe/' . $subscriberId) }}">Unsubscribe</a>
    </p>
  </div>

</div>
</body>
</html>
