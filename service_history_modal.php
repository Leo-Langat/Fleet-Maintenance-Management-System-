<?php
require_once 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service History</title>
    <style>
        .fmms-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .fmms-modal-content {
            position: relative;
            background-color: #fff;
            padding: 20px;
            width: 80%;
            max-width: 800px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            margin: 0;
        }

        .fmms-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            flex-shrink: 0;
        }

        .fmms-modal-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin: 0;
        }

        .fmms-close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            transition: color 0.3s;
        }

        .fmms-close-modal:hover {
            color: #333;
        }

        .fmms-search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-shrink: 0;
        }

        .fmms-search-form input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            flex: 1;
        }

        .search-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-btn:hover {
            background-color: #2980b9;
        }

        .fmms-table-search {
            display: none;
        }

        .fmms-table-container {
            max-height: 400px;
            overflow-y: auto;
        }

        .fmms-service-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
        }

        .fmms-service-table th,
        .fmms-service-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .fmms-service-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
        }

        .fmms-service-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Custom scrollbar styling */
        .fmms-table-container::-webkit-scrollbar {
            width: 8px;
        }

        .fmms-table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .fmms-table-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
            transition: background 0.3s ease;
        }

        .fmms-table-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Ensure table header stays fixed while scrolling */
        .fmms-service-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .fmms-service-table th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
            box-shadow: 0 2px 2px rgba(0, 0, 0, 0.05);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
        }

        .fmms-service-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .fmms-service-table tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.2s ease;
        }

        /* Add a subtle fade effect at the bottom of the scrollable area */
        .fmms-table-container::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(to bottom, rgba(255,255,255,0) 0%, rgba(255,255,255,1) 100%);
            pointer-events: none;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 20px;
        }

        .table-search {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .table-search input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            flex: 1;
            transition: border-color 0.3s ease;
        }

        .table-search input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }
    </style>
</head>
<body>
    <div class="fmms-modal-overlay" id="serviceHistoryModal">
        <div class="fmms-modal-content">
            <div class="fmms-modal-header">
                <h2 class="fmms-modal-title">Service History</h2>
                <button class="fmms-close-modal" onclick="closeServiceHistoryModal()">&times;</button>
            </div>
            
            <div class="fmms-search-form">
                <input type="text" id="registrationNo" placeholder="Enter Vehicle Registration Number" required>
                <button class="search-btn" onclick="searchVehicle()">Search</button>
            </div>

            <div class="fmms-table-search" style="display: none;">
                <input type="text" id="historySearch" placeholder="Search service history..." onkeyup="filterHistory()">
            </div>

            <div class="fmms-table-container">
                <table class="fmms-service-table" id="historyTable">
                    <thead>
                        <tr>
                            <th>Checkout Time</th>
                            <th>Task</th>
                            <th>Service Center</th>
                            <th>Mileage</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <!-- History data will be populated here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function showServiceHistoryModal() {
            document.getElementById('serviceHistoryModal').style.display = 'flex';
        }

        function closeServiceHistoryModal() {
            document.getElementById('serviceHistoryModal').style.display = 'none';
            document.getElementById('historyTableBody').innerHTML = '';
            document.getElementById('registrationNo').value = '';
            document.querySelector('.fmms-table-search').style.display = 'none';
        }

        function searchVehicle() {
            const registrationNo = document.getElementById('registrationNo').value.trim();
            if (!registrationNo) {
                alert('Please enter a registration number');
                return;
            }

            fetch('get_service_history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'registration_no=' + encodeURIComponent(registrationNo)
            })
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('historyTableBody');
                tbody.innerHTML = '';
                
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="no-results">No service history found for this vehicle</td></tr>';
                    document.querySelector('.fmms-table-search').style.display = 'none';
                    return;
                }

                document.querySelector('.fmms-table-search').style.display = 'flex';
                
                data.forEach(record => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${record.checkout_time || '-'}</td>
                        <td>${record.task_name}</td>
                        <td>${record.service_center_name}</td>
                        <td>${record.mileage_at_service}</td>
                        <td>${record.service_notes}</td>
                    `;
                    tbody.appendChild(row);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching service history');
            });
        }

        function filterHistory() {
            const input = document.getElementById('historySearch');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('historyTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let found = false;
                const td = tr[i].getElementsByTagName('td');
                
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }
    </script>
</body>
</html> 