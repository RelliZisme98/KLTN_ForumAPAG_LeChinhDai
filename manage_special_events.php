<?php
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $event_name = $_POST['event_name'];
        $event_link = $_POST['event_link'];
        $event_icon = $_POST['event_icon'];
        $event_class = $_POST['event_class'];

        if (!empty($_POST['event_id'])) {
            $stmt = $conn->prepare("UPDATE specialevents SET event_name=?, event_link=?, event_icon=?, event_class=? WHERE id=?");
            $stmt->bind_param("ssssi", $event_name, $event_link, $event_icon, $event_class, $_POST['event_id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO specialevents (event_name, event_link, event_icon, event_class) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $event_name, $event_link, $event_icon, $event_class);
        }
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM specialevents WHERE id = ?");
        $stmt->bind_param("i", $_POST['event_id']);
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$searchQuery = "";
if (isset($_GET['search'])) {
    $searchTerm = "%" . $_GET['search'] . "%";
    $stmt = $conn->prepare("SELECT * FROM specialevents WHERE event_name LIKE ?");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $special_events = $stmt->get_result();
} else {
    $special_events = $conn->query("SELECT * FROM specialevents");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Special Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .modal-header { background-color: #007bff; color: white; }
        .search-container { margin: 20px 0; }
        .action-buttons { white-space: nowrap; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Manage Special Events</h1>

        <div class="row search-container">
            <div class="col-md-6">
                <form class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search by Event Name" 
                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#eventModal">
                    <i class="bi bi-plus-circle"></i> Add New Event
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Event Name</th>
                        <th>Event Link</th>
                        <th>Event Icon</th>
                        <th>Event Class</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($event = $special_events->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($event['id']) ?></td>
                        <td><?= htmlspecialchars($event['event_name']) ?></td>
                        <td><?= htmlspecialchars($event['event_link']) ?></td>
                        <td><i class="<?= htmlspecialchars($event['event_icon']) ?>"></i> <?= htmlspecialchars($event['event_icon']) ?></td>
                        <td><?= htmlspecialchars($event['event_class']) ?></td>
                        <td class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="editEvent(<?= htmlspecialchars(json_encode($event)) ?>)">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteEvent(<?= $event['id'] ?>)">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" id="eventForm">
                    <div class="modal-body">
                        <input type="hidden" name="event_id" id="event_id">
                        <div class="mb-3">
                            <label class="form-label">Event Name</label>
                            <input type="text" name="event_name" id="event_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Link</label>
                            <input type="text" name="event_link" id="event_link" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Icon</label>
                            <input type="text" name="event_icon" id="event_icon" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event Class</label>
                            <input type="text" name="event_class" id="event_class" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEvent(event) {
            document.getElementById('modalTitle').textContent = 'Edit Event';
            document.getElementById('event_id').value = event.id;
            document.getElementById('event_name').value = event.event_name;
            document.getElementById('event_link').value = event.event_link;
            document.getElementById('event_icon').value = event.event_icon;
            document.getElementById('event_class').value = event.event_class;
            new bootstrap.Modal(document.getElementById('eventModal')).show();
        }

        function deleteEvent(id) {
            if (confirm('Are you sure you want to delete this event?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="event_id" value="${id}">
                    <input type="hidden" name="delete" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('eventModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('eventForm').reset();
            document.getElementById('event_id').value = '';
            document.getElementById('modalTitle').textContent = 'Add New Event';
        });
    </script>
</body>
</html>
