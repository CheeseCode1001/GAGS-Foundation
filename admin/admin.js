// ============ ADMIN INITIALIZATION ============
window.csrfToken = '';


function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>'"]/g, function(match) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[match];
    });
}

function showTableSkeletons(tbodyId, colCount) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    tbody.innerHTML = Array(5).fill(`<tr style="border-bottom:1px solid var(--border);">
            ${Array(colCount).fill('<td style="padding:16px 24px;"><div class="skeleton skeleton-text" style="margin:0;"></div></td>').join('')}
        </tr>`).join('');
}

function showGallerySkeletons() {
    const grid = document.getElementById('gallery-grid');
    if (!grid) return;
    grid.innerHTML = Array(4).fill(`<div class="gallery-item skeleton" style="position:relative; padding-bottom:100%; border-radius:8px;"></div>`).join('');
}

document.addEventListener('DOMContentLoaded', async () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Determine if we're on login or dashboard
    const isLoginPage = document.getElementById('login-form');

    if (isLoginPage) {
        initLoginForm();
    } else {
        await checkAuthAndInit();
    }
});

// ============ LOGIN HANDLING ============
function initLoginForm() {
    const form = document.getElementById('login-form');
    const errorDiv = document.getElementById('error-message');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        try {
            const response = await fetch('../api/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();

            if (response.ok) {
                window.location.href = 'dashboard.html';
            } else {
                errorDiv.textContent = data.error || 'Login failed. Please try again.';
                errorDiv.style.display = 'block';
            }
        } catch (error) {
            console.error('Login error:', error);
            errorDiv.textContent = 'An error occurred. Please try again.';
            errorDiv.style.display = 'block';
        }
    });
}

// ============ AUTH CHECK & DASHBOARD INIT ============
async function checkAuthAndInit() {
    try {
        const response = await fetch('../api/auth-status');
        const data = await response.json();

        if (!data.authenticated) {
            window.location.href = 'login.html';
            return;
        }

        window.csrfToken = data.csrf_token || '';
        document.getElementById('admin-username').textContent = data.username;
        initDashboard();
    } catch (error) {
        console.error('Auth check error:', error);
        window.location.href = 'login.html';
    }
}

// ============ DASHBOARD INITIALIZATION ============
function initDashboard() {
    initSidebarNavigation();
    initLogout();
    loadAllData();
    initModals();
    initForms();
}

// ============ SIDEBAR NAVIGATION ============
function initSidebarNavigation() {
    const links = document.querySelectorAll('.sidebar-link');
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const section = link.dataset.section;

            // Update active states
            links.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            // Update sections
            document.querySelectorAll('.admin-section').forEach(s => {
                s.classList.remove('active');
            });
            document.getElementById(`${section}-section`).classList.add('active');

            window.location.hash = section;
        });
    });

    const hash = window.location.hash.replace('#', '');
    if (hash) {
        const activeLink = document.querySelector(`.sidebar-link[data-section="${hash}"]`);
        if (activeLink) activeLink.click();
    }
}

// ============ LOGOUT ============
function initLogout() {
    document.getElementById('logout-btn').addEventListener('click', async () => {
        try {
            await fetch('../api/logout', { 
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': window.csrfToken }
            });
            window.location.href = 'login.html';
        } catch (error) {
            console.error('Logout error:', error);
        }
    });
}

// ============ LOAD ALL DATA ============
async function loadAllData() {
    await loadPrograms();
    await loadProjects();
    await loadDonations();
    await loadGallery();
    await loadPartners();
    await loadContacts();
    initSearchFilters();
}

