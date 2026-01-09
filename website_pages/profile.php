<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info (note: no skills column here, we use user_skills table)
$stmt = $conn->prepare("SELECT username, email, country_id, city_id, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    // Safety: if somehow user not found, force logout
    header("Location: logout.php");
    exit();
}

// Fetch all countries
$countries = $conn->query("SELECT id, name FROM countries ORDER BY name ASC");

// Fetch skills data only if jobseeker
$skillsMaster = [];
$userSkills = [];

if ($user['role'] === 'Jobseeker') {
    // All predefined skills
    $skillsMasterResult = $conn->query("SELECT id, name FROM skills_master ORDER BY name ASC");
    while ($row = $skillsMasterResult->fetch_assoc()) {
        $skillsMaster[] = $row;
    }

    // User's current skills
    $usStmt = $conn->prepare("
        SELECT sm.id, sm.name
        FROM user_skills us
        JOIN skills_master sm ON us.skill_id = sm.id
        WHERE us.user_id = ?
        ORDER BY sm.name ASC
    ");
    $usStmt->bind_param("i", $user_id);
    $usStmt->execute();
    $usRes = $usStmt->get_result();
    while ($row = $usRes->fetch_assoc()) {
        $userSkills[] = $row;
    }
}

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
        .profile-container input,
        .profile-container select,
        .profile-container textarea {
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

        /* Skills UI */
        .skills-wrapper {
            margin-top: 10px;
        }
        .skills-input-container {
            position: relative;
        }
        #skill-input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        #skills-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ccc;
            border-top: none;
            max-height: 180px;
            overflow-y: auto;
            z-index: 9999;
            display: none;
        }
        #skills-suggestions div {
            padding: 8px 10px;
            cursor: pointer;
        }
        #skills-suggestions div:hover {
            background: #f0f0f0;
        }
        #skills-tags {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .skill-tag {
            background: #f2f2f2;
            border: 1px solid #ccc;
            border-radius: 16px;
            padding: 4px 10px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .skill-tag button {
            border: none;
            background: transparent;
            color: #666;
            font-size: 14px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
    </style>

    <script>
    // COUNTRY / CITY LOGIC
    function loadCities(countryId, selectedCity = null) {
        const cityDropdown = document.getElementById("city");
        if (!cityDropdown) return;

        if (!countryId) {
            cityDropdown.innerHTML = "<option value=''>Select country first</option>";
            return;
        }

        fetch("get_cities.php?country_id=" + encodeURIComponent(countryId))
            .then(response => response.json())
            .then(data => {
                cityDropdown.innerHTML = "";
                if (!data || data.length === 0) {
                    cityDropdown.innerHTML = "<option value=''>No cities found</option>";
                    return;
                }

                data.forEach(city => {
                    const option = document.createElement("option");
                    option.value = city.id;
                    option.textContent = city.name;
                    if (selectedCity && String(selectedCity) === String(city.id)) {
                        option.selected = true;
                    }
                    cityDropdown.appendChild(option);
                });
            })
            .catch(err => {
                console.error("Error loading cities:", err);
                cityDropdown.innerHTML = "<option value=''>Error loading cities</option>";
            });
    }

    document.addEventListener("DOMContentLoaded", function () {
        const country = "<?php echo $user['country_id'] ?? ''; ?>";
        const city = "<?php echo $user['city_id'] ?? ''; ?>";

        if (country !== "") {
            loadCities(country, city);
        }
    });
    </script>

</head>
<body>

<header>
    <div class="container">
        <h1>Your Profile</h1>
        <nav>
            <ul>
                <li><a href="<?php echo $dashboardLink; ?>">Dashboard</a></li>
                <li>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></li>
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
    <?php elseif (isset($_GET['error'])): ?>
        <p style="color:red;">Error: <?php echo htmlspecialchars($_GET['error']); ?></p>
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
                    <?php if ((int)$user['country_id'] === (int)$c['id']) echo "selected"; ?>>
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>City</label>
        <select name="city_id" id="city" required>
            <option value="">Select a country first</option>
        </select>

        <?php if ($user['role'] === 'Jobseeker'): ?>
            <label>Skills</label>
            <div class="skills-wrapper">
                <div class="skills-input-container">
                    <input
                        type="text"
                        id="skill-input"
                        placeholder="Type a skill and press Enter or select from the list"
                        autocomplete="off"
                    >
                    <div class="skills-suggestions" id="skills-suggestions"></div>
                </div>
                <div class="skills-tags" id="skills-tags"></div>
                <input type="hidden" name="skill_ids" id="skill_ids">
            </div>
        <?php endif; ?>

        <label>New Password (optional)</label>
        <input type="password" name="password">

        <button type="submit">Save Changes</button>
    </form>

</div>
</main>

<?php if ($user['role'] === 'Jobseeker'): ?>
<script>
    // SKILLS LOGIC (only for jobseekers)
    const allSkills = <?php echo json_encode($skillsMaster); ?>;
    const userSkillsInitial = <?php echo json_encode($userSkills); ?>;

    let selectedSkills = userSkillsInitial.map(s => ({ id: s.id, name: s.name }));

    const skillInput = document.getElementById('skill-input');
    const suggestionsBox = document.getElementById('skills-suggestions');
    const tagsContainer = document.getElementById('skills-tags');
    const hiddenInput = document.getElementById('skill_ids');

    function renderTags() {
        tagsContainer.innerHTML = '';
        selectedSkills.forEach(skill => {
            const tag = document.createElement('div');
            tag.className = 'skill-tag';
            tag.innerHTML = `
                <span>${skill.name}</span>
                <button type="button" data-id="${skill.id}">&times;</button>
            `;
            tagsContainer.appendChild(tag);
        });
        hiddenInput.value = selectedSkills.map(s => s.id).join(',');
    }

    function showSuggestions(filtered) {
        if (!filtered.length) {
            suggestionsBox.style.display = 'none';
            return;
        }
        suggestionsBox.innerHTML = '';
        filtered.forEach(skill => {
            const div = document.createElement('div');
            div.textContent = skill.name;
            div.dataset.id = skill.id;
            suggestionsBox.appendChild(div);
        });
        suggestionsBox.style.display = 'block';
    }

    function filterSkills(query) {
        query = query.toLowerCase();
        if (!query) {
            suggestionsBox.style.display = 'none';
            return;
        }
        const filtered = allSkills.filter(s =>
            s.name.toLowerCase().includes(query) &&
            !selectedSkills.some(sel => sel.id == s.id)
        );
        showSuggestions(filtered);
    }

    function addSkillById(id) {
        const skill = allSkills.find(s => s.id == id);
        if (!skill) return;
        if (selectedSkills.some(s => s.id == skill.id)) return;
        selectedSkills.push({ id: skill.id, name: skill.name });
        renderTags();
    }

    skillInput.addEventListener('input', function() {
        filterSkills(this.value);
    });

    skillInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const firstSuggestion = suggestionsBox.querySelector('div');
            if (firstSuggestion) {
                addSkillById(firstSuggestion.dataset.id);
                skillInput.value = '';
                suggestionsBox.style.display = 'none';
            }
        }
    });

    suggestionsBox.addEventListener('click', function(e) {
        if (e.target && e.target.dataset.id) {
            addSkillById(e.target.dataset.id);
            skillInput.value = '';
            suggestionsBox.style.display = 'none';
        }
    });

    tagsContainer.addEventListener('click', function(e) {
        if (e.target.tagName.toLowerCase() === 'button') {
            const id = e.target.dataset.id;
            selectedSkills = selectedSkills.filter(s => s.id != id);
            renderTags();
        }
    });

    document.addEventListener('click', function(e) {
        if (!suggestionsBox.contains(e.target) && e.target !== skillInput) {
            suggestionsBox.style.display = 'none';
        }
    });

    // Initial render
    renderTags();
</script>
<?php endif; ?>

</body>
</html>
