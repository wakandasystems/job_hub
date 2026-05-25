<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $emailSubject }}</title>
<style>
  body { margin: 0; padding: 0; background: #f4f6f8; font-family: 'Segoe UI', Arial, sans-serif; color: #2d3748; }
  .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  .header { background: linear-gradient(135deg, #1a202c 0%, #2d6a4f 100%); padding: 36px 40px 28px; text-align: center; }
  .header img { height: 40px; margin-bottom: 12px; }
  .header h1 { margin: 0; color: #fff; font-size: 22px; font-weight: 700; letter-spacing: -.3px; }
  .header p { margin: 6px 0 0; color: rgba(255,255,255,.78); font-size: 14px; }
  .body { padding: 36px 40px; }
  .greeting { font-size: 16px; margin-bottom: 20px; }
  .intro { font-size: 15px; line-height: 1.7; color: #4a5568; margin-bottom: 28px; }
  .benefits { background: #f7fafc; border-radius: 8px; padding: 22px 26px; margin-bottom: 28px; }
  .benefits h3 { margin: 0 0 14px; font-size: 14px; text-transform: uppercase; letter-spacing: .6px; color: #718096; }
  .benefit { display: flex; align-items: flex-start; margin-bottom: 12px; }
  .benefit:last-child { margin-bottom: 0; }
  .benefit-icon { font-size: 18px; margin-right: 12px; flex-shrink: 0; line-height: 1.4; }
  .benefit-text { font-size: 14px; line-height: 1.6; color: #4a5568; }
  .benefit-text strong { color: #2d3748; }
  .cta-wrap { text-align: center; margin: 32px 0; }
  .cta { display: inline-block; background: #2d6a4f; color: #fff !important; text-decoration: none; padding: 14px 36px; border-radius: 6px; font-weight: 700; font-size: 15px; letter-spacing: .2px; }
  .cta:hover { background: #245a42; }
  .note { font-size: 12px; color: #a0aec0; text-align: center; margin-top: 8px; }
  .footer { background: #f7fafc; border-top: 1px solid #e2e8f0; padding: 20px 40px; text-align: center; }
  .footer p { margin: 4px 0; font-size: 12px; color: #a0aec0; }
  .footer a { color: #718096; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>WakandaJobs Newsletter</h1>
    <p>Your weekly edge in Africa's job market</p>
  </div>

  <div class="body">
    <p class="greeting">Hi <strong>{{ $recipientName }}</strong>,</p>

    @if($accountType === 'employer')
    <p class="intro">
      You have an active employer account on WakandaJobs — but you're not yet receiving our weekly newsletter.
      Each week we share hiring trends, standout candidates, and platform updates that help recruiters
      like you <strong>find the right talent faster</strong>.
    </p>

    <div class="benefits">
      <h3>What you'll get every week</h3>
      <div class="benefit">
        <span class="benefit-icon">📈</span>
        <span class="benefit-text"><strong>Hiring trends &amp; market insights</strong> — know which roles are in demand and what candidates expect.</span>
      </div>
      <div class="benefit">
        <span class="benefit-icon">🌍</span>
        <span class="benefit-text"><strong>Top active job seekers</strong> — featured profiles across Africa ready to move.</span>
      </div>
      <div class="benefit">
        <span class="benefit-icon">📣</span>
        <span class="benefit-text"><strong>Platform updates</strong> — new features, posting tips, and tools to get more applicants.</span>
      </div>
      <div class="benefit">
        <span class="benefit-icon">🎯</span>
        <span class="benefit-text"><strong>Featured listing opportunities</strong> — early access to promotions for boosting your jobs.</span>
      </div>
    </div>

    @else
    <p class="intro">
      You have an active job seeker account on WakandaJobs — but you're not yet receiving our weekly newsletter.
      Every week we hand-pick the <strong>best new opportunities</strong> across Africa so you never miss
      a role that matches your goals.
    </p>

    <div class="benefits">
      <h3>What you'll get every week</h3>
      <div class="benefit">
        <span class="benefit-icon">🔥</span>
        <span class="benefit-text"><strong>Top featured jobs</strong> — hand-picked opportunities from leading companies across Africa.</span>
      </div>
      <div class="benefit">
        <span class="benefit-icon">📂</span>
        <span class="benefit-text"><strong>Trending categories</strong> — see which industries are hiring most so you can target your applications.</span>
      </div>
      <div class="benefit">
        <span class="benefit-icon">⚡</span>
        <span class="benefit-text"><strong>First to know</strong> — new roles delivered to your inbox before they go viral.</span>
      </div>
      <div class="benefit">
        <span class="benefit-icon">💡</span>
        <span class="benefit-text"><strong>Career tips</strong> — practical advice on CV writing, interviews, and salary negotiation.</span>
      </div>
    </div>
    @endif

    <div class="cta-wrap">
      <a href="{{ $subscribeUrl }}" class="cta">Yes, Subscribe Me — It's Free</a>
    </div>
    <p class="note">One click. No spam. Unsubscribe anytime.</p>
  </div>

  <div class="footer">
    <p><strong>WakandaJobs</strong> — Careers across Africa and beyond</p>
    <p>© {{ date('Y') }} Wakanda Systems. All rights reserved.</p>
    <p><a href="https://www.wakandajobs.com">wakandajobs.com</a></p>
  </div>
</div>
</body>
</html>
