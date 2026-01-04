console.log('dashboard.js loaded')

function openModal(modalId) {
  document.getElementById(modalId).classList.add("active")
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove("active")
  // Clear form if closing add/edit modals
  if (modalId === "addTaskModal") {
    document.getElementById("addTaskForm").reset()
    const sel = document.getElementById("categorySelect")
    if (sel) sel.value = ""
  }
}

function openAddTaskModal(categoryId) {
  const sel = document.getElementById("categorySelect")
  if (sel) sel.value = categoryId
  openModal("addTaskModal")
}

// openEditTaskModal is defined below with dueDate support

function openEditTaskModal(taskId, title, description, categoryId, dueDate) {
  document.getElementById("editTaskId").value = taskId
  document.getElementById("editTaskTitle").value = title
  document.getElementById("editTaskDescription").value = description
  const sel = document.getElementById('editCategorySelect')
  if (sel) sel.value = categoryId
  const dueInput = document.getElementById('editTaskDueDate')
  if (dueInput) dueInput.value = dueDate || ''
  openModal("editTaskModal")
}

function openDeleteConfirm(taskId) {
  document.getElementById("deleteTaskId").value = taskId
  openModal("deleteConfirmModal")
}

function toggleTaskMenu(button) {
  const menu = button.nextElementSibling
  const isVisible = menu.style.display !== "none"

  // Close all other menus
  document.querySelectorAll(".task-actions-menu").forEach((m) => {
    m.style.display = "none"
  })

  // Toggle current menu
  menu.style.display = isVisible ? "none" : "block"
}

document.addEventListener("click", (event) => {
  if (!event.target.closest(".task-menu")) {
    document.querySelectorAll(".task-actions-menu").forEach((m) => {
      m.style.display = "none"
    })
  }
})

const addTaskForm = document.getElementById("addTaskForm")
if (addTaskForm) {
  addTaskForm.addEventListener("submit", async (e) => {
    e.preventDefault()

    const categoryId = document.getElementById("categorySelect").value
    const title = document.getElementById("taskTitle").value
    const description = document.getElementById("taskDescription").value
    const dueDate = (document.getElementById('taskDueDate') && document.getElementById('taskDueDate').value) ? document.getElementById('taskDueDate').value : null

  try {
    const response = await fetch("api/tasks/create.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        category_id: categoryId,
        title: title,
        description: description,
        due_date: dueDate
      }),
    })

    const data = await response.json()

    if (data.success) {
      closeModal("addTaskModal")
      location.reload() // Reload to show new task
    } else {
      alert("Error: " + data.message)
    }
  } catch (error) {
    alert("Failed to create task: " + error.message)
  }
  })
} else {
  console.warn('addTaskForm element not found — form listeners not attached')
}

const editTaskForm = document.getElementById("editTaskForm")
if (editTaskForm) {
  editTaskForm.addEventListener("submit", async (e) => {
    e.preventDefault()

    const taskId = document.getElementById("editTaskId").value
    const title = document.getElementById("editTaskTitle").value
    const description = document.getElementById("editTaskDescription").value
    const categoryId = document.getElementById("editCategorySelect").value
    const dueDate = (document.getElementById('editTaskDueDate') && document.getElementById('editTaskDueDate').value) ? document.getElementById('editTaskDueDate').value : null

  try {
    const response = await fetch("api/tasks/update.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        task_id: taskId,
        title: title,
        description: description,
        category_id: categoryId,
        due_date: dueDate
      }),
    })

    const data = await response.json()

    if (data.success) {
      closeModal("editTaskModal")
      location.reload() // Reload to show updated task
    } else {
      alert("Error: " + data.message)
    }
  } catch (error) {
    alert("Failed to update task: " + error.message)
  }
  })
} else {
  console.warn('editTaskForm element not found — form listeners not attached')
}

function confirmDelete() {
  const taskId = document.getElementById("deleteTaskId").value
  deleteTask(taskId)
}

async function deleteTask(taskId) {
  try {
    const response = await fetch("api/tasks/delete.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        task_id: taskId,
      }),
    })

    const data = await response.json()

    if (data.success) {
      closeModal("deleteConfirmModal")
      location.reload() // Reload to show task removed
    } else {
      alert("Error: " + data.message)
    }
  } catch (error) {
    alert("Failed to delete task: " + error.message)
  }
}
// moveTask removed — category assignment is handled at creation time via the Add Task modal

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    document.querySelectorAll(".modal.active").forEach((modal) => {
      modal.classList.remove("active")
    })
  }
})

// Sidebar toggle logic
;(function() {
  const toggle = document.getElementById('sidebarToggle')
  const body = document.body
  const STORAGE_KEY = 'tm_sidebar_open'

  function setOpen(open) {
    if (open) {
      body.classList.add('sidebar-open')
    } else {
      body.classList.remove('sidebar-open')
    }
    try {
      localStorage.setItem(STORAGE_KEY, open ? '1' : '0')
    } catch (e) {}
  }

  // initialize from storage
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored === '1') setOpen(true)
  } catch (e) {}

  if (toggle) {
    toggle.addEventListener('click', () => {
      const isOpen = body.classList.contains('sidebar-open')
      setOpen(!isOpen)
    })
  }

  // Close sidebar when clicking outside on small screens
  document.addEventListener('click', (e) => {
    if (!body.classList.contains('sidebar-open')) return
    if (window.innerWidth > 800) return
    if (!e.target.closest('.sidebar') && !e.target.closest('#sidebarToggle')) {
      setOpen(false)
    }
  })
})()