// ============ PROGRAMS MANAGEMENT ============
async function loadPrograms() {
    try {
        showTableSkeletons("programs-tbody", 4);
        const response = await fetch('../api/programs');
        const programs = await response.json();
        const tbody = document.getElementById('programs-tbody');
        tbody.innerHTML = '';

        programs.forEach(program => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td><img src="${escapeHTML(program.image) || '../assets/images/programs-skills.png'}" width="32" height="32" style="object-fit:cover; border-radius:4px; vertical-align:middle; margin-right:8px; display:inline-block;">${escapeHTML(program.title)}</td>
        <td><span class="badge">${escapeHTML(program.tag) || '-'}</span></td>
        <td>${escapeHTML((program.description || '').substring(0, 50))}...</td>
        <td>
          <div style="display:flex; gap:8px;">
            <button class="btn btn-sm" onclick="editProgram(${parseInt(program.id, 10)})">Edit</button>
            <button class="btn btn-sm" onclick="deleteProgram(${parseInt(program.id, 10)})" style="color:var(--cta);">Delete</button>
          </div>
        </td>
      `;
            tbody.appendChild(tr);
        });

        if (programs.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4">
                <div class="empty-state active" style="padding: 60px 20px; display:flex; flex-direction:column; align-items:center; gap:16px; width:100%;">
                    <i data-lucide="folder-open" class="empty-state-icon" style="width:48px;height:48px;color:var(--text-muted);opacity:0.5;"></i>
                    <h3 class="empty-state-title" style="margin:0;color:var(--primary);font-size:1.25rem;">No Programs Found</h3>
                    <p class="empty-state-desc" style="margin:0;color:var(--text-muted);">You haven't added any programs yet.</p>
                </div>
            </td></tr>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    } catch (error) {
        console.error('Load programs error:', error);
    }
}

window.editProgram = function (id) {
    const link = document.querySelector(`[data-section="programs"]`);
    link.click();
    openFormModal('program', id);
};

window.deleteProgram = function (id) {
    openDeleteConfirm('program', id, () => {
        fetch(`../api/programs/${id}`, { 
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.csrfToken }
        })
            .then(() => loadPrograms())
            .catch(error => console.error('Delete error:', error));
    });
};

document.getElementById('add-program-btn').addEventListener('click', () => {
    openFormModal('program');
});

// ============ PROJECTS MANAGEMENT ============
async function loadProjects() {
    try {
        showTableSkeletons("projects-tbody", 5);
        const response = await fetch('../api/projects');
        const projects = await response.json();
        const tbody = document.getElementById('projects-tbody');
        tbody.innerHTML = '';

        projects.forEach(project => {
            const progress = project.goal_amount > 0 ? Math.round((project.raised_amount / project.goal_amount) * 100) : 0;
            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td><img src="${escapeHTML(project.image) || '../assets/images/hero-banner.png'}" width="32" height="32" style="object-fit:cover; border-radius:4px; vertical-align:middle; margin-right:8px; display:inline-block;">${escapeHTML(project.title)}</td>
        <td>₦${parseFloat(project.goal_amount).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
        <td>₦${parseFloat(project.raised_amount).toLocaleString('en-US', { minimumFractionDigits: 2 })} (${progress}%)</td>
        <td><span class="badge" style="background:${project.status === 'active' ? 'var(--green-pale)' : project.status === 'completed' ? 'var(--bg-alt)' : 'var(--bg-alt)'}; color:var(--text-dark);">${escapeHTML(project.status)}</span></td>
        <td>
          <div style="display:flex; gap:8px;">
            <button class="btn btn-sm" onclick="editProject(${parseInt(project.id, 10)})">Edit</button>
            <button class="btn btn-sm" onclick="deleteProject(${parseInt(project.id, 10)})" style="color:var(--cta);">Delete</button>
          </div>
        </td>
      `;
            tbody.appendChild(tr);
        });

        if (projects.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5">
                <div class="empty-state active" style="padding: 60px 20px; display:flex; flex-direction:column; align-items:center; gap:16px; width:100%;">
                    <i data-lucide="clipboard-list" class="empty-state-icon" style="width:48px;height:48px;color:var(--text-muted);opacity:0.5;"></i>
                    <h3 class="empty-state-title" style="margin:0;color:var(--primary);font-size:1.25rem;">No Active Projects</h3>
                    <p class="empty-state-desc" style="margin:0;color:var(--text-muted);">You don't have any projects yet.</p>
                </div>
            </td></tr>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    } catch (error) {
        console.error('Load projects error:', error);
    }
}

