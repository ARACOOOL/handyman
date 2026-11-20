(() => {
  const root = document.documentElement;
  root.classList.add("js");
  const themeButton = document.querySelector(".theme-toggle");
  const scheme = matchMedia("(prefers-color-scheme: dark)");
  let preference;
  try {
    preference = localStorage.getItem("lion-theme");
  } catch {}
  if (preference === "light" || preference === "dark")
    root.dataset.theme = preference;
  const updateTheme = () => {
    const dark = root.dataset.theme
      ? root.dataset.theme === "dark"
      : scheme.matches;
    themeButton.setAttribute(
      "aria-label",
      `Switch to ${dark ? "light" : "dark"} mode`,
    );
    themeButton.innerHTML = `<i class="ph ph-${dark ? "sun" : "moon"}" aria-hidden="true"></i>`;
    document.querySelector('meta[name="theme-color"]').content = dark
      ? "#1b211d"
      : "#f6f7f4";
  };
  updateTheme();
  scheme.addEventListener("change", updateTheme);
  themeButton.addEventListener("click", () => {
    const dark = root.dataset.theme
      ? root.dataset.theme === "dark"
      : scheme.matches;
    root.dataset.theme = dark ? "light" : "dark";
    try {
      localStorage.setItem("lion-theme", root.dataset.theme);
    } catch {}
    updateTheme();
  });

  const menuButton = document.querySelector(".menu-toggle");
  const navigation = document.querySelector("#navigation");
  const closeMenu = () => {
    navigation.classList.remove("is-open");
    menuButton.setAttribute("aria-expanded", "false");
    menuButton.setAttribute("aria-label", "Open navigation");
  };
  menuButton.addEventListener("click", () => {
    const open = navigation.classList.toggle("is-open");
    menuButton.setAttribute("aria-expanded", String(open));
    menuButton.setAttribute(
      "aria-label",
      `${open ? "Close" : "Open"} navigation`,
    );
  });
  navigation
    .querySelectorAll("a")
    .forEach((link) => link.addEventListener("click", closeMenu));
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closeMenu();
  });
  matchMedia("(min-width: 768px)").addEventListener("change", closeMenu);

  const reducedMotion = matchMedia("(prefers-reduced-motion: reduce)");
  if ("IntersectionObserver" in window && !reducedMotion.matches) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.remove("is-pending");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08 },
    );
    document.querySelectorAll(".reveal").forEach((el) => {
      el.classList.add("is-pending");
      observer.observe(el);
    });
    reducedMotion.addEventListener("change", () => {
      if (reducedMotion.matches) {
        observer.disconnect();
        document
          .querySelectorAll(".is-pending")
          .forEach((el) => el.classList.remove("is-pending"));
      }
    });
  }

  const track = document.querySelector("#testimonials-track");
  if (track) {
    const carousel = track.closest(".testimonials-carousel");
    const cards = [...track.querySelectorAll(".testimonial")];
    const controls = carousel.querySelector(".testimonials-controls");
    const previous = controls.querySelector('[data-direction="-1"]');
    const next = controls.querySelector('[data-direction="1"]');
    const status = controls.querySelector(".testimonials-status");
    const cardStep = () => cards[1].offsetLeft - cards[0].offsetLeft;
    const updateCarousel = () => {
      const step = cardStep();
      const first = Math.round(track.scrollLeft / step);
      const visible = Math.max(
        1,
        Math.round((track.clientWidth + step - cards[0].offsetWidth) / step),
      );
      previous.disabled = track.scrollLeft < 2;
      next.disabled =
        track.scrollLeft >= track.scrollWidth - track.clientWidth - 2;
      const label = `Testimonials ${first + 1} to ${Math.min(cards.length, first + visible)} of ${cards.length}`;
      if (status.textContent !== label) status.textContent = label;
    };
    const moveCarousel = (index) => {
      track.scrollTo({
        left: Math.max(
          0,
          Math.min(index * cardStep(), track.scrollWidth - track.clientWidth),
        ),
        behavior: reducedMotion.matches ? "instant" : "smooth",
      });
    };
    controls.querySelectorAll("button").forEach((button) => {
      button.addEventListener("click", () => {
        moveCarousel(
          Math.round(track.scrollLeft / cardStep()) +
            Number(button.dataset.direction),
        );
      });
    });
    track.addEventListener("keydown", (event) => {
      const current = Math.round(track.scrollLeft / cardStep());
      const destinations = {
        ArrowLeft: current - 1,
        ArrowRight: current + 1,
        Home: 0,
        End: cards.length - 1,
      };
      if (!(event.key in destinations)) return;
      event.preventDefault();
      moveCarousel(destinations[event.key]);
    });
    // Only this small native scroll area is observed; the page scroll is untouched.
    track.addEventListener("scroll", updateCarousel, { passive: true });
    new ResizeObserver(updateCarousel).observe(track);
    controls.hidden = false;
    updateCarousel();
  }

  const form = document.querySelector("#quote-form");
  const success = document.querySelector("#form-success");
  const formError = document.querySelector("#form-error");
  const submit = form.querySelector(".form-submit");
  const attachments = form.querySelector("#attachments");
  const workType = form.elements.work_type;
  const syncWorkFields = () => {
    form.querySelectorAll("[data-work-fields]").forEach((section) => {
      const active = section.dataset.workFields === workType.value;
      section.hidden = !active;
      section.querySelectorAll("input, select").forEach((field) => {
        field.disabled = !active;
        field.required = active && field.hasAttribute("data-required");
      });
    });
    const handyman = workType.value === "handyman";
    form.elements.message.required = handyman;
    form.elements.message.minLength = handyman ? 10 : 0;
    form.querySelector("#message-optional").hidden = handyman;
    form.querySelector("#message-optional").textContent = workType.value ? "(optional)" : "(optional for cleaning)";
  };
  syncWorkFields();
  [...workType].forEach((input) => input.addEventListener("change", syncWorkFields));

  const dialog = document.querySelector("#quote-dialog");
  const dialogBody = document.querySelector("#quote-dialog-body");
  const content = document.querySelector("#quote-content");
  const formHome = document.querySelector("#quote-form-home");
  if (typeof dialog.showModal === "function") {
    let opener;
    document.querySelectorAll('a[href="#quote"], a.service-select').forEach((link) => {
      link.setAttribute("aria-haspopup", "dialog");
      link.addEventListener("click", (event) => {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        opener = link;
        if (link.dataset.service) {
          if (form.hidden) {
            success.hidden = true;
            form.hidden = false;
          }
          workType.value = "handyman";
          form.elements.service.value = link.dataset.service;
          syncWorkFields();
        }
        dialogBody.append(content);
        dialog.showModal();
        dialogBody.scrollTop = 0;
        document.querySelector("#quote-dialog-title").focus();
      });
    });
    document.querySelector("#quote-dialog-close").addEventListener("click", () => dialog.close());
    dialog.addEventListener("close", () => {
      formHome.append(content);
      opener?.focus({ preventScroll: true });
    });
  }
  const setError = (field, message) => {
    const error = document.getElementById(`${field}-error`);
    if (error) {
      error.textContent = message;
      [...form.elements].filter((input) => input.name === field || input.name === `${field}[]`).forEach((input) => {
        input.setAttribute("aria-invalid", "true");
      });
    } else {
      formError.textContent = message;
      formError.hidden = false;
    }
  };
  const uploadError = () => {
    const files = [...attachments.files];
    if (files.length > Number(attachments.dataset.maxFiles)) {
      return `Choose up to ${attachments.dataset.maxFiles} images.`;
    }
    if (files.reduce((total, file) => total + file.size, 0) > Number(attachments.dataset.maxBytes)) {
      return "Keep all images together under 10 MB.";
    }
    return "";
  };
  attachments.addEventListener("change", () => {
    const message = uploadError();
    form.querySelector("#attachments-error").textContent = "";
    attachments.removeAttribute("aria-invalid");
    if (message) setError("attachments", message);
  });
  const clearErrors = () => {
    formError.hidden = true;
    form
      .querySelectorAll("[aria-invalid]")
      .forEach((el) => el.removeAttribute("aria-invalid"));
    form.querySelectorAll(".field-error").forEach((el) => {
      el.textContent = "";
    });
  };
  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (submit.disabled) return;
    clearErrors();
    const invalidUpload = uploadError();
    if (invalidUpload) {
      setError("attachments", invalidUpload);
      attachments.focus();
      return;
    }
    submit.disabled = true;
    submit.querySelector("span").textContent = "Saving your request…";
    form.setAttribute("aria-busy", "true");
    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: { Accept: "application/json" },
      });
      const data = response.headers.get("content-type")?.includes("application/json")
        ? await response.json()
        : {};
      if (data.csrf) form.elements.csrf.value = data.csrf;
      if (!response.ok) {
        const errors = data.errors || {
          _form: response.status === 413
            ? "The upload was too large for the server. Try again with smaller images."
            : "Something went wrong. Please try again.",
        };
        Object.entries(errors).forEach(([field, message]) => setError(field, message));
        (form.querySelector('[aria-invalid="true"]:not(:disabled)') || formError).focus();
        return;
      }
      if (!data.success) throw new Error("Missing submission confirmation");
      form.hidden = true;
      success.hidden = false;
      success.querySelector(".reference").textContent =
        `Reference: ${data.reference}`;
      success.setAttribute("tabindex", "-1");
      success.focus({ preventScroll: true });
      success.scrollIntoView({
        behavior: reducedMotion.matches ? "instant" : "smooth",
        block: "center",
      });
      form.reset();
      form.elements.csrf.value = data.csrf;
      syncWorkFields();
    } catch {
      setError("_form", "We couldn’t confirm your request. Check your connection and try again. Your details are still here.");
      formError.focus();
    } finally {
      submit.disabled = false;
      submit.querySelector("span").textContent = "Request a quote";
      form.removeAttribute("aria-busy");
    }
  });
  document
    .querySelector("#another-request")
    .addEventListener("click", (event) => {
      event.preventDefault();
      success.hidden = true;
      form.hidden = false;
      clearErrors();
      form.querySelector('[name="work_type"]').focus();
    });
})();
