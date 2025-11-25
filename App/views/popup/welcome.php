<?php
// welcome.php
$showPopup = !isset($_COOKIE['welcome_closed']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome</title>
  <style>
    body { margin: 0; font-family: sans-serif; }
    .overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }
    .popup {
      position: relative;
      background: #0A3167;
      border-radius: 12px;
      padding: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      max-width: 400px;
    }
    .popup img {
      display: block;
      width: 100%;
      height: auto;
      border-radius: 8px;
    }
    .close-btn {
      position: absolute;
      top: 6px;
      right: 6px;
      cursor: pointer;
      width: 28px;
      height: 28px;
      background: #C5A572;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .close-btn svg {
      width: 18px;
      height: 18px;
      stroke: black;
    }
  </style>
</head>
<body>
<?php if ($showPopup): ?>
  <div class="overlay bg-primary" id="popup">
    <div class="popup">
      <div class="close-btn" id="closeBtn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
        </svg>
      </div>
      <img src="https://qkjsnpejxzujoaktpgpq.supabase.co/storage/v1/object/public/nutrinexas/welocme.gif" alt="Welcome">
    </div>
  </div>
  <script>
    document.getElementById('closeBtn').addEventListener('click', function() {
      document.getElementById('popup').style.display = 'none';
      document.cookie = "welcome_closed=true; path=/; max-age=" + (60*60*24*7); // 7 days
    });
  </script>
<?php endif; ?>
</body>
</html>
