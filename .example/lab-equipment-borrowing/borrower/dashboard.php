<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Lab Equipment Borrowing System</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

  <header class="site-header">
    <div class="brand">
      <span class="logo-dot"></span>
      <h2>Lab Equipment Borrowing System</h2>
    </div>
    <nav>
      <a href="catalog.php">Equipment Catalog</a>
      <a href="my_requests.php">My Requests</a>
      <a href="profile.php">Profile</a>
      <a href="../auth/logout.php">Logout</a>
    </nav>
  </header>

  <main class="page-content">
    <h1>Welcome, Ankita Roy</h1>
    <p class="page-note">Here's a quick look at your borrowing activity.</p>

    <!-- Quick stat cards -->
    <div class="landing-options">
      <div class="landing-card">
        <div class="landing-icon">&#8987;</div>
        <h3>2</h3>
        <p>Pending Requests</p>
      </div>
      <div class="landing-card">
        <div class="landing-icon">&#9989;</div>
        <h3>1</h3>
        <p>Approved Requests</p>
      </div>
      <div class="landing-card">
        <div class="landing-icon">&#128230;</div>
        <h3>3</h3>
        <p>Currently Issued</p>
      </div>
    </div>

    <div style="margin-top: 30px;">
      <a href="catalog.php" class="btn">Browse Equipment</a>
      <a href="my_requests.php" class="btn btn-secondary">View All My Requests</a>
    </div>

    <h2 style="margin-top: 36px;">Recent Requests</h2>

    <table>
      <tr>
        <th>Equipment</th>
        <th>From</th>
        <th>To</th>
        <th>Status</th>
      </tr>
      <tr>
        <td>Arduino Uno Kit</td>
        <td>2026-09-10</td>
        <td>2026-09-17</td>
        <td>Issued</td>
      </tr>
      <tr>
        <td>HDMI Cable</td>
        <td>2026-09-12</td>
        <td>2026-09-13</td>
        <td>Approved</td>
      </tr>
      <tr>
        <td>Projector</td>
        <td>2026-09-15</td>
        <td>2026-09-16</td>
        <td>Pending</td>
      </tr>
      <tr>
        <td>Extension Board</td>
        <td>2026-09-08</td>
        <td>2026-09-09</td>
        <td>Returned</td>
      </tr>
      <tr>
        <td>Lab Manual — DBMS</td>
        <td>2026-09-05</td>
        <td>2026-09-07</td>
        <td>Returned</td>
      </tr>
    </table>

  </main>

  <footer class="site-footer">
    <p>&copy; Lab Equipment Borrowing System</p>
  </footer>

  <script src="../assets/js/script.js"></script>
</body>
</html>