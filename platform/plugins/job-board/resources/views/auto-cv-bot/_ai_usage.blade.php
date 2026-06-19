@php
    $totalTokens = $session->ai_total_prompt_tokens + $session->ai_total_completion_tokens;
@endphp
<div class="d-flex flex-wrap gap-3 align-items-center">
    <div>
        <span class="text-muted small">Token usage:</span>
        <strong>{{ number_format($totalTokens) }}</strong>
        <span class="text-muted small">({{ number_format($session->ai_total_prompt_tokens) }} prompt + {{ number_format($session->ai_total_completion_tokens) }} completion)</span>
    </div>
    <div>
        <span class="text-muted small">Total cost:</span>
        <strong>${{ number_format($session->ai_total_cost_usd, 4) }}</strong>
    </div>
    <div class="text-muted small">{{ count($session->ai_calls ?: []) }} AI calls</div>
</div>
