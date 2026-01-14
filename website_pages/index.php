<?php
session_start();
include('timeout_check.php');
include('db.php');

//FETCH COUNTRIES & SKILLS
$countries = $conn->query("SELECT id, name FROM countries ORDER BY name ASC");

$skillsMaster = [];
$skillsResult = $conn->query("SELECT id, name FROM skills_master ORDER BY name ASC");
while ($row = $skillsResult->fetch_assoc())
{
    $skillsMaster[] = $row;
}

//FILTERS
$where = [];
$params = [];
$types = "";

// Keyword search (title only)
if (!empty($_GET['search']))
{
    $where[] = "j.title LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
    $types .= "s";
}

// Country filter
if (!empty($_GET['country']))
{
    $where[] = "j.country_id = ?";
    $params[] = intval($_GET['country']);
    $types .= "i";
}

// City filter
if (!empty($_GET['city']))
{
    $where[] = "j.city_id = ?";
    $params[] = intval($_GET['city']);
    $types .= "i";
}

// Job type filter
if (!empty($_GET['job-type']))
{
    $where[] = "j.job_type = ?";
    $params[] = $_GET['job-type'];
    $types .= "s";
}

// MULTI-SKILL FILTER (AND LOGIC, SEARCH BY ID)
if (!empty($_GET['skills']))
{
    $skillIds = explode(",", $_GET['skills']);

    foreach ($skillIds as $skillId) {
        $skillId = intval($skillId);

        if ($skillId > 0)
        {
            // Search for the ID inside the comma-separated string
            $where[] = "FIND_IN_SET(?, j.skills_required)";
            $params[] = $skillId;
            $types .= "i";
        }
    }
}


// PAGINATION
$jobsPerPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $jobsPerPage;

// Count total jobs
$countSql = "
    SELECT COUNT(*) AS total
    FROM jobs j
    LEFT JOIN countries c ON j.country_id = c.id
    LEFT JOIN cities ci ON j.city_id = ci.id
";

if (!empty($where))
{
    $countSql .= " WHERE " . implode(" AND ", $where);
}

$countStmt = $conn->prepare($countSql);
if (!empty($params))
{
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalJobs = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalJobs / $jobsPerPage));

//  MAIN JOB QUERY
$sql = "
    SELECT 
        j.*,
        c.name AS country_name,
        ci.name AS city_name
    FROM jobs j
    LEFT JOIN countries c ON j.country_id = c.id
    LEFT JOIN cities ci ON j.city_id = ci.id
";

