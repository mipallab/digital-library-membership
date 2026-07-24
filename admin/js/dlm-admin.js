/* Shared Admin Javascript Functions & Interactive Features */

document.addEventListener('DOMContentLoaded', () => {
    initNavigation();
    initMicroInteractions();
    initModalSystem();
    initSidebarCollapse();
    initMediaUploaders();
    initCatalogSearch();
    initSettingsTabs();
    initCharts();
    initCustomScrollbars();
    handleInitialNavigation();

    // Initialize Dashboard Revenue Growth chart with dynamic data
    if (typeof window.toggleDashboardRevenue === 'function') {
        window.toggleDashboardRevenue('monthly');
    }
});

/* 1. SPA ROUTING & NAVIGATION SYSTEM */
function initNavigation() {
    window.addEventListener('popstate', (event) => {
        let sectionName = 'dashboard';
        if (event.state && event.state.section) {
            sectionName = event.state.section;
        } else {
            const urlParams = new URLSearchParams(window.location.search);
            sectionName = urlParams.get('tab') || 'dashboard';
        }
        window.navigateSpa(sectionName, false);
    });
}

window.navigateSpa = function(sectionName, pushState = true) {
    // Hide all sections
    document.querySelectorAll('.spa-section').forEach(sec => {
        sec.classList.add('hidden');
    });

    // Show target section
    const targetSec = document.getElementById('sec-' + sectionName);
    if (targetSec) {
        targetSec.classList.remove('hidden');
    }

    // Highlight active link in sidebar and mobile nav
    document.querySelectorAll('[data-nav]').forEach(link => {
        if (link.getAttribute('data-nav') === sectionName) {
            link.classList.add('nav-active');
            link.classList.remove('text-secondary', 'hover:bg-surface-container-low/50', 'hover:text-on-surface');
        } else {
            link.classList.remove('nav-active');
            link.classList.add('text-secondary', 'hover:bg-surface-container-low/50', 'hover:text-on-surface');
        }
    });

    // Update history URL query parameters
    if (pushState) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('tab', sectionName);
        window.history.pushState({ section: sectionName }, '', currentUrl.toString());
    }

    // Redraw charts if entering sales/analytics section
    if (sectionName === 'analytics') {
        initCharts();
    }
};

function handleInitialNavigation() {
    const urlParams = new URLSearchParams(window.location.search);
    const sectionName = urlParams.get('tab') || 'dashboard';
    window.navigateSpa(sectionName, false);
}

/* 2. MODAL MANAGEMENT SYSTEM & DYNAMIC CRUD POPULATION */
window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden'; // Lock background scrolling
    }
};

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        document.body.style.overflow = ''; // Restore background scrolling
    }
};

