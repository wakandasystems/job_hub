---
title: "Wakanda Jobs Monetization Meeting Cheatsheet"
subtitle: "High-Level Talking Points"
date: "June 2, 2026"
---

# Wakanda Jobs Monetization Meeting Cheatsheet

## Meeting Goal

Use this document to explain how Wakanda Jobs has moved from a basic job board into a monetized hiring marketplace. The key message is that the platform now has several revenue paths across employers, candidates, job visibility, candidate access, job alerts, and professional services.

## Executive Summary

Wakanda Jobs now monetizes both sides of the marketplace:

- Employers pay for hiring reach, candidate access, credits, subscriptions, featured placement, and verified talent unlocks.
- Candidates pay for visibility and trust products such as application boosts and Wakanda Badge verification.
- The platform supports both automated online payments and manual payment workflows such as bank transfer or cash on delivery.
- Admin review and approval flows are built into the monetization process, which protects trust and allows manual operational control.
- Organic jobs are prioritized above crawled jobs, protecting platform quality and making paid employer tools more valuable.

## Monetization Pillars Implemented

### 1. Employer Credit System

Employers can buy credit packages. Approved credit orders add credits to the employer account and create a transaction record.

Credits are the internal currency that powers several monetized actions:

- Candidate contact reveal.
- Talent Pool profile unlock.
- Application boost bidding.
- Job renewal and other employer actions connected to the existing credits system.

Meeting point: credits create prepaid revenue and make multiple platform features easier to monetize without forcing a separate checkout every time.

### 2. Employer Subscriptions

Packages now support billing cycles:

- One-time packages add credits.
- Monthly subscriptions create time-bound employer access.
- Annual subscriptions create longer-term recurring revenue.

Active subscriptions can unlock premium employer features such as candidate search access and job posting allowance per cycle.

Meeting point: subscriptions create predictable recurring revenue, while one-time credits serve smaller employers who are not ready for recurring plans.

### 3. Featured Jobs and Bid-Based Placement

Employers can pay to feature jobs. Approved featured orders:

- Mark the job as featured.
- Set a `featured_until` expiry.
- Store a `featured_bid`.
- Rank featured jobs by bid after organic priority.

Only the first 8 featured jobs per listing page receive the visible "Featured" badge.

Meeting point: the bid system turns limited attention on the jobs page into a revenue product. Employers who want stronger visibility can pay more.

### 4. Paid Job Alert Packages

The job alert system supports free and paid usage:

- Free users have a monthly alert limit.
- Paid alert packages create approved monthly quota records.
- Paid quota checks use the `activePaid()` scope to avoid treating unpaid or pending orders as active.

Meeting point: job alerts monetize high-intent candidates while keeping a free tier for growth.

### 5. Wakanda Badge Verification

Candidates can pay for Wakanda verification. The default configured cost is ZMW 50 unless changed in settings.

The flow is:

1. Candidate starts verification checkout.
2. Payment is processed online or manually.
3. Paid request moves to pending admin review.
4. Admin approves or rejects with a 1-5 score.
5. Approved candidates receive `wakanda_verified`, `wakanda_score`, and `wakanda_verified_at`.
6. Employers see the Wakanda Badge in applicant lists and Talent Pool contexts.

Meeting point: Wakanda Badge monetizes candidate trust while improving employer confidence in applicant quality.

### 6. Talent Pool Unlocks

Employers can browse Wakanda-verified candidates in the Talent Pool. Unlocking a profile:

- Costs credits, defaulting to 20 credits through `wakanda_unlock_cost`.
- Is permanent for that employer/candidate pair.
- Cannot be charged twice because of the unique unlock constraint.
- Reveals candidate details such as email, phone, country, experience, bio, resume URL, and verification score.

Meeting point: the Talent Pool converts vetted candidate supply into direct employer revenue.

### 7. Candidate Contact Reveal

Employer access to candidate contact information is paywalled:

- Employers with qualifying subscriptions can reveal contacts through subscription access.
- Employers without subscription access can spend credits.
- The default credit cost is controlled by `cv_reveal_credit_cost`.

Meeting point: this creates a simple "pay for hiring intent" model without blocking the public candidate discovery experience.

### 8. Application Boost System

After applying to internal jobs, candidates with credits can boost their application. The system:

- Deducts credits atomically.
- Increments `jb_applications.boost_bid`.
- Sorts employer applicant lists by `boost_bid DESC`.
- Shows boosted rows with a credit badge.

Meeting point: this turns candidate demand for visibility into revenue while still preserving the application flow.

### 9. Career Services Orders

Career services have a checkout and admin delivery flow. Orders are created as pending, paid through supported payment methods, and managed by admin after approval.

Meeting point: career services add service revenue beyond job advertising.

### 10. Payment and Admin Control

The payment system is integrated through Botble payment hooks. It supports:

- Online gateway payments.
- Manual payments such as bank transfer and cash on delivery.
- Payment references.
- Admin approval for manual payments.
- Admin notifications for review-based workflows.
- Correct post-payment redirect and cancellation flows per checkout type.

Meeting point: the revenue infrastructure is reusable, which makes future paid products faster to add.

## Positioning for Investors or Partners

Wakanda Jobs is not only a listings website. It is becoming a hiring transaction platform with multiple monetization layers:

- Attention: featured jobs and boosted applications.
- Access: candidate search, CV reveal, and Talent Pool unlocks.
- Trust: Wakanda Badge verification.
- Recurrence: employer subscriptions and paid alert quotas.
- Services: career service orders.

## Strong Talking Points

- "We have monetized both employer demand and candidate demand."
- "Credits let us sell prepaid usage and apply it across multiple products."
- "The featured job bid model lets the market price visibility."
- "The Talent Pool creates a premium employer product from verified candidate supply."
- "Wakanda Badge turns trust into both a quality signal and a revenue product."
- "Manual payment support matters locally because not every buyer will use card payments."
- "Admin approval flows protect quality while still allowing us to collect revenue."

## Questions to Be Ready For

### How does the platform make money today?

Through employer credits, subscription packages, featured job orders, paid job alert packages, candidate verification, Talent Pool unlocks, contact reveals, application boosts, and career service orders.

### What is the most scalable revenue stream?

Employer subscriptions and paid candidate access are the most scalable because they create repeat employer spend. Featured jobs and boosts add performance-based upside.

### What protects quality?

Organic jobs are prioritized, Wakanda Badge requires admin review, Talent Pool only includes verified candidates, and manual payments require approval before benefits are granted.

### Why use credits?

Credits reduce payment friction. Employers or candidates can buy once and spend across multiple high-intent actions.

### What should be measured next?

Track conversion from free to paid, credit purchase frequency, credit burn rate, featured job purchases, Talent Pool unlocks, verification approval rate, subscription renewal rate, and revenue per employer.

## Closing Message

The project now has the foundations of a monetized marketplace. The next stage is not just adding more features, but improving packaging, pricing, analytics, and sales execution around the revenue systems already implemented.