if (!empty($where))
{
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY j.created_at DESC LIMIT ? OFFSET ?";

$mainParams = $params;
$mainTypes = $types . "ii";
$mainParams[] = $jobsPerPage;
$mainParams[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($mainTypes, ...$mainParams);
$stmt->execute();
$result = $stmt->get_result();

/* Keep filters in pagination */
function buildPageUrl($pageNum)
{
    $query = $_GET;
    $query['page'] = $pageNum;
    return 'index.php?' . http_build_query($query);
}

/* Restore selected values */
$selectedCountry = $_GET['country'] ?? "";
$selectedCity = $_GET['city'] ?? "";
$selectedJobType = $_GET['job-type'] ?? "";
$selectedSkills = !empty($_GET['skills']) ? explode(",", $_GET['skills']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <script>
        const preSelectedCountry = "<?php echo $selectedCountry; ?>";
        const preSelectedCity = "<?php echo $selectedCity; ?>";

        function loadCities(countryId)
        {
            const citySelect = document.getElementById("city");
            citySelect.innerHTML = "<option>Loading...</option>";

            if (!countryId)
            {
                citySelect.innerHTML = "<option value=''>Select a country first</option>";
                return;
            }

            fetch("get_cities.php?country_id=" + countryId).then(res => res.json()).then(data =>
            {
                citySelect.innerHTML = "<option value=''>Any</option>";
                data.forEach(city =>
                {
                    const opt = document.createElement("option");
                    opt.value = city.id;
                    opt.textContent = city.name;

                    if (preSelectedCity == city.id) opt.selected = true;

                    citySelect.appendChild(opt);
                });
            });
        }

        document.addEventListener("DOMContentLoaded", () =>
        {
            if (preSelectedCountry)
            {
                document.getElementById("country").value = preSelectedCountry;
                loadCities(preSelectedCountry);
            }
        });
    </script>
</head>

<body>

    <header>
        <div class="container">
            <h1>Job Portal</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li>Hello, <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)</li>

                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                            <li><a href="admin_dashboard.php">Dashboard</a></li>
                        <?php elseif ($_SESSION['role'] === 'Employer'): ?>
                            <li><a href="employer_dashboard.php">Dashboard</a></li>
                        <?php elseif ($_SESSION['role'] === 'Jobseeker'): ?>
                            <li><a href="jobseeker_dashboard.php">Dashboard</a></li>
                        <?php endif; ?>

                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="signup.php">Sign Up</a></li>
                        <li><a href="login.php">Sign In</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>


    <!--  FULL-WIDTH SEARCH BAR  -->
    <div style="
        width:100%;
        padding:20px 0;
        display:flex;
        justify-content:center;
        background:#f8f8f8;
        border-bottom:1px solid #ddd;
        margin-bottom:20px;
    ">
        <form action="index.php" method="GET" style="
            width:80%;
            max-width:900px;
            display:flex;
            gap:10px;
        ">
            <input 
                type="text" 
                name="search" 
                placeholder="Search job titles..." 
                value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                style="
                    flex:1;
                    padding:12px 15px;
                    font-size:16px;
                    border:1px solid #ccc;
                    border-radius:6px;
                "
            >

            <!-- Preserve all other filters -->
            <?php foreach ($_GET as $key => $value): ?>
                <?php if ($key !== 'search' && $key !== 'page'): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endif; ?>
            <?php endforeach; ?>

            <button type="submit" style="
                padding:12px 20px;
                background:#007BFF;
                color:white;
                border:none;
                border-radius:6px;
                cursor:pointer;
                font-size:16px;
            ">Search</button>
        </form>
    </div>
    <!--  END SEARCH BAR  -->


    <div class="main-content">

        <!-- Filters -->
        <aside class="filters">
            <h3>Filters</h3>
            <form action="index.php" method="GET">

                <!--  KEEP SEARCH BAR VALUE  -->
                <input type="hidden" name="search" 
                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">

                <!-- Country -->
                <label>Country</label>
                <select id="country" name="country" onchange="loadCities(this.value)">
                    <option value="">Any</option>
                    <?php foreach ($countries as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selectedCountry == $c['id'] ? "selected" : "" ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- City -->
                <label>City</label>
                <select id="city" name="city">
                    <option value="">Select a country first</option>
                </select>

                <!-- Job Type -->
                <label>Job Type</label>
                <select name="job-type">
                    <option value="">Any</option>
                    <option value="Full-time" <?= $selectedJobType == "Full-time" ? "selected" : "" ?>>Full-time</option>
                    <option value="Part-time" <?= $selectedJobType == "Part-time" ? "selected" : "" ?>>Part-time</option>
                    <option value="Remote" <?= $selectedJobType == "Remote" ? "selected" : "" ?>>Remote</option>
                    <option value="Internship" <?= $selectedJobType == "Internship" ? "selected" : "" ?>>Internship</option>
                    <option value="Contract" <?= $selectedJobType == "Contract" ? "selected" : "" ?>>Contract</option>
                </select>

                <!-- MULTI SKILL SEARCH -->
                <label>Skills</label>
                <div class="skills-wrapper">
                    <div class="skills-input-container">
                        <input type="text" id="skill-input" placeholder="Type a skill..." autocomplete="off">
                        <div id="skills-suggestions"></div>
                    </div>

                    <div id="skills-tags"></div>

                    <input type="hidden" name="skills" id="skills">
                </div>

                <button type="submit">Apply Filters</button>
            </form>
        </aside>

        <!-- Job Listings -->
        <main class="job-listings">
            <h2>Job Listings</h2>

            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="job-item">
                        <h4><?= htmlspecialchars($row['title']) ?></h4>

                        <p><strong>Company:</strong> <?= htmlspecialchars($row['company']) ?></p>

                        <p><strong>Location:</strong>
                            <?= htmlspecialchars(trim($row['country_name'] . ", " . $row['city_name'], " ,")) ?>
                        </p>

                        <p><strong>Type:</strong> <?= htmlspecialchars($row['job_type']) ?></p>

                        <p><strong>Description:</strong>
                            <?= substr(htmlspecialchars($row['description']), 0, 100) ?>...
                        </p>

                        <a href="job_details.php?id=<?= $row['id'] ?>">View Details</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No job listings found.</p>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= buildPageUrl($page - 1) ?>">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="<?= buildPageUrl($i) ?>" class="<?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= buildPageUrl($page + 1) ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <footer>
        <p>&copy; 2025 Job Portal. All rights reserved.</p>
    </footer>

    <script>
        const allSkills = <?= json_encode($skillsMaster) ?>;
        let selectedSkills = [];

        // Restore selected skills from URL
        <?php if (!empty($selectedSkills)): ?>
        selectedSkills = <?= json_encode(array_values(array_filter(array_map(function($id) use ($skillsMaster)
        {
            foreach ($skillsMaster as $s)
            {
                if ($s['id'] == $id) return $s;
            }
            return null;
        }, $selectedSkills)))) ?>;
        <?php endif; ?>

        const skillInput = document.getElementById("skill-input");
        const suggestionsBox = document.getElementById("skills-suggestions");
        const tagsContainer = document.getElementById("skills-tags");
        const hiddenInput = document.getElementById("skills");

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

            const filtered = allSkills.filter(s =>
                s.name.toLowerCase().includes(q) &&
                !selectedSkills.some(sel => sel.id == s.id)
            );

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

        // Render tags on page load
        renderTags();
    </script>
</body>
</html>

<?php $conn->close(); ?>
