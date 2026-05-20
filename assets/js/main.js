document.addEventListener("DOMContentLoaded", function () {
  // Table search functionality
  document.querySelectorAll("[data-table-search]").forEach(function (input) {
    const table = document.getElementById(input.dataset.tableSearch);
    if (!table) return;
    input.addEventListener("input", function () {
      const keyword = this.value.trim().toLowerCase();
      table.querySelectorAll("tbody tr").forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(keyword)
          ? ""
          : "none";
      });
    });
  });

  // Table filter dropdowns
  document.querySelectorAll("[data-filter-target]").forEach(function (select) {
    const table = document.getElementById(select.dataset.filterTarget);
    if (!table) return;
    select.addEventListener("change", function () {
      const value = this.value.toLowerCase();
      table.querySelectorAll("tbody tr").forEach(function (row) {
        row.style.display =
          !value || row.innerText.toLowerCase().includes(value) ? "" : "none";
      });
    });
  });

  // Initialize password match validation on settings pages
  initPasswordMatchValidation();
});

/**
 * Toggle password visibility with lock/unlock emojis
 * @param {string} inputId - The ID of the password input field
 */
function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  const button = input.nextElementSibling;

  if (input.type === "password") {
    input.type = "text";
    button.innerHTML = "🔓";
    button.title = "Hide password";
  } else {
    input.type = "password";
    button.innerHTML = "🔒";
    button.title = "Show password";
  }
}

/**
 * Real-time password match validation
 * Shows ✅ when passwords match, ❌ when they don't
 */
function initPasswordMatchValidation() {
  const confirmInput = document.getElementById("confirm_password");
  if (!confirmInput) return;

  // Remove any existing listener by replacing with new one
  const newConfirm = confirmInput.cloneNode(true);
  confirmInput.parentNode.replaceChild(newConfirm, confirmInput);

  newConfirm.addEventListener("input", function () {
    const password = document.getElementById("new_password").value;
    const confirm = this.value;
    let existingMsg = document.getElementById("passwordMatchMsg");

    if (password !== confirm && confirm !== "") {
      if (!existingMsg) {
        const newMsg = document.createElement("small");
        newMsg.id = "passwordMatchMsg";
        newMsg.style.color = "#e74c3c";
        newMsg.style.display = "block";
        newMsg.style.marginTop = "5px";
        this.parentElement.parentElement.appendChild(newMsg);
        existingMsg = document.getElementById("passwordMatchMsg");
      }
      existingMsg.innerHTML = "❌ Passwords do not match";
      existingMsg.style.color = "#e74c3c";
    } else if (password === confirm && password !== "") {
      if (!existingMsg) {
        const newMsg = document.createElement("small");
        newMsg.id = "passwordMatchMsg";
        newMsg.style.color = "#27ae60";
        newMsg.style.display = "block";
        newMsg.style.marginTop = "5px";
        this.parentElement.parentElement.appendChild(newMsg);
        existingMsg = document.getElementById("passwordMatchMsg");
      }
      existingMsg.innerHTML = "✅ Passwords match";
      existingMsg.style.color = "#27ae60";
    } else {
      if (existingMsg) {
        existingMsg.remove();
      }
    }
  });
}

/**
 * Clear all filters on students/teachers tables
 */
function clearFilters() {
  // Clear search input
  var searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.value = "";
    // Trigger the search event
    var event = new Event("input", { bubbles: true });
    searchInput.dispatchEvent(event);
  }

  // Reset status filter
  var statusFilter = document.getElementById("statusFilter");
  if (statusFilter) {
    statusFilter.value = "";
    var changeEvent = new Event("change", { bubbles: true });
    statusFilter.dispatchEvent(changeEvent);
  }

  // Reset course filter
  var courseFilter = document.getElementById("courseFilter");
  if (courseFilter) {
    courseFilter.value = "";
    var changeEvent = new Event("change", { bubbles: true });
    courseFilter.dispatchEvent(changeEvent);
  }

  // Reset subject filter (for teachers page)
  var subjectFilter = document.getElementById("subjectFilter");
  if (subjectFilter) {
    subjectFilter.value = "";
    var changeEvent = new Event("change", { bubbles: true });
    subjectFilter.dispatchEvent(changeEvent);
  }

  // Also trigger table search refresh for any custom table search
  var tables = document.querySelectorAll("[data-table-search]");
  tables.forEach(function (input) {
    var table = document.getElementById(input.dataset.tableSearch);
    if (table) {
      var keyword = "";
      table.querySelectorAll("tbody tr").forEach(function (row) {
        row.style.display = "";
      });
    }
  });
}
