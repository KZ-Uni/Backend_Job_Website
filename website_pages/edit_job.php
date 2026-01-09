<?php
session_start();
include('timeout_check.php');
include('db.php');

// Allow BOTH Admin and Employer
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Employer'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = (int)$_SESSION['user_id'];

// Check if job ID is provided
if (!isset($_GET['id'])) {
    $redirect = ($role === 'Admin') ? "admin_dashboard.php" : "employer_dashboard.php";
    header("Location: $redirect?error=noid");
    exit();
}

$job_id = (int)$_GET['id'];

/* ---------------------------------------------------------
   FETCH JOB
   Admin → can edit ANY job
   Employer → can edit ONLY their own job
--------------------------------------------------------- */
if ($role === 'Admin') {
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->bind_param("i", $job_id);
} else {
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
    $stmt->bind_param("ii", $job_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $redirect = ($role === 'Admin') ? "admin_dashboard.php" : "employer_dashboard.php";
    header("Location: $redirect?error=notfound");
    exit();
}

$job = $result->fetch_assoc();
$message = "";

/* ---------------------------------------------------------
   FETCH COUNTRIES
--------------------------------------------------------- */
$countries = $conn->query("SELECT id, name FROM countries ORDER BY name ASC");

/* ---------------------------------------------------------
   FETCH SKILLS
--------------------------------------------------------- */
$skillsMaster = [];
$skillsResult = $conn->query("SELECT id, name FROM skills_master ORDER BY name ASC");
while ($row = $skillsResult->fetch_assoc()) {
    $skillsMaster[] = $row;
}

$selectedSkills = explode(",", $job['skills_required']);

/* ---------------------------------------------------------
   HANDLE UPDATE
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = $conn->real_escape_string($_POST['title']);
    $company     = $conn->real_escape_string($_POST['company']);
    $country_id  = (int)$_POST['country_id'];
    $city_id     = (int)$_POST['city_id'];
    $job_type    = $conn->real_escape_string($_POST['job_type']);
    $description = $conn->real_escape_string($_POST['description']);
    $skills_required = $_POST['skill_ids'] ?? "";

    /* ---------------------------------------------------------
       UPDATE QUERY
       Admin → update ANY job
       Employer → update ONLY their own job
    --------------------------------------------------------- */
    if ($role === 'Admin') {
        $update = $conn->prepare("
            UPDATE jobs 
            SET title=?, company=?, country_id=?, city_id=?, job_type=?, description=?, skills_required=?
            WHERE id=?
        ");

        $update->bind_param(
            "ssiisssi",
            $title,
            $company,
            $country_id,
            $city_id,
            $job_type,
            $description,
            $skills_required,
            $job_id
        );

    } else {
        $update = $conn->prepare("
            UPDATE jobs 
            SET title=?, company=?, country_id=?, city_id=?, job_type=?, description=?, skills_required=?
            WHERE id=? AND employer_id=?
        ");

        $update->bind_param(
            "ssiisssii",
            $title,
            $company,
            $country_id,
            $city_id,
            $job_type,
            $description,
            $skills_required,
            $job_id,
            $user_id
        );
    }

    if ($update->execute()) {
        $redirect = ($role === 'Admin') ? "admin_dashboard.php" : "employer_dashboard.php";
        header("Location: $redirect?updated=1");
        exit();
    } else {
        $message = "<p style='color:red;'>Error updating job: " . $conn->error . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Job</title>
    <link rel="stylesheet" href="../css/style.css">

    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="../javascript/timeout.js"></script>
    <?php endif; ?>

    <script>
    function loadCities(countryId, selectedCity = null) {
        const cityDropdown = document.getElementById("city");

        fetch("get_cities.php?country_id=" + countryId)
            .then(res => res.json())
            .then(data => {
                cityDropdown.innerHTML = "";
                data.forEach(city => {
                    const opt = document.createElement("option");
                    opt.value = city.id;
                    opt.textContent = city.name;
                    if (selectedCity && selectedCity == city.id) opt.selected = true;
                    cityDropdown.appendChild(opt);
                });
            });
    }
    </script>

    <style>
        .skills-wrapper { margin-top: 10px; }
        .skills-input-container { position: relative; }
        #skill-input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; }
        #skills-suggestions {
            position: absolute; top: 100%; left: 0; right: 0;
            background: white; border: 1px solid #ccc; border-top: none;
            max-height: 180px; overflow-y: auto; display: none; z-index: 9999;
        }
        #skills-suggestions div { padding: 8px; cursor: pointer; }
        #skills-suggestions div:hover { background: #f0f0f0; }
        #skills-tags { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 6px; }
        .skill-tag {
            background: #f2f2f2; border: 1px solid #ccc; border-radius: 16px;
            padding: 4px 10px; display: inline-flex; align-items: center; gap: 6px;
        }
        .skill-tag button {
            background: none; border: none; cursor: pointer; color: #666; font-size: 14px;
        }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1>Edit Job</h1>
        <nav>
            <ul>
                <li><a href="employer_dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="container" style="width:60%; margin:30px auto;">
        <h2>Edit Job Listing</h2>
        <?php echo $message; ?>

        <form action="edit_job.php?id=<?php echo $job_id; ?>" method="POST" class="signup-form">

            <label>Job Title</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>

            <label>Company</label>
            <input type="text" name="company" value="<?php echo htmlspecialchars($job['company']); ?>" required>

            <label>Country</label>
            <select name="country_id" onchange="loadCities(this.value)" required>
                <option value="">Select Country</option>
                <?php foreach ($countries as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id']==$job['country_id'] ? "selected" : "" ?>>
                        <?= $c['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>City</label>
            <select name="city_id" id="city" required></select>

            <script>
                loadCities(<?= $job['country_id'] ?>, <?= $job['city_id'] ?>);
            </script>

            <label>Job Type</label>
            <select name="job_type" required>
                <option value="Full-time"   <?= $job['job_type']=="Full-time" ? "selected" : "" ?>>Full-time</option>
                <option value="Part-time"   <?= $job['job_type']=="Part-time" ? "selected" : "" ?>>Part-time</option>
                <option value="Remote"      <?= $job['job_type']=="Remote" ? "selected" : "" ?>>Remote</option>
                <option value="Internship"  <?= $job['job_type']=="Internship" ? "selected" : "" ?>>Internship</option>
                <option value="Contract"    <?= $job['job_type']=="Contract" ? "selected" : "" ?>>Contract</option>
            </select>

            <label>Description</label>
            <textarea name="description" rows="5" cols="47" required><?php echo htmlspecialchars($job['description']); ?></textarea>

            <label>Required Skills</label>
            <div class="skills-wrapper">
                <div class="skills-input-container">
                    <input type="text" id="skill-input" placeholder="Type a skill..." autocomplete="off">
                    <div id="skills-suggestions"></div>
                </div>
                <div id="skills-tags"></div>
                <input type="hidden" name="skill_ids" id="skill_ids">
            </div>

            <script>
            const allSkills = <?= json_encode($skillsMaster) ?>;
            let selectedSkills = <?= json_encode($selectedSkills) ?>.map(id => parseInt(id));

            const skillInput = document.getElementById("skill-input");
            const suggestionsBox = document.getElementById("skills-suggestions");
            const tagsContainer = document.getElementById("skills-tags");
            const hiddenInput = document.getElementById("skill_ids");

            function renderTags() {
                tagsContainer.innerHTML = "";
                selectedSkills.forEach(id => {
                    const skill = allSkills.find(s => s.id == id);
                    if (!skill) return;

                    const tag = document.createElement("div");
                    tag.className = "skill-tag";
                    tag.innerHTML = `${skill.name} <button data-id="${skill.id}">&times;</button>`;
                    tagsContainer.appendChild(tag);
                });
                hiddenInput.value = selectedSkills.join(",");
            }

            renderTags();

            skillInput.addEventListener("input", () => {
                const q = skillInput.value.toLowerCase();
                if (!q) {
                    suggestionsBox.style.display = "none";
                    return;
                }

                const filtered = allSkills.filter(s =>
                    s.name.toLowerCase().includes(q) &&
                    !selectedSkills.includes(s.id)
                );

                suggestionsBox.innerHTML = "";
                filtered.forEach(skill => {
                    const div = document.createElement("div");
                    div.textContent = skill.name;
                    div.dataset.id = skill.id;
                    div.onclick = () => {
                        selectedSkills.push(skill.id);
                        renderTags();
                        skillInput.value = "";
                        suggestionsBox.style.display = "none";
                    };
                    suggestionsBox.appendChild(div);
                });

                suggestionsBox.style.display = filtered.length ? "block" : "none";
            });

            tagsContainer.addEventListener("click", e => {
                if (e.target.tagName === "BUTTON") {
                    const id = parseInt(e.target.dataset.id);
                    selectedSkills = selectedSkills.filter(s => s !== id);
                    renderTags();
                }
            });
            </script>

            <button type="submit">Update Job</button>
        </form>
    </div>
</main>

<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>

</body>
</html>
