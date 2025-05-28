import {Controller} from "@hotwired/stimulus";
import '../../styles/sonner.css'

const VISIBLE_TOASTS_AMOUNT = 3;
const VIEWPORT_OFFSET = "32px";
const TOAST_LIFETIME = 4000;
const TOAST_WIDTH = 356;
const GAP = 14;
const SWIPE_THRESHOLD = 20;
const TIME_BEFORE_UNMOUNT = 200;

const SuccessIcon = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" height="20" width="20">
        <path
          fill-rule="evenodd"
          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
          clip-rule="evenodd"
        />
    </svg>`;

const WarningIcon = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" height="20" width="20">
        <path
          fill-rule="evenodd"
          d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z"
          clip-rule="evenodd"
        />
    </svg>`;

const InfoIcon = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" height="20" width="20">
        <path
            fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
            clip-rule="evenodd"
        />
    </svg>`;

const ErrorIcon = `
    <svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 20 20" fill="currentColor" height="20" width="20">
        <path
          fill-rule="evenodd"
          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z"
          clip-rule="evenodd"
        />
    </svg>`;

export default class UISonner extends Controller {
    static values = {
        toasts: Array
    }

    initialize() {
        const that = this

        window.addEventListener('sonner:show', function (event) {
            const {message, type, description} = event.detail
            that.show(message, {description, type})
        });

        window.addEventListener('sonner:success', function (event) {
            const {message, description} = event.detail
            that.success(message, {description})
        });

        window.addEventListener('sonner:error', function (event) {
            const {message, description} = event.detail
            that.error(message, {description})
        });

        window.addEventListener('sonner:warning', function (event) {
            const {message, description} = event.detail
            that.warning(message, {description})
        });

        window.addEventListener('sonner:info', function (event) {
            const {message, description} = event.detail
            that.info(message, {description})
        });
    }

    connect() {
        this.init()

        if (this.hasToastsValue) {
            this.toastsValue.forEach((toast) => {
                this.show(toast.message, {description: toast.description, type: toast.type})
            });

            this.toastsValue = []
        }
    }

    init({closeButton = false, richColors = false, position = "top-center"} = {}) {
        if (this.reinitializeToaster()) {
            return;
        }

        this.renderToaster({closeButton, richColors, position});

        const ol = document.getElementById("sonner-toaster-list");
        this.registerMouseOver(ol);
        this.registerKeyboardShortcuts(ol);
    }

    success(msg, options = {}) {
        this.show(this.translate(msg), { type: "success", ...options });
    }

    error(msg, options = {}) {
        this.show(this.translate(msg), { type: "error", ...options });
    }

    info(msg, options = {}) {
        this.show(this.translate(msg), { type: "info", ...options });
    }

    warning(msg, options = {}) {
        this.show(this.translate(msg), { type: "warning", ...options });
    }

    show(msg, { description, type, ...options } = {}) {
        const that = this

        const list = document.getElementById("sonner-toaster-list");
        const {toast, id} = this.renderToast(list, msg, {description, type});

        // Wait for the toast to be mounted before registering swipe events
        window.setTimeout(function () {
            const el = list.children[0];
            const height = el.getBoundingClientRect().height;

            el.setAttribute("data-mounted", "true");
            el.setAttribute("data-initial-height", height);
            el.style.setProperty("--initial-height", `${height}px`);
            list.style.setProperty("--front-toast-height", `${height}px`);

            that.registerSwipe(id);
            that.refreshProperties();
            that.registerRemoveTimeout(el);
        }, 16);
    }

    remove(id) {
        const el = document.querySelector(`[data-id="${id}"]`);
        if (!el) return;
        el.setAttribute("data-removed", "true");
        this.refreshProperties();

        const previousTid = el.getAttribute("data-unmount-tid");
        if (previousTid) window.clearTimeout(previousTid);

        const tid = window.setTimeout(function () {
            el.parentElement?.removeChild(el);
        }, TIME_BEFORE_UNMOUNT);
        el.setAttribute("data-unmount-tid", tid);
    }

    getAsset = (type) => {
        switch (type) {
            case "success":
                return SuccessIcon;

            case "info":
                return InfoIcon;

            case "warning":
                return WarningIcon;

            case "error":
                return ErrorIcon;

            default:
                return null;
        }
    }

    genid() {
        return (Date.now().toString(36) + Math.random().toString(36).substring(2, 12).padStart(12, 0));
    }

    translate(key, parameters = {}) {
        if (typeof Translator !== 'undefined' && Translator.trans) {
            return Translator.trans(key, parameters);
        }
        return key; // Fallback to the key if Translator is not available
    }

    renderToast(list, msg, {type, description}) {
        const that = this

        const toast = document.createElement("div");
        list.prepend(toast);
        const id = this.genid();
        const count = list.children.length;
        const asset = this.getAsset(type);
        toast.outerHTML = `<li
          aria-live="polite"
          aria-atomic="true"
          role="status"
          tabindex="0"
          data-id="${id}"
          data-type="${type}"
          data-sonner-toast=""
          data-mounted="false"
          data-styled="true"
          data-promise="false"
          data-removed="false"
          data-visible="true"
          data-y-position="${list.getAttribute("data-y-position")}"
          data-x-position="${list.getAttribute("data-x-position")}"
          data-index="${0}"
          data-front="true"
          data-swiping="false"
          data-dismissible="true"
          data-swipe-out="false"
          data-expanded="false"
          style="--index: 0; --toasts-before: ${0}; --z-index: ${count}; --offset: 0px; --initial-height: 0px;"
            >
              ${list.getAttribute("data-close-button") === "true" ? `<button
                  aria-label="Close"
                  data-disabled=""
                  class="absolute top-0.5 right-0.5 border border-neutral-800 text-neutral-800 bg-neutral-100 rounded-sm"
                  onclick="that.remove('${id}')"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="12"
                    height="12"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                  </svg>
                </button>
              ` : ""}
              ${asset ? `
            <div data-icon="" class="">
              ${this.getAsset(type)}
            </div>
            ` : ""}
            <div 
                  data-content="" 
                  class="">
              <div data-title="" class="">
                ${msg}
              </div>
              ${description ? `<div data-description="" class="">${description}</div>` : ""}
            </div>
        </li>
           `;
        return {toast, id};
    }

    renderToaster({closeButton, richColors, position}) {
        position = position.split("-");
        this.element.innerHTML = `
            <section aria-label="Notifications alt+T" tabindex="-1">
                  <ol
                    dir="ltr"
                    tabindex="-1"
                    data-sonner-toaster="true"
                    data-theme="light"
                    data-close-button="${closeButton}"
                    data-rich-colors="${richColors}"
                    data-y-position="${position[0]}"
                    data-x-position="${position[1]}"
                    style="--front-toast-height: 0px; --offset: ${VIEWPORT_OFFSET}; --width: ${TOAST_WIDTH}px; --gap: ${GAP}px;"
                    id="sonner-toaster-list"
                  ></ol>
            </section>
        `;
    }

    registerMouseOver(ol) {
        const that = this

        ol.addEventListener("mouseenter", function () {
            for (let i = 0; i < ol.children.length; i++) {
                const el = ol.children[i];
                if (el.getAttribute("data-expanded") === "true") continue;
                el.setAttribute("data-expanded", "true");

                that.clearRemoveTimeout(el);
            }
        });
        ol.addEventListener("mouseleave", function () {
            for (let i = 0; i < ol.children.length; i++) {
                const el = ol.children[i];
                if (el.getAttribute("data-expanded") === "false") continue;
                el.setAttribute("data-expanded", "false");

                that.registerRemoveTimeout(el);
            }
        });
    }

    registerKeyboardShortcuts(ol) {
        window.addEventListener("keydown", function (e) {
            if (e.altKey && e.code === "KeyT") {
                if (ol.children.length === 0) return;
                const expanded = ol.children[0].getAttribute("data-expanded");
                const newExpanded = expanded === "true" ? "false" : "true";
                for (let i = 0; i < ol.children.length; i++) {
                    ol.children[i].setAttribute("data-expanded", newExpanded);
                }
            }
        });
    }

    clearRemoveTimeout(el) {
        const tid = el.getAttribute("data-remove-tid");
        if (tid) window.clearTimeout(tid);
    }

    reinitializeToaster() {
        const ol = document.getElementById("sonner-toaster-list");
        if (!ol) return;
        for (let i = 0; i < ol.children.length; i++) {
            const el = ol.children[i];
            const id = el.getAttribute("data-id");
            this.registerSwipe(id);
            this.refreshProperties();
            this.registerRemoveTimeout(el);
        }
        return ol;
    }

    registerSwipe(id) {
        const that = this

        const el = document.querySelector(`[data-id="${id}"]`);
        if (!el) return;
        let dragStartTime = null;
        let pointerStart = null;
        const y = el.getAttribute("data-y-position");
        el.addEventListener("pointerdown", function (event) {
            dragStartTime = new Date();
            event.target.setPointerCapture(event.pointerId);
            if (event.target.tagName === "BUTTON") return;
            el.setAttribute("data-swiping", "true");
            pointerStart = {x: event.clientX, y: event.clientY};
        });
        el.addEventListener("pointerup", function (event) {
            pointerStart = null;
            const swipeAmount = Number(el.style.getPropertyValue("--swipe-amount").replace("px", "") || 0,);
            const timeTaken = new Date().getTime() - dragStartTime.getTime();
            const velocity = Math.abs(swipeAmount) / timeTaken;

            // Remove only if threshold is met
            if (Math.abs(swipeAmount) >= SWIPE_THRESHOLD || velocity > 0.11) {
                el.setAttribute("data-swipe-out", "true");
                that.remove(id);
                return;
            }

            el.style.setProperty("--swipe-amount", "0px");
            el.setAttribute("data-swiping", "false");
        });

        el.addEventListener("pointermove", function (event) {
            if (!pointerStart) return;
            const yPosition = event.clientY - pointerStart.y;
            const xPosition = event.clientX - pointerStart.x;

            const clamp = y === "top" ? Math.min : Math.max;
            const clampedY = clamp(0, yPosition);
            const swipeStartThreshold = event.pointerType === "touch" ? 10 : 2;
            const isAllowedToSwipe = Math.abs(clampedY) > swipeStartThreshold;

            if (isAllowedToSwipe) {
                el.style.setProperty("--swipe-amount", `${yPosition}px`);
            } else if (Math.abs(xPosition) > swipeStartThreshold) {
                // User is swiping in wrong direction, so we disable swipe gesture
                // for the current pointer down interaction
                pointerStart = null;
            }
        });
    }

    refreshProperties() {
        const list = document.getElementById("sonner-toaster-list");
        let heightsBefore = 0;
        let removed = 0;
        for (let i = 0; i < list.children.length; i++) {
            const el = list.children[i];
            if (el.getAttribute("data-removed") === "true") {
                removed++;
                continue;
            }
            const idx = i - removed;
            el.setAttribute("data-index", idx);
            el.setAttribute("data-front", idx === 0 ? "true" : "false");
            el.setAttribute("data-visible", idx < VISIBLE_TOASTS_AMOUNT ? "true" : "false",);
            el.style.setProperty("--index", idx);
            el.style.setProperty("--toasts-before", idx);
            el.style.setProperty("--offset", `${GAP * idx + heightsBefore}px`);
            el.style.setProperty("--z-index", list.children.length - i);
            heightsBefore += Number(el.getAttribute("data-initial-height"));
        }
    }

    registerRemoveTimeout(el) {
        const that = this

        const tid = window.setTimeout(function () {
            that.remove(el.getAttribute("data-id"));
        }, TOAST_LIFETIME);
        el.setAttribute("data-remove-tid", tid);
    }
}
