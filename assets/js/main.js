const initAppToggler = () => {

    const appTogglers = document.querySelectorAll(".app-toggler");
	const appMenubars = document.getElementById("appMenubar"); // single element
	
	if (!appMenubars || appTogglers.length === 0) return;

	appTogglers.forEach(toggler => {
		toggler.addEventListener("click", () => {
			toggler.classList.toggle("active");

			if (window.innerWidth >= 1191) {
				// Toggle the value between 'full' and 'mini'
				const currentValue = document.documentElement.getAttribute("data-app-sidebar");
				document.documentElement.setAttribute(
					"data-app-sidebar",
					currentValue === "full" ? "mini" : "full"
				);
			} else {
				// For <1191, toggle 'open' on the single menubar
				appMenubars.classList.toggle("open");
			}
		});
	});

	// Hover logic for mini sidebar
	appMenubars.addEventListener("mouseenter", () => {
		if (document.documentElement.getAttribute("data-app-sidebar") === "mini") {
			document.documentElement.setAttribute("data-app-sidebar", "mini-hover");
		}
	});

	appMenubars.addEventListener("mouseleave", () => {
		if (document.documentElement.getAttribute("data-app-sidebar") === "mini-hover") {
			document.documentElement.setAttribute("data-app-sidebar", "mini");
		}
	});
};

