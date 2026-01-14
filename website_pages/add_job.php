<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer')
{
    header("Location: login.php");
    exit();
}

$employer_id = $_SESSION['user_id'];
$message = "";

$countries = $conn->query("SELECT id, name FROM countries ORDER BY name ASC");

$skillsMaster = [];
$skillsResult = $conn->query("SELECT id, name FROM skills_master ORDER BY name ASC");
while ($row = $skillsResult->fetch_assoc())
{
    $skillsMaster[] = $row;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $title = $conn->real_escape_string($_POST['title']);
    $company = $conn->real_escape_string($_POST['company']);
    $country_id = intval($_POST['country_id']);
    $city_id = intval($_POST['city_id']);
    $job_type = $conn->real_escape_string($_POST['job_type']);
    $description = $conn->real_escape_string($_POST['description']);

    $skills_required = $_POST['skill_ids'] ?? "";
    
    $sql = $conn->prepare("
        INSERT INTO jobs (employer_id, title, company, country_id, city_id, job_type, description, skills_required)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $employer_id = (int)$employer_id;
    $country_id  = (int)$country_id;
    $city_id     = (int)$city_id;

    $sql->bind_param(
        "issiisss",
        $employer_id,      // i
        $title,            // s
        $company,          // s
        $country_id,       // i
        $city_id,          // i
        $job_type,         // s
        $description,      // s
        $skills_required   // s
    );


    if ($sql->execute())
    {
        $message = "<p style='color:green;'>Job posted successfully!</p>";
    }
    else
    {
        $message = "<p style='color:red;'>Error: " . $conn->error . "</p>";
    }
}?>

<!DOCTYPE html>
<html>
<head>
    <title>Post New Job</title>
    <link rel="stylesheet" href="../css/style.css">
    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="../javascript/timeout.js"></script>
    <?php endif; ?>

    <script>
        function loadCities(countryId)
        {
            const cityDropdown = document.getElementById("city");
            if (!countryId) 
            {
                cityDropdown.innerHTML = "<option value=''>Select country first</option>";
                return;
            }

            fetch("get_cities.php?country_id=" + countryId).then(res => res.json()).then(data =>
            {
                cityDropdown.innerHTML = "";
                data.forEach(city =>
                {
                    const opt = document.createElement("option");
                    opt.value = city.id;
                    opt.textContent = city.name;
                    cityDropdown.appendChild(opt);
                });
            });
        }
    </script>

</head>
<body>
    <header>
        <div class="container">
            <h1>Post New Job</h1>
            <nav>
                <ul>
                    <li><a href="employer_dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="signup-form">
            <h2>New Job Listing</h2>
            <?php echo $message; ?>

            <form action="add_job.php" method="POST">

                <label>Job Title</label>
                <input type="text" name="title" required>

                <label>Company</label>
                <input type="text" name="company" required>

                <label>Country</label>
                <select name="country_id" id="country" onchange="loadCities(this.value)" required>
                    <option value="">Select Country</option>
                    <?php foreach ($countries as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                    <?php endforeach; ?>
                </select>

                <label>City</label>
                <select name="city_id" id="city" required>
                    <option value="">Select a country first</option>
                </select>

                <label>Job Type</label>
                <select name="job_type" required>
                    <option value="Full-time">Full-time</option>
                    <option value="Part-time">Part-time</option>
                    <option value="Remote">Remote</option>
                    <option value="Internship">Internship</option>
                    <option value="Contract">Contract</option>
                </select>


                <label>Description</label>
                <textarea name="description" rows="5" cols="47" required></textarea>

                <label>Required Skills</label>
                <div class="skills-wrapper">
                    <div class="skills-input-container">
                        <input type="text" id="skill-input" placeholder="Type a skill..." autocomplete="off">
                        <div id="skills-suggestions"></div>
                    </div>
                    <div id="skills-tags"></div>
                    <input type="hidden" name="skill_ids" id="skill_ids">
                </div>

                <button type="submit">Post Job</button>
            </form>
        </div>
    </main>

    <script>
        const allSkills = <?= json_encode($skillsMaster) ?>;
        let selectedSkills = [];

        const skillInput = document.getElementById("skill-input");
        const suggestionsBox = document.getElementById("skills-suggestions");
        const tagsContainer = document.getElementById("skills-tags");
        const hiddenInput = document.getElementById("skill_ids");

        function renderTags()
        {
            tagsContainer.innerHTML = "";
            selectedSkills.forEach(skill =>
            {
                const tag = document.createElement("div");
                tag.className = "skill-tag";
                tag.innerHTML = `${skill.name} <button data-id="${skill.id}">&times;</button>`;
                tagsContainer.appendChild(tag);
            });

            hiddenInput.value = selectedSkills.map(s => s.id).join(",");
        }

        skillInput.addEventListener("input", () =>
        {
            const q = skillInput.value.toLowerCase();
            if (!q)
            {
                suggestionsBox.style.display = "none";
                return;
            }

            const filtered = allSkills.filter(s => s.name.toLowerCase().includes(q) && !selectedSkills.some(sel => sel.id == s.id));

            suggestionsBox.innerHTML = "";
            filtered.forEach(skill =>
            {
                const div = document.createElement("div");
                div.textContent = skill.name;
                div.dataset.id = skill.id;
                div.onclick = () =>
                {
                    selectedSkills.push(skill);
                    renderTags();
                    skillInput.value = "";
                    suggestionsBox.style.display = "none";
                };

                suggestionsBox.appendChild(div);
            });

            suggestionsBox.style.display = filtered.length ? "block" : "none";
        });

        tagsContainer.addEventListener("click", e =>
        {
            if (e.target.tagName === "BUTTON")
            {
                const id = e.target.dataset.id;
                selectedSkills = selectedSkills.filter(s => s.id != id);
                renderTags();
            }
        });
    </script>
</body>
</html>
