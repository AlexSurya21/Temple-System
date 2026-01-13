<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: devotee_login.php");
  exit;   
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Calendar</title>

  <!-- FullCalendar (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    body{
      font-family:Poppins,sans-serif;
      background: linear-gradient(135deg, #ff8a4a, #ff5f7c);
      min-height:100vh;
      padding:30px 16px;
      display:flex;
      justify-content:center;
    }
    .wrap{
      width:100%;
      max-width:1100px;
      background:#fff;
      border-radius:26px;
      box-shadow:0 24px 60px rgba(0,0,0,.18);
      padding:24px;
    }
    .top{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      margin-bottom:14px;
    }
    .top h1{
      font-size:24px;
      margin:0;
      color:#2a2344;
    }
    .sub{
      color:#7c7a90;
      font-size:13px;
      margin-top:4px;
    }
    .back{
      text-decoration:none;
      color:#7d2cff;
      font-weight:600;
      background:#f3ecff;
      padding:8px 12px;
      border-radius:999px;
      font-size:13px;
      white-space:nowrap;
    }
    #calendar{
      margin-top:10px;
    }
    .note{
      color:#7c7a90;
      font-size:12px;
      margin-top:10px;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div>
        <h1>Calendar</h1>
        <div class="sub">Pick a date, switch Month/Week/Day view, and click events.</div>
      </div>
      <a class="back" href="devotee_dashboard.php">← Back to Dashboard</a>
    </div>

    <div id="calendar"></div>
    <div class="note">Tip: Click any event to see details. Use Month/Week/Day buttons to change view.</div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const calEl = document.getElementById('calendar');

  const calendar = new FullCalendar.Calendar(calEl, {
    initialView: 'dayGridMonth',
    height: "auto",

    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },

    navLinks: true,     // click day/week names
    nowIndicator: true,
    selectable: true,   // can select a date range
    dayMaxEvents: true, // "+more" links

    // fetch events from PHP
    events: 'calendar_events.php',

    eventClick: function(info) {
      const e = info.event;
      const start = e.start ? e.start.toLocaleString() : '-';
      alert(
        "Class: " + e.title +
        "\nDate/Time: " + start +
        (e.extendedProps.status ? "\nStatus: " + e.extendedProps.status : "")
      );
    },

    dateClick: function(info) {
      // user clicked a date — can extend later (add personal notes, etc.)
      console.log("Clicked date:", info.dateStr);
    }
  });

  calendar.render();
});
</script>
</body>
</html>