const saerchList = () => {
	const isAdmin = window.location.pathname.includes('/admin/');
	const prefix = isAdmin ? '../' : '';
	const adminPrefix = isAdmin ? '' : 'admin/';

	let listItems = [
		// User pages
		{ name: 'Dashboard Overview', url: prefix + 'dashboard.php', category: 'Creator Area', icon: 'fa-solid fa-gauge' },
		{ name: 'My Videos', url: prefix + 'videos.php', category: 'Creator Area', icon: 'fa-solid fa-video' },
		{ name: 'Upload Video', url: prefix + 'video-upload.php', category: 'Creator Area', icon: 'fa-solid fa-cloud-arrow-up' },
		{ name: 'Landing Page Builder', url: prefix + 'builder.php', category: 'Creator Area', icon: 'fa-solid fa-wand-magic-sparkles' },
		{ name: 'Domain Store', url: prefix + 'domain.php', category: 'Creator Area', icon: 'fa-solid fa-globe' },
		{ name: 'Packages & Pricing', url: prefix + 'package.php', category: 'Creator Area', icon: 'fa-solid fa-box' },
		{ name: 'Package Subscribers', url: prefix + 'package-subscribers.php', category: 'Creator Area', icon: 'fa-solid fa-users' },
		{ name: 'Financial Transactions', url: prefix + 'transactions.php', category: 'Creator Area', icon: 'fa-solid fa-receipt' },
		{ name: 'Withdrawals & Payouts', url: prefix + 'withdrawals.php', category: 'Creator Area', icon: 'fa-solid fa-money-bill-wave' },
		{ name: 'Help & Support', url: prefix + 'support.php', category: 'Creator Area', icon: 'fa-solid fa-headset' },
		{ name: 'My Profile', url: prefix + 'profile.php', category: 'Creator Area', icon: 'fa-solid fa-user' },
		{ name: 'Account Settings', url: prefix + 'settings.php', category: 'Creator Area', icon: 'fa-solid fa-gear' },

		// Admin pages
		{ name: 'Admin Overview', url: adminPrefix + 'index.php', category: 'Administration', icon: 'fa-solid fa-gauge-high' },
		{ name: 'User Management', url: adminPrefix + 'users.php', category: 'Administration', icon: 'fa-solid fa-users-gear' },
		{ name: 'System Videos', url: adminPrefix + 'videos.php', category: 'Administration', icon: 'fa-solid fa-video' },
		{ name: 'Global Transactions', url: adminPrefix + 'transactions.php', category: 'Administration', icon: 'fa-solid fa-money-check-dollar' },
		{ name: 'Payout Requests', url: adminPrefix + 'withdrawals.php', category: 'Administration', icon: 'fa-solid fa-money-bill-transfer' },
		{ name: 'System Announcements', url: adminPrefix + 'announcements.php', category: 'Administration', icon: 'fa-solid fa-bullhorn' },
		{ name: 'Support Tickets', url: adminPrefix + 'tickets.php', category: 'Administration', icon: 'fa-solid fa-headset' },
		{ name: 'Platform Settings', url: adminPrefix + 'settings.php', category: 'Administration', icon: 'fa-solid fa-gears' }
	];

	// Recently Searched logic
	const renderRecentSearched = () => {
		let recent = JSON.parse(localStorage.getItem('recently_searched') || '[]');
		let recentContainer = $('#recentlyResults');
		
		recentContainer.find('ul').remove();
		recentContainer.find('.no-recent').remove();
		
		if (recent.length > 0) {
			let ul = $("<ul class='list-inline search-list'></ul>");
			recent.forEach(item => {
				ul.append(
					`<li>
						<a class="search-item" href="${item.url}">
							<i class="${item.icon}"></i> <span>${item.name}</span>
						</a>
					</li>`
				);
			});
			recentContainer.append(ul);
		} else {
			recentContainer.append('<div class="no-recent text-muted text-2xs ps-1 pb-2">No recent searches</div>');
		}
	};

	// Initial render
	renderRecentSearched();

	// Save to recently searched on click
	$(document).on("click", ".search-item", function() {
		let name = $(this).find('span').text();
		let url = $(this).attr('href');
		
		// Find category from list
		let found = listItems.find(item => item.url === url || item.name === name);
		if (!found) {
			let iconClass = $(this).find('i').attr('class') || 'fa-solid fa-video';
			found = {
				name: name,
				url: url,
				category: 'Matched Videos',
				icon: iconClass
			};
		}
		if (found) {
			let recent = JSON.parse(localStorage.getItem('recently_searched') || '[]');
			recent = recent.filter(item => item.url !== found.url);
			recent.unshift(found);
			recent = recent.slice(0, 5);
			localStorage.setItem('recently_searched', JSON.stringify(recent));
		}
	});

	// Rerender when modal is shown or inputs are cleared
	$('#searchResultsModal').on('shown.bs.modal', function () {
		renderRecentSearched();
		$("#searchInput").val('').focus();
		$("#searchContainer").empty().hide();
		$('#recentlyResults').show();
	});

	// Keydown handling for Enter navigation
	$("#searchInput").on("keydown", function(e) {
		if (e.key === "Enter") {
			e.preventDefault();
			let firstLink = $("#searchContainer").find(".search-item").first();
			if (firstLink.length > 0) {
				firstLink[0].click();
			}
		}
	});

	let searchTimeout;

	// Search functionality
	$("#searchInput").on("keyup", function() {
		let query = $(this).val().toLowerCase();
		let searchContainer = $("#searchContainer");
		
		clearTimeout(searchTimeout);
		
		if (query.length === 0) {
			searchContainer.empty().hide();
			$('#recentlyResults').show();
			renderRecentSearched();
			return;
		}

		searchTimeout = setTimeout(async () => {
			$('#recentlyResults').hide();
			searchContainer.empty().hide();

			// 1. Filter local site pages
			let matchedPages = listItems.filter(item =>
				item.name.toLowerCase().includes(query) ||
				item.category.toLowerCase().includes(query)
			);

			// 2. Fetch matching videos from backend
			let matchedVideos = [];
			try {
				let res = await fetch(`${prefix}api/search.php?q=${encodeURIComponent(query)}`);
				matchedVideos = await res.json();
			} catch (e) {
				console.error("Video search error:", e);
			}

			// Render results
			if (matchedPages.length > 0 || (matchedVideos && matchedVideos.length > 0)) {
				// Render Pages Grouped by Category
				if (matchedPages.length > 0) {
					let grouped = {};
					matchedPages.forEach(item => {
						if (!grouped[item.category]) grouped[item.category] = [];
						grouped[item.category].push(item);
					});

					for (let cat in grouped) {
						searchContainer.append(
							`<span class="text-uppercase text-2xs fw-semibold text-muted d-block mb-2 mt-3">${cat}</span>`
						);
						let ul = $("<ul class='list-inline search-list'></ul>");
						grouped[cat].forEach(item => {
							ul.append(
								`<li>
									<a class="search-item" href="${item.url}">
										<i class="${item.icon}"></i> <span>${item.name}</span>
									</a>
								</li>`
							);
						});
						searchContainer.append(ul);
					}
				}

				// Render Videos Grouped under "Videos" Category
				if (matchedVideos && matchedVideos.length > 0) {
					searchContainer.append(
						`<span class="text-uppercase text-2xs fw-semibold text-muted d-block mb-2 mt-3">Matched Videos</span>`
					);
					let ul = $("<ul class='list-inline search-list'></ul>");
					matchedVideos.forEach(vid => {
						if (isAdmin) {
							ul.append(
								`<li>
									<a class="search-item" href="${prefix}video_view.php?v=${vid.slug}" target="_blank">
										<i class="fa-solid fa-play text-success"></i> <span>Watch: ${vid.title}</span>
									</a>
								</li>`
							);
						} else {
							ul.append(
								`<li>
									<a class="search-item" href="${prefix}video-edit.php?id=${vid.id}">
										<i class="fa-solid fa-pen-to-square text-primary"></i> <span>Edit: ${vid.title}</span>
									</a>
								</li>
								<li>
									<a class="search-item" href="${prefix}video_view.php?v=${vid.slug}" target="_blank">
										<i class="fa-solid fa-play text-success"></i> <span>Watch: ${vid.title}</span>
									</a>
								</li>`
							);
						}
					});
					searchContainer.append(ul);
				}

				searchContainer.show();
			} else {
				searchContainer.append(`
					<div class="text-center pb-5 pt-4">
						<div class="avatar avatar-lg bg-danger-subtle shadow-secondary rounded-circle text-danger mb-3 m-auto">
							<i class="fi fi-rr-assessment"></i>
						</div>
						<h5 class="mb-1">No result found</h5>
						<div class="text-muted">Please try again with a different query</div>
					</div>
				`);
				searchContainer.show();
			}
		}, 250); // 250ms debounce
	});
};