function initModalSystem() {
    // Bind buttons with data-open-modal attribute
    document.body.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-open-modal]');
        if (trigger) {
            const modalId = trigger.getAttribute('data-open-modal');
            window.openModal(modalId);
        }

        const closeTrigger = e.target.closest('[data-close-modal]');
        if (closeTrigger) {
            const modalId = closeTrigger.getAttribute('data-close-modal') || closeTrigger.closest('.fixed').id;
            window.closeModal(modalId);
        }

        // Close on backdrop clicks
        if (e.target.classList.contains('modal-backdrop')) {
            const modalId = e.target.closest('.fixed').id;
            window.closeModal(modalId);
        }
    });

    // Populate Edit Book Modal from Row attributes
    jQuery(document).on('click', '.btn-edit-book', function() {
        const tr = jQuery(this).closest('tr');
        const id = tr.attr('data-id');
        const title = tr.attr('data-title');
        const author = tr.attr('data-author');
        const description = tr.attr('data-description');
        const cover = tr.attr('data-cover');
        const status = tr.attr('data-status');
        const category = tr.attr('data-category');
        const tags = tr.attr('data-tags');

        jQuery('#edit-book-id').val(id);
        jQuery('#edit-book-title').val(title);
        jQuery('#edit-book-author').val(author);
        jQuery('#edit-book-description').val(description);
        jQuery('#edit-book-cover-input').val(cover);
        jQuery('#edit-book-status').val(status);
        jQuery('#edit-book-category').val(category);
        jQuery('#edit-book-tags').val(tags);

        if (cover) {
            jQuery('#edit-cover-preview').attr('src', cover).removeClass('hidden');
            jQuery('#edit-cover-placeholder').addClass('hidden');
        } else {
            jQuery('#edit-cover-preview').addClass('hidden');
            jQuery('#edit-cover-placeholder').removeClass('hidden');
        }

        window.openModal('edit-book-modal');
    });

    // Populate Delete Book Modal
    jQuery(document).on('click', '.btn-delete-book', function() {
        const tr = jQuery(this).closest('tr');
        const id = tr.attr('data-id');
        const title = tr.attr('data-title');

        jQuery('#delete-book-id').val(id);
        jQuery('#delete-book-title-display').text(title);

        window.openModal('delete-book-modal');
    });

    // Populate Edit Transaction Modal
    jQuery(document).on('click', '.btn-edit-tx', function() {
        const tr = jQuery(this).closest('tr');
        const id = tr.attr('data-id');
        const username = tr.attr('data-username') || 'Deleted User';
        const useremail = tr.attr('data-useremail') || '—';
        const subId = tr.attr('data-sub-id');
        const txId = tr.attr('data-tx-id');
        const provider = tr.attr('data-provider');
        const amount = tr.attr('data-amount');
        const currency = tr.attr('data-currency');
        const status = tr.attr('data-status');

        jQuery('#edit-tx-db-id').val(id);
        jQuery('#edit-tx-user-display').val(username + ' (' + useremail + ')');
        jQuery('#edit-tx-sub-display').val(subId);
        jQuery('#edit-tx-ref-display').val(txId);
        jQuery('#edit-tx-provider-display').val(provider.toUpperCase());
        jQuery('#edit-tx-amount-display').val(parseFloat(amount).toFixed(2));
        jQuery('#edit-tx-currency-display').val(currency.toUpperCase());
        jQuery('#edit-tx-status').val(status);

        window.openModal('edit-transaction-modal');
    });

    // Populate Send Email Modal
    jQuery(document).on('click', '.btn-send-email', function() {
        const tr = jQuery(this).closest('tr');
        const email = tr.attr('data-email');
        const name = tr.attr('data-name');

        jQuery('#send-email-recipient-input').val(email);
        jQuery('#send-email-recipient-display').val(name + ' <' + email + '>');

        window.openModal('send-email-modal');
    });

    // Populate Edit Member (Override) Modal
    jQuery(document).on('click', '.btn-edit-member', function() {
        const tr = jQuery(this).closest('tr');
        const name = tr.attr('data-name');
        const email = tr.attr('data-email');
        const status = tr.attr('data-status');
        const tier = tr.attr('data-tier') || 'monthly';
        const expires = tr.attr('data-expires') || '';

        jQuery('#edit-member-name').val(name);
        jQuery('#edit-member-email').val(email);
        jQuery('#edit-member-email-hidden').val(email);
        jQuery('#edit-member-override-status').val(status === 'active' ? 'active' : 'disabled');
        jQuery('#edit-member-plan-interval').val(tier);
        jQuery('#edit-member-expires-at').val(expires);

        if (status === 'active') {
            jQuery('.edit-override-active-fields').show();
        } else {
            jQuery('.edit-override-active-fields').hide();
        }

        window.openModal('edit-member-modal');
    });

    jQuery(document).on('change', '#edit-member-override-status', function() {
        if (jQuery(this).val() === 'active') {
            jQuery('.edit-override-active-fields').show();
        } else {
            jQuery('.edit-override-active-fields').hide();
        }
    });

    // Populate Delete Member Modal
    jQuery(document).on('click', '.btn-delete-member', function() {
        const tr = jQuery(this).closest('tr');
        const dbId = tr.attr('data-db-id');
        const userId = tr.attr('data-user-id');
        const name = tr.attr('data-name');

        jQuery('#delete-member-db-id').val(dbId);
        jQuery('#delete-member-user-id').val(userId);
        jQuery('#delete-member-name-display').text(name);

        window.openModal('delete-member-modal');
    });
}

