<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Restaurant</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Parsley CSS -->
    <style>
        .parsley-errors-list {
            color: red;
            font-size: 0.9rem;
            margin-top: 5px;
            list-style: none;
            padding-left: 0;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/admin/dashboard">Admin Panel</a>
        <div class="d-flex">
            <a href="/admin/dashboard" class="btn btn-outline-primary ms-2">Dashboard</a>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <div class="card shadow p-4">
        <h2 class="mb-4 text-center">Add New Restaurant</h2>

        <form method="POST" action="/admin/restaurant/store" id="restaurantForm" data-parsley-validate > 
            @csrf

            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" 
                       class="form-control" 
                       name="name" 
                       required 
                       data-parsley-required-message="Restaurant name is required">
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" 
                       class="form-control" 
                       name="email" 
                       required
                       data-parsley-type="email"
                       data-parsley-type-message="Enter valid email address">
            </div>

            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" 
                       class="form-control" 
                       name="phone" 
                       required
                       data-parsley-pattern="^[0-9]{10,15}$"
                       data-parsley-pattern-message="Enter valid phone (10-15 digits)">
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" 
                       class="form-control" 
                       id="password"
                       name="password" 
                       required
                       data-parsley-minlength="8"
                       data-parsley-minlength-message="Password must be at least 8 characters">
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" 
                       class="form-control" 
                       name="password_confirmation"
                       required
                       data-parsley-equalto="#password"
                       data-parsley-equalto-message="Passwords do not match">
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" 
                       class="form-control" 
                       name="address" 
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">City</label>
                <input type="text" 
                       class="form-control" 
                       name="city" 
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">State</label>
                <input type="text" 
                       class="form-control" 
                       name="state" 
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Pincode</label>
                <input type="text" 
                       class="form-control" 
                       name="pincode" 
                       required
                       data-parsley-pattern="^[0-9]{4,10}$"
                       data-parsley-pattern-message="Enter valid pincode">
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-success px-4">
                    Register Restaurant
                </button>
                <a href="/admin/dashboard" class="btn btn-secondary ms-2">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<!-- jQuery (required for Parsley) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- Parsley JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/parsley.js/2.9.2/parsley.min.js"></script>

<script>
    $(document).ready(function () {
        // console.log("jQuery loaded:", typeof $);
        // console.log("Parsley loaded:", typeof $.fn.parsley);
        $('#restaurantForm').parsley();
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>