const currentYear = () => {
    const elements = document.querySelectorAll('.currentYear');
    const currentYear = new Date().getFullYear();

    elements.forEach(element => {
        element.textContent = currentYear;
    });
};

const setElementHeight = () => {
    const footer = document.querySelector('.footer-wrapper');
	if (footer) {
		const footerHeight = footer ? footer.offsetHeight : 0;
		document.documentElement.style.setProperty('--footer-height', `${footerHeight}px`);
	}
	
	const chatBox = document.querySelector('.chat-wrapper');
	if (chatBox) {
		const chatHeight = chatBox.offsetHeight;
		document.documentElement.style.setProperty('--chat-height', `${chatHeight}px`);
	}
	
};

const initSelectPicker = () => {
	
	document.querySelectorAll('.select-status').forEach(dropdown => {
		const toggleButton = dropdown.querySelector('.dropdown-toggle');
		const items = dropdown.querySelectorAll('.dropdown-item');

		const updateButtonClassAndText = (text, selectedClass) => {
			// Remove btn-* except btn-sm, btn-lg
			toggleButton.classList.forEach(cls => {
				if (/^btn-/.test(cls) && !['btn-sm', 'btn-lg'].includes(cls)) {
					toggleButton.classList.remove(cls);
				}
			});

			if (selectedClass) {
				toggleButton.classList.add(...selectedClass.split(' '));
			}

			toggleButton.textContent = text;
		};

		// Handle default selection on page load
		const selectedItem = dropdown.querySelector('.dropdown-item[data-selected="true"]');
		if (selectedItem) {
			const defaultText = selectedItem.textContent.trim();
			const defaultClass = selectedItem.getAttribute('data-class');
			updateButtonClassAndText(defaultText, defaultClass);
		}

		// Handle selection on click
		items.forEach(item => {
			item.addEventListener('click', (e) => {
				e.preventDefault();
				items.forEach(i => i.removeAttribute('data-selected'));
				item.setAttribute('data-selected', 'true');

				const selectedText = item.textContent.trim();
				const selectedClass = item.getAttribute('data-class');
				updateButtonClassAndText(selectedText, selectedClass);
			});
		});
	});
};

function initSectionCheckboxSync() {
    document.querySelectorAll('.data-row-checkbox').forEach(function(section) {
        const masterCheckbox = section.querySelector('[data-row-checkbox]');
        const checkboxes = section.querySelectorAll('[data-checkbox]');

        if (!masterCheckbox || checkboxes.length === 0) return;

        masterCheckbox.addEventListener('change', function() {
            const checked = this.checked;
            checkboxes.forEach(function(cb) {
                cb.checked = checked;
            });
        });

        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                const allChecked = Array.from(checkboxes).every(c => c.checked);
                masterCheckbox.checked = allChecked;
            });
        });
    });
}

