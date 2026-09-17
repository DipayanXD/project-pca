/* Progressive enhancement only: HTML owns every page's primary structure. */
document.addEventListener("DOMContentLoaded", () => {
  // Keep the existing icon set available when pages are opened directly from disk.
  // Some browsers block external SVG <use> fragments under the file: protocol.
  const iconSprite = `<svg class="svg-sprite" aria-hidden="true" focusable="false"><defs>
<symbol id="i-arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></symbol><symbol id="i-arrow-left" viewBox="0 0 24 24"><path d="M19 12H5m6 6-6-6 6-6"/></symbol><symbol id="i-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></symbol><symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol><symbol id="i-chevron" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></symbol><symbol id="i-close" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></symbol><symbol id="i-dashboard" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></symbol><symbol id="i-doc" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></symbol><symbol id="i-file-plus" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M12 18v-6m-3 3h6"/></symbol><symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="2.5"/></symbol><symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></symbol><symbol id="i-more" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1" fill="currentColor"/><circle cx="12" cy="12" r="1" fill="currentColor"/><circle cx="12" cy="19" r="1" fill="currentColor"/></symbol><symbol id="i-paperclip" viewBox="0 0 24 24"><path d="m20.5 11.5-8.8 8.8a6 6 0 0 1-8.5-8.5L12 3a4 4 0 1 1 5.7 5.7L8.8 17.6a2 2 0 0 1-2.8-2.8l8.1-8.1"/></symbol><symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol><symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></symbol><symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.1 2.1-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5v.2h-3v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1-2.1-2.1.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H5.3v-3h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1 2.1-2.1.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5v-.2h3v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 2.1 2.1-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.2v3h-.2a1.7 1.7 0 0 0-1.4 1z"/></symbol><symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></symbol><symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0M16 5a3 3 0 0 1 0 6M17 14a6 6 0 0 1 4 6"/></symbol>
</defs></svg>`;
  document.body.insertAdjacentHTML("afterbegin", iconSprite);
  document
    .querySelectorAll('use[href^="icons.svg#"]')
    .forEach((use) =>
      use.setAttribute(
        "href",
        use.getAttribute("href").replace("icons.svg", ""),
      ),
    );
  const icon = (name, size = 15) =>
    `<svg class="icon" width="${size}" height="${size}" aria-hidden="true"><use href="#i-${name}"/></svg>`;
  const toastRegion = document.querySelector(".toast-region");
  const toast = (message, type = "info") => {
    if (!toastRegion) return;
    const notice = document.createElement("div");
    notice.className = `toast ${type}`;
    notice.innerHTML = `<span>${type === "success" ? "✓" : "i"}</span><p></p><button aria-label="Dismiss notification">${icon("close")}</button>`;
    notice.querySelector("p").textContent = message;
    notice
      .querySelector("button")
      .addEventListener("click", () => notice.remove());
    toastRegion.append(notice);
    window.setTimeout(() => {
      notice.style.opacity = "0";
      notice.style.transform = "translateY(100%)";
      window.setTimeout(() => notice.remove(), 220);
    }, 3600);
  };

  document
    .querySelectorAll("[data-toast]")
    .forEach((button) =>
      button.addEventListener("click", () => toast(button.dataset.toast)),
    );

  document.querySelectorAll("[data-mobile-nav]").forEach((button) =>
    button.addEventListener("click", () => {
      const shell = button.closest(".app-shell");
      const open = shell.classList.toggle("nav-open");
      button.setAttribute("aria-expanded", String(open));
    }),
  );

  document.querySelectorAll("[data-password-toggle]").forEach((button) =>
    button.addEventListener("click", () => {
      const input = document.getElementById(button.dataset.passwordToggle);
      const isHidden = input.type === "password";
      input.type = isHidden ? "text" : "password";
      button.setAttribute(
        "aria-label",
        `${isHidden ? "Hide" : "Show"} password`,
      );
    }),
  );

  document.querySelectorAll("form[data-validate]").forEach((form) =>
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      let valid = true;
      form.querySelectorAll("[required]").forEach((input) => {
        const error = input.closest(".field")?.querySelector(".error");
        let message = "";
        if (!input.value.trim()) message = "This field is required.";
        else if (input.type === "email" && !input.validity.valid)
          message = "Enter a valid email address.";
        if (
          input.name === "confirmPassword" &&
          input.value !== form.elements.password.value
        )
          message = "Passwords do not match.";
        if (error) error.textContent = message;
        input.toggleAttribute("aria-invalid", Boolean(message));
        if (message) valid = false;
      });
      if (!valid) return;
      const target = form.dataset.redirect;
      if (target) {
        toast(
          form.closest(".auth-card")
            ? "Your account is ready."
            : "Complaint submitted successfully.",
          "success",
        );
        window.setTimeout(() => {
          window.location.href = target;
        }, 350);
      } else toast("Profile updated.", "success");
    }),
  );

  const complaintTable = document.getElementById("complaint-table");
  if (complaintTable) {
    const rows = [...complaintTable.tBodies[0].rows];
    const search = document.getElementById("complaint-search");
    const filters = [...document.querySelectorAll("[data-complaint-filter]")];
    const sort = document.querySelector("[data-complaint-sort]");
    const empty = document.getElementById("complaint-empty");
    const loading = document.querySelector(".loading-line");
    const update = () => {
      if (loading) {
        loading.classList.remove("is-hidden");
        window.setTimeout(() => loading.classList.add("is-hidden"), 160);
      }
      const query = (search?.value || "").toLowerCase();
      const status =
        filters.find((x) => x.dataset.complaintFilter === "status")?.value ||
        "";
      const category =
        filters.find((x) => x.dataset.complaintFilter === "category")?.value ||
        "";
      const visible = rows.filter((row) => {
        const match =
          row.textContent.toLowerCase().includes(query) &&
          (!status || row.dataset.status === status) &&
          (!category || row.dataset.category === category);
        row.classList.toggle("is-hidden", !match);
        return match;
      });
      if (sort)
        rows
          .sort((a, b) =>
            sort.value === "old"
              ? Number(a.dataset.date) - Number(b.dataset.date)
              : sort.value === "status"
                ? a.dataset.status.localeCompare(b.dataset.status)
                : Number(b.dataset.date) - Number(a.dataset.date),
          )
          .forEach((row) => complaintTable.tBodies[0].append(row));
      empty?.classList.toggle("is-hidden", visible.length !== 0);
    };
    search?.addEventListener("input", update);
    filters.forEach((filter) => filter.addEventListener("change", update));
    sort?.addEventListener("change", update);
    document
      .querySelector("[data-clear-filters]")
      ?.addEventListener("click", () => {
        if (search) search.value = "";
        filters.forEach((filter) => (filter.value = ""));
        if (sort) sort.value = "new";
        update();
      });
  }

  const userTable = document.getElementById("user-table");
  if (userTable) {
    const search = document.getElementById("user-search");
    const role = document.getElementById("user-role");
    const rows = [...userTable.tBodies[0].rows];
    const update = () =>
      rows.forEach((row) =>
        row.classList.toggle(
          "is-hidden",
          !(
            row.textContent
              .toLowerCase()
              .includes((search.value || "").toLowerCase()) &&
            (!role.value || row.dataset.role === role.value)
          ),
        ),
      );
    search.addEventListener("input", update);
    role.addEventListener("change", update);
  }

  document.querySelectorAll("[data-open-modal]").forEach((button) =>
    button.addEventListener("click", () => {
      const modal = document.getElementById(button.dataset.openModal);
      modal.classList.remove("is-hidden");
      modal.setAttribute("aria-hidden", "false");
      modal.querySelector("[data-close-modal]").focus();
    }),
  );
  document.querySelectorAll("[data-close-modal]").forEach((button) =>
    button.addEventListener("click", () => {
      const modal = button.closest(".modal-backdrop");
      modal.classList.add("is-hidden");
      modal.setAttribute("aria-hidden", "true");
    }),
  );
  document
    .querySelector("[data-save-status]")
    ?.addEventListener("click", () => {
      const value = document.getElementById("status-select").value;
      const remark = document.getElementById("admin-remark").value.trim();
      const badge = document.querySelector("[data-status-badge]");
      if (badge) {
        badge.className = `status ${value.toLowerCase().replace(" ", "-")}`;
        badge.innerHTML = `<i></i>${value}`;
      }
      document
        .querySelectorAll("[data-last-updated]")
        .forEach((element) => (element.textContent = "Just now"));
      if (remark)
        document.querySelector("[data-admin-response]").textContent = remark;
      if (value === "Resolved") {
        document
          .querySelector("[data-timeline-progress]")
          ?.classList.add("complete");
        document.querySelector("[data-timeline-progress] span").textContent =
          "✓";
        const resolved = document.querySelector("[data-timeline-resolved]");
        resolved?.classList.add("current");
        if (resolved)
          resolved.querySelector("small").textContent =
            "Completed and confirmed";
      }
      const modal = document.querySelector(".modal-backdrop");
      modal?.classList.add("is-hidden");
      toast("Complaint update saved.", "success");
    });
});
