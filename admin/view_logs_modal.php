<?php
require_once __DIR__ . '/../utils/logger.php';
$logger = new Logger();
$logs = $logger->getLogs();
?>

<div id="viewLogsModal" class="modal">
    <div class="modal-content" style="width: 80%; max-width: 1000px; max-height: 80vh;">
        <div class="modal-header">
            <h2>System Logs</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" style="overflow: hidden;">
            <input type="text" id="logSearch" placeholder="Search logs..." style="width:100%;margin-bottom:10px;padding:8px 12px;border-radius:4px;border:1px solid #ccc;">
            <div class="logs-table-container" style="max-height: 60vh; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px;">
                <table class="logs-table" style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                            <td><?php echo htmlspecialchars($log['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            <td><button class="copy-btn" onclick="copyLogRow(this)">Copy</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.modal-header h2 {
    margin: 0;
    color: #333;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: #333;
}

.logs-table tr:nth-child(even) { background: #f9f9f9; }
.logs-table th, .logs-table td { padding: 8px 12px; }
.logs-table th { background: #34495E; color: #fff; }
.logs-table-container { border: 1px solid #ddd; border-radius: 6px; }
.copy-btn {
    background: #3498DB;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 4px 10px;
    cursor: pointer;
    font-size: 13px;
    transition: background 0.2s;
}
.copy-btn:hover { background: #2471A3; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('viewLogsModal');
    const closeBtn = modal.querySelector('.close');
    const searchInput = document.getElementById('logSearch');
    const tableBody = document.getElementById('logsTableBody');
    
    // Function to open the modal
    window.openLogsModal = function() {
        modal.style.display = 'block';
    };
    
    // Close modal when clicking the close button
    closeBtn.onclick = function() {
        modal.style.display = 'none';
    };
    
    // Close modal when clicking outside the modal content
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };

    // Search/filter logs
    searchInput.addEventListener('input', function() {
        const value = this.value.toLowerCase();
        Array.from(tableBody.rows).forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(value) ? '' : 'none';
        });
    });
});

function copyLogRow(btn) {
    const row = btn.closest('tr');
    const text = Array.from(row.children).slice(0, -1).map(td => td.textContent).join(' | ');
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = 'Copied!';
        setTimeout(() => { btn.textContent = 'Copy'; }, 1200);
    });
}
</script> 