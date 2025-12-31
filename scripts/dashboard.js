function openModal(modalId) {
  document.getElementById(modalId).classList.add("active")
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove("active")
  // Clear form if closing add/edit modals
  if (modalId === "addTaskModal") {
    document.getElementById("addTaskForm").reset()
    document.getElementById("categoryIdInput").value = ""
  }
}

function openAddTaskModal(categoryId) {
  document.getElementById("categoryIdInput").value = categoryId
  openModal("addTaskModal")
}

function openEditTaskModal(taskId, title, description) {
  document.getElementById("editTaskId").value = taskId
  document.getElementById("editTaskTitle").value = title
  document.getElementById("editTaskDescription").value = description
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

document.getElementById("addTaskForm").addEventListener("submit", async (e) => {
  e.preventDefault()

  const categoryId = document.getElementById("categoryIdInput").value
  const title = document.getElementById("taskTitle").value
  const description = document.getElementById("taskDescription").value

  try {
    const response = await fetch("/api/tasks/create.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        category_id: categoryId,
        title: title,
        description: description,
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

document.getElementById("editTaskForm").addEventListener("submit", async (e) => {
  e.preventDefault()

  const taskId = document.getElementById("editTaskId").value
  const title = document.getElementById("editTaskTitle").value
  const description = document.getElementById("editTaskDescription").value

  try {
    const response = await fetch("/api/tasks/update.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        task_id: taskId,
        title: title,
        description: description,
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

function confirmDelete() {
  const taskId = document.getElementById("deleteTaskId").value
  deleteTask(taskId)
}

async function deleteTask(taskId) {
  try {
    const response = await fetch("/api/tasks/delete.php", {
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

async function moveTask(taskId, newCategoryId) {
  if (!newCategoryId) return

  try {
    const response = await fetch("/api/tasks/move.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        task_id: taskId,
        category_id: newCategoryId,
      }),
    })

    const data = await response.json()

    if (data.success) {
      location.reload() // Reload to show task moved
    } else {
      alert("Error: " + data.message)
    }
  } catch (error) {
    alert("Failed to move task: " + error.message)
  }
}

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    document.querySelectorAll(".modal.active").forEach((modal) => {
      modal.classList.remove("active")
    })
  }
})
