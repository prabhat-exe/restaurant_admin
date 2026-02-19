<!DOCTYPE html>
<html>
<head>
    <title>Restaurant Login</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

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
        <div class="col-md-5">

            <div class="card shadow">
                <div class="card-header bg-dark text-white text-center">
                    <h4>Restaurant Login</h4>
                </div>

                <div class="card-body">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST"
                          action="{{ route('restaurant.login.submit') }}"
                          id="loginForm"
                          data-parsley-validate>
                        @csrf

                        <div class="mb-3">
                            <label>Email *</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   required
                                   data-parsley-type="email"
                                   data-parsley-trigger="keyup"
                                   data-parsley-type-message="Enter valid email address">
                        </div>

                        <div class="mb-3">
                            <label>Password *</label>
                            <input type="password"
                                   name="password"
                                   class="form-control"
                                   required
                                   data-parsley-minlength="6"
                                   data-parsley-trigger="keyup"
                                   data-parsley-minlength-message="Password must be at least 6 characters">
                        </div>

                        <button class="btn btn-dark w-100">
                            Login
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="{{ route('restaurant.register.form') }}">Register</a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Parsley JS -->
<script src="https://cdn.jsdelivr.net/npm/parsleyjs@2.9.2/dist/parsley.min.js"></script>

<script>
    $('#loginForm').parsley();
</script>

</body>
</html>
