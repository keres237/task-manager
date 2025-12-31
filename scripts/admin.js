document.querySelectorAll(".nav-link").forEach((link) => {
  link.addEventListener("click", function (e) {
    e.preventDefault()

    // Remove active class from all links
    document.querySelectorAll(".nav-link").forEach((l) => {
      l.classList.remove("active")
    })

    // Add active class to clicked link
    this.classList.add("active")

    // Hide all tab contents
    document.querySelectorAll(".tab-content").forEach((tab) => {
      tab.classList.remove("active")
    })

    // Show selected tab
    const tabId = this.getAttribute("data-tab")
    document.getElementById(tabId).classList.add("active")
  })
})
