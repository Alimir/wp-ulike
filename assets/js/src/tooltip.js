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

  // Helper: Get element position (viewport-relative for fixed positioning)
  const getOffset = (element) => {
    const rect = element.getBoundingClientRect();
    return {
      top: rect.top,
      left: rect.left,
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
    // Force a reflow to ensure tooltip has proper dimensions
    void tooltip.offsetHeight;

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

    // Use fixed positioning (viewport-relative) for consistent positioning
    tooltip.style.position = "fixed";
    tooltip.style.left = `${pos.left}px`;
    tooltip.style.top = `${pos.top}px`;

    if (arrow) {
      arrow.className = `ulf-arrow ulf-arrow-${pos.arrow}`;
    }

    // Mark as positioned to show arrow
    tooltip.setAttribute("data-positioned", "true");
  };

  // Helper: Convert array-like to array
  const arrayFrom = Array.from || ((arr) => Array.prototype.slice.call(arr));

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

    // Get title from attribute or hidden content element
    if (!options.title) {
      // Check for hidden content element (for dynamic content like likers)
      const hiddenContent = element.querySelector('[data-tooltip-content]');
      if (hiddenContent) {
        const tooltipState = hiddenContent.getAttribute('data-tooltip-state');
        if (tooltipState === 'ready') {
          options.title = hiddenContent.innerHTML.trim();
        }
      }

      // Fallback to title attribute
      if (!options.title) {
        const titleAttr = element.getAttribute("title");
        if (titleAttr) {
          options.title = titleAttr;
          element.removeAttribute("title");
        }
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
    let scrollHandler = null;
    let scrollHandlerOptions = null;
    let isHovering = false; // Track if user is currently hovering

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
        // Position after a brief delay to ensure dimensions are calculated
        requestAnimationFrame(() => {
          if (tooltip && tooltip.parentNode) {
            positionTooltip(tooltip, reference, options.position || "top");
          }
        });
      } else {
        // Update existing tooltip content
        const contentEl = tooltip.querySelector(".ulf-content");
        if (contentEl) {
          isLoading = showLoading === true;
          contentEl.innerHTML = isLoading
            ? createSpinnerHTML()
            : (options.title || "&nbsp;");
        }
        // Reposition after content update (with delay for dimension calculation)
        requestAnimationFrame(() => {
          if (tooltip && tooltip.parentNode) {
            positionTooltip(tooltip, reference, options.position || "top");
          }
        });
      }

      // Add hover handlers to tooltip if trigger is hover
      if (options.trigger === "hover" || !options.trigger) {
        tooltip.addEventListener("mouseenter", () => {
          clearTimeout(hideTimeout);
        });
        tooltip.addEventListener("mouseleave", handleHide);
      }

      // Add scroll listener to hide tooltip on scroll (standard behavior)
      // This prevents tooltip from appearing to "move" when scrolling
      // Most tooltip libraries (Tippy.js, Popper.js) hide tooltips on scroll
      if (!scrollHandler) {
        scrollHandler = () => {
          if (tooltip && tooltip.parentNode) {
            hide();
          }
        };
        // Use capture phase and passive for better performance
        scrollHandlerOptions = { capture: true, passive: true };
        window.addEventListener("scroll", scrollHandler, scrollHandlerOptions);
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

    // Helper: Get or create hidden content element
    const getTooltipContentElement = () => {
      let hiddenContent = element.querySelector('[data-tooltip-content]');
      if (!hiddenContent) {
        hiddenContent = document.createElement("div");
        hiddenContent.setAttribute('data-tooltip-content', '');
        hiddenContent.style.display = 'none';
        element.appendChild(hiddenContent);
      }
      return hiddenContent;
    };

    // Helper: Set tooltip state
    const setTooltipState = (state) => {
      const hiddenContent = getTooltipContentElement();
      hiddenContent.setAttribute('data-tooltip-state', state);
    };

    // Helper: Get tooltip state
    const getTooltipState = () => {
      const hiddenContent = element.querySelector('[data-tooltip-content]');
      return hiddenContent ? hiddenContent.getAttribute('data-tooltip-state') : null;
    };

    const updateContent = (content) => {
      // Update options.title to keep it in sync
      options.title = content || "";

      // Update hidden content element (for dynamic content)
      const hiddenContent = getTooltipContentElement();
      hiddenContent.innerHTML = content || "";

      // Set state: 'ready' if has content, 'empty' if no content
      const hasContent = content && content.trim().length > 0;
      setTooltipState(hasContent ? 'ready' : 'empty');

      // If content is empty, hide tooltip immediately (don't show empty tooltip)
      if (!hasContent) {
        if (tooltip && tooltip.parentNode) {
          hide();
        }
        return;
      }

      // If tooltip is visible, update it immediately
      if (tooltip && tooltip.parentNode) {
        const contentEl = tooltip.querySelector(".ulf-content");
        if (contentEl) {
          contentEl.innerHTML = content;
          isLoading = false;
          // Reposition after content update
          const reference = options.child ? (element.querySelector(options.child) || element) : element;
          requestAnimationFrame(() => {
            if (tooltip && tooltip.parentNode) {
              positionTooltip(tooltip, reference, options.position || "top");
            }
          });
        }
      } else if (isHovering) {
        // Tooltip not visible but user is hovering - show it
        show(false);
      }
    };

    const hide = () => {
      if (!tooltip || !tooltip.parentNode) return;

      // Remove scroll listener when hiding
      if (scrollHandler && scrollHandlerOptions) {
        window.removeEventListener("scroll", scrollHandler, scrollHandlerOptions);
        scrollHandler = null;
        scrollHandlerOptions = null;
      }

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

    // Helper: Get cached content from hidden element
    const getCachedContent = () => {
      const hiddenContent = element.querySelector('[data-tooltip-content]');
      return hiddenContent ? hiddenContent.innerHTML.trim() : '';
    };

    // Event handlers
    const handleShow = () => {
      clearTimeout(hideTimeout);
      isHovering = true;

      const tooltipState = getTooltipState();

      // Handle different states
      if (tooltipState === 'empty') return; // Don't show empty tooltip

      if (tooltipState === 'ready') {
        const cachedContent = getCachedContent();
        if (cachedContent) {
          options.title = cachedContent;
          showTimeout = setTimeout(show, SHOW_DELAY);
        } else {
          setTooltipState('empty'); // Content disappeared - mark as empty
        }
        return;
      }

      if (tooltipState === 'loading') {
        show(true);
        return;
      }

      // Not initialized - request data
      if (!tooltipState || tooltipState === '') {
        if (instance.requestData && instance.requestData()) {
          show(true);
        } else {
          setTooltipState('loading');
          show(true);
        }
        return;
      }

      // Fallback for static content
      if (options.showLoadingImmediately) {
        show(true);
      } else {
        showTimeout = setTimeout(show, SHOW_DELAY);
      }
    };

    const handleHide = () => {
      clearTimeout(showTimeout);
      isHovering = false;
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
        if (instance.contentUpdateHandler) {
          element.removeEventListener("tooltip-content-updated", instance.contentUpdateHandler);
        }
        if (scrollHandler && scrollHandlerOptions) {
          window.removeEventListener("scroll", scrollHandler, scrollHandlerOptions);
          scrollHandler = null;
          scrollHandlerOptions = null;
        }
        tooltipInstances.delete(element);
        if (options.id) delete tooltipInstancesById[options.id];
      },
    };

    tooltipInstances.set(element, instance);
    if (options.id) {
      tooltipInstancesById[options.id] = instance;
    }

    // Expose helper methods for external use
    instance.setLoadingState = () => {
      setTooltipState('loading');
    };

    // If tooltip is created with hover trigger, check state immediately
    // This handles the case when tooltip is created while user is already hovering
    if (!options.trigger || options.trigger === "hover") {
      setTimeout(() => {
        const currentState = getTooltipState();
        if (!currentState || currentState === '') {
          handleShow(); // Will request data
        } else if (currentState === 'loading') {
          show(true);
        } else if (currentState === 'ready') {
          const cachedContent = getCachedContent();
          if (cachedContent) {
            options.title = cachedContent;
            showTimeout = setTimeout(show, SHOW_DELAY);
          }
        }
      }, 0);
    }

    // Request data from external source (e.g., AJAX)
    // This method checks state and triggers event or calls dataFetcher callback if provided
    instance.requestData = () => {
      const currentState = getTooltipState();

      // If already loaded (ready or empty), don't request again
      if (currentState === 'ready' || currentState === 'empty') {
        return false; // Data already available
      }

      // If already loading, don't request again
      if (currentState === 'loading') {
        return false; // Already requesting
      }

      // Set loading state
      setTooltipState('loading');

      // If dataFetcher callback is provided, use it directly
      if (typeof options.dataFetcher === 'function') {
        options.dataFetcher(element, options.id);
        return true;
      }

      // Fallback: trigger event for external handler (backward compatibility)
      setTimeout(() => {
        const event = new CustomEvent("tooltip-request-data", {
          bubbles: true,
          detail: { element, tooltipId: options.id }
        });
        element.dispatchEvent(event);
        document.dispatchEvent(event);
      }, 0);

      return true;
    };

    // Listen for content updates via custom event (optional, for external updates)
    const contentUpdateHandler = (e) => {
      const detail = e.detail || {};
      if (detail.element === element || (detail.target && element.contains(detail.target))) {
        updateContent(detail.content || "");
      }
    };
    element.addEventListener("tooltip-content-updated", contentUpdateHandler);
    instance.contentUpdateHandler = contentUpdateHandler;

    return element;
  }

  // Expose
  window.WordpressUlikeTooltipPlugin = WordpressUlikeTooltipPlugin;
  window.WordpressUlikeTooltip = {
    visible: activeTooltips,
    defaults,
    getInstanceById: (id) => tooltipInstancesById[id],
  };

  // Expose as jQuery plugin for backward compatibility (if jQuery is available)
  // This allows users' existing jQuery code to continue working
  // Example: $('.element').WordpressUlikeTooltip({...})
  if (typeof jQuery !== 'undefined' && jQuery && jQuery.fn) {
    jQuery.fn.WordpressUlikeTooltip = function (options) {
      return this.each(function () {
        new WordpressUlikeTooltipPlugin(this, options);
      });
    };
  }
})(window, document);
