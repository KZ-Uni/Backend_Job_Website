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
        // Load user's existing skills
        fetch("load_user_skills.php")
            .then(res => res.json())
            .then(data => {
                data.forEach(skill => addSkillTag(skill.id, skill.name));
                updateHiddenField();
            });

        const input = document.getElementById("skill-input");
        const suggestionsBox = document.getElementById("skill-suggestions");
        let selectedSkills = [];

        input.addEventListener("input", function () {
            const query = this.value.trim();
            if (query.length < 1) {
                suggestionsBox.style.display = "none";
                return;
            }

            fetch("search_skills.php?q=" + encodeURIComponent(query))
                .then(res => res.json())
                .then(skills => {
                    suggestionsBox.innerHTML = "";
                    suggestionsBox.style.display = "block";

                    skills.forEach(skill => {
                        const div = document.createElement("div");
                        div.textContent = skill.name;
                        div.style.padding = "8px";
                        div.style.cursor = "pointer";

                        div.onclick = () => {
                            addSkillTag(skill.id, skill.name);
                            updateHiddenField();
                            suggestionsBox.style.display = "none";
                            input.value = "";
                        };

                        suggestionsBox.appendChild(div);
                    });
                });
        });

        // Add skill on Enter
        input.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                suggestionsBox.style.display = "none";
            }
        });

        function addSkillTag(id, name) {
            if (selectedSkills.some(s => s.id == id)) return;

            selectedSkills.push({ id, name });

            const tag = document.createElement("div");
            tag.className = "skill-tag";
            tag.style.padding = "6px 10px";
            tag.style.background = "#007BFF";
            tag.style.color = "white";
            tag.style.borderRadius = "20px";
            tag.style.display = "flex";
            tag.style.alignItems = "center";
            tag.style.gap = "6px";

            tag.innerHTML = `${name} <span style="cursor:pointer; font-weight:bold;">×</span>`;

            tag.querySelector("span").onclick = () => {
                selectedSkills = selectedSkills.filter(s => s.id != id);
                tag.remove();
                updateHiddenField();
            };

            document.getElementById("selected-skills").appendChild(tag);
        }

        function updateHiddenField() {
            document.getElementById("skill_ids").value = selectedSkills.map(s => s.id).join(",");
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

        <div id="skills-container" style="border:1px solid #ccc; padding:10px; border-radius:6px;">
            <div id="selected-skills" style="margin-bottom:10px; display:flex; flex-wrap:wrap; gap:8px;">
                <!-- Filled by JS -->
            </div>

            <input type="text" id="skill-input" placeholder="Type a skill..." 
                style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;">

            <div id="skill-suggestions" 
                style="border:1px solid #ccc; border-top:none; display:none; background:white; position:absolute; z-index:10; width:55%;">
            </div>

            <!-- Hidden field to store selected skill IDs -->
            <input type="hidden" name="skill_ids" id="skill_ids">
        </div>

        <?php endif; ?>


        <label>New Password (optional)</label>
        <input type="password" name="password">

        <button type="submit">Save Changes</button>
    </form>

</div>
</main>

</body>
</html>
