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
</style>
</head>

<body>

<div class="container">
  <img src="../SKYTRUFIBER.png" width="120" style="border-radius:50%; margin-bottom:15px;">
  <h2 style="color:#003d66; margin:0;">Data Privacy Notice</h2>

  <p style="font-size:15px; text-align:justify;">
    SkyTruFiber is committed to protecting your personal information in accordance with the Data Privacy Act of 2012 (RA 10173).
    We collect and process your information for installation, customer support, notifications, and billing.
    Your data will never be shared externally without your consent.
  </p>

  <label><input type="radio" name="consent" value="yes"> YES, I agree and allow SkyTruFiber to process my information.</label>
  <label><input type="radio" name="consent" value="no"> NO, I do not agree.</label>

  <button onclick="proceed()">Continue</button>
</div>

<script>
function proceed() {
  const choice = document.querySelector('input[name="consent"]:checked');
  if (!choice) { alert("⚠ Please select YES or NO"); return; }

  if (choice.value === "yes") {
    window.location.href = "register.php";
  } else {
    window.location.href = "skytrufiber.php?msg=success";
  }
}
</script>

</body>
</html>