window.editProject = function (id) {
    openFormModal('project', id);
};

window.deleteProject = function (id) {
    openDeleteConfirm('project', id, () => {
        fetch(`../api/projects/${id}`, { 
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.csrfToken }
        })
            .then(() => loadProjects())
            .catch(error => console.error('Delete error:', error));
    });
};

document.getElementById('add-project-btn').addEventListener('click', () => {
    openFormModal('project');
});

// ============ DONATIONS MANAGEMENT ============
async function loadDonations() {
    try {
        showTableSkeletons("donations-tbody", 6);
        const response = await fetch('../api/donations');
        const donations = await response.json();
        const tbody = document.getElementById('donations-tbody');
        tbody.innerHTML = '';

        donations.forEach(donation => {
            const date = new Date(donation.created_at).toLocaleDateString();
            let phone = '-';
            if (donation.message && donation.message.startsWith('Phone: ')) {
                phone = donation.message.replace('Phone: ', '').trim();
            }
            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td>${escapeHTML(donation.donor_name)}</td>
        <td>${escapeHTML(donation.email)}</td>
        <td>${escapeHTML(phone)}</td>
        <td>₦${parseFloat(donation.amount).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
        <td>${escapeHTML(date)}</td>
        <td>
          <button class="btn btn-sm" onclick="deleteDonation(${parseInt(donation.id, 10)})" style="color:var(--cta);">Delete</button>
        </td>
      `;
            tbody.appendChild(tr);
        });

        if (donations.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6">
                <div class="empty-state active" style="padding: 60px 20px; display:flex; flex-direction:column; align-items:center; gap:16px; width:100%;">
                    <i data-lucide="heart" class="empty-state-icon" style="width:48px;height:48px;color:var(--text-muted);opacity:0.5;"></i>
                    <h3 class="empty-state-title" style="margin:0;color:var(--primary);font-size:1.25rem;">No Donations Yet</h3>
                    <p class="empty-state-desc" style="margin:0;color:var(--text-muted);">You haven't received any donations yet.</p>
                </div>
            </td></tr>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    } catch (error) {
        console.error('Load donations error:', error);
    }
}

window.deleteDonation = function (id) {
    openDeleteConfirm('donation', id, () => {
        fetch(`../api/donations/${id}`, { 
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.csrfToken }
        })
            .then(() => loadDonations())
            .catch(error => console.error('Delete error:', error));
    });
};

// ============ GALLERY MANAGEMENT ============
async function loadGallery() {
    try {
        showGallerySkeletons();
        const response = await fetch('../api/gallery');
        const gallery = await response.json();
        const grid = document.getElementById('gallery-grid');
        grid.innerHTML = '';

        gallery.forEach(item => {
            const div = document.createElement('div');
            div.className = 'gallery-item';
            div.innerHTML = `
        <div style="position:relative; padding-bottom:100%; background:var(--bg-alt);">
          <img src="${escapeHTML(item.image)}" alt="${escapeHTML(item.caption)}" style="position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover;">
          <div style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0); opacity:0; transition:all 0.3s ease;" class="gallery-overlay">
            <div style="position:absolute; bottom:0; left:0; right:0; background:rgba(0,0,0,0.7); color:var(--white); padding:12px; text-align:center;">
              <p style="margin:0; font-size:0.9rem;">${escapeHTML(item.caption) || 'No caption'}</p>
              <button class="btn btn-sm" onclick="deleteGallery(${parseInt(item.id, 10)})" style="margin-top:8px; background:var(--cta); color:var(--white);">Delete</button>
            </div>
          </div>
        </div>
      `;
            grid.appendChild(div);
        });

        if (gallery.length === 0) {
            grid.innerHTML = `
                <div class="empty-state active" style="grid-column: 1 / -1; padding: 60px 20px; display:flex; flex-direction:column; align-items:center; gap:16px;">
                    <i data-lucide="image" class="empty-state-icon" style="width:48px;height:48px;color:var(--text-muted);opacity:0.5;"></i>
                    <h3 class="empty-state-title" style="margin:0;color:var(--primary);font-size:1.25rem;">Gallery is Empty</h3>
                    <p class="empty-state-desc" style="margin:0;color:var(--text-muted);">You haven't uploaded any photos yet.</p>
                </div>
            `;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    } catch (error) {
        console.error('Load gallery error:', error);
    }
}

window.deleteGallery = function (id) {
    openDeleteConfirm('gallery', id, () => {
        fetch(`../api/gallery/${id}`, { 
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.csrfToken }
        })
            .then(() => loadGallery())
            .catch(error => console.error('Delete error:', error));
    });
};

document.getElementById('add-gallery-btn').addEventListener('click', () => {
    document.getElementById('gallery-modal').style.display = 'flex';
});

// ============ PARTNERS MANAGEMENT ============
async function loadPartners() {
    try {
        showTableSkeletons("partners-tbody", 5);
        const response = await fetch('../api/partners');
        const partners = await response.json();
        const tbody = document.getElementById('partners-tbody');
        tbody.innerHTML = '';

        partners.forEach(partner => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td>${escapeHTML(partner.org_name)}</td>
        <td>${escapeHTML(partner.contact_name) || '-'}</td>
        <td>${escapeHTML(partner.email)}</td>
        <td><span class="badge">${escapeHTML(partner.partnership_type) || '-'}</span></td>
        <td>
          <button class="btn btn-sm" onclick="deletePartner(${parseInt(partner.id, 10)})" style="color:var(--cta);">Delete</button>
        </td>
      `;
            tbody.appendChild(tr);
        });

        if (partners.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5">
                <div class="empty-state active" style="padding: 60px 20px; display:flex; flex-direction:column; align-items:center; gap:16px; width:100%;">
                    <i data-lucide="users" class="empty-state-icon" style="width:48px;height:48px;color:var(--text-muted);opacity:0.5;"></i>
                    <h3 class="empty-state-title" style="margin:0;color:var(--primary);font-size:1.25rem;">No Partners Yet</h3>
                    <p class="empty-state-desc" style="margin:0;color:var(--text-muted);">You don't have any partnership applications yet.</p>
                </div>
            </td></tr>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    } catch (error) {
        console.error('Load partners error:', error);
    }
}

window.deletePartner = function (id) {
    openDeleteConfirm('partner', id, () => {
        fetch(`../api/partners/${id}`, { 
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.csrfToken }
        })
            .then(() => loadPartners())
            .catch(error => console.error('Delete error:', error));
    });
};

