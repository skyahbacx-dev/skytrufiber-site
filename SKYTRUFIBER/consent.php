<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Privacy Consent - SkyTruFiber</title>
<style>
/* SAME CSS AS BEFORE */
</style>
</head>

<body>

<div class="container" id="step1">
  <img src="../SKYTRUFIBER.png" width="120" style="border-radius:50%; margin-bottom:15px;">
  <h2 style="color:#003d66; margin:0;">Data Privacy Notice</h2>

  <p style="font-size:15px; text-align:justify;">
    SkyTruFiber by AHBA Development is committed to protecting your personal information...
  </p>

  <label><input type="radio" name="consent" value="yes"> YES, I agree.</label>
  <label><input type="radio" name="consent" value="no"> NO, I do not agree.</label>

  <button onclick="nextStep()">Continue</button>

  <br><br>
  <a href="/fiber" style="text-decoration:none; color:#004466; font-weight:bold;">⬅ Go back</a>
</div>

<div class="container hidden" id="step2">
  <img src="../SKYTRUFIBER.png" width="120">
  <h2 style="color:#003d66;">Where did you learn about this site?</h2>

  <label><input type="radio" name="source" value="QR Code"> QR Code</label>
  <label><input type="radio" name="source" value="Gmail"> Gmail</label>
  <label><input type="radio" name="source" value="Others"> Others</label>

  <button onclick="goRegister()">Submit</button>

  <br><br>
  <a href="/fiber" style="color:#004466; font-weight:bold;">⬅ Go back</a>
</div>

<script>
function nextStep() {
    const c = document.querySelector('input[name="consent"]:checked');
    if (!c) return alert("Please select YES or NO");

    if (c.value === "yes") {
        document.getElementById("step1").classList.add("hidden");
        document.getElementById("step2").classList.remove("hidden");
    } else {
        window.location.href = "no_consent.php";
    }
}

function goRegister() {
    const src = document.querySelector('input[name="source"]:checked');
    if (!src) return alert("Please choose a source.");

    // clean route → encrypted in index.php
    window.location.href = "/fiber/register?source=" + encodeURIComponent(src.value);
}
</script>

</body>
</html>
