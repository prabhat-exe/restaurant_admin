<!DOCTYPE html>
<html>
<head>
    <title>Restaurant Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <a href="{{ route('menu.import.form') }}" class="btn btn-light btn-sm me-2">
                Import Menu
            </a>

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