/* 3. WORDPRESS MEDIA UPLOADER FOR COVERS */
function initMediaUploaders() {
    initWPBooksMediaUploader('add-book-select-cover-btn', 'add-book-cover-input', 'add-cover-preview', 'add-cover-placeholder');
    initWPBooksMediaUploader('edit-book-select-cover-btn', 'edit-book-cover-input', 'edit-cover-preview', 'edit-cover-placeholder');

    // Register drag & drop file upload format checks
    jQuery(document).on('change', '.dlm-file-input', function(e) {
        const file = e.target.files[0];
        const $container = jQuery(this).closest('.relative');
        const $label = $container.find('.select-file-label');
        const $icon = $container.find('.fa-file-pdf');
        
        // Remove any existing error messages
        $container.parent().find('.dlm-file-error').remove();

        if (!file) {
            $label.text(jQuery(this).attr('required') ? 'Drag & Drop or Click to upload book' : 'Drag & Drop or Click to upload new file');
            $icon.removeClass('text-primary/95').addClass('text-secondary/40');
            return;
        }

        const ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'pdf') {
            e.target.value = '';
            $label.text(jQuery(this).attr('required') ? 'Drag & Drop or Click to upload book' : 'Drag & Drop or Click to upload new file');
            $icon.removeClass('text-primary/95').addClass('text-secondary/40');
            
            // Add inline error message
            $container.after('<p class="dlm-file-error text-error text-xs font-bold mt-2">Only PDF format is allowed for book uploads.</p>');
            return;
        }

        // Success! Display selected file size
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        $label.html(`<span class="text-primary font-bold">Selected:</span> ${file.name} (${sizeMB} MB)`);
        $icon.removeClass('text-secondary/40').addClass('text-primary/95');
    });
}

function initWPBooksMediaUploader(btnId, inputId, previewId, placeholderId) {
    var mediaUploader;
    jQuery(document).on('click', '#' + btnId, function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media({
            title: 'Choose Cover Image',
            button: { text: 'Use Image' },
            multiple: false
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            jQuery('#' + inputId).val(attachment.url);
            jQuery('#' + previewId).attr('src', attachment.url).removeClass('hidden');
            jQuery('#' + placeholderId).addClass('hidden');
        });
        mediaUploader.open();
    });
}

/* 4. SETTINGS SUB-TAB SWITCHING */
function initSettingsTabs() {
    window.switchSettingsTab = function(tabName) {
        // Toggle tab highlights
        const tabIds = ['general', 'stripe', 'paypal', 'woocommerce', 'security'];
        tabIds.forEach(id => {
            const btn = document.getElementById('tab-settings-' + id);
            const panel = document.getElementById('panel-settings-' + id);
            if (btn && panel) {
                if (id === tabName) {
                    btn.className = "w-full text-left px-5 py-3 rounded-xl font-bold text-sm bg-primary/10 text-primary transition-all flex items-center gap-3";
                    panel.classList.remove('hidden');
                } else {
                    btn.className = "w-full text-left px-5 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-surface-container-low transition-all flex items-center gap-3";
                    panel.classList.add('hidden');
                }
            }
        });
    };
}

/* 5. SIDEBAR COLLAPSE TOGGLER */
function initSidebarCollapse() {
    const toggleBtn = document.getElementById('sidebar-toggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
        });
    }
}

/* 6. INTERACTIVE MICRO-INTERACTIONS */
function initMicroInteractions() {
    document.querySelectorAll('.bento-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            const icon = card.querySelector('.fa-solid, .fa-regular, .fa-brands, .fa');
            if (icon) {
                icon.style.transform = 'scale(1.2) rotate(-5deg)';
                icon.style.transition = 'transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
            }
        });
        card.addEventListener('mouseleave', () => {
            const icon = card.querySelector('.fa-solid, .fa-regular, .fa-brands, .fa');
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }
        });
    });
}

/* 7. DYNAMIC LIVE CHARTS (ChartJS) */
function compileRevenueChartData(mode) {
    const rawTxs = window.dlmAnalyticsData.transactions || [];
    let labels = [];
    let data = [];

    if (mode === 'weekly') {
        const daysToCompute = 7;
        const now = new Date();
        for (let i = daysToCompute - 1; i >= 0; i--) {
            const d = new Date();
            d.setDate(now.getDate() - i);
            const weekday = d.toLocaleDateString(undefined, { weekday: 'short' });
            labels.push(weekday);
            
            const sum = rawTxs.reduce((acc, tx) => {
                const txDate = new Date(tx.created_at);
                if (txDate.toDateString() === d.toDateString()) {
                    return acc + parseFloat(tx.amount);
                }
                return acc;
            }, 0);
            data.push(sum);
        }
    } else {
        const now = new Date();
        for (let i = 11; i >= 0; i--) {
            const d = new Date();
            d.setMonth(now.getMonth() - i);
            const monthLabel = d.toLocaleDateString(undefined, { month: 'short' });
            labels.push(monthLabel);

            const sum = rawTxs.reduce((acc, tx) => {
                const txDate = new Date(tx.created_at);
                if (txDate.getMonth() === d.getMonth() && txDate.getFullYear() === d.getFullYear()) {
                    return acc + parseFloat(tx.amount);
                }
                return acc;
            }, 0);
            data.push(sum);
        }
    }
    return { labels, data };
}

