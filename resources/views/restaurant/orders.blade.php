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
            <a href="{{ route('restaurant.dashboard') }}" class="btn btn-light btn-sm me-2">
                Items
            </a>


            <form method="POST" action="{{ route('restaurant.logout') }}" class="d-inline">
                @csrf
                <button class="btn btn-danger btn-sm">Logout</button>
            </form>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <h4>Order List</h4>

    <div class="card shadow">
        <div class="card-body table-responsive">

            @if($orders->count() > 0)
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $index => $order)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $order->order_id }}</td>
                                <td>{{ $order->customer_name ?? 'N/A' }}</td>
                                <td>₹ {{ $order->total_price ?? 0 }}</td>
                                <td>
                                    <span class="badge bg-success">
                                        {{ $order->status ?? 'Placed' }}
                                    </span>
                                </td>
                                <td>{{ $order->created_at->format('d M Y h:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-warning">
                    No Orders Found
                </div>
            @endif

        </div>
    </div>


</div>

</body>
</html>