function initTooltips() {
	const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
	const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

function initPopover() {
	const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
	const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
		return new bootstrap.Popover(popoverTriggerEl)
	})
}

function initSidebarMenu() {
	
	jQuery('.app-navbar .menubar > li.menu-arrow > a').next('.menu-inner').slideUp();
	jQuery('.app-navbar .menu-inner > li > a').next('.menu-inner').slideUp();
	
	jQuery('.app-navbar .menubar > li.menu-arrow > a, .app-navbar .menu-inner > li > a').unbind().on('click', function(e){
		if(jQuery(this).hasClass('open')){
			jQuery(this).removeClass('open');
			jQuery(this).parent('li').children('.menu-inner').slideUp();
		}else{
			if (!e.ctrlKey) {
				jQuery(this).addClass('open');
			}
			if(jQuery(this).parent('li').children('.menu-inner').length > 0){
				
				e.preventDefault();
				jQuery(this).next('.menu-inner').slideDown();
				jQuery(this).parent('li').siblings('li').find('a:first').removeClass('open');
				jQuery(this).parent('li').siblings('li').children('.menu-inner').slideUp();
			}else{
				jQuery(this).next('.menu-inner').slideUp();
			}
		}
	});
	
	for (var nk = window.location,
		o = $(".app-navbar .menubar a").filter(function(){
		return this.href == nk;
	}).addClass("active").parent().addClass("active").parent().show().siblings('a').addClass("active open").parent().parent().show().siblings('a').addClass("open");;){
		if (!o.is("li")) {
			break;
		}
		o = o.parent().slideDown().parent('li').children('a').addClass("active");
	}
}

function initCheckable() {
    document.querySelectorAll('.checkable-wrapper').forEach(function(wrapper) {
        const checkAll = wrapper.querySelector('.checkable-check-all');
        const checkboxes = wrapper.querySelectorAll('.checkable-check-input');

        // Initialize checked state on load
        checkboxes.forEach(function(checkbox) {
            const item = checkbox.closest('.checkable-item');
            if (checkbox.checked && item) {
                item.classList.add('is-checked');
            }
        });

        // Handle "Select All"
        if (checkAll) {
            checkAll.addEventListener('change', function () {
                const isChecked = this.checked;
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = isChecked;
                    const item = checkbox.closest('.checkable-item');
                    if (item) {
                        item.classList.toggle('is-checked', isChecked);
                    }
                });
            });
        }

        // Handle individual checkbox toggle
        wrapper.addEventListener('change', function (e) {
            if (e.target.matches('.checkable-check-input')) {
                const item = e.target.closest('.checkable-item');
                if (item) {
                    item.classList.toggle('is-checked', e.target.checked);
                }

                // Update "Select All" state
                const allChecked = wrapper.querySelectorAll('.checkable-check-input:not(:checked)').length === 0;
                if (checkAll) {
                    checkAll.checked = allChecked;
                }
            }
        });
    });
}

function initEmailSidebarToggle() {
    const toggler = document.querySelector('.mail-sidebar-toggler');
    const sidebar = document.querySelector('.mail-sidebar');
    const overlay = document.querySelector('.sidebar-mobile-overlay');

    if (toggler && sidebar && overlay) {
        toggler.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show', sidebar.classList.contains('open'));
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
    }
}

function initChatSidebarToggle() {
    const toggler = document.querySelector('.chat-sidebar-toggler');
    const sidebar = document.querySelector('.chat-sidebar');
    const overlay = document.querySelector('.sidebar-mobile-overlay');
    const btnClose = document.querySelector('.btn-close');

    if (toggler && sidebar && overlay) {
        toggler.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show', sidebar.classList.contains('open'));
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
		
		btnClose.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
    }
}

function initBookmarks() {
    document.addEventListener('click', (e) => {
        const bookmark = e.target.closest('.mail-item-bookmark');
        if (bookmark) {
            bookmark.classList.toggle('active');
        }
    });
}