// ============ CONTACTS MANAGEMENT ============
async function loadContacts() {
    try {
        showTableSkeletons("contacts-tbody", 7);
        const response = await fetch('../api/contact');
        const contacts = await response.json();
        const tbody = document.getElementById('contacts-tbody');
        tbody.innerHTML = '';

        contacts.forEach(contact => {
            const date = new Date(contact.created_at).toLocaleDateString();
            const initial = escapeHTML(contact.name).charAt(0).toUpperCase() || '?';
            const colors = ['#E76F51', '#2A9D8F', '#E9C46A', '#F4A261', '#264653'];
            const colorIndex = (contact.name || '').length % colors.length;
            const bgColor = colors[colorIndex];

            const avatarHtml = `<div style="width:32px; height:32px; border-radius:50%; background-color:${bgColor}; color:white; display:flex; align-items:center; justify-content:center; font-weight:bold; margin-right:8px; display:inline-flex; vertical-align:middle;">${initial}</div>`;

            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td>${avatarHtml}${escapeHTML(contact.name)}</td>
        <td>${escapeHTML(contact.email)}</td>
        <td>${escapeHTML(contact.phone || '-')}</td>
        <td>${escapeHTML(contact.subject || '-')}</td>
        <td>${escapeHTML((contact.message || '').substring(0, 50))}...</td>
        <td>${escapeHTML(date)}</td>
        <td>
          <button class="btn btn-sm" onclick="deleteContact(${parseInt(contact.id, 10)})" style="color:var(--cta);">Delete</button>
        </td>
      `;
            tbody.appendChild(tr);
        });

        if (contacts.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7">
                <div class="empty-state active" style="padding: 60px 20px; display:flex; flex-direction:column; align-items:center; gap:16px; width:100%;">
                    <i data-lucide="mail" class="empty-state-icon" style="width:48px;height:48px;color:var(--text-muted);opacity:0.5;"></i>
                    <h3 class="empty-state-title" style="margin:0;color:var(--primary);font-size:1.25rem;">No Messages Yet</h3>
                    <p class="empty-state-desc" style="margin:0;color:var(--text-muted);">You don't have any contact messages yet.</p>
                </div>
            </td></tr>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    } catch (error) {
        console.error('Load contacts error:', error);
    }
}

window.deleteContact = function (id) {
    openDeleteConfirm('contact message', id, () => {
        fetch(`../api/contact/${id}`, { 
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.csrfToken }
        })
            .then(() => loadContacts())
            .catch(error => console.error('Delete error:', error));
    });
};

// ============ SEARCH FILTERS ============
function initSearchFilters() {
    const searchInputs = document.querySelectorAll('.section-search');
    searchInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const targetId = input.dataset.target;
            const container = document.getElementById(targetId);
            if (!container) return;

            if (container.tagName === 'TBODY') {
                const rows = container.querySelectorAll('tr');
                rows.forEach(row => {
                    if (row.querySelector('.empty-state')) return;
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            } else if (targetId === 'gallery-grid') {
                const items = container.querySelectorAll('.gallery-item');
                items.forEach(item => {
                    if (item.querySelector('.empty-state')) return;
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(query) ? '' : 'none';
                });
            }
        });
    });
}

// ============ MODALS ============
function initModals() {
    // Form Modal
    document.getElementById('close-form-modal').addEventListener('click', () => {
        document.getElementById('form-modal').style.display = 'none';
    });
    document.getElementById('cancel-form').addEventListener('click', () => {
        document.getElementById('form-modal').style.display = 'none';
    });

    // Gallery Modal
    document.getElementById('close-gallery-modal').addEventListener('click', () => {
        document.getElementById('gallery-modal').style.display = 'none';
    });
    document.getElementById('cancel-gallery').addEventListener('click', () => {
        document.getElementById('gallery-modal').style.display = 'none';
    });

    // Confirm Modal
    document.getElementById('close-confirm-modal').addEventListener('click', () => {
        document.getElementById('confirm-modal').style.display = 'none';
    });
    document.getElementById('cancel-confirm').addEventListener('click', () => {
        document.getElementById('confirm-modal').style.display = 'none';
    });

    // Close modals on outside click
    window.addEventListener('click', (e) => {
        const formModal = document.getElementById('form-modal');
        const galleryModal = document.getElementById('gallery-modal');
        const confirmModal = document.getElementById('confirm-modal');

        if (e.target === formModal) formModal.style.display = 'none';
        if (e.target === galleryModal) galleryModal.style.display = 'none';
        if (e.target === confirmModal) confirmModal.style.display = 'none';
    });
}

// ============ FORM HANDLING ============
let currentFormType = null;
let currentFormId = null;

async function openFormModal(type, id = null) {
    currentFormType = type;
    currentFormId = id;

    const modal = document.getElementById('form-modal');
    const title = document.getElementById('form-modal-title');
    const fieldsDiv = document.getElementById('form-fields');

    if (type === 'program') {
        title.textContent = id ? 'Edit Program' : 'Add Program';
        fieldsDiv.innerHTML = `
      <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" placeholder="Program title" required>
      </div>
      <div class="form-group">
        <label for="tag">Tag</label>
        <input type="text" id="tag" placeholder="e.g., Mental Health">
      </div>
      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" placeholder="Program description" rows="4"></textarea>
      </div>
      <div class="form-group">
        <label for="image">Image URL</label>
        <input type="text" id="image" placeholder="https://example.com/image.jpg">
      </div>
    `;
    } else if (type === 'project') {
        title.textContent = id ? 'Edit Project' : 'Add Project';
        fieldsDiv.innerHTML = `
      <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" placeholder="Project title" required>
      </div>
      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" placeholder="Project description" rows="4"></textarea>
      </div>
      <div class="form-group">
        <label for="goal_amount">Goal Amount (₦)</label>
        <input type="number" id="goal_amount" placeholder="0" step="0.01">
      </div>
      <div class="form-group">
        <label for="raised_amount">Raised Amount (₦)</label>
        <input type="number" id="raised_amount" placeholder="0" step="0.01">
      </div>
      <div class="form-group">
        <label for="status">Status</label>
        <select id="status">
          <option value="active">Active</option>
          <option value="completed">Completed</option>
          <option value="upcoming">Upcoming</option>
        </select>
      </div>
      <div class="form-group">
        <label for="image">Image URL</label>
        <input type="text" id="image" placeholder="https://example.com/image.jpg">
      </div>
    `;
    }

    if (id) {
        // Load existing data
        const endpoint = type === 'program' ? `../api/programs` : `../api/projects`;
        const response = await fetch(endpoint);
        const items = await response.json();
        const item = items.find(i => i.id === id);

        if (item) {
            if (type === 'program') {
                document.getElementById('title').value = item.title;
                document.getElementById('tag').value = item.tag || '';
                document.getElementById('description').value = item.description || '';
                document.getElementById('image').value = item.image || '';
            } else if (type === 'project') {
                document.getElementById('title').value = item.title;
                document.getElementById('description').value = item.description || '';
                document.getElementById('goal_amount').value = item.goal_amount || 0;
                document.getElementById('raised_amount').value = item.raised_amount || 0;
                document.getElementById('status').value = item.status || 'active';
                document.getElementById('image').value = item.image || '';
            }
        }
    }

    modal.style.display = 'flex';
}

function initForms() {
    document.getElementById('item-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = {};
        document.querySelectorAll('#form-fields input, #form-fields textarea, #form-fields select').forEach(input => {
            formData[input.id] = input.value;
        });

        try {
            const endpoint = currentFormType === 'program' ? '../api/programs' : '../api/projects';
            const method = currentFormId ? 'PUT' : 'POST';
            const url = currentFormId ? `${endpoint}/${currentFormId}` : endpoint;

            const response = await fetch(url, {
                method,
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken
                },
                body: JSON.stringify(formData)
            });

            if (response.ok) {
                document.getElementById('form-modal').style.display = 'none';
                if (currentFormType === 'program') loadPrograms();
                else if (currentFormType === 'project') loadProjects();
            }
        } catch (error) {
            console.error('Form submit error:', error);
        }
    });

    document.getElementById('gallery-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = {
            image: document.getElementById('gallery-image').value,
            caption: document.getElementById('gallery-caption').value,
            category: document.getElementById('gallery-category').value
        };

        try {
            const response = await fetch('../api/gallery', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken
                },
                body: JSON.stringify(formData)
            });

            if (response.ok) {
                document.getElementById('gallery-modal').style.display = 'none';
                document.getElementById('gallery-form').reset();
                loadGallery();
            }
        } catch (error) {
            console.error('Gallery upload error:', error);
        }
    });
}

// ============ DELETE CONFIRMATION ============
let deleteCallback = null;

function openDeleteConfirm(type, id, callback) {
    const modal = document.getElementById('confirm-modal');
    const message = document.getElementById('confirm-message');
    const confirmBtn = document.getElementById('confirm-delete');

    message.textContent = `Are you sure you want to delete this ${type}? This action cannot be undone.`;
    deleteCallback = callback;

    modal.style.display = 'flex';

    confirmBtn.onclick = () => {
        callback();
        modal.style.display = 'none';
    };
}
