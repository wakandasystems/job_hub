@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>Documentation</x-core::card.title>
            <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
                <a href="{{ route('documentation.index') }}"
                   class="btn btn-sm btn-{{ !$category ? 'secondary' : 'outline-secondary' }}">All</a>
                @foreach($categories as $cat)
                    <a href="{{ route('documentation.index', ['category' => $cat]) }}"
                       class="btn btn-sm btn-{{ $category === $cat ? 'primary' : 'outline-primary' }}">{{ $cat }}</a>
                @endforeach
                <a href="{{ route('documentation.create') }}" class="btn btn-sm btn-success ms-2">
                    <i class="ti ti-plus"></i> Add Entry
                </a>
            </div>
        </x-core::card.header>
        <x-core::card.body>
            @if ($docs->isEmpty())
                <p class="text-muted text-center py-4">No documentation entries found.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category</th>
                                <th>Title</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($docs as $doc)
                                <tr>
                                    <td>{{ $doc->id }}</td>
                                    <td><span class="badge bg-primary">{{ $doc->category }}</span></td>
                                    <td>{{ $doc->title }}</td>
                                    <td>{{ $doc->sort_order }}</td>
                                    <td>
                                        @if($doc->is_published)
                                            <span class="badge bg-success">Published</span>
                                        @else
                                            <span class="badge bg-secondary">Draft</span>
                                        @endif
                                    </td>
                                    <td>{{ $doc->updated_at->diffForHumans() }}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('documentation.edit', $doc->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    data-id="{{ $doc->id }}" data-title="{{ $doc->title }}">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $docs->appends(request()->query())->links() }}
            @endif
        </x-core::card.body>
    </x-core::card>

    {{-- Delete confirmation modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Documentation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete "<strong id="deleteTitle"></strong>"?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer')
    <script>
        document.getElementById('deleteModal').addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            document.getElementById('deleteTitle').textContent = btn.dataset.title;
            document.getElementById('deleteForm').action = '{{ url('admin/documentation') }}/' + btn.dataset.id;
        });
    </script>
@endpush
