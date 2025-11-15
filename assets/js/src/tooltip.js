/**
 * WP ULike Tooltip - Lightweight modern tooltip solution
 * Pure vanilla JavaScript, no dependencies
 */
(function (window, document, undefined) {
  "use strict";

  // Store tooltip instances
  const tooltipInstances = new WeakMap();
  const tooltipInstancesById = {}; // For easier access by ID
  const activeTooltips = [];

  // Default options
  const defaults = {
    id: Date.now(),
    title: "",
    trigger: "hover",
    position: "top",
    class: "",
    theme: "light",
    size: "small",
    singleton: true,
    close_on_outside_click: true,
  };

  // Constants
  const SPACING = 13;
  const SHOW_DELAY = 100;
  const HIDE_DELAY = 100;

  // Helper: Get element position
  const getOffset = (element) => {
    const rect = element.getBoundingClientRect();
    return {
      top: rect.top + window.pageYOffset,
      left: rect.left + window.pageXOffset,
      width: rect.width,
      height: rect.height,
    };
  };

  // Helper: Create loading spinner HTML
  const createSpinnerHTML = () => {
    return '<div class="ulf-loading-spinner"><div class="ulf-spinner-circle"></div><div class="ulf-spinner-circle"></div><div class="ulf-spinner-circle"></div></div>';
  };

  // Helper: Create tooltip element
  const createTooltipElement = (content, className, isLoading) => {
    const tooltip = document.createElement("div");
    tooltip.className = `ulf-tooltip ${className || ""}`;
    tooltip.setAttribute("role", "tooltip");
    
    // Ensure content is never empty to prevent glitch
    const contentHTML = isLoading ? createSpinnerHTML() : (content || "&nbsp;");
    tooltip.innerHTML = `<div class="ulf-arrow"></div><div class="ulf-content">${contentHTML}</div>`;
    return tooltip;
  };

  // Helper: Position tooltip
  const positionTooltip = (tooltip, reference, placement) => {
    const refRect = getOffset(reference);
    const tooltipRect = tooltip.getBoundingClientRect();
    const arrow = tooltip.querySelector(".ulf-arrow");
    const viewport = {
      width: window.innerWidth,
      height: window.innerHeight,
    };

    const positions = {
      top: {
        top: refRect.top - tooltipRect.height - SPACING,
        left: refRect.left + refRect.width / 2 - tooltipRect.width / 2,
        arrow: "bottom",
      },
      bottom: {
        top: refRect.top + refRect.height + SPACING,
        left: refRect.left + refRect.width / 2 - tooltipRect.width / 2,
        arrow: "top",
      },
      left: {
        top: refRect.top + refRect.height / 2 - tooltipRect.height / 2,
        left: refRect.left - tooltipRect.width - SPACING,
        arrow: "right",
      },
      right: {
        top: refRect.top + refRect.height / 2 - tooltipRect.height / 2,
        left: refRect.left + refRect.width + SPACING,
        arrow: "left",
      },
    };

    const pos = positions[placement] || positions.top;

    // Keep tooltip in viewport
    if (pos.left < 10) pos.left = 10;
    if (pos.left + tooltipRect.width > viewport.width - 10) {
      pos.left = viewport.width - tooltipRect.width - 10;
    }
    if (pos.top < 10) pos.top = 10;
    if (pos.top + tooltipRect.height > viewport.height - 10) {
      pos.top = viewport.height - tooltipRect.height - 10;
    }

    tooltip.style.left = `${pos.left}px`;
    tooltip.style.top = `${pos.top}px`;

    if (arrow) {
      arrow.className = `ulf-arrow ulf-arrow-${pos.arrow}`;
    }
    
    // Mark as positioned to show arrow
    tooltip.setAttribute("data-positioned", "true");
  };

  // Safe Array.from polyfill for older browsers (if needed)
  const arrayFrom = (arrayLike) => {
    if (Array.from) {
      return Array.from(arrayLike);
    }
    // Fallback for very old browsers
    return Array.prototype.slice.call(arrayLike);
  };

  // Main plugin
  function WordpressUlikeTooltipPlugin(element, options) {
    // Handle multiple elements
    if (element.length !== undefined && element.length > 1) {
      arrayFrom(element).forEach((el) => {
        new WordpressUlikeTooltipPlugin(el, options);
      });
      return element;
    }

    if (!element) return false;

    // Merge options
    options = Object.assign({}, defaults, options || {});

    // Get title from attribute
    if (!options.title) {
      const titleAttr = element.getAttribute("title");
      if (titleAttr) {
        options.title = titleAttr;
        element.removeAttribute("title");
      }
    }

    // Destroy existing
    const existing = tooltipInstances.get(element);
    if (existing) {
      existing.destroy();
    }

    let tooltip = null;
    let showTimeout = null;
    let hideTimeout = null;
    let isLoading = false;

    const show = (showLoading) => {
      // If showing loading, always show even if tooltip exists
      if (tooltip && tooltip.parentNode && !showLoading) return;

      // Hide others if singleton
      if (options.singleton !== false) {
        activeTooltips.forEach((t) => {
          if (t && t.hide && t.element !== element) t.hide();
        });
      }

      // Create or update tooltip
      let className = `ulf-${options.theme || "light"}-theme ulf-${options.size || "small"}`;
      if (options.class) className += ` ${options.class}`;

      // Get reference element for positioning
      let reference = element;
      if (options.child) {
        const childEl = element.querySelector(options.child);
        if (childEl) reference = childEl;
      }

      if (!tooltip || !tooltip.parentNode || showLoading) {
        if (tooltip && tooltip.parentNode) {
          tooltip.remove();
        }
        isLoading = showLoading === true;
        tooltip = createTooltipElement(
          options.title || "",
          className,
          isLoading
        );
        document.body.appendChild(tooltip);
        // Position immediately after appending (arrow is hidden via CSS until positioned)
        positionTooltip(tooltip, reference, options.position || "top");
      } else {
        // Update existing tooltip content
        const contentEl = tooltip.querySelector(".ulf-content");
        if (contentEl) {
          isLoading = showLoading === true;
          contentEl.innerHTML = isLoading
            ? createSpinnerHTML()
            : (options.title || "&nbsp;");
        }
        // Reposition after content update
        positionTooltip(tooltip, reference, options.position || "top");
      }

      // Add hover handlers to tooltip if trigger is hover
      if (options.trigger === "hover" || !options.trigger) {
        tooltip.addEventListener("mouseenter", () => {
          clearTimeout(hideTimeout);
        });
        tooltip.addEventListener("mouseleave", handleHide);
      }

      // Add to active
      const isInActive = activeTooltips.some((t) => t.element === element);
      if (!isInActive) {
        activeTooltips.push({ element, hide });
      }

      // Set ID for accessibility
      const id = `ulp-dom-${options.id}`;
      tooltip.setAttribute("id", id);
      element.setAttribute("aria-describedby", id);

      // Trigger event
      const event = new CustomEvent("ulf-show", {
        bubbles: true,
        detail: { tooltip },
      });
      element.dispatchEvent(event);
    };

    const updateContent = (content) => {
      if (!tooltip || !tooltip.parentNode) return;
      const contentEl = tooltip.querySelector(".ulf-content");
      if (contentEl) {
        contentEl.innerHTML = content || "&nbsp;";
        isLoading = false;
        // Reposition after content update
        let reference = element;
        if (options.child) {
          const childEl = element.querySelector(options.child);
          if (childEl) reference = childEl;
        }
        positionTooltip(tooltip, reference, options.position || "top");
      }
    };

    const hide = () => {
      if (!tooltip || !tooltip.parentNode) return;

      tooltip.remove();
      tooltip = null;
      isLoading = false;

      // Remove from active
      const index = activeTooltips.findIndex((t) => t.element === element);
      if (index > -1) {
        activeTooltips.splice(index, 1);
      }

      // Remove aria
      element.removeAttribute("aria-describedby");

      // Trigger event
      const event = new CustomEvent("ulf-hide", { bubbles: true });
      element.dispatchEvent(event);
    };

    // Event handlers
    const handleShow = () => {
      clearTimeout(hideTimeout);
      // Show immediately if loading is requested, otherwise with delay
      if (options.showLoadingImmediately) {
        show(true);
      } else {
        showTimeout = setTimeout(show, SHOW_DELAY);
      }
    };

    const handleHide = () => {
      clearTimeout(showTimeout);
      hideTimeout = setTimeout(hide, HIDE_DELAY);
    };

    // Setup events based on trigger
    if (options.trigger === "hover" || !options.trigger) {
      element.addEventListener("mouseenter", handleShow);
      element.addEventListener("mouseleave", handleHide);
    } else if (options.trigger === "click") {
      element.addEventListener("click", (e) => {
        e.preventDefault();
        if (tooltip && tooltip.parentNode) {
          hide();
        } else {
          show();
        }
      });
    }

    // Click outside handler
    if (options.close_on_outside_click !== false) {
      const outsideHandler = (e) => {
        if (
          tooltip &&
          tooltip.parentNode &&
          !tooltip.contains(e.target) &&
          !element.contains(e.target)
        ) {
          hide();
        }
      };
      document.addEventListener("mousedown", outsideHandler);
    }

    // Store instance
    const instance = {
      show,
      showLoading: () => show(true),
      updateContent,
      hide,
      destroy: () => {
        hide();
        element.removeEventListener("mouseenter", handleShow);
        element.removeEventListener("mouseleave", handleHide);
        tooltipInstances.delete(element);
        if (options.id) {
          delete tooltipInstancesById[options.id];
        }
      },
    };

    tooltipInstances.set(element, instance);
    if (options.id) {
      tooltipInstancesById[options.id] = instance;
    }

    // Listen for WP ULike events
    const likersHandler = (e) => {
      const detail = e.detail || {};
      if (detail.likersTemplate === "popover") {
        if (detail.template && detail.template.length) {
          options.title = detail.template;
          updateContent(detail.template);
        } else {
          instance.destroy();
        }
      }
    };
    document.addEventListener("WordpressUlikeLikersMarkupUpdated", likersHandler);
    instance.likersHandler = likersHandler;

    return element;
  }

  // Expose
  window.WordpressUlikeTooltipPlugin = WordpressUlikeTooltipPlugin;
  window.WordpressUlikeTooltip = {
    visible: activeTooltips,
    bodyClickInitialized: false,
    defaults,
    getInstanceById: (id) => tooltipInstancesById[id],
  };
})(window, document);
