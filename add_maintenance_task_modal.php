<!-- Modal -->
<div id="maintenanceTaskModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Add Maintenance Task</h2>
        <div id="message" style="display:none;"></div>
        <form id="taskForm">
            <label for="task_name">Task Name:</label>
            <input type="text" id="task_name" name="task_name" required>

            <label for="estimated_time">Estimated Time (hours):</label>
            <input type="number" id="estimated_time" name="estimated_time" required>

            <label for="additional_details">Additional Details:</label>
            <textarea id="additional_details" name="additional_details"></textarea>

            <button type="submit" class="btn">Submit</button>
        </form>
    </div>
</div>

<!-- Modal Styles -->
<style>
/* Modal Overlay */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease-in-out;
}

/* Modal Content */
.modal-content {
    background: rgba(255, 255, 255, 0.95);
    padding: 25px;
    width: 40%;
    max-width: 500px;
    margin: 10% auto;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    position: relative;
    animation: slideDown 0.3s ease-in-out;
}

/* Close Button */
.close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 22px;
    font-weight: bold;
    color: #333;
    cursor: pointer;
    transition: 0.3s;
}
.close:hover {
    color: red;
}

/* Form Styling */
form {
    display: flex;
    flex-direction: column;
}
label {
    font-weight: 600;
    margin: 10px 0 5px;
}
input, textarea {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
    width: 100%;
    transition: 0.3s;
}
input:focus, textarea:focus {
    border-color: #007bff;
    box-shadow: 0 0 8px rgba(0, 123, 255, 0.2);
    outline: none;
}
textarea {
    min-height: 80px;
    resize: none;
}

/* Button Styling */
.btn {
    background: #007bff;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 15px;
    transition: 0.3s;
}
.btn:hover {
    background: #0056b3;
}

/* Message Box */
#message {
    display: none;
    padding: 10px;
    margin-top: 10px;
    border-radius: 5px;
    font-weight: bold;
    text-align: center;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

</style>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.getElementById("addMaintenanceBtn").addEventListener("click", function(event) {
    event.preventDefault();
    openModal();
});
function openModal() {
    document.getElementById("maintenanceTaskModal").style.display = "block";
}

function closeModal() {
    document.getElementById("maintenanceTaskModal").style.display = "none";
}

// Handle Form Submission with AJAX
$(document).ready(function() {
    $("#taskForm").submit(function(event) {
        event.preventDefault();
        $.ajax({
            url: "add_maintenance_task.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                $("#message").html(response.message).show().css("color", response.status === "success" ? "green" : "red");
                if (response.status === "success") {
                    $("#taskForm")[0].reset();
                    setTimeout(closeModal, 2000);
                }
            },
            error: function() {
                $("#message").html("An error occurred. Please try again.").show().css("color", "red");
            }
        });
    });
});
</script>