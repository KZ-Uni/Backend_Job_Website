// Times (in ms)
const logoutTime =  300000;      // 5 minutes
const warningTime = 240000;     // 4 minutes (1 minute before logout)

let warningTimer;
let logoutTimer;

function resetTimers() {
    clearTimeout(warningTimer);
    clearTimeout(logoutTimer);

    // Show popup at 4 minutes
    warningTimer = setTimeout(showWarning, warningTime);

    // Auto logout at 5 minutes
    logoutTimer = setTimeout(() => {
        window.location.href = "logout.php?timeout=1";
    }, logoutTime);
}

function showWarning() {
    const popup = document.getElementById("timeout-popup");
    const overlay = document.getElementById("timeout-overlay");

    overlay.style.display = "block";
    popup.style.display = "block";

    // Fade in
    setTimeout(() => {
        popup.style.opacity = "1";
    }, 10);
}

function stayLoggedIn() {
    const popup = document.getElementById("timeout-popup");
    const overlay = document.getElementById("timeout-overlay");

    popup.style.opacity = "0";

    // Fade out
    setTimeout(() => {
        popup.style.display = "none";
        overlay.style.display = "none";
    }, 200);

    resetTimers();
    fetch("ping.php"); // refresh PHP session
}

// Reset timers on activity
window.onload = resetTimers;
document.onmousemove = resetTimers;
document.onkeypress = resetTimers;
document.onclick = resetTimers;
document.onscroll = resetTimers;
