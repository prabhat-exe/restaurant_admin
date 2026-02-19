<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Restaurant Register</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Parsley CSS -->
    <style>
        .parsley-errors-list {
            color: red;
            font-size: 14px;
            margin-top: 5px;
            list-style: none;
            padding-left: 0;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow-lg border-0">
                <div class="card-header text-center bg-dark text-white">
                    <h4 class="mb-0">Restaurant Registration</h4>
                </div>

                <div class="card-body">

                    <form method="POST"
                          action="{{ route('restaurant.register') }}"
                          id="registerForm"
                          data-parsley-validate>
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Restaurant Name *</label>
                            <input type="text" name="name" class="form-control"
                                   required
                                   data-parsley-trigger="keyup"
                                   data-parsley-minlength="3"
                                   data-parsley-maxlength="255"
                                   data-parsley-minlength-message="Name must be at least 3 characters">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control"
                                   required
                                   data-parsley-type="email"
                                   data-parsley-trigger="keyup"
                                   data-parsley-type-message="Enter valid email address">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone *</label>
                            <input type="text" name="phone" class="form-control"
                                   required
                                   data-parsley-type="digits"
                                   data-parsley-length="[10,15]"
                                   data-parsley-trigger="keyup"
                                   data-parsley-length-message="Phone must be 10 to 15 digits">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control"
                                   required
                                   data-parsley-trigger="keyup"
                                   data-parsley-pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$"
                                   data-parsley-pattern-message="Min 8 chars, 1 uppercase, 1 lowercase, 1 number & 1 symbol">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm Password *</label>
                            <input type="password"
                                   name="password_confirmation"
                                   class="form-control"
                                   required
                                   data-parsley-equalto="#password"
                                   data-parsley-trigger="keyup"
                                   data-parsley-equalto-message="Passwords do not match">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address *</label>
                            <textarea name="address"
                                      class="form-control"
                                      required
                                      data-parsley-trigger="keyup"
                                      data-parsley-minlength="5"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">City *</label>
                                <input type="text" name="city"
                                       class="form-control"
                                       required
                                       data-parsley-trigger="keyup">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">State *</label>
                                <input type="text" name="state"
                                       class="form-control"
                                       required
                                       data-parsley-trigger="keyup">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pincode *</label>
                                <input type="text" name="pincode"
                                       class="form-control"
                                       required
                                       data-parsley-type="digits"
                                       data-parsley-length="[4,10]"
                                       data-parsley-trigger="keyup"
                                       data-parsley-length-message="Enter valid pincode">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100">
                            Register
                        </button>

                    </form>
                    <div class="text-center mt-3">
                        <a href="{{ route('restaurant.login') }}">Already have an account? Login</a>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- jQuery (required for Parsley) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Parsley JS -->
<script src="https://cdn.jsdelivr.net/npm/parsleyjs@2.9.2/dist/parsley.min.js"></script>

<script>
    $('#registerForm').parsley();
</script>

</body>
</html>
