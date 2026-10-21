document.addEventListener("DOMContentLoaded", () => {
  // ==================== ACTIVE NAVIGATION ====================
  // Set active class based on current URL
  function setActiveNavLink() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll(
      ".nav-link, .sidebar-link, .bottom-nav-link",
    );

    navLinks.forEach((link) => {
      const href = link.getAttribute("href");
      if (href && href !== "#") {
        // Check if current path matches the link
        if (
          currentPath === href ||
          (href !== "/" && currentPath.startsWith(href)) ||
          (href === "/dashboard" && currentPath === "/")
        ) {
          link.classList.add("active");
        } else {
          link.classList.remove("active");
        }
      }
    });
  }

  // ==================== MOBILE BOTTOM NAV SCROLL BEHAVIOR ====================
  (function () {
    const bottomNav = document.getElementById("mobileBottomNav");
    if (!bottomNav) return;

    const isMobile = () => window.innerWidth <= 768;
    const lastElementScrollTops = new WeakMap();
    let lastWindowScrollTop = window.scrollY;

    const updateVisibility = (scrollTop, lastScrollTop) => {
      if (!isMobile()) return;

      if (scrollTop > lastScrollTop && scrollTop > 50) {
        bottomNav.classList.add("hide");
      } else if (scrollTop < lastScrollTop) {
        bottomNav.classList.remove("hide");
      }
      if (scrollTop <= 10) bottomNav.classList.remove("hide");
    };

    window.addEventListener(
      "scroll",
      () => {
        const scrollTop = window.scrollY;
        updateVisibility(scrollTop, lastWindowScrollTop);
        lastWindowScrollTop = scrollTop;
      },
      { passive: true },
    );

    document.addEventListener(
      "scroll",
      (event) => {
        const scroller = event.target;
        if (
          !(scroller instanceof Element) ||
          scroller === document.documentElement ||
          scroller === document.body ||
          scroller.scrollHeight <= scroller.clientHeight
        ) {
          return;
        }

        const scrollTop = scroller.scrollTop;
        const lastScrollTop = lastElementScrollTops.get(scroller) ?? 0;
        updateVisibility(scrollTop, lastScrollTop);
        lastElementScrollTops.set(scroller, scrollTop);
      },
      { capture: true, passive: true },
    );

    window.addEventListener("resize", () => {
      lastWindowScrollTop = window.scrollY;
      if (!isMobile()) bottomNav.classList.remove("hide");
      else if (lastWindowScrollTop <= 10) bottomNav.classList.remove("hide");
    });
    if (isMobile() && lastWindowScrollTop <= 10) {
      bottomNav.classList.remove("hide");
    }
  })();

  // ==================== MOBILE MENU OVERLAY ====================
  const menuToggle = document.getElementById("mobileMenuToggle");
  const overlay = document.getElementById("mobileOverlay");
  const closeDrawer = document.getElementById("closeDrawerBtn");

  menuToggle?.addEventListener("click", () => {
    overlay?.classList.add("active");
    document.body.style.overflow = "hidden";
  });

  closeDrawer?.addEventListener("click", () => {
    overlay?.classList.remove("active");
    document.body.style.overflow = "";
  });

  overlay?.addEventListener("click", (e) => {
    if (e.target === overlay) {
      overlay.classList.remove("active");
      document.body.style.overflow = "";
    }
  });

  // ==================== SIDEBAR TOGGLE (Desktop) ====================
  const sidebarToggle = document.getElementById("sidebarToggle");
  const sidebar = document.querySelector(
    ".employer-sidebar, .dashboard-sidebar, .sidebar",
  );

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener("click", () => {
      sidebar.classList.toggle("collapsed");
      // Store preference in localStorage
      const isCollapsed = sidebar.classList.contains("collapsed");
      localStorage.setItem("sidebarCollapsed", isCollapsed);
    });

    // Restore sidebar state from localStorage
    const savedState = localStorage.getItem("sidebarCollapsed");
    if (savedState === "true") {
      sidebar.classList.add("collapsed");
    }
  }

  // ==================== DROPDOWN CLOSE ON CLICK OUTSIDE ====================
  document.addEventListener("click", function (e) {
    // Close all dropdowns when clicking outside
    const dropdowns = document.querySelectorAll(".dropdown-menu");
    dropdowns.forEach((dropdown) => {
      if (!dropdown.contains(e.target) && !e.target.closest(".menu-dots")) {
        dropdown.classList.remove("active");
      }
    });
  });

  // ==================== INITIALIZE ====================
  setActiveNavLink();

  // Re-run on popstate (back/forward navigation)
  window.addEventListener("popstate", setActiveNavLink);

  // For Turbo/Stimulus or HTMX navigation
  document.addEventListener("htmx:afterSwap", setActiveNavLink);
  document.addEventListener("turbo:load", setActiveNavLink);
});

// ==================== HELPER FUNCTIONS FOR LINKS ====================
// Make sure your HTML links have the correct classes
// Example:
// <a href="/employer/dashboard" class="nav-link active">Dashboard</a>
// <a href="/employer/jobs" class="nav-link">Jobs</a>
// <a href="/employer/applications" class="nav-link">Applications</a>

// If you're using dynamic content, you can also manually set active:
function setActiveNav(activePath) {
  document
    .querySelectorAll(".nav-link, .sidebar-link, .bottom-nav-link")
    .forEach((link) => {
      const href = link.getAttribute("href");
      if (
        href === activePath ||
        (href !== "/" && activePath.startsWith(href))
      ) {
        link.classList.add("active");
      } else {
        link.classList.remove("active");
      }
    });
}