function initCharts() {
    if (typeof Chart === 'undefined') return;

    if (window.myRevenueChart) {
        window.myRevenueChart.destroy();
        window.myRevenueChart = null;
    }
    if (window.myMembershipChart) {
        window.myMembershipChart.destroy();
        window.myMembershipChart = null;
    }

    const canvasRevenue = document.getElementById('revenueChart');
    if (canvasRevenue && window.dlmAnalyticsData) {
        const ctxRevenue = canvasRevenue.getContext('2d');
        const gradient = ctxRevenue.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(133, 83, 0, 0.2)');
        gradient.addColorStop(1, 'rgba(133, 83, 0, 0)');

        const compiled = compileRevenueChartData('weekly');

        window.myRevenueChart = new Chart(ctxRevenue, {
            type: 'line',
            data: {
                labels: compiled.labels,
                datasets: [{
                    label: 'Revenue Performance',
                    data: compiled.data,
                    borderColor: '#855300',
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#855300',
                    pointBorderColor: '#FFF',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1A1A1A',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: ' + context.parsed.y.toLocaleString() + ' ' + window.dlmAnalyticsData.currency;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.03)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const canvasMember = document.getElementById('membershipChart');
    if (canvasMember && window.dlmAnalyticsData) {
        const ctxMember = canvasMember.getContext('2d');
        const activeSubscribers = parseInt(window.dlmAnalyticsData.activeSubscribers) || 0;
        const totalSubscribers = parseInt(window.dlmAnalyticsData.totalSubscribers) || 0;
        const inactiveSubscribers = Math.max(0, totalSubscribers - activeSubscribers);
        
        window.myMembershipChart = new Chart(ctxMember, {
            type: 'doughnut',
            data: {
                labels: ['Active Subscribers', 'Inactive / Lapsed'],
                datasets: [{
                    data: (activeSubscribers === 0 && inactiveSubscribers === 0) ? [0, 0.0001] : [activeSubscribers, inactiveSubscribers],
                    backgroundColor: ['#855300', '#CAC4D0'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '85%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
}

/* Revenue Performance Toggle (Weekly / Monthly) on Analytics Page */
window.toggleAnalyticsRevenue = function(mode) {
    if (!window.myRevenueChart || !window.dlmAnalyticsData) return;

    const btnWeekly = document.getElementById('btn-analytics-weekly');
    const btnMonthly = document.getElementById('btn-analytics-monthly');

    const compiled = compileRevenueChartData(mode);

    if (mode === 'weekly') {
        btnWeekly.className = "px-4 py-1.5 text-xs font-bold rounded-md bg-white shadow-sm text-primary transition-all";
        btnMonthly.className = "px-4 py-1.5 text-xs font-bold rounded-md text-secondary hover:text-on-surface transition-all";
    } else {
        btnMonthly.className = "px-4 py-1.5 text-xs font-bold rounded-md bg-white shadow-sm text-primary transition-all";
        btnWeekly.className = "px-4 py-1.5 text-xs font-bold rounded-md text-secondary hover:text-on-surface transition-all";
    }

    window.myRevenueChart.data.labels = compiled.labels;
    window.myRevenueChart.data.datasets[0].data = compiled.data;
    window.myRevenueChart.update();
};

/* SVG Chart Overview Revenue growth Toggler */
window.toggleDashboardRevenue = function(mode) {
    const btnMonthly = document.getElementById('btn-rev-monthly');
    const btnYearly = document.getElementById('btn-rev-yearly');
    const pathFill = document.getElementById('rev-path-fill');
    const pathStroke = document.getElementById('rev-path-stroke');
    const xLabels = document.getElementById('rev-x-labels');
    const circles = document.getElementById('rev-circles');

    if (!btnMonthly || !btnYearly || !pathFill || !pathStroke || !xLabels) return;

    // Toggle button active styling
    if (mode === 'monthly') {
        btnMonthly.className = "px-4 py-1.5 text-[11px] font-bold uppercase rounded-lg bg-white text-primary shadow-sm transition-all";
        btnYearly.className = "px-4 py-1.5 text-[11px] font-bold uppercase rounded-lg text-secondary hover:text-on-surface transition-all";
    } else {
        btnYearly.className = "px-4 py-1.5 text-[11px] font-bold uppercase rounded-lg bg-white text-primary shadow-sm transition-all";
        btnMonthly.className = "px-4 py-1.5 text-[11px] font-bold uppercase rounded-lg text-secondary hover:text-on-surface transition-all";
    }

    // Get dynamic transactions
    const rawTxs = window.dlmAnalyticsData ? (window.dlmAnalyticsData.transactions || []) : [];
    let labels = [];
    let data = [];

    if (mode === 'monthly') {
        const now = new Date();
        for (let i = 11; i >= 0; i--) {
            const d = new Date();
            d.setMonth(now.getMonth() - i);
            const monthLabel = d.toLocaleDateString(undefined, { month: 'short' });
            labels.push(monthLabel);

            const sum = rawTxs.reduce((acc, tx) => {
                const txDate = new Date(tx.created_at);
                if (txDate.getMonth() === d.getMonth() && txDate.getFullYear() === d.getFullYear()) {
                    return acc + parseFloat(tx.amount);
                }
                return acc;
            }, 0);
            data.push(sum);
        }
    } else {
        const now = new Date();
        const currentYear = now.getFullYear();
        for (let i = 3; i >= 0; i--) {
            const year = currentYear - i;
            labels.push(year.toString());

            const sum = rawTxs.reduce((acc, tx) => {
                const txDate = new Date(tx.created_at);
                if (txDate.getFullYear() === year) {
                    return acc + parseFloat(tx.amount);
                }
                return acc;
            }, 0);
            data.push(sum);
        }
    }

    const N = data.length;
    if (N === 0) {
        pathFill.setAttribute('d', 'M 0 180 L 800 180 L 800 200 L 0 200 Z');
        pathStroke.setAttribute('d', 'M 0 180 L 800 180');
        xLabels.innerHTML = '';
        if (circles) circles.innerHTML = '';
        return;
    }

    const maxVal = Math.max(...data, 0);
    const scaleMax = maxVal > 0 ? maxVal : 100;

    const points = [];
    for (let i = 0; i < N; i++) {
        const x = N > 1 ? i * (800 / (N - 1)) : 0;
        const y = 180 - (data[i] / scaleMax) * 160;
        points.push({ x, y, value: data[i], label: labels[i] });
    }

    // Build cubic bezier path for smooth horizontal tension curves
    let pathD = `M ${points[0].x} ${points[0].y}`;
    for (let i = 0; i < N - 1; i++) {
        const p0 = points[i];
        const p1 = points[i+1];
        const cpX1 = p0.x + (p1.x - p0.x) / 3;
        const cpY1 = p0.y;
        const cpX2 = p0.x + 2 * (p1.x - p0.x) / 3;
        const cpY2 = p1.y;
        pathD += ` C ${cpX1} ${cpY1}, ${cpX2} ${cpY2}, ${p1.x} ${p1.y}`;
    }

    const fillD = `${pathD} L 800 200 L 0 200 Z`;

    pathFill.setAttribute('d', fillD);
    pathStroke.setAttribute('d', pathD);

    xLabels.innerHTML = labels.map(label => `<span>${label}</span>`).join('');

    if (circles) {
        const currencySymbol = (window.dlmAnalyticsData && window.dlmAnalyticsData.currency) || 'USD';
        let circlesHTML = '';
        points.forEach(p => {
            circlesHTML += `
                <circle class="hover:scale-125 transition-transform cursor-pointer" 
                        cx="${p.x}" cy="${p.y}" 
                        fill="#855300" r="5" stroke="#ffffff" stroke-width="2">
                    <title>${p.label}: ${p.value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} ${currencySymbol}</title>
                </circle>
            `;
        });
        circles.innerHTML = circlesHTML;
    }
};

/* 8. CATALOG REAL-TIME FILTERING */
function initCatalogSearch() {
    const searchInput = document.getElementById('books-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#books-table tbody tr');

            rows.forEach(row => {
                const title = (row.getAttribute('data-title') || '').toLowerCase();
                const author = (row.getAttribute('data-author') || '').toLowerCase();
                
                if (title.includes(query) || author.includes(query)) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            });
        });
    }
}

function initCustomScrollbars() {
    jQuery(document).on('mouseenter', '.dlm-hover-scrollbar', function() {
        jQuery(this).addClass('firefox-hover');
    }).on('mouseleave', '.dlm-hover-scrollbar', function() {
        jQuery(this).removeClass('firefox-hover');
    });
}

window.showAlertModal = function(title, message, type = 'success') {
    const modal = document.getElementById('dlmAlertModal');
    const iconContainer = document.getElementById('dlmAlertIcon');
    const titleEl = document.getElementById('dlmAlertTitle');
    const messageEl = document.getElementById('dlmAlertMessage');
    
    if (!modal) return;
    
    if (type === 'success') {
        iconContainer.className = "w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl bg-green-50 text-green-600 border border-green-200";
        iconContainer.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
    } else if (type === 'error') {
        iconContainer.className = "w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl bg-red-50 text-red-600 border border-red-200";
        iconContainer.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
    } else {
        iconContainer.className = "w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl bg-blue-50 text-blue-600 border border-blue-200";
        iconContainer.innerHTML = '<i class="fa-solid fa-circle-info"></i>';
    }
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

window.closeAlertModal = function() {
    const modal = document.getElementById('dlmAlertModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
};

// Auto detect success/error parameters and display alert modal
jQuery(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success') || urlParams.has('deleted') || urlParams.has('error')) {
        let title = 'Notification';
        let msg = '';
        let type = 'success';
        
        const successVal = urlParams.get('success');
        const deletedVal = urlParams.get('deleted');
        const errorVal = urlParams.get('error');

        if (successVal === 'add_book') {
            title = 'Book Added';
            msg = 'Book added successfully!';
        } else if (successVal === 'edit_book') {
            title = 'Book Updated';
            msg = 'Book updated successfully!';
        } else if (successVal === 'delete_book') {
            title = 'Book Deleted';
            msg = 'Book deleted successfully.';
        } else if (successVal === 'add_member') {
            title = 'Member Added';
            msg = 'New member created successfully!';
        } else if (successVal === 'edit_member') {
            title = 'Member Updated';
            msg = 'Member details updated successfully!';
        } else if (successVal === 'delete_member') {
            title = 'Member Deleted';
            msg = 'Member record deleted successfully.';
        } else if (successVal === 'approve_member') {
            title = 'Subscription Approved';
            msg = 'Member subscription approved successfully.';
        } else if (successVal === 'reject_member') {
            title = 'Subscription Rejected';
            msg = 'Member subscription rejected.';
        } else if (successVal === 'tx_added') {
            title = 'Transaction Added';
            msg = 'New transaction added successfully!';
        } else if (successVal === 'tx_updated') {
            title = 'Transaction Updated';
            msg = 'Transaction status updated successfully!';
        } else if (successVal === 'tx_deleted') {
            title = 'Transaction Deleted';
            msg = 'Transaction deleted successfully.';
        } else if (errorVal === 'file_too_large') {
            title = 'Upload Error';
            const maxSize = urlParams.get('max_size') || '50';
            const uploadedSize = urlParams.get('uploaded_size') || '0';
            msg = 'max book upload size is ' + maxSize + 'MB you upload ' + uploadedSize + ' mb.';
            type = 'error';
        } else if (errorVal === 'pdf_only') {
            title = 'Upload Error';
            msg = 'Only PDF format is allowed for book uploads.';
            type = 'error';
        } else if (successVal === '1') {
            title = 'Success';
            msg = 'Action completed successfully.';
        }

        if (msg) {
            window.showAlertModal(title, msg, type);
            
            // Clean up the URL parameters so reloading doesn't show it again
            urlParams.delete('success');
            urlParams.delete('deleted');
            urlParams.delete('error');
            urlParams.delete('max_size');
            urlParams.delete('uploaded_size');
            const cleanUrl = window.location.pathname + '?' + urlParams.toString();
            window.history.replaceState({}, '', cleanUrl);
        }
    }
});
