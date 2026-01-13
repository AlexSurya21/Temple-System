<?php
session_start();
require_once "../includes/db_connect.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode([]);
  exit;
}

$user_id = (int)$_SESSION['user_id'];

/*
Get active registered classes for this user
*/
$sql = "
  SELECT
    cc.class_name,
    cc.schedule_day,
    cc.schedule_time,
    cr.registration_status
  FROM class_registration cr
  JOIN cultural_class cc ON cc.class_id = cr.class_id
  WHERE cr.user_id = ?
    AND cr.registration_status = 'active'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

/*
✅ Use FullCalendar requested date range
It sends ?start=...&end=...
*/
$startParam = $_GET['start'] ?? null;
$endParam   = $_GET['end'] ?? null;

$rangeStart = $startParam ? new DateTime($startParam) : new DateTime();
$rangeEnd   = $endParam ? new DateTime($endParam) : (new DateTime())->modify('+8 weeks');

$dayMap = [
  'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
  'thursday' => 4, 'friday' => 5, 'saturday' => 6
];

$events = [];

foreach ($rows as $r) {
  $day = strtolower(trim($r['schedule_day'] ?? ''));
  $time = trim($r['schedule_time'] ?? '00:00:00');
  $title = $r['class_name'] ?? 'Class';
  $status = $r['registration_status'] ?? 'active';

  if (!isset($dayMap[$day])) continue;

  $targetDow = $dayMap[$day];

  // start cursor at rangeStart
  $cursor = clone $rangeStart;

  // move to first occurrence of target weekday inside the range
  while ((int)$cursor->format('w') !== $targetDow) {
    $cursor->modify('+1 day');     
  }

  // Create weekly occurrences until rangeEnd
  while ($cursor < $rangeEnd) {
    $dateStr = $cursor->format('Y-m-d');
    $start = $dateStr . 'T' . $time;

    $events[] = [
      "id" => md5($title . $start),
      "title" => $title,
      "start" => $start,
      "allDay" => false,
      "extendedProps" => [
        "status" => $status,
        "schedule_day" => $day,
        "schedule_time" => $time
      ]
    ];

    $cursor->modify('+1 week');
  }
}

echo json_encode($events);

