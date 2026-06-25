<x-core::card class="mt-3">
    <x-core::card.header>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <x-core::card.title>Custom Campaign Images</x-core::card.title>
            <a href="{{ route('sales-agents.show', $agent->getKey()) }}" class="btn btn-outline-dark btn-sm">
                <x-core::icon name="ti ti-layout-grid" class="me-1" /> Full image history
            </a>
        </div>
    </x-core::card.header>
    <x-core::card.body>
        @if ($campaigns->isEmpty())
            <div class="alert alert-warning mb-0">Create an active marketing campaign first, then come back here to generate posters for this agent.</div>
        @else
            <div class="alert alert-info">
                <div class="fw-semibold mb-1">Build a campaign image for this agent</div>
                <div class="small mb-0">
                    Default mode:
                    <strong>{{ \Botble\JobBoard\Models\SalesAgentMarketingImage::subjectModes()[$agent->preferredMarketingSubjectMode()] ?? 'Nakia (default)' }}</strong>.
                    @if ($agent->use_marketing_photo && ! $agent->hasMarketingPhoto())
                        Upload the agent photo above first if you want posters to include both Nakia and the agent.
                    @endif
                </div>
            </div>

            <div class="row g-3">
                @foreach ($campaigns as $campaign)
                    @php($latestImage = $latestMarketingImages->get($campaign->getKey()))
                    <div class="col-xl-4 col-lg-6">
                        <div class="border rounded h-100 p-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold">{{ $campaign->name }}</div>
                                    <div class="text-muted small">
                                        {{ $campaign->promo_price ?: 'No promo price' }}
                                        @if ($campaign->promo_end_date)
                                            · Ends {{ $campaign->promo_end_date->format('Y-m-d') }}
                                        @endif
                                    </div>
                                </div>
                                <span class="badge bg-success text-white">Active</span>
                            </div>

                            @if ($latestImage && $latestImage->status === 'completed' && $latestImage->imageUrl())
                                <div class="ratio ratio-1x1 bg-light rounded overflow-hidden mb-3">
                                    <img src="{{ $latestImage->imageUrl() }}" alt="{{ $campaign->name }}" style="width:100%;height:100%;object-fit:cover;">
                                </div>
                            @else
                                <div class="border rounded bg-light d-flex align-items-center justify-content-center text-center text-muted small mb-3 px-3" style="min-height:220px;">
                                    @if (! $latestImage)
                                        No image generated for this campaign yet.
                                    @elseif ($latestImage->status === 'failed')
                                        Generation failed.<br>{{ \Illuminate\Support\Str::limit($latestImage->error_message, 120) }}
                                    @else
                                        {{ ucfirst($latestImage->status) }}...
                                    @endif
                                </div>
                            @endif

                            <form method="POST" action="{{ route('sales-agents.marketing-images.generate', $agent->getKey()) }}" class="mb-2">
                                @csrf
                                <input type="hidden" name="campaign_id" value="{{ $campaign->getKey() }}">
                                <label class="form-label small fw-semibold">Image subject</label>
                                <div class="d-flex gap-2">
                                    <select name="subject_mode" class="form-select form-select-sm">
                                        @foreach (\Botble\JobBoard\Models\SalesAgentMarketingImage::subjectModes() as $value => $label)
                                            <option value="{{ $value }}" @selected($agent->preferredMarketingSubjectMode() === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <x-core::icon name="ti ti-photo-ai" class="me-1" /> Generate
                                    </button>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <button
                                        type="submit"
                                        formaction="{{ route('sales-agents.campaigns.send', $agent->getKey()) }}"
                                        class="btn btn-success btn-sm"
                                    >
                                        <x-core::icon name="ti ti-brand-whatsapp" class="me-1" /> Send Campaign
                                    </button>
                                    <span class="text-muted small align-self-center">If no finished image exists for this campaign and subject, Wakanda Jobs will generate one first, then send it automatically.</span>
                                </div>
                            </form>

                            @if ($latestImage && $latestImage->status === 'completed' && $latestImage->image_path)
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('sales-agents.marketing-images.download', [$agent->getKey(), $latestImage->getKey()]) }}" class="btn btn-outline-dark btn-sm">
                                        <x-core::icon name="ti ti-download" class="me-1" /> Download
                                    </a>
                                    <form method="POST" action="{{ route('sales-agents.marketing-images.send', [$agent->getKey(), $latestImage->getKey()]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <x-core::icon name="ti ti-brand-whatsapp" class="me-1" /> Send
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-core::card.body>
</x-core::card>
