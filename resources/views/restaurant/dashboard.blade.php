<!DOCTYPE html>
<html>
<head>
    <title>Restaurant Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">
            Welcome {{ auth('restaurant')->user()->name }}
        </span>

        <div>
            <a href="#" class="btn btn-light btn-sm me-2">
                Orders
            </a>

            @if(!$hasMenu)
                <a href="{{ route('menu.import.form') }}" class="btn btn-warning btn-sm me-2">
                    Import Menu
                </a>
            @endif

            <form method="POST" action="{{ route('restaurant.logout') }}" class="d-inline">
                @csrf
                <button class="btn btn-danger btn-sm">Logout</button>
            </form>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <h4>Menu Items</h4>

    <div class="card shadow">
        <div class="card-body table-responsive">

            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Steps/Variation/Addons</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $index => $item)
                        <tr>
                            <td>{{ $items->firstItem() + $index }}</td>

                            <td>
                                @if($item->image)
                                    <img src="{{ $item->image }}" width="50">
                                @else
                                    -
                                @endif
                            </td>

                            <td>{{ $item->name }}</td>

                            <td>₹ {{ $item->price }}</td>

                            
                            <td>
                                @if($item->is_available)
                                <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                            <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#variationModal{{ $item->id }}">
                                V
                            </button>
                             <!-- Addon Button -->
                            <button class="btn btn-sm btn-outline-success"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addonModal{{ $item->id }}">
                                A
                            </button>

                            <!-- Addon Modal -->
                            <div class="modal fade" id="addonModal{{ $item->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">

                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">
                                                Addons - {{ $item->name }}
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white"
                                                    data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">

                                            @if($item->addons->count() > 0)

                                                <table class="table table-bordered">
                                                    <thead class="table-secondary">
                                                        <tr>
                                                            <th>Addon Name</th>
                                                            <th>POS Price</th>
                                                            <th>Web Price</th>
                                                            <th>Mobile Price</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($item->addons as $addon)
                                                            <tr>
                                                                <td>
                                                                    {{ $addon->addonItem->name ?? '-' }}
                                                                </td>
                                                                <td>₹ {{ $addon->pos_price }}</td>
                                                                <td>₹ {{ $addon->web_price }}</td>
                                                                <td>₹ {{ $addon->mobile_price }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>

                                            @else
                                                <p class="text-center text-muted">
                                                    No addons available for this item.
                                                </p>
                                            @endif

                                        </div>

                                    </div>
                                </div>
                            </div>
                            <!-- Variation Modal -->
                            <div class="modal fade" id="variationModal{{ $item->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">

                                        <div class="modal-header bg-dark text-white">
                                            <h5 class="modal-title">
                                                Variations - {{ $item->name }}
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white"
                                                    data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">

                                            @if($item->variations->count() > 0)

                                                <table class="table table-bordered">
                                                    <thead class="table-secondary">
                                                        <tr>
                                                            <th>Variation Name</th>
                                                            <th>POS Price</th>
                                                            <th>Web Price</th>
                                                            <th>Mobile Price</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($item->variations as $var)
                                                            <tr>
                                                                <td>{{ $var->variation->variation_name ?? '-' }}</td>
                                                                <td>₹ {{ $var->pos_price }}</td>
                                                                <td>₹ {{ $var->web_price }}</td>
                                                                <td>₹ {{ $var->mobile_price }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>

                                            @else
                                                <p class="text-center text-muted">
                                                    No variations available for this item.
                                                </p>
                                            @endif

                                        </div>

                                    </div>
                                </div>
                            </div>
                        </td>
                            <td>{{ $item->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                No Items Found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-3">
        {{ $items->links('pagination::bootstrap-5') }}
    </div>

</div>

</body>
</html>
