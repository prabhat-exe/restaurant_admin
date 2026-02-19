<!DOCTYPE html>
<html>
<head>
    <title>Import Menu JSON</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">
            Import Menu
        </span>

        <a href="{{ route('restaurant.dashboard') }}" class="btn btn-light btn-sm">
            Back to Dashboard
        </a>
    </div>
</nav>

<div class="container mt-4">

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow">
        <div class="card-body">

            <form method="POST" action="{{ route('menu.import') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Paste JSON Here</label>
                    <textarea name="json_data" rows="15" class="form-control"></textarea>
                </div>

                <button class="btn btn-primary">
                    Import Menu
                </button>

            </form>

        </div>
    </div>

</div>

</body>
</html>
