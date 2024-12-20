// Course recommendation popup functionality
document.addEventListener("DOMContentLoaded", function () {
  console.log("Script loaded"); // Debug log

  // Find the recommend button
  var recommendBtn = document.getElementById("recommend-course-btn");
  console.log("Recommend button:", recommendBtn); // Debug log

  if (!recommendBtn) {
    console.error("Recommend button not found!");
    return;
  }

  // Create modal HTML
  var modalHtml = `
        <div id="recommendModal" class="recommend-modal">
            <div class="recommend-modal-content">
                <div class="recommend-modal-header">
                    <h3>Recommend Course</h3>
                    <span class="recommend-close">&times;</span>
                </div>
                <div class="recommend-modal-body">
                    <form id="recommendForm" method="post">
                        <div class="recommend-form-group">
                            <input type="text" id="searchUsers" placeholder="Search users..." class="recommend-form-control">
                            <select id="users" name="users[]" multiple size="10" class="recommend-form-control">
                                <!-- Users will be loaded here -->
                            </select>
                        </div>
                        <div class="recommend-form-actions">
                            <button type="submit" class="recommend-btn recommend-btn-primary">Recommend</button>
                            <button type="button" class="recommend-btn recommend-btn-secondary recommend-close-btn">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

  // Add modal to page
  document.body.insertAdjacentHTML("beforeend", modalHtml);

  // Add CSS
  var css = `
        .recommend-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .recommend-modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 5px;
        }
        .recommend-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .recommend-close, .recommend-close-btn {
            cursor: pointer;
        }
        .recommend-close:hover {
            color: #000;
            text-decoration: none;
        }
        #searchUsers {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        #users {
            width: 100%;
            min-height: 200px;
        }
        .recommend-form-actions {
            margin-top: 20px;
            text-align: right;
        }
        .recommend-form-actions button {
            margin-left: 10px;
        }
        .recommend-btn {
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .recommend-btn-primary {
            background-color: #0f6fc5;
            color: white;
            border: none;
        }
        .recommend-btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
        }
    `;

  var style = document.createElement("style");
  style.textContent = css;
  document.head.appendChild(style);

  // Get modal elements
  var modal = document.getElementById("recommendModal");
  var closeBtn = modal.querySelector(".recommend-close");
  var closeBtnBottom = modal.querySelector(".recommend-close-btn");
  var searchInput = document.getElementById("searchUsers");
  var usersSelect = document.getElementById("users");
  var form = document.getElementById("recommendForm");

  // Open modal when recommend button is clicked
  recommendBtn.addEventListener("click", function (e) {
    console.log("Button clicked"); // Debug log
    e.preventDefault();
    modal.style.display = "block";
    loadUsers();
  });

  // Close modal when X is clicked
  closeBtn.addEventListener("click", function () {
    modal.style.display = "none";
  });

  // Close modal when Cancel is clicked
  closeBtnBottom.addEventListener("click", function () {
    modal.style.display = "none";
  });

  // Close modal when clicking outside
  window.addEventListener("click", function (e) {
    if (e.target == modal) {
      modal.style.display = "none";
    }
  });

  // Handle search
  searchInput.addEventListener("keyup", function () {
    var value = this.value.toLowerCase();
    var options = usersSelect.options;

    for (var i = 0; i < options.length; i++) {
      var text = options[i].text.toLowerCase();
      options[i].style.display = text.includes(value) ? "" : "none";
    }
  });

  // Handle form submission
  form.addEventListener("submit", function (e) {
    e.preventDefault();

    // Get selected users
    var selectedUsers = Array.from(usersSelect.selectedOptions).map(
      (option) => option.value
    );
    if (selectedUsers.length === 0) {
      alert("Please select at least one user");
      return;
    }

    // Get course ID
    var courseId = recommendBtn.getAttribute("data-courseid");
    if (!courseId) {
      alert("Course ID not found");
      return;
    }

    // Create form data
    var formData = new FormData();
    formData.append("action", "recommend");
    formData.append("courseid", courseId);
    selectedUsers.forEach((userId) => {
      formData.append("users[]", userId);
    });

    // Send request
    fetch(M.cfg.wwwroot + "/local/courserecommend/ajax.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        console.log("Response status:", response.status);
        return response.json();
      })
      .then((data) => {
        console.log("Response data:", data);
        if (data.success) {
          modal.style.display = "none";
          alert(data.message || "Recommendations sent successfully!");
        } else {
          alert(data.message || "An error occurred");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred while sending recommendations");
      });
  });

  // Load users function
  function loadUsers() {
    console.log("Loading users..."); // Debug log

    // Get course ID from button's data attribute
    var courseId = recommendBtn.getAttribute("data-courseid");
    console.log("Course ID:", courseId); // Debug log

    if (!courseId) {
      console.error("Course ID not found!");
      alert("Error: Course ID not found");
      return;
    }

    // Create URL with parameters
    var params = new URLSearchParams({
      action: "getusers",
      courseid: courseId,
    });

    var url =
      M.cfg.wwwroot + "/local/courserecommend/ajax.php?" + params.toString();
    console.log("Request URL:", url); // Debug log

    fetch(url)
      .then((response) => {
        console.log("Response status:", response.status); // Debug log
        console.log("Response headers:", response.headers); // Debug log
        return response.json();
      })
      .then((data) => {
        console.log("Data:", data); // Debug log
        if (data.success) {
          usersSelect.innerHTML = "";
          data.data.forEach((user) => {
            var option = new Option(user.name, user.id);
            usersSelect.add(option);
          });
        } else {
          console.error("Error loading users:", data.message);
          alert("Error loading users: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Error loading users");
      });
  }
});
