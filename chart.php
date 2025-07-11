<?php
require_once 'db.php';

try {
    $stmt = $conn->query("SELECT event_id AS id, event_name AS name FROM events WHERE event_status = 'Ongoing'");
    $ongoingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching ongoing events: " . $e->getMessage());
}

$statusTypes = array('Assigned', 'Accepted', 'Rejected', 'Present', 'Absent');
$statusColors = array(
    'Assigned' => '#3949ab',
    'Accepted' => '#4dd0e1',
    'Rejected' => '#ffcc80',
    'Present'  => '#81c784',
    'Absent'   => '#e57373'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Ongoing Events - Participant Status</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>    
  <style>
  body {
    margin: 0;
    padding: 40px 20px;
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #2b2b2b 0%, #1a1a1a 100%) !important;
    color: #e0e0e0;
    min-height: 100vh;
  }

  h2 {
    font-size: 24px;
    color: #cccccc;
    margin-bottom: 30px;
    text-align: center;
  }

  .chart-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 30px;
    padding: 0 20px;
    max-width: 1000px;
    margin: 0 auto;
    justify-content: center;
  }

  .chart-container {
    background: rgba(255, 255, 255, 0.06);
    padding: 20px;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 400px;
    position: relative;
  }

  canvas {
    width: 100% !important;
    height: 100% !important;
    max-height: 350px;
  }

  .no-events {
    font-size: 20px;
    color: #ffffff;
    text-align: center;
    margin: 100px auto;
  }

  .legend-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 15px;
    margin-top: 20px;
    max-width: 400px;
  }

  .legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    min-width: 120px;
  }

  .legend-color {
    width: 15px;
    height: 15px;
    border-radius: 3px;
  }

  .back-link {
    display: block;
    text-align: center;
    margin-top: 40px;
    color: #bbbbbb;
    text-decoration: none;
  }

  .back-link:hover {
    text-decoration: underline;
    color: #dddddd;
  }
</style>

</head>
<body>
    <?php include 'sidebar.php'; ?>
  <h2>Ongoing Events</h2>
  <?php if (empty($ongoingEvents)): ?>
    <p class="no-events">No Ongoing Events.</p>
  <?php else: ?>
    <div class="chart-grid">
      <?php foreach ($ongoingEvents as $event): ?>
        <?php
        try {
            $stmt = $conn->prepare("
                SELECT participant_status, COUNT(*) as count
                FROM participants
                WHERE event_id = ?
                GROUP BY participant_status
            ");
            $stmt->execute(array($event['id']));
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $statusData = array();
            foreach ($data as $row) {
                $statusData[$row['participant_status']] = $row['count'];
            }
            $counts = array();
            foreach ($statusTypes as $type) {
                $counts[] = isset($statusData[$type]) ? $statusData[$type] : 0;
            }
        } catch (PDOException $e) {
            echo "Error fetching data for event ID {$event['id']}: " . $e->getMessage();
            continue;
        }
        ?>
        <div class="chart-container">
          <canvas id="chart-<?= htmlspecialchars($event['id']) ?>"></canvas>
          <div class="legend-container">
            <?php foreach ($statusTypes as $type): ?>
              <div class="legend-item">
                <div class="legend-color" style="background-color: <?= $statusColors[$type] ?>"></div>
                <?= htmlspecialchars($type) ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <script>
          (function() {
            const ctx = document.getElementById('chart-<?= $event['id'] ?>').getContext('2d');
            new Chart(ctx, {
              type: 'pie',
              data: {
                labels: <?= json_encode($statusTypes) ?>,
                datasets: [{
                  label: 'Participant Status',
                  data: <?= json_encode($counts) ?>,
                  backgroundColor: [
                    '<?= $statusColors['Assigned'] ?>',
                    '<?= $statusColors['Accepted'] ?>',
                    '<?= $statusColors['Rejected'] ?>',
                    '<?= $statusColors['Present'] ?>',
                    '<?= $statusColors['Absent'] ?>'
                  ],
                  borderColor: '#fff',
                  borderWidth: 1
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    display: false
                  },
                  tooltip: {
                    enabled: true
                  },
                  title: {
                    display: true,
                    text: '<?= htmlspecialchars($event['name']) ?>',
                    color: '#c5cae9',
                    font: {
                      size: 18
                    }
                  }
                }
              }
            });
          })();
        </script>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <a href="profile.php" class="back-link">← Back to Dashboard</a>
</body>
</html>