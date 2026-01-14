// Time in ms
const logoutTime =  300000; // 5 min
const warningTime = 240000; // 4 min

let warningTimer;
let logoutTimer;

function resetTimers() 
{
    clearTimeout(warningTimer);
    clearTimeout(logoutTimer);

    warningTimer = setTimeout(showWarning, warningTime);

    logoutTimer = setTimeout(() =>
    {
        window.location.href = "logout.php?timeout=1";
    }, logoutTime);
}

function showWarning()
{
    const popup = document.getElementById("timeout-popup");
    const overlay = document.getElementById("timeout-overlay");

    overlay.style.display = "block";
    popup.style.display = "block";

    setTimeout(() =>
    {
        popup.style.opacity = "1";
    }, 10);
}

// hide again
function stayLoggedIn()
{
    const popup = document.getElementById("timeout-popup");
    const overlay = document.getElementById("timeout-overlay");

    popup.style.opacity = "0";


    setTimeout(() =>
    {
        popup.style.display = "none";
        overlay.style.display = "none";
    }, 200);

    resetTimers();
    fetch("ping.php");
}

// Reset timers
window.onload = resetTimers;
document.onmousemove = resetTimers;
document.onkeydown = resetTimers;
document.onclick = resetTimers;
document.onscroll = resetTimers;