const ThemeSwitcher = () => {
	'use strict';

	// Cookie Utility: Get a cookie value by name
	const getCookie = (name) => {
		const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
		return match ? match[2] : null;
	};

	// Cookie Utility: Set a cookie with optional expiry
	const setCookie = (name, value, days = 365) => {
		const expires = new Date(Date.now() + days * 864e5).toUTCString();
		document.cookie = `${name}=${value}; expires=${expires}; path=/`;
	};

	// Get stored theme from cookie (if available)
	const getStoredTheme = () => getCookie('theme');

	// Save selected theme to cookie
	const setStoredTheme = (theme) => setCookie('theme', theme);

	// Determine preferred theme: cookie > system preference
	const getPreferredTheme = () => {
		const storedTheme = getStoredTheme();
		if (storedTheme) return storedTheme;
		return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
	};

	// Apply the theme to <html data-bs-theme="...">
	const setTheme = (theme) => {
		const appliedTheme = (theme === 'auto')
		  ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
		  : theme;

		document.documentElement.setAttribute('data-bs-theme', appliedTheme);
	};

	// Update active state and icon based on selected theme
	const showActiveTheme = (theme, focus = false) => {
		const themeSwitcher = document.querySelector('#ld-theme');              // button
		const themeSwitcherText = document.querySelector('#ld-theme-text');    // button label
		const activeThemeIcon = document.querySelector('.theme-icon-active');  // <i> icon
		const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`);

		if (!themeSwitcher || !btnToActive || !activeThemeIcon) return;

		// Remove existing active styles
		document.querySelectorAll('[data-bs-theme-value]').forEach(el => {
			el.classList.remove('active');
			el.setAttribute('aria-pressed', 'false');
		});

		// Add active styles to the selected one
		btnToActive.classList.add('active');
		btnToActive.setAttribute('aria-pressed', 'true');

		// Replace icon class
		const selectedIcon = btnToActive.querySelector('i');
		if (selectedIcon) {
			activeThemeIcon.className = selectedIcon.className + ' theme-icon-active';
		}

		// Update button ARIA label
		const label = `${themeSwitcherText?.textContent || 'Theme'} (${btnToActive.dataset.bsThemeValue})`;
		themeSwitcher.setAttribute('aria-label', label);

		if (focus) themeSwitcher.focus();
	};

	// Auto-change if system theme changes (for "auto" mode)
	window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
		if (getStoredTheme() === 'auto') {
			setTheme('auto');
		}
	});

	// On page ready, apply theme and set up click events
	window.addEventListener('DOMContentLoaded', () => {
		const preferredTheme = getPreferredTheme();         // "light" / "dark" / "auto"
		setTheme(preferredTheme);                           // apply theme
		showActiveTheme(preferredTheme);                    // update icon/active

		// Theme buttons click handler
		document.querySelectorAll('[data-bs-theme-value]').forEach(toggle => {
			toggle.addEventListener('click', () => {
			const theme = toggle.getAttribute('data-bs-theme-value');
				setStoredTheme(theme);         // save in cookie
				setTheme(theme);              // apply
				showActiveTheme(theme, true); // update UI
			});
		});
	});
};

function initSidebarPanel() {
	document.addEventListener('click', function(e) {
		const toggler = e.target.closest('.sidebar-panel-toggler');
		if (toggler) {
			const panel = document.querySelector('.app-sidebar-panel');
			if (panel) {
				panel.classList.toggle('show');
			}
		}

		const closeBtn = e.target.closest('.sidebar-close');
		if (closeBtn) {
			document.querySelectorAll('.app-sidebar-panel').forEach(panel => {
				panel.classList.remove('show');
			});
		}
	});
}

document.addEventListener("DOMContentLoaded", () => {
    Waves.init();
	initAppToggler();
	saerchList();
	setElementHeight();
	currentYear();
	initSectionCheckboxSync();
	initSelectPicker();
	initTooltips();
	initPopover();
	initCheckable();
	initSidebarMenu();
	initEmailSidebarToggle();
	initChatSidebarToggle();
	initBookmarks();
	ThemeSwitcher();
	initSidebarPanel();
});


$(document).ready(function () {
	$('.dataTable').each(function() {
		const dtInstance = $(this).DataTable();
		dtInstance.on('draw.dt', function() {
			initSelectPicker();
			initSectionCheckboxSync();
		});
	});
});