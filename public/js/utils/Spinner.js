// Loader behavior moved from main.js to Spinner.js

window.Spinner = (function () {
  let loaderShown = false;
  let loaderHidden = false;
  let loaderCreated = false;

  function createLoaderOverlay() {
    if (loaderCreated || loaderHidden) {
      return;
    }

    if (document.getElementById("jobconnect-loader")) {
      loaderCreated = true;
      return;
    }

    loaderCreated = true;
    loaderShown = true;

    const overlay = document.createElement("div");
    overlay.id = "jobconnect-loader";
    overlay.className = "jobconnect-loader-overlay";

    const container = document.createElement("div");
    container.className = "jobconnect-loader-container";

    const outerCircle = document.createElement("div");
    outerCircle.className = "jobconnect-outer-circle";

    const innerCircle = document.createElement("div");
    innerCircle.className = "jobconnect-inner-circle";

    const middleCircle = document.createElement("div");
    middleCircle.className = "jobconnect-middle-circle";

    const contentWrapper = document.createElement("div");
    contentWrapper.className = "jobconnect-content-wrapper";

    const logo = document.createElement("div");
    logo.className = "jobconnect-loader-logo";
    logo.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M2 17L12 22L22 17" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M2 12L12 17L22 12" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <circle cx="12" cy="12" r="2" fill="white" stroke="white" stroke-width="1.5"/>
            </svg>
        `;

    const brand = document.createElement("div");
    brand.className = "jobconnect-loader-brand";
    brand.textContent = "JobConnect";

    const tagline = document.createElement("div");
    tagline.className = "jobconnect-loader-tagline";
    tagline.textContent = "Connecting Talent with Opportunity";

    const progress = document.createElement("div");
    progress.className = "jobconnect-loader-progress";
    progress.innerHTML = `
            <div class="jobconnect-progress-dot"></div>
            <div class="jobconnect-progress-dot"></div>
            <div class="jobconnect-progress-dot"></div>
            <div class="jobconnect-progress-dot"></div>
        `;

    contentWrapper.appendChild(logo);
    contentWrapper.appendChild(brand);
    contentWrapper.appendChild(tagline);
    contentWrapper.appendChild(progress);

    container.appendChild(outerCircle);
    container.appendChild(innerCircle);
    container.appendChild(middleCircle);
    container.appendChild(contentWrapper);

    overlay.appendChild(container);
    document.body.appendChild(overlay);

    document.body.style.overflow = "hidden";
  }

  function fadeInPageContent() {
    const existingContent = document.querySelector(".page-content");
    if (
      existingContent &&
      existingContent.classList.contains("page-content-visible")
    ) {
      return;
    }

    let contentElement =
      document.querySelector("main") ||
      document.querySelector(".main-content") ||
      document.querySelector("#app") ||
      document.querySelector(".page-content") ||
      document.body;

    if (contentElement === document.body) {
      let existingWrapper = document.querySelector(".page-content");
      if (existingWrapper) {
        contentElement = existingWrapper;
      } else {
        const wrapper = document.createElement("div");
        wrapper.className = "page-content";

        try {
          while (document.body.firstChild) {
            const child = document.body.firstChild;
            if (child.id === "jobconnect-loader") {
              break;
            }
            wrapper.appendChild(child);
          }
        } catch (e) {
          console.warn("Error wrapping body content:", e);
          document.body.classList.add("page-content");
          contentElement = document.body;
        }

        if (wrapper.children.length > 0 || wrapper.childNodes.length > 0) {
          document.body.appendChild(wrapper);
          contentElement = wrapper;
        } else {
          document.body.classList.add("page-content");
          contentElement = document.body;
        }
      }
    }

    if (contentElement && !contentElement.classList.contains("page-content")) {
      contentElement.classList.add("page-content");
    }

    if (contentElement) {
      requestAnimationFrame(() => {
        contentElement.classList.add("page-content-visible");
      });
    }
  }

  function hideLoader() {
    if (loaderHidden) {
      return;
    }

    const overlay = document.getElementById("jobconnect-loader");
    if (overlay) {
      loaderHidden = true;
      overlay.classList.add("jobconnect-hide");

      setTimeout(() => {
        if (overlay && overlay.parentNode) {
          overlay.parentNode.removeChild(overlay);
          document.body.style.overflow = "";
        }
      }, 600);
    }
  }

  function handlePageReady() {
    // After wrapping, find the toast container and move it back to body
    const toastContainer = document.querySelector(".toast-container");
    if (toastContainer) {
      document.body.appendChild(toastContainer);
    }
    fadeInPageContent();
    setTimeout(() => {
      hideLoader();
    }, 100);
  }

  if (document.readyState === "loading") {
    createLoaderOverlay();

    document.addEventListener("DOMContentLoaded", function () {
      handlePageReady();
    });

    window.addEventListener("load", function () {
      if (!loaderHidden) {
        handlePageReady();
      }
    });
  } else {
    loaderHidden = true;
    fadeInPageContent();

    const existingLoader = document.getElementById("jobconnect-loader");
    if (existingLoader) {
      existingLoader.remove();
    }

    document.body.style.overflow = "";
  }

  // Expose spinner methods globally
  return {
    show: createLoaderOverlay,
    remove: hideLoader,
  };
})();
