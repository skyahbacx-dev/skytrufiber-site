<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Privacy Consent - SkyTruFiber</title>
<style>
body {
  margin:0;
  font-family:Arial, sans-serif;
  background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
  display:flex; justify-content:center; align-items:center;
  height:100vh; text-align:center;
}

.container {
  background:white; padding:35px 45px;
  border-radius:15px; width:520px;
  box-shadow:0 4px 12px rgba(0,0,0,0.15);
  animation:fade .5s ease-out;
}

@keyframes fade { from{opacity:0;} to{opacity:1;} }

button {
  padding:10px 30px; border:none; cursor:pointer;
  background:#0099cc; color:white; border-radius:8px; font-size:16px;
  margin-top:15px;
}

button:hover { background:#0077a7; }

label { display:block; text-align:left; margin-top:12px; font-size:15px; }

.hidden { display:none; }
</style>
</head>

<body>

<div class="container" id="step1">
  <img src="../SKYTRUFIBER.png" width="120" style="border-radius:50%; margin-bottom:15px;">
  <h2 style="color:#003d66; margin:0;">Data Privacy Notice</h2>

  <p style="font-size:15px; text-align:justify;">
    SkyTruFiber by AHBA Development is committed to protecting your personal information under the Data Privacy Act of 2012.
    We collect, process and store your data for service installation, support, updates and inquiries.
  </p>

  <label><input type="radio" name="consent" value="yes"> YES, I agree and allow SkyTruFiber by AHBA Developmet to process my information.</label>
  <label><input type="radio" name="consent" value="no"> NO, I do not agree.</label>

  <button onclick="nextStep()">Continue</button>

  <br><br>
  <a href="skytrufiber.php" style="
    display:inline-block; text-decoration:none;
    color:#004466; font-weight:bold;">⬅ Go back to Customer Portal
  </a>
</div>



<div class="container hidden" id="step2">
  <img src="../SKYTRUFIBER.png" width="120" style="border-radius:50%; margin-bottom:15px;">
  <h2 style="color:#003d66;">Where did you learn about this site?</h2>

  <label><input type="radio" name="source" value="QR Code"> QR Code</label>
  <label><input type="radio" name="source" value="Gmail"> Gmail</label>
  <label><input type="radio" name="source" value="Others"> Others</label>

  <button onclick="goRegister()">Submit</button>

  <br><br>
  <a href="skytrufiber.php" style="
    display:inline-block; text-decoration:none;
    color:#004466; font-weight:bold;">⬅ Go back to Customer Portal
  </a>
</div>


<script>
function nextStep() {
  const choice = document.querySelector('input[name="consent"]:checked');
  if (!choice) { alert("⚠ Please select YES or NO"); return; }

  if (choice.value === "yes") {
    document.getElementById("step1").classList.add("hidden");
    document.getElementById("step2").classList.remove("hidden");
  } else {
    window.location.href = "no_consent.php";
  }
}

function goRegister() {
  const src = document.querySelector('input[name="source"]:checked');
  if (!src) { alert("⚠ Please choose a source."); return; }
  window.location.href = "register.php?source=" + encodeURIComponent(src.value);
}
</script>

</body>
</html>
