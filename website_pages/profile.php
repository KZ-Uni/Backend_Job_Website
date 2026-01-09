<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT username, email, country_id, city_id, skills, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch all countries
$countries = $conn->query("SELECT id, name FROM countries ORDER BY name ASC");

// Determine dashboard link
$dashboardLink = "index.php";
if ($user['role'] === 'Jobseeker') $dashboardLink = "jobseeker_dashboard.php";
if ($user['role'] === 'Employer') $dashboardLink = "employer_dashboard.php";
if ($user['role'] === 'Admin') $dashboardLink = "admin_dashboard.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Profile</title>
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .profile-container {
            width: 60%;
            margin: 30px auto;
            background: #fff;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .profile-container label {
            margin-top: 15px;
            font-weight: bold;
        }
        .profile-container input, .profile-container select, .profile-container textarea {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .profile-container button {
            margin-top: 20px;
            padding: 10px 18px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            width: 100%;
        }
        .profile-container button:hover {
            background: #0056b3;
        }
    </style>

    <script>
    function loadCities(countryId, selectedCity = null) {
        if (!countryId) {
            document.getElementById("city").innerHTML = "<option value=''>Select country first</option>";
            return;
        }

        fetch("get_cities.php?country_id=" + countryId)
            .then(response => response.json())
            .then(data => {
                let cityDropdown = document.getElementById("city");
                cityDropdown.innerHTML = "";

                data.forEach(city => {
                    let option = document.createElement("option");
                    option.value = city.id;
                    option.textContent = city.name;

                    if (selectedCity && selectedCity == city.id) {
                        option.selected = true;
                    }

                    cityDropdown.appendChild(option);
                });
            });
    }
    </script>

</head>
<body>

<header>
    <div class="container">
        <h1>Your Profile</h1>
        <nav>
            <ul>
                <li><a href="<?php echo $dashboardLink; ?>">Dashboard</a></li>
                <li>Hello, <?php echo $_SESSION['username']; ?></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
<div class="profile-container">

    <h2>Edit Profile</h2>

    <?php if (isset($_GET['updated'])): ?>
        <p style="color:green;">Profile updated successfully!</p>
    <?php endif; ?>

    <form action="profile_update.php" method="POST">

        <label>Username</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

        <label>Country</label>
        <select name="country_id" id="country" onchange="loadCities(this.value)" required>
            <option value="">Select Country</option>
            <?php while ($c = $countries->fetch_assoc()): ?>
                <option value="<?php echo $c['id']; ?>" 
                    <?php if ($user['country_id'] == $c['id']) echo "selected"; ?>>
                    <?php echo $c['name']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>City</label>
        <select name="city_id" id="city" required>
            <option value="">Select a country first</option>
        </select>

        <script>
            window.onload = function() {
                let country = "<?php echo $user['country_id']; ?>";
                let city = "<?php echo $user['city_id']; ?>";
                if (country) loadCities(country, city);
            }
        </script>

        <?php if ($user['role'] === 'Jobseeker'): ?>
            <label>Skills</label>
            <textarea name="skills" rows="4"><?php echo htmlspecialchars($user['skills']); ?></textarea>
        <?php endif; ?>

        <label>New Password (optional)</label>
        <input type="password" name="password">

        <button type="submit">Save Changes</button>
    </form>

</div>
</main>

</body>
</html>
