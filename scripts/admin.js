var _navLinks = document.querySelectorAll('.nav-link')
for (var i = 0; i < _navLinks.length; i++) {
  (function (link) {
    link.addEventListener('click', function (e) {
      var tabId = this.getAttribute('data-tab')
      if (!tabId) return

      var href = this.getAttribute('href')
      if (href && !href.startsWith('#') && href.indexOf(window.location.pathname.split('/').pop()) === -1) {
        return
      }

      e.preventDefault()

      var allNav = document.querySelectorAll('.nav-link')
      for (var j = 0; j < allNav.length; j++) allNav[j].classList.remove('active')
      this.classList.add('active')

      var tabs = document.querySelectorAll('.tab-content')
      for (var k = 0; k < tabs.length; k++) tabs[k].classList.remove('active')

      var target = document.getElementById(tabId)
      if (target) target.classList.add('active')
    })
  })(_navLinks[i])
}

// admin/tasks AJAX + filters (only runs on tasks page)
(function () {
  const form = document.getElementById('tasks-filters')
  const tbody = document.getElementById('tasks-tbody')
  const statsContainer = document.getElementById('tasks-stats')
  const warningEl = document.getElementById('tasks-warning')
  const dueHeader = document.getElementById('due-header')

  if (!form || !tbody || !statsContainer) return

  function qs(obj) {
    return Object.keys(obj)
      .filter((k) => obj[k] !== null && obj[k] !== undefined && obj[k] !== '')
      .map((k) => encodeURIComponent(k) + '=' + encodeURIComponent(obj[k]))
      .join('&')
  }

  function renderStats(stats) {
    statsContainer.innerHTML = ''
    const items = [
      {label: 'Total Tasks', key: 'total_tasks'},
      {label: 'Done', key: 'done'},
      {label: 'Doing', key: 'doing'},
      {label: 'Overdue', key: 'overdue'},
      {label: 'Macrotasks', key: 'macrotasks'},
      {label: 'Microtasks', key: 'microtasks'}
    ]
    items.forEach((it) => {
      const card = document.createElement('div')
      card.className = 'stat-card'
      card.innerHTML = `<div class="stat-number">${stats[it.key] ?? 0}</div><div class="stat-label">${it.label}</div>`
      statsContainer.appendChild(card)
    })
  }

  function renderTasks(tasks, hasDueDate) {
    tbody.innerHTML = ''
    if (hasDueDate) {
      dueHeader.style.display = ''
    } else {
      dueHeader.style.display = 'none'
    }

    if (!tasks || tasks.length === 0) {
      const tr = document.createElement('tr')
      const td = document.createElement('td')
      td.setAttribute('colspan', hasDueDate ? '5' : '4')
      td.textContent = 'No tasks found.'
      tr.appendChild(td)
      tbody.appendChild(tr)
      return
    }

    tasks.forEach((t) => {
      const tr = document.createElement('tr')
      const td1 = document.createElement('td')
      td1.innerHTML = `<strong>${escapeHtml(t.title)}</strong>` + (t.description ? `<br><small>${escapeHtml(t.description.substring(0,120))}</small>` : '')
      tr.appendChild(td1)

      const tdUser = document.createElement('td')
      tdUser.textContent = t.username
      tr.appendChild(tdUser)

      const tdCat = document.createElement('td')
      tdCat.textContent = t.category_name
      tr.appendChild(tdCat)

      if (hasDueDate) {
        const tdDue = document.createElement('td')
        tdDue.textContent = t.due_date ?? ''
        tr.appendChild(tdDue)
      }

      const tdCreated = document.createElement('td')
      tdCreated.textContent = (new Date(t.created_at)).toLocaleDateString()
      tr.appendChild(tdCreated)

      tbody.appendChild(tr)
    })
  }

  function escapeHtml(str) {
    if (!str) return ''
    return String(str).replace(/[&<>"']/g, function (m) {
      return ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      })[m]
    })
  }

  let pending = null
  function fetchData() {
    if (pending) pending.abort()
    const controller = new AbortController()
    pending = controller

    const formData = new FormData(form)
    const params = {}
    for (const [k, v] of formData.entries()) params[k] = v

    const docPath = window.location.pathname
    const url = docPath + '?ajax=1&' + qs(params)

    fetch(url, {signal: controller.signal, credentials: 'same-origin'})
      .then((r) => {
        if (!r.ok) {
          return r.text().then((text) => { throw new Error('HTTP ' + r.status + ': ' + text) })
        }
        return r.json()
      })
      .then((data) => {
        if (!data || !data.success) {
          warningEl.innerHTML = '<div class="alert alert-error">Failed to load tasks.</div>'
          return
        }
        renderStats(data.stats || {})
        renderTasks(data.tasks || [], data.hasDueDate)
        if (!data.hasDueDate) {
          warningEl.innerHTML = '<div class="alert alert-warning">Overdue filtering requires a `due_date` column on the `tasks` table.</div>'
        } else {
          warningEl.innerHTML = ''
        }
      })
      .catch((err) => {
        if (err.name === 'AbortError') return
        console.error('Failed to fetch tasks:', err)
        warningEl.innerHTML = '<div class="alert alert-error">Error loading tasks. See console for details.</div>'
      })
      .finally(() => { pending = null })
  }

  function debounce(fn, ms) {
    let t
    return function () {
      clearTimeout(t)
      t = setTimeout(() => fn.apply(this, arguments), ms)
    }
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault()
    fetchData()
  })

  document.getElementById('reset-filters').addEventListener('click', function () {
    form.reset()
    fetchData()
  })

  const searchInput = document.getElementById('search')
  searchInput.addEventListener('input', debounce(() => fetchData(), 400))

  document.addEventListener('DOMContentLoaded', fetchData)
  if (document.readyState === 'complete' || document.readyState === 'interactive') fetchData()
})()

