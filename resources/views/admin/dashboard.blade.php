
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">
            Welcome Admin
        </span>
        <div>
            <form method="POST" action="{{ route('admin.logout') }}" class="d-inline">
                @csrf
                <button class="btn btn-danger btn-sm">Logout</button>
            </form>
    </div>
</nav>
<div class="container mt-5">
	<h2 class="mb-4">Registered Restaurants</h2>
    
	<table class="table table-bordered table-striped">
		<thead class="thead-dark">
		<tr>
			<th>Name</th>
			<th>Email</th>
			<th>Phone</th>
			<th>Address</th>
			<th>Status</th>
			<th>Edit</th>
		</tr>
		<div class="d-flex justify-content-between align-items-center mb-4">
		<a href="{{ route('admin.restaurant.create') }}" class="btn btn-primary">Add New Restaurant</a>
	</div>
</div>
	</thead>
		<tbody>
			@foreach($restaurants as $restaurant)
			<tr>
				<td>{{ $restaurant->name }}</td>
				<td>{{ $restaurant->email }}</td>
				<td>{{ $restaurant->phone }}</td>
				<td>{{ $restaurant->address }}</td>
				<td>
					@if($restaurant->is_active)
						<span class="badge bg-success px-3 py-2">
							<i class="fas fa-check-circle me-1"></i>Active
						</span>
					@else
						<span class="badge bg-danger px-3 py-2">
							<i class="fas fa-times-circle me-1"></i> Inactive
						</span>
					@endif
				</td>
				<td>
					<button class="btn btn-warning btn-sm edit-btn" data-id="{{ $restaurant->id }}" data-name="{{ $restaurant->name }}" data-email="{{ $restaurant->email }}" data-phone="{{ $restaurant->phone }}" data-address="{{ $restaurant->address }}" data-status="{{ $restaurant->is_active }}">Edit</button>
				</td>
			</tr>
			@endforeach
		</div>

		<!-- Edit Restaurant Modal -->
		<div class="modal fade" id="editRestaurantModal" tabindex="-1" aria-labelledby="editRestaurantModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="editRestaurantModalLabel">Edit Restaurant</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<form id="editRestaurantForm">
							<input type="hidden" id="edit_id" name="id">
							<div class="mb-3">
								<label for="edit_name" class="form-label">Name</label>
								<input type="text" class="form-control" id="edit_name" name="name" required>
							</div>
							<div class="mb-3">
								<label for="edit_email" class="form-label">Email</label>
								<input type="email" class="form-control" id="edit_email" name="email" required>
							</div>
							<div class="mb-3">
								<label for="edit_phone" class="form-label">Phone</label>
								<input type="text" class="form-control" id="edit_phone" name="phone" required>
							</div>
							<div class="mb-3">
								<label for="edit_address" class="form-label">Address</label>
								<input type="text" class="form-control" id="edit_address" name="address" required>
							</div>
							<div class="mb-3">
								<label for="edit_status" class="form-label">Status</label>
								<select class="form-control" id="edit_status" name="is_active">
									<option value="1">Active</option>
									<option value="0">Inactive</option>
								</select>
							</div>
							<button type="submit" class="btn btn-primary">Save Changes</button>
						</form>
					</div>
				</div>
			</div>
		</div>

		<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
		<script>
		$(document).ready(function() {
				$('.edit-btn').on('click', function() {
						$('#edit_id').val($(this).data('id'));
						$('#edit_name').val($(this).data('name'));
						$('#edit_email').val($(this).data('email'));
						$('#edit_phone').val($(this).data('phone'));
						$('#edit_address').val($(this).data('address'));
						$('#edit_status').val($(this).data('status'));
						$('#editRestaurantModal').modal('show');
				});

				$('#editRestaurantForm').on('submit', function(e) {
						e.preventDefault();
						var id = $('#edit_id').val();
						var data = $(this).serialize();
						$.ajax({
								url: '/admin/restaurant/update/' + id,
								method: 'POST',
								data: data + '&_token={{ csrf_token() }}',
								success: function(response) {
										location.reload();
								},
								error: function(xhr) {
										alert('Update failed. Please check your input.');
								}
						});
				});
		});
		</script>
		</tbody>
	</table>
</div>
</body>
</html>
