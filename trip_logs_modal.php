<div id="tripLogsModal" class="modalt" onclick="closeTripLogsModal(event)">
    <div class="modal-contentt" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2>Log Your Trip</h2>
        <span class="close" onclick="closeTripLogsModal()">&times;</span>
        </div>
        <div class="modal-body">
        <!-- Error Message Container -->
            <div id="error-message" class="error-message"></div>
        <form id="tripLogsForm" action="submit_trip_log.php" method="POST">
                <div class="form-group">
            <label for="mileage">Enter Mileage</label>
            <div class="input-container">
                <input 
                    type="number" 
                    id="mileage" 
                    name="mileage" 
                    required 
                    min="0" 
                    placeholder="Current Mileage"
                >
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
            </div>

            <input 
                type="hidden" 
                name="vehicle_id" 
                value="<?= isset($vehicle['vehicle_id']) ? htmlspecialchars($vehicle['vehicle_id']) : '' ?>"
            >

                <div class="button-group">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-check"></i> Submit
                    </button>
                    <button type="button" class="cancel-btn" onclick="closeTripLogsModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
        </form>
        </div>
    </div>
</div>

<style>
/* Modal overlay */
.modalt {
    display: none;
    position: fixed;
    z-index: 999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
}

/* Modal content container */
.modal-contentt {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    width: 80%;
    max-width: 500px;
    margin: 0;
}

/* Modal header */
.modal-header {
    background-color: #2C3E50;
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

/* Modal body */
.modal-body {
    padding: 25px;
}

/* Form group */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #2C3E50;
    font-weight: 500;
}

/* Input container */
.input-container {
    position: relative;
}

.input-container input {
    width: 100%;
    padding: 12px 15px;
    padding-right: 40px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.3s ease;
}

.input-container input:focus {
    outline: none;
    border-color: #3498DB;
}

.input-container i {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #95a5a6;
}

/* Error message */
.error-message {
    color: #e74c3c;
    font-size: 14px;
    margin-bottom: 15px;
    text-align: center;
    display: none;
}

/* Button group */
.button-group {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

/* Buttons */
.submit-btn, .cancel-btn {
    flex: 1;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.submit-btn {
    background-color: #3498DB;
    color: white;
}

.submit-btn:hover {
    background-color: #2980B9;
}

.cancel-btn {
    background-color: #e74c3c;
    color: white;
}

.cancel-btn:hover {
    background-color: #c0392b;
}

/* Close button */
.close {
    color: white;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close:hover {
    color: #ecf0f1;
}
</style>

<script>
// Closes the modal when clicking outside its content
function closeTripLogsModal(event) {
    if (event && event.target.id === 'tripLogsModal') {
        document.getElementById("tripLogsModal").style.display = "none";
    } else if (!event || event.type === 'click') {
        document.getElementById("tripLogsModal").style.display = "none";
    }
}

// Opens the modal
function openTripLogsModal() {
    document.getElementById('tripLogsModal').style.display = 'flex';
}

// AJAX form submission
document.getElementById("tripLogsForm").addEventListener("submit", function(event) {
    event.preventDefault();

    let formData = new FormData(this);
    let errorMessageDiv = document.getElementById("error-message");

    fetch("submit_trip_log.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "error") {
            errorMessageDiv.innerHTML = data.message;
            errorMessageDiv.style.display = "block";
        } else if (data.status === "success") {
            alert(data.message);
            closeTripLogsModal();
            location.reload();
        }
    })
    .catch(error => {
        console.error("Error:", error);
        errorMessageDiv.innerHTML = "Something went wrong. Please try again.";
        errorMessageDiv.style.display = "block";
    });
});
</script>
